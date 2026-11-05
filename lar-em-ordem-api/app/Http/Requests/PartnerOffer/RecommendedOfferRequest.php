<?php

namespace App\Http\Requests\PartnerOffer;

use App\Http\Requests\PerPageRequest;

class RecommendedOfferRequest extends PerPageRequest
{
    /**
     * Ofertas em vigor de todos os parceiros: pesquisa, tipo e paginação.
     * ?search=fibra&category_id=3&per_page=5
     * (search: título, descrição ou nome do parceiro).
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'search'      => ['sometimes', 'nullable', 'string', 'max:100'],
            'category_id' => ['sometimes', 'integer', 'exists:offer_categories,id'],
        ];
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'search.max'         => 'A pesquisa não pode ter mais de 100 caracteres.',
            'category_id.exists' => 'O tipo de oferta indicado não existe.',
        ];
    }
}