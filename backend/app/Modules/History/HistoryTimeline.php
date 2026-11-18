<?php

namespace App\Modules\History;

use App\Models\CareRecipient;
use App\Models\User;
use App\Modules\Care\Services\AccessControl;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class HistoryTimeline
{
    public const KINDS = [
        'routine' => ['event', 'task', 'journal', 'feeding'],
        'health' => ['medication', 'vaccine'],
        'expenses' => ['expense'],
    ];

    public function __construct(private AccessControl $access) {}

    public function areas(User $user, CareRecipient $recipient): array
    {
        return array_values(array_filter(
            array_keys(self::KINDS),
            fn ($area) => $this->access->allowed($user, $recipient, $area),
        ));
    }

    public function query(CareRecipient $recipient, array $areas, array $filters): Builder
    {
        $kinds = array_merge(...array_map(fn ($area) => self::KINDS[$area], $areas));
        $entries = DB::table('care_entries as e')
            ->where('e.organization_id', $recipient->organization_id)
            ->where('e.care_recipient_id', $recipient->id)
            ->whereIn('e.kind', $kinds);
        $proposals = (clone $entries)->whereNull('e.related_entry_id')
            ->join('care_proposals as p', 'p.care_entry_id', '=', 'e.id');
        $events = (clone $proposals)->selectRaw("
            'proposal' as event_type, p.id as event_id, p.created_at as recorded_at, p.created_by as actor_id,
            e.kind, e.title, p.payload as details, p.status, p.operation, p.version, e.id as record_id,
            NULL as related_id, NULL as occurred_at, NULL as reason
        ");
        $events->unionAll((clone $proposals)->join('care_decisions as d', 'd.care_proposal_id', '=', 'p.id')
            ->whereNotNull('d.decided_at')->whereIn('d.status', ['accepted', 'rejected'])->selectRaw("
                'decision', d.id, d.decided_at, d.user_id, e.kind, e.title, p.payload,
                d.status, p.operation, p.version, e.id, NULL, NULL, d.reason
            "));
        $events->unionAll((clone $proposals)->where('p.status', 'withdrawn')->selectRaw("
            'withdrawal', p.id, p.updated_at, p.created_by, e.kind, e.title, p.payload,
            p.status, p.operation, p.version, e.id, NULL, NULL, NULL
        "));
        $events->unionAll((clone $entries)->whereNotNull('e.related_entry_id')->selectRaw("
            'execution', e.id, e.created_at, e.created_by, e.kind, e.title, e.details,
            e.status, NULL, NULL, e.id, e.related_entry_id, e.due_at, e.description
        "));
        if (in_array('routine', $areas, true)) {
            $profiles = DB::table('care_profile_proposals as p')->where('p.care_recipient_id', $recipient->id);
            $events->unionAll((clone $profiles)->selectRaw("
                'profile_proposal', p.id, p.created_at, p.created_by, 'profile', 'Revisão cadastral',
                NULL, p.status, p.operation, p.version, p.care_recipient_id, NULL, NULL, NULL
            "));
            $events->unionAll((clone $profiles)->join('care_profile_decisions as d', 'd.care_profile_proposal_id', '=', 'p.id')
                ->whereNotNull('d.decided_at')->whereIn('d.status', ['accepted', 'rejected'])->selectRaw("
                    'profile_decision', d.id, d.decided_at, d.user_id, 'profile', 'Revisão cadastral',
                    NULL, d.status, p.operation, p.version, p.care_recipient_id, NULL, NULL, d.reason
                "));
            $events->unionAll($profiles->where('p.status', 'withdrawn')->selectRaw("
                'profile_withdrawal', p.id, p.updated_at, p.created_by, 'profile', 'Revisão cadastral',
                NULL, p.status, p.operation, p.version, p.care_recipient_id, NULL, NULL, NULL
            "));
        }

        return DB::query()->fromSub($events, 'events')->join('users as u', 'u.id', '=', 'events.actor_id')
            ->select('events.*', 'u.name as author')
            ->where('recorded_at', '>=', CarbonImmutable::parse($filters['from'], $filters['timezone'])->utc())
            ->where('recorded_at', '<', CarbonImmutable::parse($filters['to'], $filters['timezone'])->addDay()->utc())
            ->orderByDesc('recorded_at')->orderBy('event_type')->orderByDesc('event_id');
    }

    public function present(object $row): array
    {
        $details = json_decode($row->details ?? '{}', true);
        $correction = $row->event_type === 'execution' && isset($details['corrects_execution_id']);

        return [
            'id' => $row->event_type.':'.$row->event_id,
            'type' => $correction ? 'correction' : $row->event_type,
            'area' => $row->kind === 'profile' ? 'routine' : $this->access->area($row->kind),
            'title' => $details['data']['title'] ?? $row->title,
            'author' => $row->author,
            'recorded_at' => CarbonImmutable::parse($row->recorded_at, 'UTC')->toIso8601String(),
            'occurred_at' => $row->occurred_at ? CarbonImmutable::parse($row->occurred_at, 'UTC')->toIso8601String() : null,
            'status' => $row->status,
            'operation' => $row->operation,
            'version' => $row->version,
            'record_id' => $row->record_id,
            'related_id' => $details['corrects_execution_id'] ?? $row->related_id,
            'outcome' => $details['outcome'] ?? null,
            'reason' => $correction ? ($details['correction_reason'] ?? null) : $row->reason,
            'notes' => $correction ? $row->reason : null,
        ];
    }
}
