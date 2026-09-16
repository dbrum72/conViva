<?php

namespace App\Http\Controllers;

use App\Models\CareEntry;
use App\Models\CareNotification;
use App\Services\Care\AccessControl;
use Illuminate\Http\Request;

class CareOverviewController extends Controller
{
    public function __construct(private AccessControl $access) {}

    public function agenda(Request $r)
    {
        $ids = $this->access->visible($r->user())->pluck('id');

        return CareEntry::whereIn('care_recipient_id', $ids)->whereIn('status', ['pending', 'completed'])->whereNotNull('due_at')->with('recipient:id,name,organization_id')->orderBy('due_at')->get()->filter(fn ($e) => $this->access->allowed($r->user(), $e->recipient, $this->access->area($e->kind)))->values();
    }

    public function notifications(Request $r)
    {
        return CareNotification::where('user_id', $r->user()->id)->with('recipient')->latest()->limit(100)->get()->filter(fn ($n) => $n->recipient && $this->access->allowed($r->user(), $n->recipient, $n->area))->values();
    }

    public function read(Request $r, int $notification)
    {
        $n = CareNotification::where('user_id', $r->user()->id)->findOrFail($notification);
        $this->access->authorize($r->user(), $n->recipient, $n->area);
        $n->update(['read_at' => now()]);

        return response()->noContent();
    }
}
