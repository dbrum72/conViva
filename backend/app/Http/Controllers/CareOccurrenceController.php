<?php

namespace App\Http\Controllers;

use App\Http\Requests\CareOccurrenceExecutionRequest;
use App\Models\CareOccurrence;
use App\Services\Care\CareAgenda;
use App\Services\Care\CareRecords;
use Illuminate\Http\Request;

class CareOccurrenceController extends Controller
{
    public function execute(CareOccurrenceExecutionRequest $request, int $occurrence, CareAgenda $agenda)
    {
        return response()->json($agenda->execute($request->user(), $occurrence, $request->validated()), 201);
    }

    public function correct(CareOccurrenceExecutionRequest $request, int $occurrence, int $execution, CareAgenda $agenda)
    {
        return response()->json($agenda->execute($request->user(), $occurrence, $request->validated(), $execution), 201);
    }

    public function cancel(Request $request, int $occurrence, CareRecords $records)
    {
        $data = $request->validate(['scope' => 'required|in:one,future']);
        $o = CareOccurrence::with('schedule.entry.recipient')->findOrFail($occurrence);

        return $records->cancelOccurrence($request->user(), $o->schedule->entry->recipient, $occurrence, $data['scope']);
    }
}
