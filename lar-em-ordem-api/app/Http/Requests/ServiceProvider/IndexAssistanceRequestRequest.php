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
     * Casa obrigatória (a casa onde o morador está) e filtro opcional por estado:
     * ?property_id=01...&status=open
     */
    public function rules(): array
    {
        return [
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
            'property_id.required' => 'Indique a casa.',
            'property_id.exists'   => 'A casa indicada não existe.',
        ];
    }
}