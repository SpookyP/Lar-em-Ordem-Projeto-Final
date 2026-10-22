<?php

namespace App\Http\Requests\ServiceProvider;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Dados do prestador + opcional (zonas, especialidades da lista e especialidades próprias).
     */
    public function rules(): array
    {
        return [
            'company_name' => 'required|string|max:255',
            'nif'          => 'required|string|digits:9',
            'phone'        => 'required|string|max:15',
            'provider_email'        => 'nullable|email|max:255',
            'description'  => 'required|string',

            'zone_ids'   => 'sometimes|array',
            'zone_ids.*' => [
                'integer', 'distinct',
                Rule::exists('service_zones', 'id')->whereNull('deleted_at'),
            ],
            'service_specialty_ids'   => 'sometimes|array',
            'service_specialty_ids.*' => [
                'integer', 'distinct',
                Rule::exists('service_specialties', 'id')->whereNull('deleted_at'),
            ],
            'custom_specialties'                       => 'sometimes|array',
            'custom_specialties.*.problem_category_id' => [
                'required', 'integer',
                Rule::exists('problem_categories', 'id')->whereNull('deleted_at'),
            ],
            'custom_specialties.*.name' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'zone_ids.*.exists'                               => 'Uma das zonas indicadas não existe.',
            'zone_ids.*.distinct'                             => 'A lista de zonas tem valores repetidos.',
            'service_specialty_ids.*.exists'                  => 'Uma das especialidades indicadas não existe.',
            'service_specialty_ids.*.distinct'                => 'A lista de especialidades tem valores repetidos.',
            'custom_specialties.*.problem_category_id.required' => 'Indique a categoria da especialidade própria.',
            'custom_specialties.*.problem_category_id.exists' => 'A categoria indicada não existe.',
            'custom_specialties.*.name.required'              => 'Indique o nome da especialidade própria.',
        ];
    }
}
