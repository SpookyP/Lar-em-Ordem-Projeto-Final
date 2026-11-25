<?php

namespace App\Http\Requests\PartnerOffer;

use App\Http\Requests\PerPageRequest;

/**
 * Partner list: ?per_page= (1 to 15), ?page= and an optional ?search= (name or description).
 */
class IndexPartnerRequest extends PerPageRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return parent::messages() + [
            'search.max' => 'A pesquisa não pode ter mais de 100 caracteres.',
        ];
    }
}