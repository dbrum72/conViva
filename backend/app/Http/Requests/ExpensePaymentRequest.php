<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExpensePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:20480'];
    }
}
