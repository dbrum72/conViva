<?php

namespace App\Http\Controllers;

use App\Http\Requests\CareFactRequest;
use App\Models\CareRecipient;
use App\Services\Care\CareToday;
use Illuminate\Http\Request;

class CareTodayController extends Controller
{
    public function __construct(private CareToday $today) {}

    public function show(Request $request): array
    {
        return $this->today->overview($request->user());
    }

    public function record(CareFactRequest $request, CareRecipient $recipient)
    {
        $fact = $this->today->record($request->user(), $recipient, $request->validated());

        return response()->json(['id' => $fact->id], $fact->wasRecentlyCreated ? 201 : 200);
    }

    public function acknowledge(Request $request, CareRecipient $recipient)
    {
        $data = $request->validate(['version' => ['required', 'regex:/\A[a-f0-9]{64}\z/']]);
        $this->today->acknowledge($request->user(), $recipient, $data['version']);

        return response()->noContent();
    }
}
