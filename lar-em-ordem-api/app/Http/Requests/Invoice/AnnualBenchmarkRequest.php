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
}