<?php

namespace App\Http\Requests;

use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrganizationMemberRoleUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $organization =
            app(CurrentOrganization::class)
                ->get();

        return [
            'role' => [
                'required',
                'string',

                Rule::exists(
                    'roles',
                    'name',
                )
                    ->where(
                        'organization_id',
                        $organization->getKey(),
                    )
                    ->where(
                        'guard_name',
                        'api',
                    ),
            ],
        ];
    }
}
