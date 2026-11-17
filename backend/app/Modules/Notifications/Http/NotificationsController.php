<?php

namespace App\Modules\Notifications\Http;

use App\Http\Controllers\Controller;
use App\Models\CareNotification;
use App\Modules\Notifications\CareNotifications;
use App\Modules\Notifications\ReminderPreferences;
use App\Services\Care\AccessControl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationsController extends Controller
{
    public function __construct(private AccessControl $access) {}

    public function notifications(Request $r)
    {
        $r->validate(['page' => 'sometimes|integer|min:1', 'per_page' => 'sometimes|integer|min:1|max:100']);
        $query = app(CareNotifications::class)->visible($r->user())->with('recipient')->latest()->orderByDesc('id');
        $page = $r->has('page') ? $query->paginate($r->integer('per_page', 25)) : null;
        $items = $page?->getCollection() ?? $query->limit(100)->get();
        $dates = DB::table('notification_reminder_deliveries')->whereIn('care_notification_id', $items->modelKeys())->pluck('agenda_date', 'care_notification_id');
        $items->each(function ($n) use ($dates) {
            $target = DB::table('care_notification_targets')->where('care_notification_id', $n->id)->first();
            $n->destination = $target ? ['type' => $target->care_profile_proposal_id ? 'profile' : 'entry', 'proposal' => $target->care_profile_proposal_id ?? $target->care_proposal_id] : null;
            if (isset($dates[$n->id])) {
                $n->destination = ['type' => 'agenda', 'date' => $dates[$n->id]];
            }

            return $n;
        });

        return $page ?? $items;
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

    public function preferences(Request $request, ReminderPreferences $preferences)
    {
        return $preferences->get($request->user()->id);
    }

    public function savePreferences(Request $request, ReminderPreferences $preferences)
    {
        return $preferences->save($request->user()->id, $request->validate([
            'internal' => 'required|boolean',
            'email' => 'required|boolean',
            'third_party' => 'required|boolean',
            'summary' => 'required|boolean',
            'advance' => 'required|integer|between:5,1440',
            'quiet_start' => 'nullable|required_with:quiet_end|date_format:H:i|different:quiet_end',
            'quiet_end' => 'nullable|required_with:quiet_start|date_format:H:i',
        ]));
    }
}
