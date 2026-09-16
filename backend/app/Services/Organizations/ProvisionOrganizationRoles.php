<?php

namespace App\Services\Organizations;

use App\Models\Organization;
use App\Support\Organizations\OrganizationRoleDefinitions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ProvisionOrganizationRoles
{
    public function execute(
        Organization $organization,
    ): void {
        DB::transaction(
            function () use (
                $organization,
            ): void {
                $this->ensurePermissionsExist();

                foreach (
                    OrganizationRoleDefinitions::definitions() as $name => $definition
                ) {
                    $role =
                        Role::query()
                            ->updateOrCreate(
                                [
                                    'organization_id' => $organization->getKey(),

                                    'name' => $name,

                                    'guard_name' => 'api',
                                ],
                                [
                                    'description' => $definition['description'],
                                ],
                            );

                    $role->syncPermissions(
                        $definition['permissions']
                    );
                }
            }
        );

        app(
            PermissionRegistrar::class
        )->forgetCachedPermissions();
    }

    private function ensurePermissionsExist(): void
    {
        $requiredPermissions =
            OrganizationRoleDefinitions::permissions();

        $existingPermissions =
            Permission::query()
                ->where(
                    'guard_name',
                    'api',
                )
                ->whereIn(
                    'name',
                    $requiredPermissions,
                )
                ->pluck('name')
                ->all();

        $missingPermissions =
            array_values(
                array_diff(
                    $requiredPermissions,
                    $existingPermissions,
                )
            );

        if (
            $missingPermissions === []
        ) {
            return;
        }

        throw ValidationException::withMessages([
            'permissions' => [
                sprintf(
                    'As seguintes permissões ainda não estão cadastradas: %s.',
                    implode(
                        ', ',
                        $missingPermissions,
                    ),
                ),
            ],
        ]);
    }
}
