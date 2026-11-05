<?php

namespace App\Http\Requests\ServiceProvider;

use App\Http\Requests\PerPageRequest;

class AvailableAssistanceRequestRequest extends PerPageRequest
{
    /**
     * Filtragem para o Prestador de Serviços (GET /assistance-requests/available)
     * Pesquisa opcional e paginação: ?search=caldeira&location=Matosinhos&per_page=5
     * (search: título/descrição; location: localidade, concelho ou distrito da casa).
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'search'   => ['sometimes', 'nullable', 'string', 'max:100'],
            'location' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'search.max'   => 'A pesquisa não pode ter mais de 100 caracteres.',
            'location.max' => 'A localização não pode ter mais de 100 caracteres.',
        ];
    }
}