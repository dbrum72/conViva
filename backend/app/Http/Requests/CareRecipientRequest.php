<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CareRecipientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'routine_profile' => 'sometimes|nullable|array:preferences,instructions,school,authorized_people,identification,contacts',
            'routine_profile.preferences' => 'nullable|string|max:2000',
            'routine_profile.instructions' => 'nullable|string|max:4000',
            'routine_profile.school' => 'nullable|prohibited_unless:kind,child|string|max:500',
            'routine_profile.authorized_people' => 'nullable|prohibited_unless:kind,child|string|max:2000',
            'routine_profile.identification' => 'nullable|prohibited_unless:kind,pet|string|max:150',
            'routine_profile.contacts' => 'sometimes|array|max:10',
            'routine_profile.contacts.*' => 'required|array:name,relationship,phone',
            'routine_profile.contacts.*.name' => 'required|string|max:150',
            'routine_profile.contacts.*.relationship' => 'nullable|string|max:100',
            'routine_profile.contacts.*.phone' => 'required|string|max:50',
            'health_profile' => 'sometimes|nullable|array:instructions,contacts',
            'health_profile.instructions' => 'nullable|string|max:4000',
            'health_profile.contacts' => 'sometimes|array|max:10',
            'health_profile.contacts.*' => 'required|array:name,relationship,phone',
            'health_profile.contacts.*.name' => 'required|string|max:150',
            'health_profile.contacts.*.relationship' => 'nullable|string|max:100',
            'health_profile.contacts.*.phone' => 'required|string|max:50',
            'name' => 'required|string|max:150', 'kind' => ['required', Rule::in(['child', 'adult', 'pet'])], 'birth_date' => 'nullable|date|before_or_equal:today', 'species' => 'nullable|required_if:kind,pet|string|max:80', 'breed' => 'nullable|string|max:80', 'status' => ['sometimes', Rule::in(['active', 'archived'])]];
    }
}
