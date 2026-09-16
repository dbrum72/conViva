<?php

namespace App\Services\OrganizationInvitations;

use App\Models\CareAccess;
use App\Models\CareRecipient;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Services\Care\AccessControl;
use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Validation\ValidationException;

class InvitationCareAccess
{
    public function __construct(private AccessControl $access, private CurrentOrganization $context) {}

    public function normalize(Organization $group, User $inviter, string $role, array $grants): array
    {
        if (count($grants) !== 1) {
            throw ValidationException::withMessages(['care_accesses' => 'O convite deve incluir somente o assistido deste grupo.']);
        }
        $previous = getPermissionsTeamId();
        try {
            setPermissionsTeamId($group->id);
            $inviter->unsetRelation('roles')->unsetRelation('permissions');

            return $this->context->run($group, function () use ($group, $inviter, $role, $grants) {
                $result = [];
                foreach ($grants as $grant) {
                    $recipient = CareRecipient::where('organization_id', $group->id)->where('status', 'active')->find($grant['care_recipient_id']);
                    if (! $recipient || ! $this->access->responsible($inviter, $recipient)) {
                        throw ValidationException::withMessages(['care_accesses' => 'O convite só pode incluir assistidos ativos sob sua responsabilidade neste grupo.']);
                    }
                    $areas = $role === 'responsavel' ? AccessControl::AREAS : array_values(array_unique($grant['areas'] ?? []));
                    if (! $areas || array_diff($areas, AccessControl::AREAS)) {
                        throw ValidationException::withMessages(['care_accesses' => 'Selecione as áreas autorizadas de cada assistido.']);
                    }
                    $result[] = ['care_recipient_id' => $recipient->id, 'areas' => $areas, 'can_edit' => $role === 'responsavel' || ($role === 'cuidador' && (bool) ($grant['can_edit'] ?? false))];
                }

                return $result;
            });
        } finally {
            setPermissionsTeamId($previous);
            $inviter->unsetRelation('roles')->unsetRelation('permissions');
        }
    }

    public function apply(OrganizationInvitation $invitation, User $user): void
    {
        if (! $invitation->inviter) {
            throw ValidationException::withMessages(['care_accesses' => 'O responsável pelo convite não está mais disponível.']);
        }
        $grants = $this->normalize($invitation->organization, $invitation->inviter, $invitation->role, $invitation->care_accesses ?? []);
        foreach ($grants as $grant) {
            CareAccess::updateOrCreate(['care_recipient_id' => $grant['care_recipient_id'], 'user_id' => $user->id], ['areas' => $grant['areas'], 'can_edit' => $grant['can_edit'], 'expires_at' => null]);
        }
    }
}
