<?php

namespace App\Http\Requests\ServiceProvider;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAssistanceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Casa obrigatória (a casa onde o morador está), filtro opcional por estado e paginação:
     * ?property_id=01...&status=open&per_page=5 (por defeito 5 por página, máximo 15)
     */
    public function rules(): array
    {
        return [
            'property_id' => [
                'required', 'ulid',
                Rule::exists('properties', 'id')->whereNull('deleted_at'),
            ],
            'status' => 'sometimes|in:open,closed,cancelled',
            'per_page' => 'sometimes|integer|between:1,15',
        ];
    }

    public function messages(): array
    {
        return [
            'property_id.required' => 'Indique a casa.',
            'property_id.exists'   => 'A casa indicada não existe.',
            'per_page.between'     => 'O número de resultados por página tem de estar entre 1 e 15.',
        ];
    }
}