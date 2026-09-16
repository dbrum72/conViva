<?php

namespace App\Http\Controllers;

use App\Http\Requests\CareDecisionRequest;
use App\Http\Requests\CareEntryRequest;
use App\Models\CareRecipient;
use App\Services\Care\AccessControl;
use App\Services\Care\CareRecords;
use Illuminate\Http\Request;

class CareEntryController extends Controller
{
    public function __construct(private AccessControl $access, private CareRecords $records) {}

    public function index(Request $r, CareRecipient $recipient)
    {
        $this->access->authorize($r->user(), $recipient);

        return $recipient->entries()->with('shares.user:id,name', 'author:id,name', 'proposals.decisions.user:id,name')->orderByDesc('created_at')->get()->filter(fn ($e) => $this->access->allowed($r->user(), $recipient, $this->access->area($e->kind)))->values();
    }

    public function store(CareEntryRequest $r, CareRecipient $recipient)
    {
        return response()->json($this->records->save($r->user(), $recipient, $r->validated()), 201);
    }

    public function update(CareEntryRequest $r, CareRecipient $recipient, int $entry)
    {
        return $this->records->save($r->user(), $recipient, $r->validated(), $recipient->entries()->findOrFail($entry));
    }

    public function complete(Request $r, CareRecipient $recipient, int $entry)
    {
        return $this->records->complete($r->user(), $recipient, $entry);
    }

    public function destroy(Request $r, CareRecipient $recipient, int $entry)
    {
        return $this->records->cancel($r->user(), $recipient, $entry);
    }

    public function pay(Request $r, CareRecipient $recipient, int $entry, int $share)
    {
        return $this->records->pay($r->user(), $recipient, $entry, $share);
    }

    public function execute(Request $r, CareRecipient $recipient, int $entry)
    {
        $data = $r->validate(['description' => 'nullable|string|max:10000']);

        return response()->json($this->records->execute($r->user(), $recipient, $entry, $data['description'] ?? null), 201);
    }

    public function decide(CareDecisionRequest $r, CareRecipient $recipient, int $entry, int $proposal)
    {
        return $this->records->decide($r->user(), $recipient, $entry, $proposal, $r->validated('decision'), $r->validated('reason'));
    }

    public function withdraw(Request $r,CareRecipient $recipient,int $entry,int $proposal)
    {
        return $this->records->withdraw($r->user(),$recipient,$entry,$proposal);
    }
}
