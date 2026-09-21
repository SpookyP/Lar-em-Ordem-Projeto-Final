<?php

namespace App\Http\Requests\Vault;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Valida se os IDs enviados existem efetivamente nas tabelas relacionadas
            'property_id' => ['required', 'integer', 'exists:properties,id'],
            'document_category_id' => ['required', 'integer', 'exists:document_categories,id'],

            // Dados opcionais preenchidos pelo utilizador
            'description' => ['nullable', 'string', 'max:1000'],

            // Validação do ficheiro
            'file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'property_id.exists' => 'The selected property is invalid.',
            'document_category_id.exists' => 'The selected document category is invalid.',
            'file.mimes' => 'Only PDF files are allowed for data extraction.',
            'file.max' => 'The PDF size must not exceed 10MB.',
        ];
    }
}
