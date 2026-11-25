<?php

namespace App\Http\Requests\Condominium;

use App\Http\Requests\PerPageRequest;

/**
 * Document list: ?per_page= (1 to 15) and an optional ?category_id=.
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