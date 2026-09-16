<?php

namespace App\Services\Organizations;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class CreateCareGroup
{
    public function execute(User $user, string $name): Organization
    {
        return DB::transaction(function () use ($user, $name) {
            $group = Organization::create(['name' => $name, 'slug' => (Str::slug($name) ?: 'grupo').'-'.Str::lower(Str::random(8)), 'status' => 'active']);
            $group->users()->attach($user->id, ['status' => 'active', 'joined_at' => now()]);
            app(ProvisionOrganizationRoles::class)->execute($group);
            $previous = getPermissionsTeamId();
            try {
                setPermissionsTeamId($group->id);
                $user->unsetRelation('roles')->unsetRelation('permissions');
                $user->assignRole(Role::where('organization_id', $group->id)->where('name', 'responsavel')->firstOrFail());
            } finally {
                setPermissionsTeamId($previous);
                $user->unsetRelation('roles')->unsetRelation('permissions');
            }

            return $group;
        });
    }
}
