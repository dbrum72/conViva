<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrganizationInvitationAcceptRequest;
use App\Http\Requests\OrganizationInvitationStoreRequest;
use App\Models\CareRecipient;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Services\OrganizationInvitations\AcceptOrganizationInvitation;
use App\Services\OrganizationInvitations\IssueOrganizationInvitation;
use App\Services\OrganizationInvitations\ResendOrganizationInvitation;
use App\Services\OrganizationInvitations\RevokeOrganizationInvitation;
use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class OrganizationInvitationController extends Controller
{
    public function index(
        CurrentOrganization $currentOrganization,
    ): JsonResponse {
        $invitations =
            $currentOrganization
                ->get()
                ->invitations()
                ->with([
                    'inviter:id,name,email',
                ])
                ->latest()
                ->get()
                ->map(
                    fn (
                        OrganizationInvitation $invitation
                    ): array => $this->administrativePayload(
                        $invitation
                    )
                )
                ->values();

        return response()->json(
            $invitations
        );
    }

    public function store(
        OrganizationInvitationStoreRequest $request,
        CurrentOrganization $currentOrganization,
        IssueOrganizationInvitation $issuer,
    ): JsonResponse {
        $invitation =
            $issuer->execute(
                organization: $currentOrganization->get(),

                inviter: $request->user('api'),

                email: $request->validated(
                    'email'
                ),

                careAccesses: $request->validated('care_accesses'),

                role: $request->validated(
                    'role'
                ),
            );

        return response()->json(
            $invitation,
            201
        );
    }

    public function resend(
        int $organizationInvitation,
        CurrentOrganization $currentOrganization,
        ResendOrganizationInvitation $resender,
    ): JsonResponse {
        $invitation =
            $this->resolveAdministrativeInvitation(
                $currentOrganization,
                $organizationInvitation,
            );

        $invitation =
            $resender->execute(
                $invitation
            );

        return response()->json(
            $this->administrativePayload(
                $invitation
            )
        );
    }

    public function revoke(
        int $organizationInvitation,
        CurrentOrganization $currentOrganization,
        RevokeOrganizationInvitation $revoker,
    ): JsonResponse {
        $invitation =
            $this->resolveAdministrativeInvitation(
                $currentOrganization,
                $organizationInvitation,
            );

        $invitation =
            $revoker->execute(
                $invitation
            );

        return response()->json(
            $this->administrativePayload(
                $invitation
            )
        );
    }

    public function showAcceptance(
        string $token,
    ): JsonResponse {
        $invitation =
            $this->resolveInvitation(
                $token
            );

        $this->ensureAcceptable(
            $invitation
        );

        $registrationRequired =
            ! User::query()
                ->where(
                    'email',
                    $invitation->email
                )
                ->exists();

        return response()->json([
            'email' => $invitation->email,

            'role' => $invitation->role,

            'care_accesses' => $invitation->care_accesses,
            'recipients' => CareRecipient::withoutGlobalScopes()->where('organization_id', $invitation->organization_id)->whereIn('id', array_column($invitation->care_accesses ?? [], 'care_recipient_id'))->get(['id', 'name']),
            'registration_required' => $registrationRequired,

            'organization' => [
                'id' => $invitation
                    ->organization
                    ->id,

                'name' => $invitation
                    ->organization
                    ->name,

                'slug' => $invitation
                    ->organization
                    ->slug,
            ],
        ]);
    }

    public function accept(
        OrganizationInvitationAcceptRequest $request,
        string $token,
        AcceptOrganizationInvitation $acceptor,
    ): JsonResponse {
        $invitation = $this->resolveInvitation($token);

        $this->ensureAcceptable($invitation);

        $registrationRequired =
            ! User::query()
                ->where(
                    'email',
                    $invitation->email
                )
                ->exists();

        $user =
            $acceptor->execute(
                invitation: $invitation,

                data: $request->validated(),
            );

        $organization = [
            'id' => $invitation->organization->id,
            'name' => $invitation->organization->name,
            'slug' => $invitation->organization->slug,
        ];

        $response = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'organization' => $organization,
            'role' => $invitation->role,
        ];

        if ($registrationRequired) {
            $guard =
                Auth::guard(
                    'api'
                );

            $authToken = $guard->login($user);

            $response = [
                'token' => $authToken,
                'access_token' => $authToken,
                'token_type' => 'bearer',
                'expires_in' => $guard->factory()->getTTL() * 60,
                'organizations' => [$organization],
                ...$response,
            ];
        }

        return response()->json($response);
    }

    private function resolveInvitation(
        string $token,
    ): OrganizationInvitation {
        return OrganizationInvitation::query()
            ->with(
                'organization'
            )
            ->where(
                'token_hash',
                OrganizationInvitation::hashToken(
                    $token
                )
            )
            ->firstOrFail();
    }

    private function resolveAdministrativeInvitation(
        CurrentOrganization $currentOrganization,
        int $invitationId,
    ): OrganizationInvitation {
        return $currentOrganization
            ->get()
            ->invitations()
            ->with([
                'organization',
                'inviter:id,name,email',
            ])
            ->whereKey(
                $invitationId
            )
            ->firstOrFail();
    }

    private function administrativePayload(
        OrganizationInvitation $invitation,
    ): array {
        $invitation->loadMissing([
            'inviter:id,name,email',
        ]);

        return [
            'id' => $invitation->getKey(),

            'email' => $invitation->email,

            'role' => $invitation->role,

            'care_accesses' => $invitation->care_accesses,
            'status' => $invitation->administrativeStatus(),

            'expires_at' => $invitation
                ->expires_at
                ?->toISOString(),

            'accepted_at' => $invitation
                ->accepted_at
                ?->toISOString(),

            'revoked_at' => $invitation
                ->revoked_at
                ?->toISOString(),

            'created_at' => $invitation
                ->created_at
                ?->toISOString(),

            'inviter' => $invitation->inviter
                ? [
                    'id' => $invitation
                        ->inviter
                        ->getKey(),

                    'name' => $invitation
                        ->inviter
                        ->name,

                    'email' => $invitation
                        ->inviter
                        ->email,
                ]
                : null,
        ];
    }

    private function ensureAcceptable(
        OrganizationInvitation $invitation,
    ): void {
        if (
            ! $invitation->isAcceptable()
        ) {
            abort(
                410,
                'Este convite não está mais disponível.'
            );
        }
    }
}
