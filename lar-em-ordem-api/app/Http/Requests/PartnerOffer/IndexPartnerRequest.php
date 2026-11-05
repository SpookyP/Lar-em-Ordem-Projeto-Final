<?php

namespace App\Http\Requests\PartnerOffer;

use App\Http\Requests\PerPageRequest;

/**
 * Lista de parceiros: ?per_page= (1–15), ?page= e ?search= opcional (nome ou descrição).
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