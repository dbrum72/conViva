<?php

namespace App\Http\Requests\Auth;

class ResetPasswordRequest extends ForgotPasswordRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'token' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'Link incompleto. Solicite um novo link de recuperação.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'password.max' => 'A senha deve ter no máximo 72 caracteres.',
            'password.confirmed' => 'A confirmação da senha não corresponde à senha informada.',
        ];
    }
}
