<?php

namespace App\Http\Controllers;

use App\Services\Care\DecisionCenter;
use Illuminate\Http\Request;

class DecisionCenterController extends Controller
{
    public function __construct(private DecisionCenter $center) {}

    public function index(Request $request)
    {
        $filters = $request->validate([
            'scope' => 'sometimes|in:mine,sent,all',
            'status' => 'nullable|in:pending,accepted,rejected,withdrawn',
            'type' => 'nullable|in:entry,profile',
            'page' => 'sometimes|integer|min:1', 'per_page' => 'sometimes|integer|min:1|max:50',
        ]);

        return $this->center->listing($request->user(), $filters);
    }

    public function show(Request $request, string $type, int $proposal)
    {
        abort_unless(in_array($type, ['entry', 'profile']), 404);

        return $this->center->show($request->user(), $type, $proposal);
    }
}
