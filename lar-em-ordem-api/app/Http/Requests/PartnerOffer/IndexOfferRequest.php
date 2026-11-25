<?php

namespace App\Http\Requests\PartnerOffer;

use App\Http\Requests\PerPageRequest;
use Illuminate\Validation\Rule;

class IndexOfferRequest extends PerPageRequest
{
    /**
     * Partner offers: optional status filter and pagination.
     * ?status=current|scheduled|expired|inactive&per_page=5 (without status it returns all of them).
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