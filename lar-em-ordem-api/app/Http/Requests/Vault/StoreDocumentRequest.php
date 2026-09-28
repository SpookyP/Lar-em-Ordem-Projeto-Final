<?php

namespace App\Http\Requests\Vault;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    /**
     * Determina se o utilizador está autorizado a realizar este pedido.
     * Atualmente, permite todos os pedidos que já passaram pela autenticação.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtém as regras de validação que se aplicam ao pedido de upload de documento.
     * Garante que os relacionamentos existem, os limites de texto são respeitados
     * e o ficheiro é um PDF válido dentro do limite de tamanho.
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

    /**
     * Define as mensagens de erro personalizadas para regras de validação específicas.
     *
     * @return array<string, string>
     */
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
