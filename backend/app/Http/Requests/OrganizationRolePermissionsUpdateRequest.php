<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrganizationRolePermissionsUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'permissions' => [
                'present',
                'array',
            ],

            'permissions.*' => [
                'string',
                'distinct',
                Rule::exists('permissions', 'name')
                    ->where('guard_name', 'api'),
            ],
        ];
    }
}
