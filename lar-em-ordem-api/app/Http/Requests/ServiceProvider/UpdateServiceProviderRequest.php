<?php

namespace App\Http\Requests\ServiceProvider;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceProviderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Todos os campos são opcionais. As listas (zonas, especialidades da lista, especialidades próprias),
     * quando enviadas, substituem as anteriores; uma lista vazia retira todas. Se não forem enviadas, ficam iguais.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_name' => 'sometimes|required|string|max:255',
            'nif'          => 'sometimes|required|string|digits:9',
            'phone'       => 'sometimes|required|string|max:15',
            'provider_email'        => 'sometimes|nullable|email|max:255',
            'description' => 'sometimes|required|string',
            'active'      => 'sometimes|boolean',

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