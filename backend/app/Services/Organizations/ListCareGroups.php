<?php

namespace App\Services\Organizations;

use App\Models\User;
use App\Services\Care\AccessControl;
use App\Support\Tenancy\CurrentOrganization;

class ListCareGroups
{
    public function __construct(private CurrentOrganization $context, private AccessControl $access) {}

    public function execute(User $user): array
    {
        $previousTeam = getPermissionsTeamId();
        try {
            return $user->organizations()->where('organizations.status', 'active')->wherePivot('status', 'active')->orderBy('organizations.name')->get()->map(function ($group) use ($user) {
                return $this->context->run($group, function () use ($group, $user) {
                    setPermissionsTeamId($group->id);
                    $user->unsetRelation('roles')->unsetRelation('permissions');
                    $recipient = $this->access->visible($user)->first();

                    return [
                        'id' => $group->id,
                        'name' => $group->name,
                        'slug' => $group->slug,
                        'roles' => $user->getRoleNames()->values()->all(),
                        'recipient' => $recipient ? [
                            'id' => $recipient->id,
                            'name' => $recipient->name,
                            'kind' => $recipient->kind,
                            'status' => $recipient->status,
                            'capabilities' => $this->access->capabilities($user, $recipient),
                        ] : null,
                    ];
                });
            })->all();
        } finally {
            setPermissionsTeamId($previousTeam);
            $user->unsetRelation('roles')->unsetRelation('permissions');
        }
    }
}
