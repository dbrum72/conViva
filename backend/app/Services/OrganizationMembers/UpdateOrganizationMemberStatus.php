<?php

namespace App\Services\OrganizationMembers;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateOrganizationMemberStatus
{
    public function handle(Organization $organization, User $user, string $status, User $actor): void
    {
        DB::transaction(function () use ($organization, $user, $status, $actor) {
            $organization->newQuery()->whereKey($organization->id)->lockForUpdate()->firstOrFail();
            if (! $organization->users()->whereKey($user->id)->exists()) {
                throw ValidationException::withMessages(['member' => 'O usuário não pertence ao grupo atual.']);
            }
            $isResponsible = DB::table('model_has_roles')->join('roles', 'roles.id', '=', 'model_has_roles.role_id')->where('model_has_roles.organization_id', $organization->id)->where('model_has_roles.model_type', User::class)->where('model_has_roles.model_id', $user->id)->where('roles.name', 'responsavel')->exists();
            if ($isResponsible && (int) $user->id !== (int) $actor->id) {
                throw ValidationException::withMessages(['member' => 'Somente o próprio responsável pode alterar a situação do seu vínculo.']);
            }
            $organization->users()->updateExistingPivot($user->id, ['status' => $status]);
        });
    }
}
