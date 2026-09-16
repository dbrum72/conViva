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
        return ['name' => 'required|string|max:150', 'kind' => ['required', Rule::in(['child', 'adult', 'pet'])], 'birth_date' => 'nullable|date|before_or_equal:today', 'species' => 'nullable|required_if:kind,pet|string|max:80', 'breed' => 'nullable|string|max:80', 'status' => ['sometimes', Rule::in(['active', 'archived'])]];
    }
}
