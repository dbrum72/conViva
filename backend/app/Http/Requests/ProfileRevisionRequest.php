<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRevisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = ['operation' => 'required|in:save,archive', 'version' => 'required|integer|min:0'];
        if ($this->input('operation') === 'save') {
            $rules += (new CareRecipientRequest)->rules();
            unset($rules['status']);
        }

        return $rules;
    }
}
