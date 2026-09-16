<?php

namespace App\Http\Requests;

use App\Models\OrganizationInvitation;
use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class OrganizationInvitationStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $email = $this->input(
            'email'
        );

        $role = $this->input(
            'role'
        );

        $this->merge([
            'email' => is_string($email)
                ? Str::lower(
                    trim($email)
                )
                : $email,

            'role' => is_string($role)
                ? trim($role)
                : $role,
        ]);
    }

    public function rules(): array
    {
        $organizationId =
            app(
                CurrentOrganization::class
            )->id();

        return [
            'care_accesses' => 'required|array|size:1',
            'care_accesses.*.care_recipient_id' => 'required|integer|distinct',
            'care_accesses.*.areas' => 'required|array|min:1',
            'care_accesses.*.areas.*' => 'required|in:routine,health,documents,finance',
            'care_accesses.*.can_edit' => 'required|boolean',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',

                Rule::unique(
                    'organization_invitations',
                    'email'
                )
                    ->where(
                        fn ($query) => $query
                            ->where(
                                'organization_id',
                                $organizationId,
                            )
                            ->where(
                                'status',
                                OrganizationInvitation::STATUS_PENDING,
                            )
                            ->where(
                                'expires_at',
                                '>',
                                now(),
                            )
                    ),
            ],

            'role' => [
                'required',
                'string',
                'max:100',

                Rule::exists(
                    'roles',
                    'name'
                )
                    ->where(
                        fn ($query) => $query
                            ->where(
                                'organization_id',
                                $organizationId,
                            )
                            ->where(
                                'guard_name',
                                'api',
                            )
                    ),
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (
                Validator $validator
            ): void {
                if (
                    $validator->errors()
                        ->has('email')
                ) {
                    return;
                }

                $organization =
                    app(
                        CurrentOrganization::class
                    )->get();

                $email =
                    $this->string(
                        'email'
                    )->toString();

                $hasActiveMembership =
                    $organization
                        ->users()
                        ->where(
                            'users.email',
                            $email,
                        )
                        ->wherePivot(
                            'status',
                            'active',
                        )
                        ->exists();

                if ($hasActiveMembership) {
                    $validator
                        ->errors()
                        ->add(
                            'email',
                            'Este usuário já possui vínculo ativo com a organização.'
                        );
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Informe o e-mail do convidado.',

            'email.email' => 'Informe um endereço de e-mail válido.',

            'email.unique' => 'Já existe um convite pendente válido para este e-mail.',

            'role.required' => 'Informe a função do convidado.',

            'role.exists' => 'A função informada não está disponível nesta organização.',
        ];
    }
}
