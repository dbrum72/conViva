<?php

namespace App\Services\Registration;

use App\Models\Organization;
use App\Models\User;
use App\Services\Organizations\ProvisionOrganizationRoles;
use App\Support\Organizations\OrganizationRoleDefinitions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class RegisterOrganization
{
    public function __construct(
        private readonly ProvisionOrganizationRoles $provisioner,
    ) {}

    public function execute(
        array $data,
    ): array {
        return DB::transaction(
            function () use ($data): array {
                $user =
                    User::query()
                        ->create([
                            'name' => $data['name'],

                            'email' => $data['email'],

                            'password' => $data['password'],
                        ]);

                $organization =
                    Organization::query()
                        ->create([
                            'name' => $data['organization_name'],

                            'slug' => $this->generateUniqueSlug(
                                $data['organization_name']
                            ),

                            'status' => 'active',
                        ]);

                $organization
                    ->users()
                    ->attach(
                        $user->getKey(),
                        [
                            'status' => 'active',

                            'joined_at' => now(),
                        ],
                    );

                $this->provisioner->execute(
                    $organization
                );

                $role =
                    Role::query()
                        ->where(
                            'organization_id',
                            $organization->getKey(),
                        )
                        ->where(
                            'name',
                            OrganizationRoleDefinitions::ADMINISTRATOR,
                        )
                        ->where(
                            'guard_name',
                            'api',
                        )
                        ->firstOrFail();

                $previousTeamId =
                    getPermissionsTeamId();

                try {
                    setPermissionsTeamId(
                        $organization->getKey()
                    );

                    $this->clearPermissionRelations(
                        $user
                    );

                    $user->syncRoles([
                        $role,
                    ]);

                    $this->clearPermissionRelations(
                        $user
                    );
                } finally {
                    setPermissionsTeamId(
                        $previousTeamId
                    );

                    $this->clearPermissionRelations(
                        $user
                    );
                }

                return [
                    'user' => $user,

                    'organization' => $organization,
                ];
            }
        );
    }

    private function generateUniqueSlug(
        string $name,
    ): string {
        $base =
            Str::slug(
                $name
            );

        if ($base === '') {
            $base =
                'organizacao';
        }

        $slug =
            $base;

        $suffix =
            2;

        while (
            Organization::query()
                ->where(
                    'slug',
                    $slug,
                )
                ->exists()
        ) {
            $slug =
                $base
                .'-'
                .$suffix;

            $suffix++;
        }

        return $slug;
    }

    private function clearPermissionRelations(
        User $user,
    ): void {
        $user->unsetRelation(
            'roles'
        );

        $user->unsetRelation(
            'permissions'
        );
    }
}
