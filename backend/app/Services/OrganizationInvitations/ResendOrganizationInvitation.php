<?php

namespace App\Services\OrganizationInvitations;

use App\Mail\OrganizationInvitationMail;
use App\Models\OrganizationInvitation;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Throwable;

class ResendOrganizationInvitation
{
    public function execute(
        OrganizationInvitation $invitation,
    ): OrganizationInvitation {
        $previousTokenHash =
            $invitation->token_hash;

        $previousExpiresAt =
            $invitation
                ->expires_at
                ->copy();

        $token =
            Str::random(64);

        $newTokenHash =
            OrganizationInvitation::hashToken(
                $token
            );

        $invitation =
            DB::transaction(
                function () use (
                    $invitation,
                    $newTokenHash,
                ): OrganizationInvitation {
                    $lockedInvitation =
                        OrganizationInvitation::query()
                            ->whereKey(
                                $invitation->getKey()
                            )
                            ->lockForUpdate()
                            ->firstOrFail();

                    if (
                        ! $lockedInvitation->isAcceptable()
                    ) {
                        throw new ConflictHttpException(
                            'Somente convites pendentes e válidos podem ser reenviados.'
                        );
                    }

                    $lockedInvitation->update([
                        'token_hash' => $newTokenHash,

                        'expires_at' => now()->addDays(
                            OrganizationInvitation::DEFAULT_EXPIRATION_DAYS
                        ),

                        'accepted_at' => null,

                        'revoked_at' => null,
                    ]);

                    return $lockedInvitation
                        ->load([
                            'organization',
                            'inviter',
                        ]);
                }
            );

        try {
            Mail::to(
                $invitation->email
            )->send(
                new OrganizationInvitationMail(
                    invitation: $invitation,

                    token: $token,
                )
            );
        } catch (Throwable $exception) {
            $this->restorePreviousToken(
                invitationId: $invitation->getKey(),
                newTokenHash: $newTokenHash,
                previousTokenHash: $previousTokenHash,
                previousExpiresAt: $previousExpiresAt,
            );

            throw $exception;
        }

        return $invitation;
    }

    private function restorePreviousToken(
        int $invitationId,
        string $newTokenHash,
        string $previousTokenHash,
        CarbonInterface $previousExpiresAt,
    ): void {
        DB::transaction(
            function () use (
                $invitationId,
                $newTokenHash,
                $previousTokenHash,
                $previousExpiresAt,
            ): void {
                $currentInvitation =
                    OrganizationInvitation::query()
                        ->whereKey(
                            $invitationId
                        )
                        ->lockForUpdate()
                        ->first();

                if (
                    $currentInvitation === null
                    || $currentInvitation->token_hash !== $newTokenHash
                    || ! $currentInvitation->isPending()
                ) {
                    return;
                }

                $currentInvitation->update([
                    'token_hash' => $previousTokenHash,

                    'expires_at' => $previousExpiresAt,
                ]);
            }
        );
    }
}
