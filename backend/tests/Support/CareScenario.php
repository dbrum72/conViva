<?php

namespace Tests\Support;

use App\Models\CareAccess;
use App\Models\CareRecipient;
use App\Models\Organization;
use App\Models\User;
use App\Services\Organizations\ProvisionOrganizationRoles;
use App\Support\Tenancy\CurrentOrganization;
use Spatie\Permission\Models\Role;

/** Synthetic fixtures for isolated tests; never a production seeder. */
final class CareScenario
{
    public static function create(string $kind): array
    {
        app(CurrentOrganization::class)->clear();
        $group = Organization::create(['name' => 'Cenário '.$kind, 'slug' => 'scenario-'.$kind, 'status' => 'active']);
        app(ProvisionOrganizationRoles::class)->execute($group);
        $owner = self::member($group, 'responsavel');
        $recipient = CareRecipient::create([
            'organization_id' => $group->id,
            'created_by' => $owner->id,
            'name' => 'Assistido sintético '.$kind,
            'kind' => $kind,
            'species' => $kind === 'pet' ? 'Cachorro' : null,
        ]);

        return compact('group', 'owner', 'recipient');
    }

    public static function member(Organization $group, string $role, ?User $user = null): User
    {
        $user ??= User::factory()->create();
        $group->users()->attach($user->id, ['status' => 'active', 'joined_at' => now()]);
        $previous = getPermissionsTeamId();
        try {
            setPermissionsTeamId($group->id);
            $user->unsetRelation('roles')->unsetRelation('permissions');
            $user->assignRole(Role::where('organization_id', $group->id)->where('name', $role)->firstOrFail());
        } finally {
            setPermissionsTeamId($previous);
            $user->unsetRelation('roles')->unsetRelation('permissions');
        }

        return $user;
    }

    public static function grant(CareRecipient $recipient, User $user, array $areas, bool $edit = true, ?string $expiresAt = null): CareAccess
    {
        return CareAccess::create([
            'care_recipient_id' => $recipient->id,
            'user_id' => $user->id,
            'areas' => $areas,
            'can_edit' => $edit,
            'expires_at' => $expiresAt,
        ]);
    }
}
