<?php

namespace App\Http\Requests\Vault;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $document = $this->route('document');
        $documentId = is_object($document) ? $document->id : $document;
        $propertyId = is_object($document) ? $document->property_id : $this->input('property_id');

        return [
            'document_category_id' => ['sometimes', 'required', 'integer', 'exists:document_categories,id'],

            'name' => [
                'sometimes',
                'required',
                'string',
                'min:3',
                'max:150',
                Rule::unique('vault_documents', 'name')->where('property_id', $propertyId)->ignore($documentId)
            ],

            'description' => ['nullable', 'string', 'max:1000'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:15360'],
            'issue_date' => ['nullable', 'date', 'before_or_equal:today'],
            'expiration_date' => ['nullable', 'date', 'after:issue_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'document_category_id.exists' => 'A categoria de documento é inválida.',
            'title.unique' => 'Já existe outro documento com este nome nesta habitação.',
            'file.mimes' => 'Apenas são aceites ficheiros em formato PDF, JPG, JPEG ou PNG.',
            'file.max' => 'O ficheiro não pode ultrapassar os 15MB.',
            'expiration_date.after' => 'A data de validade tem de ser posterior à data de emissão.',
            'issue_date.before_or_equal' => 'A data de emissão não pode ser no futuro.',
        ];
    }
}
