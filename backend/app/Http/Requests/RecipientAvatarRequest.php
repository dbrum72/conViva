<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecipientAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4096,max_height=4096']];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'Selecione uma foto.',
            'image.image' => 'Envie uma imagem válida.',
            'image.mimes' => 'Use uma foto JPG, PNG ou WebP.',
            'image.max' => 'A foto deve ter no máximo 2 MB.',
            'image.dimensions' => 'A foto deve ter no máximo 4096 pixels de largura e altura.',
            'image.uploaded' => 'Não foi possível receber a foto. Verifique o tamanho do arquivo.',
        ];
    }
}
