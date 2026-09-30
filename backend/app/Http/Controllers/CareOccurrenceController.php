<?php

namespace App\Http\Controllers;

use App\Models\CareOccurrence;
use App\Services\Care\CareAgenda;
use App\Services\Care\CareRecords;
use Illuminate\Http\Request;

class CareOccurrenceController extends Controller
{
    public function execute(Request $request, int $occurrence, CareAgenda $agenda)
    {
        $data = $request->validate(['description' => 'nullable|string|max:10000', 'occurred_at' => 'required|date|before_or_equal:now']);

        return response()->json($agenda->execute($request->user(), $occurrence, $data), 201);
    }

    public function cancel(Request $request, int $occurrence, CareRecords $records)
    {
        $data = $request->validate(['scope' => 'required|in:one,future']);
        $o = CareOccurrence::with('schedule.entry.recipient')->findOrFail($occurrence);

        return $records->cancelOccurrence($request->user(), $o->schedule->entry->recipient, $occurrence, $data['scope']);
    }
}
