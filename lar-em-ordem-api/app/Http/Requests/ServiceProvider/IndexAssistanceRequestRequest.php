<?php

namespace App\Http\Requests\ServiceProvider;

use Illuminate\Validation\Rule;
use App\Http\Requests\PerPageRequest;

class IndexAssistanceRequestRequest extends PerPageRequest
{
    /**
     * Filtragem para o morador (GET /assistance-requests)
     * Casa obrigatória (a casa onde o morador está), filtro opcional por estado e paginação:
     * ?property_id=01...&status=open&per_page=5 (por defeito 5 por página, máximo 15)
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'property_id' => [
                'required', 'ulid',
                Rule::exists('properties', 'id')->whereNull('deleted_at'),
            ],
            'status' => 'sometimes|in:open,closed,cancelled',
        ];
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'property_id.required' => 'Indique a casa.',
            'property_id.exists'   => 'A casa indicada não existe.',
        ];
    }
}