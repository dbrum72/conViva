<?php

namespace App\Modules\Expenses\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CareRecipient;
use App\Modules\Expenses\Http\Requests\ExpensePaymentRequest;
use App\Modules\Expenses\Services\ExpenseQuery;
use App\Modules\Expenses\Services\ExpensePayments;
use Illuminate\Http\Request;

class ExpensesController extends Controller
{
    public function __construct(private ExpensePayments $payments) {}

    public function index(Request $r, ExpenseQuery $expenses)
    {
        $filters = $r->validate([
            'period' => 'sometimes|in:all,today,week,month,last_month,last_7,last_30,year,custom',
            'from' => 'required_if:period,custom|nullable|date_format:Y-m-d',
            'to' => 'required_if:period,custom|nullable|date_format:Y-m-d|after_or_equal:from',
            'category' => 'sometimes|nullable|string|max:100',
            'participant' => 'sometimes|integer|min:1',
            'status' => 'sometimes|in:pending,completed,awaiting_approval,rejected,withdrawn,cancelled',
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:50',
        ]);

        return $expenses->listing($r->user(), $filters);
    }

    public function pay(ExpensePaymentRequest $r, CareRecipient $recipient, int $entry, int $share)
    {
        return $this->payments->pay($r->user(), $recipient, $entry, $share, $r->file('receipt'));
    }

    public function receipt(Request $r, CareRecipient $recipient, int $entry, int $share)
    {
        return $this->payments->download($r->user(), $recipient, $entry, $share);
    }
}
