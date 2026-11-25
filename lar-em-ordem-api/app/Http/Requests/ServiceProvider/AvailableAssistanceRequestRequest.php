<?php

namespace App\Http\Requests\ServiceProvider;

use App\Http\Requests\PerPageRequest;

class AvailableAssistanceRequestRequest extends PerPageRequest
{
    /**
     * Filters for the service provider (GET /assistance-requests/available)
     * Optional search and pagination: ?search=caldeira&location=Matosinhos&per_page=5
     * (search: title/description; location: the property's locality, county or district).
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