<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CareEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'affected_user_ids' => 'sometimes|array|max:50', 'affected_user_ids.*' => 'integer|distinct',
            'kind' => ['required', Rule::in(['event', 'task', 'journal', 'medication', 'vaccine', 'feeding', 'expense'])],
            'title' => 'required|string|max:200', 'description' => 'nullable|string|max:10000', 'due_at' => 'nullable|date', 'ends_at' => 'nullable|date|after_or_equal:due_at', 'assigned_user_id' => 'nullable|integer',
            'amount_cents' => 'nullable|required_if:kind,expense|integer|min:1|max:1000000000',
            'details' => 'nullable|array:dose,frequency,route,food,quantity,provider',
            'details.*' => 'nullable|string|max:500',
            'shares' => 'nullable|array|max:50', 'shares.*.user_id' => 'required|integer|distinct', 'shares.*.amount_cents' => 'required|integer|min:1',
        ];
    }
}
