<?php

namespace App\Modules\Notifications;

use App\Models\CareNotification;
use App\Models\CareOccurrence;
use App\Models\CareProfileProposal;
use App\Models\CareProposal;
use App\Models\CareRecipient;
use App\Models\CareSchedule;
use App\Models\Organization;
use App\Models\User;
use App\Modules\Care\Services\AccessControl;
use App\Modules\Care\Services\GenerateCareOccurrences;
use App\Modules\Care\Services\ProposalGuard;
use App\Support\Tenancy\CurrentOrganization;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class DeliverReminders implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [60, 300];

    public function __construct(public int $organizationId, public int $userId) {}

    public function handle(CurrentOrganization $context, ReminderPreferences $preferences, AccessControl $access): void
    {
        Cache::put('notifications:worker-heartbeat', now()->timestamp, now()->addDay());
        $group = Organization::where('status', 'active')->find($this->organizationId);
        if (! $group) {
            return;
        }

        $previousTeam = getPermissionsTeamId();
        setPermissionsTeamId($group->id);
        try {
            $this->deliverForGroup($context, $group, $preferences, $access);
        } finally {
            setPermissionsTeamId($previousTeam);
        }
    }

    private function deliverForGroup(CurrentOrganization $context, Organization $group, ReminderPreferences $preferences, AccessControl $access): void
    {
        $failed = $context->run($group, function () use ($group, $preferences, $access) {
            $user = $group->users()->whereKey($this->userId)->wherePivot('status', 'active')->first();
            if (! $user) {
                return;
            }
            $recipient = $access->visible($user)->where('status', 'active')->first();
            if (! $recipient) {
                return;
            }

            $settings = $preferences->get($user->id);
            if (! $settings['internal'] && ! $settings['email']) {
                return;
            }
            $now = CarbonImmutable::now()->utc();
            CareSchedule::whereHas('entry', fn ($query) => $query->where('care_recipient_id', $recipient->id)
                ->where('publish_to_agenda', true)->whereIn('status', ['pending', 'completed']))
                ->where(fn ($query) => $query->whereNull('valid_until')->orWhere('valid_until', '>', $now->subDay()))
                ->each(fn ($schedule) => app(GenerateCareOccurrences::class)->generate($schedule, $now->subDay(), $now->addDay()->addMinute()));

            return DB::transaction(function () use ($group, $preferences, $access, $user, $recipient) {
                $recipient = app(ProposalGuard::class)->lock($recipient);
                if ($recipient->status !== 'active' || ! Organization::whereKey($group->id)->where('status', 'active')->whereHas('users', fn ($q) => $q->whereKey($user->id)->where('organization_user.status', 'active'))->exists()) {
                    return;
                }
                $user->unsetRelation('roles')->unsetRelation('permissions');
                $settings = $preferences->get($user->id);
                $local = CarbonImmutable::now($group->timezone);
                if ((! $settings['internal'] && ! $settings['email']) || $this->quiet($settings, $local->format('H:i'))) {
                    return;
                }
                $events = $this->events($user, $recipient, $access, $settings, $local);
                if ($settings['summary']) {
                    if ($local->hour >= 18 && $events) {
                        $summaries = [];
                        foreach (collect($events)->groupBy(2) as $area => $items) {
                            $count = $items->count();
                            $summaries[] = ['summary:'.$local->toDateString().':'.$area, "Resumo de lembretes · {$count} atualização(ões).", $area, null];
                        }

                        return $this->deliver($user, $recipient->id, $summaries, $settings);
                    }

                    return;
                }

                return $this->deliver($user, $recipient->id, $events, $settings);
            });
        });
        if ($failed) {
            throw new RuntimeException('Falha no transporte de lembretes do conViva.');
        }
    }

    private function events(User $user, CareRecipient $recipient, AccessControl $access, array $settings, CarbonImmutable $now): array
    {
        $events = [];
        $proposals = CareProposal::where('status', 'pending')
            ->whereHas('entry', fn ($q) => $q->where('care_recipient_id', $recipient->id))
            ->whereHas('decisions', fn ($q) => $q->where('user_id', $user->id)->where('status', 'pending'))
            ->with('entry', 'decisions')->get();
        foreach ($proposals as $proposal) {
            $area = $access->area($proposal->entry->kind);
            if ($access->allowed($user, $recipient, $area, true) && ! app(ProposalGuard::class)->blockers($recipient, $proposal, $area)) {
                $events[] = ['proposal:'.$proposal->id, 'Uma proposta aguarda sua decisão.', $area, ['care_proposal_id' => $proposal->id]];
            }
        }
        $profiles = CareProfileProposal::where('status', 'pending')->where('care_recipient_id', $recipient->id)
            ->whereHas('decisions', fn ($q) => $q->where('user_id', $user->id)->where('status', 'pending'))->with('decisions')->get();
        foreach ($profiles as $proposal) {
            if ($access->canManageProfile($user, $recipient) && ! app(ProposalGuard::class)->blockers($recipient, $proposal, 'routine', true)) {
                $events[] = ['profile:'.$proposal->id, 'Uma revisão cadastral aguarda sua decisão.', 'routine', ['care_profile_proposal_id' => $proposal->id]];
            }
        }
        $occurrences = CareOccurrence::where('status', 'scheduled')->whereNull('execution_entry_id')
            ->whereBetween('starts_at', [$now->utc()->subDay(), $now->utc()->addMinutes($settings['advance'])])
            ->whereHas('schedule.entry', fn ($q) => $q->where('care_recipient_id', $recipient->id)->where('publish_to_agenda', true)
                ->whereIn('status', ['pending', 'completed'])->whereDoesntHave('proposals', fn ($p) => $p->where('status', 'pending')))
            ->with('schedule')->get();
        foreach ($occurrences as $occurrence) {
            if (($occurrence->schedule->valid_until && $occurrence->starts_at >= $occurrence->schedule->valid_until)
                || ($occurrence->starts_at <= $now && ! $occurrence->isOverdue())) {
                continue;
            }
            $snapshot = $occurrence->schedule->snapshot;
            $kind = $snapshot['kind'];
            $area = $access->area($kind);
            $assigned = $snapshot['assigned_user_id'];
            if (in_array($kind, ['expense', 'journal']) || ! $access->allowed($user, $recipient, $area)) {
                continue;
            }
            if (! $access->canExecuteCare($user, $recipient, $kind, $assigned) && ! $settings['third_party']) {
                continue;
            }
            $late = $occurrence->isOverdue();
            $events[] = [
                ($late ? 'overdue:' : 'upcoming:').$occurrence->id,
                $late ? 'Uma ocorrência está vencida e sem execução registrada.' : 'Uma ocorrência está próxima.',
                $area,
                ['agenda' => $occurrence->starts_at->setTimezone($now->timezone)->toDateString()],
            ];
        }

        return $events;
    }

    private function deliver(User $user, int $recipientId, array $events, array $settings): bool
    {
        $failed = false;
        foreach ($events as [$key, $message, $area, $target]) {
            foreach (['internal', 'email'] as $channel) {
                if (! $settings[$channel]) {
                    continue;
                }
                $scope = [
                    'organization_id' => $this->organizationId, 'user_id' => $user->id,
                    'event_key' => $key, 'channel' => $channel,
                ];
                $query = DB::table('notification_reminder_deliveries')->where($scope);
                if ($query->exists()) {
                    continue;
                }
                if ($channel === 'internal') {
                    $agenda = $target['agenda'] ?? null;
                    $notification = CareNotification::create([
                        'care_recipient_id' => $recipientId, 'user_id' => $user->id, 'area' => $area, 'message' => $message,
                    ]);
                    if ($agenda) {
                        DB::table('notification_reminder_deliveries')->insert([...$scope, 'delivered_at' => now(), 'care_notification_id' => $notification->id, 'agenda_date' => $agenda]);

                        continue;
                    }
                    if ($target) {
                        DB::table('care_notification_targets')->insert(['care_notification_id' => $notification->id, ...$target]);
                    }
                } else {
                    try {
                        Mail::raw('Você tem um lembrete no conViva. Consulte as notificações, a Central de Decisões ou a Agenda após entrar.', function ($mail) use ($user) {
                            $mail->to($user->email)->subject('Lembrete do conViva');
                        });
                    } catch (Throwable) {
                        $failed = true;

                        continue;
                    }
                }
                DB::table('notification_reminder_deliveries')->insert([...$scope, 'delivered_at' => now()]);
            }
        }

        return $failed;
    }

    private function quiet(array $settings, string $time): bool
    {
        $start = $settings['quiet_start'];
        $end = $settings['quiet_end'];

        return $start && $end && ($start < $end ? $time >= $start && $time < $end : $time >= $start || $time < $end);
    }

    public function failed(Throwable $error): void
    {
        Log::warning('Falha definitiva no processamento de lembretes.', ['organization_id' => $this->organizationId]);
    }
}
