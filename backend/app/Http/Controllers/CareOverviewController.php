<?php

namespace App\Http\Controllers;

use App\Services\Care\CareAgenda;
use App\Services\Care\CareFinance;
use Illuminate\Http\Request;

class CareOverviewController extends Controller
{
    public function agenda(Request $r)
    {
        $filters = $r->validate([
            'from' => 'sometimes|date_format:Y-m-d', 'to' => 'sometimes|date_format:Y-m-d|after_or_equal:from',
            'kind' => 'nullable|in:event,task,feeding,journal,medication,vaccine,expense',
            'executor' => 'nullable|integer|min:1', 'status' => 'sometimes|in:scheduled,executed,cancelled,superseded,not_administered,all',
            'health_entry' => 'sometimes|integer|min:1',
            'page' => 'sometimes|integer|min:1', 'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        return app(CareAgenda::class)->listing($r->user(), $filters);
    }

    public function finance(Request $r)
    {
        $filters = $r->validate([
            'period' => 'sometimes|in:all,today,week,month,last_month,last_7,last_30,year,custom',
            'from' => 'required_if:period,custom|nullable|date_format:Y-m-d',
            'to' => 'required_if:period,custom|nullable|date_format:Y-m-d|after_or_equal:from',
        ]);

        return app(CareFinance::class)->listing($r->user(), $filters);
    }
}
