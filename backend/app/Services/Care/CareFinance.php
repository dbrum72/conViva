<?php

namespace App\Services\Care;

use App\Models\CareEntry;
use App\Models\User;
use App\Support\Tenancy\CurrentOrganization;
use Carbon\CarbonImmutable;

class CareFinance
{
    public function __construct(private AccessControl $access, private CareRecords $records, private ExpensePayments $payments) {}

    public function listing(User $user, array $filters = []): array
    {
        $recipients = $this->access->visible($user, 'finance')->get()
            ->filter(fn ($p) => $this->access->allowed($user, $p, 'finance'));
        $zone = app(CurrentOrganization::class)->get()->timezone;
        $today = CarbonImmutable::now($zone)->startOfDay();
        $period = $filters['period'] ?? 'all';
        [$from, $to] = match ($period) {
            'today' => [$today, $today],
            'week' => [$today->startOfWeek(1), $today->endOfWeek(0)],
            'month' => [$today->startOfMonth(), $today->endOfMonth()],
            'last_month' => [$today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth()],
            'last_7' => [$today->subDays(6), $today],
            'last_30' => [$today->subDays(29), $today],
            'year' => [$today->startOfYear(), $today->endOfYear()],
            'custom' => [CarbonImmutable::parse($filters['from'], $zone)->startOfDay(), CarbonImmutable::parse($filters['to'], $zone)->startOfDay()],
            default => [null, null],
        };
        $entries = CareEntry::whereIn('care_recipient_id', $recipients->pluck('id'))->where('kind', 'expense')
            ->whereNull('related_entry_id')
            ->when($from, fn ($q) => $q->whereRaw('COALESCE(due_at, created_at) >= ?', [$from->utc()])
                ->whereRaw('COALESCE(due_at, created_at) < ?', [$to->startOfDay()->addDay()->utc()]))
            ->with('shares.user:id,name', 'author:id,name', 'proposals.decisions.user:id,name')
            ->orderByDesc('created_at')->orderByDesc('id')->get();
        $confirmed = $entries->filter(fn ($e) => $e->revision > 0 && in_array($e->status, ['pending', 'completed']));
        $total = (int) $confirmed->sum('amount_cents');
        $paid = (int) $confirmed->flatMap(fn ($e) => $e->shares)->whereNotNull('paid_at')->sum('amount_cents');
        $entries->each(function ($entry) use ($user, $recipients) {
            $recipient = $recipients->firstWhere('id', $entry->care_recipient_id);
            $this->records->decorate($entry);
            $this->payments->decorate($user, $recipient, $entry);
            $entry->setAttribute('finance_group', $entry->revision > 0 && in_array($entry->status, ['pending', 'completed']) ? 'confirmed' : ($entry->status === 'awaiting_approval' ? 'proposals' : 'history'));
            $entry->unsetRelation('recipient');
            $entry->setAttribute('recipient', $recipient->only(['id', 'name']));
            $entry->setAttribute('can_change', $this->access->allowed($user, $recipient, 'finance', true)
                && (int) $entry->created_by === (int) $user->id && $entry->status !== 'cancelled'
                && ! $entry->proposals->contains('status', 'pending') && ! $entry->shares->contains(fn ($s) => $s->paid_at !== null));
        });

        return [
            'data' => $entries,
            'period' => ['key' => $period, 'from' => $from?->format('Y-m-d'), 'to' => $to?->format('Y-m-d'), 'timezone' => $zone],
            'recipients' => $recipients->map(fn ($p) => [...$p->only(['id', 'name']), 'can_create' => $p->status === 'active' && $this->access->allowed($user, $p, 'finance', true)])->values(),
            'summary' => ['confirmed_cents' => $total, 'paid_cents' => $paid, 'unpaid_cents' => $total - $paid],
        ];
    }
}
