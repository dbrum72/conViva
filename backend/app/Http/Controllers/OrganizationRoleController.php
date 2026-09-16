<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrganizationRolePermissionsUpdateRequest;
use App\Support\Organizations\OrganizationRoleDefinitions;
use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class OrganizationRoleController extends Controller
{
    public function index(
        CurrentOrganization $currentOrganization,
    ): JsonResponse {
        $organization =
            $currentOrganization->get();

        $roles =
            Role::query()
                ->where(
                    'organization_id',
                    $organization->getKey(),
                )
                ->where(
                    'guard_name',
                    'api',
                )
                ->withCount('permissions')
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'description',
                ])
                ->map(
                    fn (Role $role): array => [
                        'id' => $role->getKey(),

                        'name' => $role->name,

                        'description' => $role->description,

                        'permissions_count' => $role->permissions_count,
                    ]
                )
                ->values();

        return response()->json(
            $roles
        );
    }

    public function show(
        Role $role,
        CurrentOrganization $currentOrganization,
    ): JsonResponse {
        $this->ensureRoleBelongsToOrganization(
            $role,
            $currentOrganization,
        );

        return response()->json(
            $this->rolePayload($role)
        );
    }

    public function updatePermissions(
        OrganizationRolePermissionsUpdateRequest $request,
        Role $role,
        CurrentOrganization $currentOrganization,
    ): JsonResponse {
        $this->ensureRoleBelongsToOrganization(
            $role,
            $currentOrganization,
        );

        $requestedPermissions = collect(
            $request->validated('permissions')
        )->sort()->values();

        $currentPermissions = $role->permissions()
            ->pluck('name')
            ->sort()
            ->values();

        if (
            $role->name === OrganizationRoleDefinitions::SUPER_ADMIN
            && $requestedPermissions->all() !== $currentPermissions->all()
        ) {
            throw ValidationException::withMessages([
                'permissions' => 'As permissões do Super Admin são protegidas.',
            ]);
        }

        if (
            $currentPermissions->contains('roles.update')
            && ! $requestedPermissions->contains('roles.update')
            && ! $this->anotherRoleCanManageRoles(
                $role,
                $currentOrganization,
            )
        ) {
            throw ValidationException::withMessages([
                'permissions' => 'A organização deve manter ao menos uma função capaz de configurar permissões.',
            ]);
        }

        $permissions =
            Permission::query()
                ->where('guard_name', 'api')
                ->whereIn(
                    'name',
                    $requestedPermissions,
                )
                ->get();

        $role->syncPermissions($permissions);

        return response()->json(
            $this->rolePayload($role->refresh())
        );
    }

    private function anotherRoleCanManageRoles(
        Role $role,
        CurrentOrganization $currentOrganization,
    ): bool {
        return Role::query()
            ->where('organization_id', $currentOrganization->get()->getKey())
            ->where('guard_name', 'api')
            ->whereKeyNot($role->getKey())
            ->whereHas(
                'permissions',
                fn ($query) => $query->where('name', 'roles.update')
            )
            ->exists();
    }

    private function ensureRoleBelongsToOrganization(
        Role $role,
        CurrentOrganization $currentOrganization,
    ): void {
        abort_unless(
            (int) $role->organization_id ===
                (int) $currentOrganization->get()->getKey()
            && $role->guard_name === 'api',
            404,
        );
    }

    private function rolePayload(Role $role): array
    {
        $role->load('permissions');

        return [
            'id' => $role->getKey(),
            'name' => $role->name,
            'description' => $role->description,
            'permissions' => $role->permissions
                ->pluck('name')
                ->sort()
                ->values(),
            'available_permissions' => collect(
                OrganizationRoleDefinitions::permissions()
            )->sort()->values(),
        ];
    }
}
