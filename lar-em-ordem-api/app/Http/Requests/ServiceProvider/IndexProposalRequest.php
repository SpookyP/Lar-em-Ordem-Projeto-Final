<?php

namespace App\Http\Requests\ServiceProvider;

use App\Http\Requests\PerPageRequest;
use Illuminate\Validation\Rule;

class IndexProposalRequest extends PerPageRequest
{
    /**
     * Filtro opcional por estado e paginação: ?status=pending&per_page=5
     * (sem status devolve todas).
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'status' => ['sometimes', 'string', Rule::in(['pending', 'accepted', 'rejected', 'withdrawn'])],
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