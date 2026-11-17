<?php

namespace App\Modules\Expenses\Services;

use App\Models\CareEntry;
use App\Models\User;
use App\Modules\Care\Services\AccessControl;
use App\Modules\Care\Services\CareRecords;
use App\Support\Tenancy\CurrentOrganization;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class ExpenseQuery
{
    public function __construct(private AccessControl $access, private CareRecords $records, private ExpensePayments $payments) {}

    public function listing(User $user, array $filters = []): array
    {
        $recipients = $this->access->visible($user, 'expenses')->get()
            ->filter(fn ($p) => $this->access->allowed($user, $p, 'expenses'));
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
        $visible = CareEntry::whereIn('care_recipient_id', $recipients->pluck('id'))->where('kind', 'expense')
            ->whereNull('related_entry_id');
        $categories = (clone $visible)->whereNotNull('details->expense_category')
            ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(details, '$.expense_category')) AS category")
            ->distinct()->orderBy('category')->pluck('category');
        $participants = User::query()->whereExists((clone $visible)->selectRaw('1')
            ->where(fn ($query) => $this->whereParticipant($query, 'users.id', column: true))->toBase())
            ->orderBy('name')->orderBy('id')->get(['id', 'name']);
        $filtered = (clone $visible)
            ->when($from, fn ($q) => $q->whereRaw('COALESCE(due_at, created_at) >= ?', [$from->utc()])
                ->whereRaw('COALESCE(due_at, created_at) < ?', [$to->startOfDay()->addDay()->utc()]))
            ->when(isset($filters['category']), fn ($q) => $q->where('details->expense_category', $filters['category']))
            ->when($filters['participant'] ?? null, fn ($q, $id) => $q->where(fn ($participant) => $this->whereParticipant($participant, $id)))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status));
        $summary = (clone $filtered)->where('revision', '>', 0)->whereIn('status', ['pending', 'completed'])
            ->selectRaw('SUM(amount_cents) AS confirmed, SUM((
                SELECT SUM(expense_shares.amount_cents) FROM expense_shares
                WHERE expense_shares.care_entry_id = care_entries.id AND expense_shares.paid_at IS NOT NULL
            )) AS paid')->first();
        $total = (int) $summary->confirmed;
        $paid = (int) $summary->paid;
        $page = $filtered
            ->with('shares.user:id,name', 'author:id,name', 'proposals.decisions.user:id,name')
            ->orderByDesc('created_at')->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 20, page: $filters['page'] ?? 1);
        $page->getCollection()->each(function ($entry) use ($user, $recipients) {
            $recipient = $recipients->firstWhere('id', $entry->care_recipient_id);
            $this->records->decorate($entry);
            $this->payments->decorate($user, $recipient, $entry);
            $entry->setAttribute('expense_group', $entry->revision > 0 && in_array($entry->status, ['pending', 'completed']) ? 'confirmed' : ($entry->status === 'awaiting_approval' ? 'proposals' : 'history'));
            $entry->unsetRelation('recipient');
            $entry->setAttribute('recipient', $recipient->only(['id', 'name']));
            $entry->setAttribute('can_change', $this->access->allowed($user, $recipient, 'expenses', true)
                && (int) $entry->created_by === (int) $user->id && $entry->status !== 'cancelled'
                && ! $entry->proposals->contains('status', 'pending') && ! $entry->shares->contains(fn ($s) => $s->paid_at !== null));
        });

        return [
            'data' => $page->items(),
            'pagination' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
            'categories' => $categories,
            'participants' => $participants,
            'period' => ['key' => $period, 'from' => $from?->format('Y-m-d'), 'to' => $to?->format('Y-m-d'), 'timezone' => $zone],
            'recipients' => $recipients->map(fn ($p) => [...$p->only(['id', 'name']), 'can_create' => $p->status === 'active' && $this->access->allowed($user, $p, 'expenses', true)])->values(),
            'summary' => ['confirmed_cents' => $total, 'paid_cents' => $paid, 'unpaid_cents' => $total - $paid],
        ];
    }

    private function whereParticipant(Builder $query, string|int $participant, bool $column = false): Builder
    {
        $compare = $column ? 'whereColumn' : 'where';

        return $query->{$compare}('care_entries.created_by', $participant)
            ->orWhereHas('shares', fn ($shares) => $shares->{$compare}('expense_shares.user_id', $participant))
            ->orWhereHas('proposals.decisions', fn ($decisions) => $decisions->{$compare}('care_decisions.user_id', $participant));
    }
}
