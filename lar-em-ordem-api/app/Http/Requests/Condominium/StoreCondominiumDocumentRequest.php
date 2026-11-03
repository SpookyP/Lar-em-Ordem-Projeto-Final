<?php

namespace App\Http\Requests\Condominium;

use Illuminate\Foundation\Http\FormRequest;

class StoreCondominiumDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file'                             => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:15360'],
            'title'                            => ['required', 'string', 'max:255'],
            'description'                      => ['nullable', 'string', 'max:1000'],
            'document_date'                    => ['required', 'date', 'before_or_equal:today'],
            'condominium_document_category_id' => ['required', 'integer', 'exists:condominium_document_categories,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required'                           => 'Escolha o ficheiro do documento.',
            'file.mimes'                              => 'O documento tem de ser PDF, JPG ou PNG.',
            'file.max'                                => 'O documento não pode ter mais de 15 MB.',
            'title.required'                          => 'Indique o título do documento.',
            'document_date.required'                  => 'Indique a data do documento.',
            'document_date.before_or_equal'           => 'A data do documento não pode ser no futuro.',
            'condominium_document_category_id.required' => 'Escolha a categoria do documento.',
            'condominium_document_category_id.exists' => 'A categoria indicada não existe.',
        ];
    }
}