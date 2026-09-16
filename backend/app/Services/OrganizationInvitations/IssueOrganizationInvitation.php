<?php

namespace App\Services\OrganizationInvitations;

use App\Mail\OrganizationInvitationMail;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class IssueOrganizationInvitation
{
    public function execute(
        Organization $organization,
        User $inviter,
        string $email,
        string $role,
        array $careAccesses,
    ): OrganizationInvitation {
        $this->ensureEmailCanBeInvited(
            $organization,
            $email,
        );

        $careAccesses = app(InvitationCareAccess::class)->normalize($organization, $inviter, $role, $careAccesses);

        $token =
            Str::random(64);

        $invitation =
            DB::transaction(
                function () use (
                    $organization,
                    $inviter,
                    $email,
                    $role,
                    $token,
                    $careAccesses,
                ): OrganizationInvitation {
                    return $organization
                        ->invitations()
                        ->create([
                            'invited_by' => $inviter->getKey(),

                            'email' => $email,

                            'role' => $role,
                            'care_accesses' => $careAccesses,

                            'token_hash' => OrganizationInvitation::hashToken(
                                $token
                            ),

                            'status' => OrganizationInvitation::STATUS_PENDING,

                            'expires_at' => now()->addDays(
                                OrganizationInvitation::DEFAULT_EXPIRATION_DAYS
                            ),

                            'accepted_at' => null,

                            'revoked_at' => null,
                        ]);
                }
            );

        $invitation->load([
            'organization',
            'inviter',
        ]);

        try {
            Mail::to(
                $invitation->email
            )->send(
                new OrganizationInvitationMail(
                    invitation: $invitation,

                    token: $token,
                )
            );
        } catch (\Throwable $exception) {
            $invitation->delete();

            throw $exception;
        }

        return $invitation;
    }

    private function ensureEmailCanBeInvited(
        Organization $organization,
        string $email,
    ): void {
        $existingUser =
            User::query()
                ->where(
                    'email',
                    $email
                )
                ->first();

        if (
            $existingUser !== null
            && $organization
                ->users()
                ->whereKey(
                    $existingUser->getKey()
                )
                ->wherePivot(
                    'status',
                    'active'
                )
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'email' => [
                    'O usuário já possui vínculo ativo com esta organização.',
                ],
            ]);
        }

        $pendingInvitationExists =
            $organization
                ->invitations()
                ->where(
                    'email',
                    $email
                )
                ->where(
                    'status',
                    OrganizationInvitation::STATUS_PENDING
                )
                ->where(
                    'expires_at',
                    '>',
                    now()
                )
                ->exists();

        if ($pendingInvitationExists) {
            throw ValidationException::withMessages([
                'email' => [
                    'Já existe um convite pendente para este e-mail.',
                ],
            ]);
        }
    }
}
