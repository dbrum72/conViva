<?php

namespace App\Modules\Groups\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Groups\Services\Organizations\CreateCareGroup;
use App\Modules\Groups\Services\Organizations\ListCareGroups;
use Illuminate\Http\Request;

class CareGroupController extends Controller
{
    public function index(Request $request, ListCareGroups $groups)
    {
        return response()->json($groups->execute($request->user()));
    }

    public function store(Request $request, CreateCareGroup $groups)
    {
        $data = $request->validate(['name' => 'required|string|max:150', 'timezone' => 'sometimes|required|timezone:all']);

        return response()->json($groups->execute($request->user(), $data['name'], $data['timezone'] ?? 'America/Sao_Paulo'), 201);
    }
}
