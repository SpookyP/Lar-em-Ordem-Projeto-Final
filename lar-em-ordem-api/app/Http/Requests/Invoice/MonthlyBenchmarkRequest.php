<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;

class MonthlyBenchmarkRequest extends FormRequest
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
            'month'               => ['required', 'date_format:Y-m'], // Ex: 2026-09
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
            'month.required'               => 'Indique o mês a comparar.',
            'month.date_format'            => 'O mês deve ter o formato AAAA-MM (por exemplo, 2026-09).',
        ];
    }
}