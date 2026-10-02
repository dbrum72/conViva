<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CareUnavailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'starts_at' => 'required|date_format:Y-m-d',
            'ends_at' => 'required|date_format:Y-m-d|after_or_equal:starts_at',
            'timezone' => 'required|timezone:all',
            'reason' => 'nullable|string|max:2000',
        ];
    }
}
