<?php

namespace App\Modules\Care\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Care\Services\CareAgenda;
use Illuminate\Http\Request;

class CareAgendaController extends Controller
{
    public function index(Request $r)
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
}
