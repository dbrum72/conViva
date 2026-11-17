<?php

namespace App\Modules\Care\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CareOccurrenceExecutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => 'nullable|string|max:10000',
            'occurred_at' => 'required|date|before_or_equal:now',
            'outcome' => 'sometimes|required|in:administered,not_administered',
            'next_date' => 'nullable|date_format:Y-m-d',
            'reason' => $this->route('execution') !== null ? 'required|string|max:2000' : 'prohibited',
        ];
    }
}
