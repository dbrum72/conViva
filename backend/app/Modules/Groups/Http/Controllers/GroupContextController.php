<?php

namespace App\Modules\Groups\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Care\Services\AccessControl;
use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class GroupContextController extends Controller
{
    public function context(
        CurrentOrganization $currentOrganization,
    ): JsonResponse {
        $user =
            Auth::guard('api')->user();

        $organization =
            $currentOrganization->get();

        $recipient = $organization->recipients()->where('status', 'active')->first();

        return response()->json([
            'user' => $user->toArray(),

            'organization' => [
                'timezone' => $organization->timezone,
                'has_recipient' => $organization->recipients()->exists(),
                'can_manage_avatar' => $recipient && app(AccessControl::class)->responsible($user, $recipient),
                'id' => $organization->id,

                'name' => $organization->name,

                'slug' => $organization->slug,
            ],

            'roles' => $user
                ->getRoleNames()
                ->values(),

            'permissions' => $user
                ->getAllPermissions()
                ->pluck('name')
                ->values(),
        ]);
    }
}
