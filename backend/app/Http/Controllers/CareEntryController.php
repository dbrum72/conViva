<?php

namespace App\Http\Controllers;

use App\Http\Requests\CareDecisionRequest;
use App\Http\Requests\CareEntryRequest;
use App\Http\Requests\ExpensePaymentRequest;
use App\Models\CareRecipient;
use App\Services\Care\AccessControl;
use App\Services\Care\CareRecords;
use App\Services\Care\ExpensePayments;
use Illuminate\Http\Request;

class CareEntryController extends Controller
{
    public function __construct(private AccessControl $access, private CareRecords $records, private ExpensePayments $payments) {}

    public function index(Request $r, CareRecipient $recipient)
    {
        $this->access->authorize($r->user(), $recipient);

        return $recipient->entries()->with('shares.user:id,name', 'author:id,name', 'proposals.decisions.user:id,name')->orderByDesc('created_at')->get()->filter(fn ($e) => $this->access->allowed($r->user(), $recipient, $this->access->area($e->kind)))->map(fn ($e) => $this->payments->decorate($r->user(), $recipient, $this->records->decorate($e)))->values();
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

    public function pay(ExpensePaymentRequest $r, CareRecipient $recipient, int $entry, int $share)
    {
        return $this->payments->pay($r->user(), $recipient, $entry, $share, $r->file('receipt'));
    }

    public function receipt(Request $r, CareRecipient $recipient, int $entry, int $share)
    {
        return $this->payments->download($r->user(), $recipient, $entry, $share);
    }

    public function execute(Request $r, CareRecipient $recipient, int $entry)
    {
        $data = $r->validate(['description' => 'nullable|string|max:10000', 'occurred_at' => 'sometimes|required|date|before_or_equal:now']);

        return response()->json($this->records->execute($r->user(), $recipient, $entry, $data['description'] ?? null, $data['occurred_at'] ?? null), 201);
    }

    public function decide(CareDecisionRequest $r, CareRecipient $recipient, int $entry, int $proposal)
    {
        return $this->records->decide($r->user(), $recipient, $entry, $proposal, $r->validated('decision'), $r->validated('reason'));
    }

    public function withdraw(Request $r, CareRecipient $recipient, int $entry, int $proposal)
    {
        return $this->records->withdraw($r->user(), $recipient, $entry, $proposal);
    }
}
