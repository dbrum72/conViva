<?php

namespace App\Http\Controllers;

use App\Http\Requests\CareDecisionRequest;
use App\Http\Requests\ProfileRevisionRequest;
use App\Models\CareRecipient;
use App\Services\Care\ProfileRevisions;
use Illuminate\Http\Request;

class ProfileRevisionController extends Controller
{
    public function __construct(private ProfileRevisions $revisions) {}

    public function store(ProfileRevisionRequest $request, CareRecipient $recipient)
    {
        return response()->json($this->revisions->propose($request->user(), $recipient, $request->safe()->except(['operation', 'version']), $request->validated('operation'), $request->integer('version')), 201);
    }

    public function decide(CareDecisionRequest $request, CareRecipient $recipient, int $proposal)
    {
        return $this->revisions->decide($request->user(), $recipient, $proposal, $request->validated('decision'), $request->validated('reason'));
    }

    public function withdraw(Request $request, CareRecipient $recipient, int $proposal)
    {
        return $this->revisions->withdraw($request->user(), $recipient, $proposal);
    }
}
