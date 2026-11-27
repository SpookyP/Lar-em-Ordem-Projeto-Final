<?php

namespace App\Http\Requests\ServiceProvider;

use App\Http\Requests\PerPageRequest;
use Illuminate\Validation\Rule;

class IndexProposalRequest extends PerPageRequest
{
    /**
     * Optional status filter and pagination: ?status=pending&per_page=5
     * (without status it returns all of them).
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'status' => ['sometimes', 'string', Rule::in(['pending', 'expired', 'accepted', 'rejected', 'withdrawn'])],
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