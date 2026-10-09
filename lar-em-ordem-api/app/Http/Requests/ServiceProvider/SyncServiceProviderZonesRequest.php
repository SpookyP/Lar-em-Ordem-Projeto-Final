<?php

namespace App\Http\Requests\ServiceProvider;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncServiceProviderZonesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Lista completa das zonas onde o prestador atua. Substitui a lista anterior.
     * Uma lista vazia retira todas as zonas.
     */
    public function rules(): array
    {
        return [
            'zone_ids'   => 'present|array',
            'zone_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('service_zones', 'id')->whereNull('deleted_at'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'zone_ids.present'    => 'É necessário enviar a lista de zonas (pode ser vazia).',
            'zone_ids.*.exists'   => 'Uma das zonas indicadas não existe.',
            'zone_ids.*.distinct' => 'A lista de zonas tem valores repetidos.',
        ];
    }
}