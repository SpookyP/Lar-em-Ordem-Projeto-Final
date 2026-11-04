<?php

namespace App\Http\Requests\Condominium;

use App\Http\Requests\PerPageRequest;

/**
 * Listagem dos documentos: ?per_page= (1–15) e ?category_id= opcional.
 */
class IndexCondominiumDocumentRequest extends PerPageRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'category_id' => ['sometimes', 'integer', 'exists:condominium_document_categories,id'],
        ];
    }

    public function messages(): array
    {
        return parent::messages() + [
            'category_id.exists' => 'A categoria indicada não existe.',
        ];
    }
}