<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'password' => [
                'required',
                'confirmed',
                Password::min(8),
            ],

            'organization_name' => [
                'required',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',

            'email.required' => 'O e-mail é obrigatório.',

            'email.email' => 'Informe um endereço de e-mail válido.',

            'email.unique' => 'Este e-mail já está cadastrado.',

            'password.required' => 'A senha é obrigatória.',

            'password.confirmed' => 'A confirmação da senha não corresponde à senha informada.',

            'organization_name.required' => 'O nome da organização é obrigatório.',
        ];
    }
}
