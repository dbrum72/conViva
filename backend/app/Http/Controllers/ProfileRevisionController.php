<?php

namespace App\Http\Controllers;

use App\Http\Requests\CareDecisionRequest;
use App\Http\Requests\ProfileRevisionRequest;
use App\Models\CareRecipient;
use App\Services\Care\DecisionCenter;
use App\Services\Care\ProfileRevisions;
use Illuminate\Http\Request;

class ProfileRevisionController extends Controller
{
    public function __construct(private ProfileRevisions $revisions) {}

    public function store(ProfileRevisionRequest $request, CareRecipient $recipient)
    {
        $proposal = $this->revisions->propose($request->user(), $recipient, $request->safe()->except(['operation', 'version']), $request->validated('operation'), $request->integer('version'));

        return response()->json(app(DecisionCenter::class)->show($request->user(), 'profile', $proposal->id), 201);
    }

    public function decide(CareDecisionRequest $request, CareRecipient $recipient, int $proposal)
    {
        $result = $this->revisions->decide($request->user(), $recipient, $proposal, $request->validated('decision'), $request->validated('reason'));

        return app(DecisionCenter::class)->show($request->user(), 'profile', $result->id);
    }

    public function withdraw(Request $request, CareRecipient $recipient, int $proposal)
    {
        $result = $this->revisions->withdraw($request->user(), $recipient, $proposal);

        return app(DecisionCenter::class)->show($request->user(), 'profile', $result->id);
    }
}
