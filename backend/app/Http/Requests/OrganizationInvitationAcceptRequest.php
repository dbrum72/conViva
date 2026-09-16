<?php

namespace App\Http\Requests;

use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrganizationInvitationAcceptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $invitation =
            $this->resolveInvitation();

        if ($invitation === null) {
            return [];
        }

        $existingUser =
            User::query()
                ->where(
                    'email',
                    $invitation->email
                )
                ->exists();

        return [
            'name' => [
                Rule::requiredIf(
                    ! $existingUser
                ),
                'nullable',
                'string',
                'max:255',
            ],

            'password' => [
                Rule::requiredIf(
                    ! $existingUser
                ),
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],
        ];
    }

    private function resolveInvitation(): ?OrganizationInvitation
    {
        $token =
            $this->route('token');

        if (
            ! is_string($token)
            || $token === ''
        ) {
            return null;
        }

        return OrganizationInvitation::query()
            ->where(
                'token_hash',
                OrganizationInvitation::hashToken(
                    $token
                )
            )
            ->first();
    }
}
