<?php

namespace App\Http\Controllers;

use App\Models\CareNotification;
use App\Services\Care\AccessControl;
use App\Services\Care\CareAgenda;
use App\Services\Care\CareFinance;
use App\Services\Care\CareNotifications;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CareOverviewController extends Controller
{
    public function __construct(private AccessControl $access) {}

    public function agenda(Request $r)
    {
        $filters = $r->validate([
            'from' => 'sometimes|date_format:Y-m-d', 'to' => 'sometimes|date_format:Y-m-d|after_or_equal:from',
            'kind' => 'nullable|in:event,task,feeding,journal,medication,vaccine,expense',
            'executor' => 'nullable|integer|min:1', 'status' => 'sometimes|in:scheduled,executed,cancelled,superseded',
            'page' => 'sometimes|integer|min:1', 'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        return app(CareAgenda::class)->listing($r->user(), $filters);
    }

    public function finance(Request $r)
    {
        $filters = $r->validate([
            'period' => 'sometimes|in:all,today,week,month,last_month,last_7,last_30,year,custom',
            'from' => 'required_if:period,custom|nullable|date_format:Y-m-d',
            'to' => 'required_if:period,custom|nullable|date_format:Y-m-d|after_or_equal:from',
        ]);

        return app(CareFinance::class)->listing($r->user(), $filters);
    }

    public function notifications(Request $r)
    {
        return app(CareNotifications::class)->visible($r->user())->with('recipient')->latest()->limit(100)->get()->map(function ($n) {
            $target = DB::table('care_notification_targets')->where('care_notification_id', $n->id)->first();
            $n->destination = $target ? ['type' => $target->care_profile_proposal_id ? 'profile' : 'entry', 'proposal' => $target->care_profile_proposal_id ?? $target->care_proposal_id] : null;

            return $n;
        })->values();
    }

    public function unreadCount(Request $r)
    {
        return ['unread_count' => app(CareNotifications::class)->visible($r->user())->whereNull('read_at')->count()];
    }

    public function read(Request $r, int $notification)
    {
        $n = CareNotification::where('user_id', $r->user()->id)->findOrFail($notification);
        $this->access->authorize($r->user(), $n->recipient, $n->area);
        $n->update(['read_at' => now()]);

        return response()->noContent();
    }
}
