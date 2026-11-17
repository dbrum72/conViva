<?php

namespace App\Modules\Care\Services;

use App\Models\CareEntry;
use App\Models\CareFact;
use App\Models\CareRecipient;
use App\Models\CareSchedule;
use App\Models\User;
use App\Support\Tenancy\CurrentOrganization;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class CareToday
{
    private const ROUTINE = ['event', 'task', 'feeding'];

    private const HEALTH = ['medication', 'vaccine'];

    public function __construct(
        private AccessControl $access,
        private CareAgenda $agenda,
        private DecisionCenter $decisions,
        private CareRecords $records,
        private ProposalGuard $guard,
        private CareAvailability $availability,
    ) {}

    public function overview(User $user): array
    {
        $recipient = $this->access->visible($user)->where('status', 'active')->first();
        $zone = app(CurrentOrganization::class)->get()->timezone;
        $today = CarbonImmutable::now($zone)->startOfDay();
        $decisions = $this->decisions->listing($user, ['scope' => 'mine', 'per_page' => 5], page: 1);
        $result = [
            'recipient' => $recipient?->only(['id', 'name', 'kind']),
            'timezone' => $zone,
            'quick_facts' => [],
            'upcoming' => [],
            'handover' => null,
            'decisions' => $decisions->getCollection()->map(fn ($item) => array_intersect_key($item, array_flip(['id', 'title', 'type'])))->all(),
            'decision_count' => $decisions->total(),
        ];
        if (! $recipient) {
            return $result;
        }

        $result['handover'] = $this->handover($user, $recipient, $today);
        $recentByEntry = collect($result['handover']['executions'] ?? [])->groupBy('entry_id');
        $upcoming = $this->agenda->listing($user, [
            'from' => $today->toDateString(),
            'to' => $today->addDays(6)->toDateString(),
            'per_page' => 6,
        ], 'upcoming')['data'];
        $result['upcoming'] = array_map(fn ($item) => [
            ...$item, 'last_execution' => $recentByEntry->get($item['entry_id'])?->first(),
        ], $upcoming);
        $result['quick_facts'] = $this->quickFacts($user, $recipient);

        return $result;
    }

    public function record(User $user, CareRecipient $recipient, array $data): CareFact
    {
        return DB::transaction(function () use ($user, $recipient, $data) {
            $recipient = $this->guard->lock($recipient);
            $user->unsetRelation('roles')->unsetRelation('permissions');
            abort_unless(in_array($data['kind'], $this->quickFacts($user, $recipient), true), 403);
            $occurred = CarbonImmutable::parse($data['occurred_at'])->utc();
            $existing = CareFact::where('care_recipient_id', $recipient->id)->where('created_by', $user->id)
                ->where('request_id', $data['request_id'])->first();
            if ($existing) {
                abort_unless($existing->kind === $data['kind'] && $existing->occurred_at->eq($occurred->startOfSecond())
                    && $existing->description === ($data['description'] ?? null), 409, 'O identificador já foi usado em outro registro.');

                return $existing;
            }
            if ($data['kind'] !== 'observation') {
                $this->availability->ensureExecution($recipient, (int) $user->id, $occurred);
            }

            return CareFact::create([
                ...$data,
                'occurred_at' => $occurred,
                'care_recipient_id' => $recipient->id,
                'created_by' => $user->id,
            ]);
        });
    }

    public function acknowledge(User $user, CareRecipient $recipient, string $version): void
    {
        DB::transaction(function () use ($user, $recipient, $version) {
            $recipient = $this->guard->lock($recipient);
            $user->unsetRelation('roles')->unsetRelation('permissions');
            $today = CarbonImmutable::now($recipient->organization->timezone)->startOfDay();
            $handover = $this->handover($user, $recipient, $today);
            abort_unless($handover, 403);
            abort_unless(hash_equals($handover['version'], $version), 409, 'A passagem mudou. Atualize antes de confirmar a leitura.');
            if ($handover['read_at']) {
                return;
            }
            DB::table('care_handover_reads')->updateOrInsert(
                $this->readKey($user, $recipient),
                ['version' => $version, 'read_at' => now()],
            );
        });
    }

    private function handover(User $user, CareRecipient $recipient, CarbonImmutable $today): ?array
    {
        $routine = $this->access->allowed($user, $recipient, 'routine');
        $health = $this->access->allowed($user, $recipient, 'health');
        if ($recipient->status !== 'active' || (! $routine && ! $health)) {
            return null;
        }

        $from = $today->subDays(6);
        $entries = $recipient->entries()->whereIn('kind', array_merge($routine ? self::ROUTINE : [], $health ? self::HEALTH : []));
        $executions = (clone $entries)->whereNotNull('related_entry_id')->where('status', 'completed')
            ->whereRaw('COALESCE(due_at, completed_at) >= ?', [$from->utc()->toDateTimeString()])
            ->whereDoesntHave('corrections', fn ($q) => $q->whereNotNull('details->corrects_execution_id'))
            ->whereNotExists(CareEntry::query()->from('care_entries as later')->withoutGlobalScopes()
                ->selectRaw('1')->whereColumn('later.organization_id', 'care_entries.organization_id')
                ->whereColumn('later.related_entry_id', 'care_entries.related_entry_id')
                ->whereColumn('later.id', '>', 'care_entries.id')->whereNotNull('later.details->corrects_execution_id')
                ->whereNotNull('care_entries.details->corrects_execution_id')->toBase())
            ->with('author:id,name', 'relatedEntry:id,related_entry_id')->orderByRaw('COALESCE(due_at, completed_at) DESC')->orderByDesc('id')->limit(8)->get()
            ->map(fn (CareEntry $entry) => [
                'id' => $entry->id, 'kind' => $entry->kind, 'title' => $entry->title,
                'entry_id' => $entry->relatedEntry?->related_entry_id ?? $entry->related_entry_id,
                'author' => $entry->author?->name, 'description' => $entry->description,
                'occurred_at' => ($entry->due_at ?? $entry->completed_at)->toISOString(),
                'recorded_at' => $entry->created_at->toISOString(),
                'outcome' => $entry->details['outcome'] ?? (in_array($entry->kind, self::HEALTH, true) ? 'administered' : null),
            ])->all();
        $facts = $routine ? CareFact::where('care_recipient_id', $recipient->id)
            ->where('occurred_at', '>=', $from->utc())->with('author:id,name')
            ->orderByDesc('occurred_at')->orderByDesc('id')->limit(8)->get()
            ->map(fn (CareFact $fact) => [
                ...$fact->only(['id', 'kind', 'occurred_at', 'description']),
                'author' => $fact->author?->name, 'recorded_at' => $fact->created_at,
            ])->all() : [];
        $tasks = $routine ? (clone $entries)->where('kind', 'task')->whereNull('related_entry_id')->whereNull('due_at')
            ->where('revision', '>', 0)->whereIn('status', ['pending', 'completed'])
            ->whereNotIn('id', CareSchedule::query()->select('care_entry_id'))
            ->whereDoesntHave('corrections')->with('recipient', 'proposals.entry')->orderByDesc('id')->limit(6)->get()
            ->map(fn (CareEntry $entry) => $this->records->decorate($entry)->only(['id', 'kind', 'title', 'can_execute', 'execution_block']))->all() : [];
        $overdue = $this->agenda->listing($user, [
            'from' => $from->toDateString(), 'to' => $today->toDateString(), 'per_page' => 6,
        ], 'overdue')['data'];
        $version = hash('sha256', json_encode([
            $recipient->id, $user->id, $routine, $health, $executions, $facts,
            array_map(fn ($item) => array_intersect_key($item, array_flip(['id', 'title'])), $tasks),
            array_map(fn ($item) => array_intersect_key($item, array_flip(['id', 'title', 'due_at'])), $overdue),
        ], JSON_THROW_ON_ERROR));
        $read = DB::table('care_handover_reads')->where($this->readKey($user, $recipient))->where('version', $version)->value('read_at');

        return [
            'version' => $version, 'read_at' => $read ? CarbonImmutable::parse($read, 'UTC')->toISOString() : null,
            'executions' => $executions, 'facts' => $facts, 'tasks' => $tasks, 'overdue' => $overdue,
        ];
    }

    private function quickFacts(User $user, CareRecipient $recipient): array
    {
        if ($recipient->status !== 'active' || ! $this->access->allowed($user, $recipient, 'routine', true)) {
            return [];
        }

        return $recipient->kind === 'pet' ? ['feeding', 'walk', 'observation'] : ['feeding', 'observation'];
    }

    private function readKey(User $user, CareRecipient $recipient): array
    {
        return ['organization_id' => $recipient->organization_id, 'care_recipient_id' => $recipient->id, 'user_id' => $user->id];
    }
}
