<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CareDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['decision' => 'required|in:accepted,rejected', 'reason' => 'nullable|required_if:decision,rejected|string|max:2000'];
    }
}
