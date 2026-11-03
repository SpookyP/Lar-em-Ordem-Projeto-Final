<?php

namespace App\Http\Requests\Condominium;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCondominiumDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'                            => ['sometimes', 'required', 'string', 'max:255'],
            'description'                      => ['sometimes', 'nullable', 'string', 'max:1000'],
            'document_date'                    => ['sometimes', 'required', 'date', 'before_or_equal:today'],
            'condominium_document_category_id' => ['sometimes', 'required', 'integer', 'exists:condominium_document_categories,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'                           => 'Indique o título do documento.',
            'document_date.required'                   => 'Indique a data do documento.',
            'document_date.before_or_equal'            => 'A data do documento não pode ser no futuro.',
            'condominium_document_category_id.required' => 'Escolha a categoria do documento.',
            'condominium_document_category_id.exists' => 'A categoria indicada não existe.',
        ];
    }
}