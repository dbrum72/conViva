<?php

namespace App\Services\OrganizationMembers;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UpdateOrganizationMemberRole
{
    public function handle(Organization $organization, User $user, string $roleName, User $actor): void
    {
        DB::transaction(function () use ($organization, $user, $roleName, $actor) {
            $organization->newQuery()->whereKey($organization->id)->lockForUpdate()->firstOrFail();
            if (! $organization->users()->whereKey($user->id)->exists()) {
                throw ValidationException::withMessages(['member' => 'O usuário não pertence ao grupo atual.']);
            }
            $isResponsible = DB::table('model_has_roles')->join('roles', 'roles.id', '=', 'model_has_roles.role_id')->where('model_has_roles.organization_id', $organization->id)->where('model_has_roles.model_type', User::class)->where('model_has_roles.model_id', $user->id)->where('roles.name', 'responsavel')->exists();
            if ($isResponsible && (int) $user->id !== (int) $actor->id) {
                throw ValidationException::withMessages(['member' => 'Somente o próprio responsável pode alterar sua função.']);
            }
            $role = Role::where('organization_id', $organization->id)->where('guard_name', 'api')->where('name', $roleName)->firstOrFail();
            $previous = getPermissionsTeamId();
            try {
                setPermissionsTeamId($organization->id);
                $user->unsetRelation('roles')->unsetRelation('permissions');
                $user->syncRoles([$role]);
                $user->unsetRelation('roles')->unsetRelation('permissions');
            } finally {
                setPermissionsTeamId($previous);
            }
        });
    }
}
