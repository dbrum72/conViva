<?php

namespace App\Services\Care;

use App\Models\CareEntry;
use App\Models\CareOccurrence;
use App\Models\CareProposal;
use App\Models\CareSchedule;
use App\Models\CareUnavailability;
use App\Models\User;
use App\Support\Tenancy\CurrentOrganization;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class CareAgenda
{
    public function __construct(private AccessControl $access, private GenerateCareOccurrences $generator, private ProposalGuard $guard) {}

    public function listing(User $user, array $filters): array
    {
        $zone = app(CurrentOrganization::class)->get()->timezone;
        $from = CarbonImmutable::parse(($filters['from'] ?? CarbonImmutable::now($zone)->format('Y-m-d')).' 00:00', $zone)->utc();
        $to = isset($filters['to']) ? CarbonImmutable::parse($filters['to'].' 00:00', $zone)->addDay()->utc() : $from->addDays(31);
        abort_if($to <= $from || $from->diffInDays($to) > 93, 422, 'Escolha um período de até 93 dias.');
        $recipients = $this->access->visible($user)->where('status', 'active')->get();
        $entries = CareEntry::where('publish_to_agenda', true)->whereNull('related_entry_id')->where('revision', '>', 0)->where(function ($q) use ($recipients, $user) {
            $q->whereRaw('1 = 0');
            foreach ($recipients as $recipient) {
                foreach (['routine' => ['event', 'task', 'feeding', 'journal'], 'health' => ['medication', 'vaccine'], 'finance' => ['expense']] as $area => $kinds) {
                    if ($this->access->allowed($user, $recipient, $area)) {
                        $q->orWhere(fn ($q) => $q->where('care_recipient_id', $recipient->id)->whereIn('kind', $kinds));
                    }
                }
            }
        })->get();
        foreach ($entries as $entry) {
            // Compatibility for approved records created before E04.
            if ($entry->due_at && $entry->status !== 'cancelled' && ! CareSchedule::where('care_entry_id', $entry->id)->exists()) {
                DB::transaction(function () use ($entry) {
                    $this->guard->lock($entry->recipient);
                    $entry = $entry->fresh();
                    if ($entry->status !== 'cancelled' && ! CareSchedule::where('care_entry_id', $entry->id)->exists()) {
                        $this->generator->apply($entry, true);
                    }
                });
            }
        }
        $schedules = CareSchedule::whereIn('care_entry_id', $entries->pluck('id'))->get();
        foreach ($schedules as $schedule) {
            $this->generator->generate($schedule, $from->subDay(), $to);
        }
        $base = CareOccurrence::whereIn('care_schedule_id', $schedules->pluck('id'))
            ->where('starts_at', '<', $to)->where(function ($q) use ($from) {
                $q->where('ends_at', '>', $from)->orWhere('starts_at', '>=', $from);
            });
        $conflicts = (clone $base)->whereIn('status', ['scheduled', 'executed'])->with('schedule')->get();
        $query = (clone $base)->with('schedule.entry.recipient', 'schedule.entry.proposals', 'execution.author:id,name');
        if (! empty($filters['kind'])) {
            $query->whereHas('schedule', fn ($q) => $q->where('snapshot->kind', $filters['kind']));
        }
        if (! empty($filters['executor'])) {
            $query->whereHas('schedule', fn ($q) => $q->where('snapshot->assigned_user_id', (int) $filters['executor']));
        }
        $query->where('status', $filters['status'] ?? 'scheduled');
        $page = $query->orderBy('starts_at')->orderBy('id')->paginate($filters['per_page'] ?? 25);
        $page->setCollection($page->getCollection()->map(function ($o) use ($user, $conflicts, $zone) {
            $entry = $o->schedule->entry;
            $snapshot = $o->schedule->snapshot;
            $write = $this->access->allowed($user, $entry->recipient, $this->access->area($snapshot['kind']), true);
            $ready = $write && in_array($entry->status, ['pending', 'completed']) && ! $entry->proposals->contains('status', 'pending') && $o->status === 'scheduled';
            $overlap = $conflicts->contains(function ($other) use ($o, $snapshot) {
                if ($other->id === $o->id) {
                    return false;
                }
                $same = $other->schedule->snapshot['care_recipient_id'] === $snapshot['care_recipient_id']
                    || (! empty($snapshot['assigned_user_id']) && $other->schedule->snapshot['assigned_user_id'] === $snapshot['assigned_user_id']);
                $end = $o->ends_at > $o->starts_at ? $o->ends_at : $o->starts_at->addSecond();
                $otherEnd = $other->ends_at > $other->starts_at ? $other->ends_at : $other->starts_at->addSecond();

                return $same && $o->starts_at < $otherEnd && $other->starts_at < $end;
            });

            $eligible = $ready && ! in_array($snapshot['kind'], ['expense', 'journal']) && $this->access->canExecuteCare($user, $entry->recipient, $snapshot['kind'], $snapshot['assigned_user_id']);
            $assignedPeriod = $o->status === 'scheduled' && ! in_array($snapshot['kind'], ['expense', 'journal']) && ! empty($snapshot['assigned_user_id'])
                ? app(CareAvailability::class)->conflict($entry->recipient, (int) $snapshot['assigned_user_id'], $o->starts_at, $o->ends_at) : null;
            $executionBlock = $eligible ? app(CareAvailability::class)->executionBlock($entry->recipient, (int) $user->id, null, $o->starts_at, $o->ends_at) : null;

            return [
                'id' => $o->id, 'entry_id' => $entry->id, 'care_recipient_id' => $entry->care_recipient_id,
                'title' => $snapshot['title'], 'kind' => $snapshot['kind'], 'assigned_user_id' => $snapshot['assigned_user_id'],
                'due_at' => $o->starts_at->toISOString(), 'ends_at' => $o->ends_at->toISOString(),
                'local_date' => $o->starts_at->setTimezone($zone)->format('Y-m-d'),
                'timezone' => $o->schedule->rule['timezone'], 'frequency' => $o->schedule->rule['frequency'],
                'is_overdue' => ! in_array($snapshot['kind'], ['expense', 'journal']) && $o->isOverdue(),
                'status' => $o->status, 'administratively_completed' => $entry->status === 'completed',
                'recipient' => $entry->recipient->only(['id', 'name']), 'conflict' => $overlap,
                'pending_change' => $entry->proposals->contains('status', 'pending'),
                'can_execute' => $eligible && ! $executionBlock,
                'execution_block' => $executionBlock,
                'availability_conflict' => $assignedPeriod ? app(CareAvailability::class)->message($assignedPeriod, true) : null,
                'can_cancel' => $ready && $entry->status === 'pending' && $o->starts_at->isFuture() && (int) $entry->created_by === (int) $user->id,
                'execution' => $o->execution ? ['author' => $o->execution->author?->name, 'occurred_at' => $o->execution->due_at ?? $o->execution->completed_at, 'recorded_at' => $o->execution->created_at, 'description' => $o->execution->description] : null,
            ];
        }));

        $availabilityRecipient = $recipients->first(fn ($recipient) => app(CareAvailability::class)->canView($user, $recipient));
        $participants = app(CurrentOrganization::class)->get()->users()->wherePivot('status', 'active')->get()
            ->reject(fn ($participant) => $participant->hasRole('observador'));
        $visibleRecipients = $recipients->filter(fn ($recipient) => app(CareAvailability::class)->canView($user, $recipient))->keyBy('id');
        $unavailabilities = CareUnavailability::whereIn('care_recipient_id', $visibleRecipients->keys())
            ->whereIn('user_id', $participants->pluck('id'))->whereNull('cancelled_at')
            ->where('starts_at', '<=', $to->addDay()->format('Y-m-d'))->where('ends_at', '>=', $from->subDay()->format('Y-m-d'))
            ->when(! empty($filters['executor']), fn ($q) => $q->where('user_id', (int) $filters['executor']))
            ->with('user:id,name')->orderBy('starts_at')->orderBy('id')->get()->filter(fn ($period) => $period->intervalStart() < $to && $period->intervalEnd() > $from)->map(fn ($period) => [
                'id' => 'unavailability-'.$period->id, 'kind' => 'unavailability',
                'title' => 'Indisponibilidade · '.$period->user->name,
                'assigned_user_id' => $period->user_id, 'participant_name' => $period->user->name,
                'care_recipient_id' => $period->care_recipient_id,
                'recipient' => $visibleRecipients[$period->care_recipient_id]->only(['id', 'name']),
                'starts_at' => $period->starts_at->format('Y-m-d'), 'ends_at' => $period->ends_at->format('Y-m-d'),
                'due_at' => $period->intervalStart()->toISOString(), 'interval_ends_at' => $period->intervalEnd()->toISOString(),
                'timezone' => $period->timezone, 'reason' => $period->reason,
                'can_execute' => false, 'can_cancel' => false,
            ])->values()->all();

        return [...$page->toArray(), 'unavailabilities' => $unavailabilities, 'availability_recipient' => $availabilityRecipient ? [...$availabilityRecipient->only(['id', 'name']), 'can_manage' => app(CareAvailability::class)->canManage($user, $availabilityRecipient)] : null, 'executors' => User::whereIn('id', $schedules->toBase()->map(fn ($s) => $s->snapshot['assigned_user_id'])->merge(collect($unavailabilities)->pluck('assigned_user_id'))->filter()->unique())->get(['id', 'name'])->toArray(), 'timezone' => $zone, 'from' => $from->setTimezone($zone)->format('Y-m-d'), 'to' => $to->subDay()->setTimezone($zone)->format('Y-m-d')];
    }

    public function applyException(CareEntry $entry, CareProposal $proposal): void
    {
        $exception = $proposal->payload['exception'];
        $o = CareOccurrence::whereHas('schedule', fn ($q) => $q->where('care_entry_id', $entry->id))->lockForUpdate()->findOrFail($exception['occurrence_id']);
        abort_unless($o->status === 'scheduled' && $o->starts_at->isFuture(), 409, 'A ocorrência já passou ou não está disponível para cancelamento.');
        if ($exception['scope'] === 'future') {
            $o->schedule->update(['valid_until' => $o->starts_at]);
            $o->schedule->occurrences()->where('starts_at', '>=', $o->starts_at)->where('status', 'scheduled')->update(['status' => 'cancelled', 'cancelled_by_proposal_id' => $proposal->id]);
        } else {
            $o->update(['status' => 'cancelled', 'cancelled_by_proposal_id' => $proposal->id]);
        }
    }

    public function execute(User $user, int $id, array $data): CareOccurrence
    {
        return DB::transaction(function () use ($user, $id, $data) {
            $o = CareOccurrence::with('schedule.entry.recipient')->findOrFail($id);
            $recipient = $this->guard->lock($o->schedule->entry->recipient);
            $user->unsetRelation('roles')->unsetRelation('permissions');
            $entry = $recipient->entries()->lockForUpdate()->findOrFail($o->schedule->care_entry_id);
            $o = CareOccurrence::whereKey($id)->lockForUpdate()->firstOrFail();
            $snapshot = $o->schedule->snapshot;
            $this->access->authorize($user, $recipient, $this->access->area($snapshot['kind']), true);
            abort_unless($this->access->canExecuteCare($user, $recipient, $snapshot['kind'], $snapshot['assigned_user_id']), 403);
            abort_if(in_array($snapshot['kind'], ['expense', 'journal']), 422);
            abort_unless($o->status === 'scheduled' && in_array($entry->status, ['pending', 'completed']) && ! $entry->proposals()->where('status', 'pending')->exists(), 409, 'Ocorrência indisponível ou alteração pendente.');
            app(CareAvailability::class)->ensureExecution($recipient, (int) $user->id, CarbonImmutable::parse($data['occurred_at'])->utc(), $o->starts_at, $o->ends_at);
            $record = $recipient->entries()->create([
                'created_by' => $user->id, 'related_entry_id' => $entry->id, 'kind' => $snapshot['kind'],
                'title' => 'Execução: '.$snapshot['title'], 'description' => $data['description'] ?? null,
                'due_at' => CarbonImmutable::parse($data['occurred_at'])->utc(), 'completed_at' => now(), 'status' => 'completed', 'revision' => 1, 'details' => $snapshot['details'],
            ]);
            $record->proposals()->create(['created_by' => $user->id, 'version' => 1, 'operation' => 'save', 'status' => 'accepted', 'payload' => ['data' => $record->only(['title', 'description', 'due_at']), 'shares' => [], 'affected_user_ids' => []]]);
            $o->update(['status' => 'executed', 'execution_entry_id' => $record->id]);

            return $o;
        });
    }
}
