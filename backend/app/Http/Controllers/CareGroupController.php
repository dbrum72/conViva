<?php

namespace App\Http\Controllers;

use App\Services\Organizations\CreateCareGroup;
use App\Services\Organizations\ListCareGroups;
use Illuminate\Http\Request;

class CareGroupController extends Controller
{
    public function index(Request $request, ListCareGroups $groups)
    {
        return response()->json($groups->execute($request->user()));
    }

    public function store(Request $request, CreateCareGroup $groups)
    {
        $data = $request->validate(['name' => 'required|string|max:150']);

        return response()->json($groups->execute($request->user(), $data['name']), 201);
    }
}
