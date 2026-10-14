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
}