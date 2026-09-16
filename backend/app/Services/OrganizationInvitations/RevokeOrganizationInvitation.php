<?php

namespace App\Services\OrganizationInvitations;

use App\Models\OrganizationInvitation;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class RevokeOrganizationInvitation
{
    public function execute(
        OrganizationInvitation $invitation,
    ): OrganizationInvitation {
        return DB::transaction(
            function () use (
                $invitation,
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
                        'Somente convites pendentes e válidos podem ser revogados.'
                    );
                }

                $lockedInvitation->update([
                    'status' => OrganizationInvitation::STATUS_REVOKED,

                    'revoked_at' => now(),
                ]);

                return $lockedInvitation
                    ->load([
                        'organization',
                        'inviter',
                    ]);
            }
        );
    }
}
