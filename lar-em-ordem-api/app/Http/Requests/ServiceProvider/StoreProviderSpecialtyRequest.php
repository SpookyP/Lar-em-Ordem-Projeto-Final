<?php

namespace App\Http\Requests\ServiceProvider;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProviderSpecialtyRequest extends FormRequest
{
     public function rules(): array
    {
        return [
            'problem_category_id' => [
                'required', 'integer',
                Rule::exists('problem_categories', 'id')->whereNull('deleted_at'),
            ],
            'service_specialty_ids' => [
                'required_without:custom_name',
                'prohibits:custom_name',
                'array', 'min:1',
            ],
            'service_specialty_ids.*' => [
                'integer', 'distinct',
                Rule::exists('service_specialties', 'id')->whereNull('deleted_at'),
            ],
            'custom_name' => [
                'required_without:service_specialty_ids',
                'nullable', 'string', 'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'service_specialty_ids.required_without' => 'Escolha especialidades da lista ou escreva uma especialidade própria.',
            'service_specialty_ids.prohibits'        => 'Envie especialidades da lista ou uma especialidade própria, não as duas.',
            'service_specialty_ids.*.exists'         => 'Uma das especialidades indicadas não existe.',
            'service_specialty_ids.*.distinct'       => 'A lista de especialidades tem valores repetidos.',
            'problem_category_id.exists'             => 'A categoria indicada não existe.',
        ];
    }
}