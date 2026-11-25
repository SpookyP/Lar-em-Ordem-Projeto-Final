<?php

namespace App\Http\Requests\Vault;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Pré-leitura de um PDF do cofre.
 */
class ExtractDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:pdf', 'max:15360'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Escolha um ficheiro PDF.',
            'file.file'     => 'O ficheiro enviado é inválido.',
            'file.mimes'    => 'Só é possível ler automaticamente ficheiros PDF.',
            'file.max'      => 'O ficheiro não pode ultrapassar os 15MB.',
        ];
    }
}