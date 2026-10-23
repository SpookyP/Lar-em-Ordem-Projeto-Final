<?php

namespace App\Http\Requests\ServiceProvider;

use Illuminate\Foundation\Http\FormRequest;

class PerPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Listagens paginadas: ?page=2&per_page=10 (por defeito 5 por página, máximo 15).
     */
    public function rules(): array
    {
        return [
            'per_page' => 'sometimes|integer|between:1,15',
        ];
    }

    public function messages(): array
    {
        return [
            'per_page.between' => 'O número de resultados por página tem de estar entre 1 e 15.',
        ];
    }
}