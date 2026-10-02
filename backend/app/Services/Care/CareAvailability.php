<?php

namespace App\Services\Care;

use App\Models\CareRecipient;
use App\Models\CareUnavailability;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CareAvailability
{
    public function canView(User $user, CareRecipient $recipient): bool
    {
        return app(AccessControl::class)->allowed($user, $recipient);
    }

    public function sharedListing(User $user, CareRecipient $recipient): array
    {
        abort_unless($this->canView($user, $recipient), 403);
        $canManage = $this->canManage($user, $recipient);
        $page = CareUnavailability::where('care_recipient_id', $recipient->id)->with('user:id,name')->orderByDesc('starts_at')->orderByDesc('id')->paginate(20);
        $page->getCollection()->each(function ($period) use ($user, $canManage) {
            $period->setAttribute('can_cancel', $canManage && (int) $period->user_id === (int) $user->id && ! $period->cancelled_at);
        });

        return $page->toArray();
    }

    public function canManage(User $user, CareRecipient $recipient): bool
    {
        $access = app(AccessControl::class);

        return $recipient->status === 'active' && ! $user->hasRole('observador')
            && ($access->allowed($user, $recipient, 'routine', true) || $access->allowed($user, $recipient, 'health', true));
    }

    public function authorize(User $user, CareRecipient $recipient): void
    {
        abort_unless($this->canManage($user, $recipient), 403);
    }

    public function listing(User $user, CareRecipient $recipient): array
    {
        $this->authorize($user, $recipient);

        return CareUnavailability::where('care_recipient_id', $recipient->id)->where('user_id', $user->id)
            ->orderByDesc('starts_at')->paginate(20)->toArray();
    }

    public function save(User $user, CareRecipient $recipient, array $data): CareUnavailability
    {
        return DB::transaction(function () use ($user, $recipient, $data) {
            $recipient = app(ProposalGuard::class)->lock($recipient);
            $user->unsetRelation('roles')->unsetRelation('permissions');
            $this->authorize($user, $recipient);

            return CareUnavailability::create([
                'care_recipient_id' => $recipient->id, 'user_id' => $user->id,
                'starts_at' => $data['starts_at'], 'ends_at' => $data['ends_at'], 'timezone' => $data['timezone'],
                'reason' => isset($data['reason']) && trim($data['reason']) !== '' ? trim($data['reason']) : null,
            ]);
        });
    }

    public function cancel(User $user, CareRecipient $recipient, int $id): CareUnavailability
    {
        return DB::transaction(function () use ($user, $recipient, $id) {
            $recipient = app(ProposalGuard::class)->lock($recipient);
            $user->unsetRelation('roles')->unsetRelation('permissions');
            $this->authorize($user, $recipient);
            $period = CareUnavailability::where('care_recipient_id', $recipient->id)->where('user_id', $user->id)->lockForUpdate()->findOrFail($id);
            abort_if($period->cancelled_at, 409, 'Este afastamento já foi cancelado.');
            $period->update(['cancelled_at' => now()]);

            return $period;
        });
    }

    // Intervals are [start, end). A care without duration is checked as an instant.
    public function conflict(CareRecipient $recipient, int $userId, CarbonImmutable $start, ?CarbonImmutable $end = null): ?CareUnavailability
    {
        return CareUnavailability::where('care_recipient_id', $recipient->id)->where('user_id', $userId)
            ->whereNull('cancelled_at')->orderBy('starts_at')->get()
            ->first(fn ($period) => $period->intervalEnd() > $start
                && ($end && $end > $start ? $period->intervalStart() < $end : $period->intervalStart() <= $start));
    }

    public function message(CareUnavailability $period, bool $sharing = false): string
    {
        $name = User::whereKey($period->user_id)->value('name');
        $range = $period->starts_at->format('d/m/Y').' até '.$period->ends_at->format('d/m/Y').' (dias completos, '.$period->timezone.')';

        $message = $sharing ? 'Não é possível compartilhar o cuidado: '.$name.' informou indisponibilidade de '.$range.'.'
            : 'Execução impedida: você informou indisponibilidade de '.$range.'.';

        return $period->reason !== null && $period->reason !== '' ? $message.' Motivo informado: '.$period->reason : $message;
    }

    public function executionBlock(CareRecipient $recipient, int $userId, ?CarbonImmutable $occurredAt = null, ?CarbonImmutable $start = null, ?CarbonImmutable $end = null): ?string
    {
        $period = $this->conflict($recipient, $userId, CarbonImmutable::now()->utc());
        $period ??= $occurredAt ? $this->conflict($recipient, $userId, $occurredAt) : null;
        $period ??= $start ? $this->conflict($recipient, $userId, $start, $end) : null;

        return $period ? $this->message($period) : null;
    }

    public function ensureExecution(CareRecipient $recipient, int $userId, CarbonImmutable $occurredAt, ?CarbonImmutable $start = null, ?CarbonImmutable $end = null): void
    {
        $message = $this->executionBlock($recipient, $userId, $occurredAt, $start, $end);
        if ($message) {
            throw ValidationException::withMessages(['occurred_at' => $message]);
        }
    }

    public function assignmentBlock(CareRecipient $recipient, array $data, array $ids): ?array
    {
        if (! in_array($data['kind'] ?? null, ['event', 'task', 'feeding', 'medication', 'vaccine'])) {
            return null;
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', [...$ids, $data['assigned_user_id'] ?? null]))));
        if (! $ids) {
            return null;
        }
        $periods = CareUnavailability::where('care_recipient_id', $recipient->id)->whereIn('user_id', $ids)->whereNull('cancelled_at')->orderBy('starts_at')->get();
        foreach ($this->intervals($data) as [$start, $end]) {
            foreach ($periods as $period) {
                if ($period->intervalEnd() > $start && ($end > $start ? $period->intervalStart() < $end : $period->intervalStart() <= $start)) {
                    return ['user_id' => (int) $period->user_id, 'message' => $this->message($period, true)];
                }
            }
        }

        return null;
    }

    public function ensureAssignment(CareRecipient $recipient, array $data, array $ids): void
    {
        $block = $this->assignmentBlock($recipient, $data, $ids);
        if ($block) {
            throw ValidationException::withMessages(['assigned_user_id' => $block['message']]);
        }
    }

    private function intervals(array $data): iterable
    {
        $rule = $data['schedule'] ?? null;
        if (! $rule) {
            $start = ! empty($data['due_at']) ? CarbonImmutable::parse($data['due_at'])->utc() : CarbonImmutable::now()->utc();
            yield [$start, ! empty($data['ends_at']) ? CarbonImmutable::parse($data['ends_at'])->utc() : $start];

            return;
        }
        $start = CarbonImmutable::parse($rule['local_start'], $rule['timezone']);
        $last = $rule['frequency'] === 'once' ? $start->format('Y-m-d') : $rule['until'];
        for ($day = $start->startOfDay(); $day->format('Y-m-d') <= $last; $day = $day->addDay()) {
            if ($rule['frequency'] === 'weekly' && ! in_array($day->dayOfWeekIso, $rule['weekdays'])) {
                continue;
            }
            $wall = $day->format('Y-m-d').'T'.$start->format('H:i');
            $local = CarbonImmutable::parse($wall, $rule['timezone']);
            if ($local->format('Y-m-d\\TH:i') !== $wall) {
                continue;
            }
            yield [$local->utc(), $local->addMinutes($rule['duration_minutes'])->utc()];
        }
    }
}
