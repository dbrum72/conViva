<?php

namespace App\Services\OrganizationInvitations;

use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class AcceptOrganizationInvitation
{
    public function execute(
        OrganizationInvitation $invitation,
        array $data,
    ): User {
        return DB::transaction(
            function () use (
                $invitation,
                $data,
            ): User {
                $expectedTokenHash = $invitation->token_hash;
                $invitation = OrganizationInvitation::whereKey($invitation->id)->lockForUpdate()->firstOrFail();
                abort_unless($invitation->isAcceptable() && hash_equals($expectedTokenHash, $invitation->token_hash), 410, 'Este convite não está mais disponível.');
                $role =
                    Role::query()
                        ->where(
                            'organization_id',
                            $invitation->organization_id
                        )
                        ->where(
                            'name',
                            $invitation->role
                        )
                        ->where(
                            'guard_name',
                            'api'
                        )
                        ->first();

                if ($role === null) {
                    throw ValidationException::withMessages([
                        'role' => [
                            'A função associada ao convite não está mais disponível.',
                        ],
                    ]);
                }

                $user =
                    User::query()
                        ->where(
                            'email',
                            $invitation->email
                        )
                        ->first();

                if ($user === null) {
                    $user =
                        User::create([
                            'name' => $data['name'],

                            'email' => $invitation->email,

                            'password' => $data['password'],
                        ]);
                }

                $invitation
                    ->organization
                    ->users()
                    ->syncWithoutDetaching([
                        $user->getKey() => [
                            'status' => 'active',

                            'joined_at' => now(),
                        ],
                    ]);

                $previousTeamId =
                    getPermissionsTeamId();

                try {
                    setPermissionsTeamId(
                        $invitation->organization_id
                    );

                    $user->unsetRelation(
                        'roles'
                    );

                    $user->unsetRelation(
                        'permissions'
                    );

                    $user->syncRoles([
                        $role,
                    ]);

                    $user->unsetRelation(
                        'roles'
                    );

                    $user->unsetRelation(
                        'permissions'
                    );
                } finally {
                    setPermissionsTeamId(
                        $previousTeamId
                    );
                }

                app(InvitationCareAccess::class)->apply($invitation, $user);

                $invitation->update([
                    'status' => OrganizationInvitation::STATUS_ACCEPTED,

                    'accepted_at' => now(),
                ]);

                return $user;
            }
        );
    }
}
