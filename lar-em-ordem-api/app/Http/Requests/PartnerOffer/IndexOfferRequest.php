<?php

namespace App\Http\Requests\PartnerOffer;

use App\Http\Requests\PerPageRequest;
use Illuminate\Validation\Rule;

class IndexOfferRequest extends PerPageRequest
{
    /**
     * Ofertas do parceiro: filtro opcional por estado e paginação.
     * ?status=current|scheduled|expired|inactive&per_page=5 (sem status devolve todas).
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'status' => ['sometimes', 'string', Rule::in(['current', 'scheduled', 'expired', 'inactive'])],
        ];
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'status.in' => 'Estado inválido.',
        ];
    }
}