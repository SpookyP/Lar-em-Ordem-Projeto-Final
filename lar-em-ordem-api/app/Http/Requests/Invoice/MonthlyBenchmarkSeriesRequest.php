<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;

class MonthlyBenchmarkSeriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'property_id' => ['required', 'ulid'],
        ];
    }

    public function messages(): array
    {
        return [
            'property_id.required' => 'Indique a habitação que pretende consultar.',
            'property_id.ulid' => 'O identificador da habitação é inválido.',
        ];
    }
}