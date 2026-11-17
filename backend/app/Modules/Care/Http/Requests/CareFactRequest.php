<?php

namespace App\Modules\Care\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CareFactRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'request_id' => 'required|uuid',
            'kind' => 'required|in:feeding,walk,observation',
            'occurred_at' => 'required|date|before_or_equal:now',
            'description' => 'nullable|required_if:kind,observation|string|max:10000',
        ];
    }
}
