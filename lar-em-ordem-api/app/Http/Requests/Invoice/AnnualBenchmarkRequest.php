<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;

class AnnualBenchmarkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'property_id'         => ['nullable', 'ulid'],
            'consumption_type_id' => ['required', 'integer', 'exists:consumption_types,id'],
            'year'                => ['required', 'digits:4', 'integer'], // Ex: 2026
        ];
    }

    /**
     * Mensagens de erro em português.
     */
    public function messages(): array
    {
        return [
            'property_id.ulid'             => 'O identificador da habitação é inválido.',
            'consumption_type_id.required' => 'Indique o tipo de consumo.',
            'consumption_type_id.integer'  => 'O tipo de consumo é inválido.',
            'consumption_type_id.exists'   => 'O tipo de consumo selecionado não existe.',
            'year.required'                => 'Indique o ano a comparar.',
            'year.digits'                  => 'O ano deve ter 4 dígitos (por exemplo, 2026).',
            'year.integer'                 => 'O ano é inválido.',
        ];
    }
}