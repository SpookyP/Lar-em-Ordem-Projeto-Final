<?php

namespace App\Http\Requests\ServiceProvider;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequestAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'images'   => 'required|array|min:1|max:2',
            'images.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'images.required' => 'Envie pelo menos uma imagem.',
            'images.max'      => 'Pode enviar no máximo 2 imagens.',
            'images.*.image'  => 'O ficheiro tem de ser uma imagem.',
            'images.*.mimes'  => 'A imagem tem de ser JPG, PNG ou WEBP.',
            'images.*.max'    => 'Cada imagem pode ter no máximo 5 MB.',
        ];
    }
}