<?php

namespace App\Modules\Care\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CareRecipient;
use App\Modules\Care\Http\Requests\CareUnavailabilityRequest;
use App\Modules\Care\Services\CareAvailability;
use Illuminate\Http\Request;

class CareUnavailabilityController extends Controller
{
    public function index(Request $request, CareRecipient $recipient, CareAvailability $availability)
    {
        return $availability->listing($request->user(), $recipient);
    }

    public function sharedIndex(Request $request, CareRecipient $recipient, CareAvailability $availability)
    {
        return $availability->sharedListing($request->user(), $recipient);
    }

    public function store(CareUnavailabilityRequest $request, CareRecipient $recipient, CareAvailability $availability)
    {
        return response()->json($availability->save($request->user(), $recipient, $request->validated()), 201);
    }

    public function cancel(Request $request, CareRecipient $recipient, int $period, CareAvailability $availability)
    {
        return $availability->cancel($request->user(), $recipient, $period);
    }
}
