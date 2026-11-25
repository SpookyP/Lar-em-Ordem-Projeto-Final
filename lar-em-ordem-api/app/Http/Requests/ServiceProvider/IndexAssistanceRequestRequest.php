<?php

namespace App\Http\Requests\ServiceProvider;

use Illuminate\Validation\Rule;
use App\Http\Requests\PerPageRequest;

class IndexAssistanceRequestRequest extends PerPageRequest
{
    /**
     * Filters for the resident (GET /assistance-requests)
     * Property required (the one the resident is in), optional status filter and pagination:
     * ?property_id=01...&status=open&per_page=5 (5 per page by default, 15 at most)
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