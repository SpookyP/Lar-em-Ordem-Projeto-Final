<?php

namespace App\Http\Requests\ServiceProvider;

use Illuminate\Foundation\Http\FormRequest;

class StoreProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

     public function rules(): array
    {
        return [
            'estimated_value' => 'required|numeric|min:0.01|max:9999999999.99',
            'description'     => 'required|string|max:5000',
            'validity'        => 'required|date|after_or_equal:today',
            'available_date'  => 'required|date|after_or_equal:today',
            'available_from'  => 'required|date_format:H:i',
            'available_until' => 'required|date_format:H:i|after:available_from',
        ];
    }

    public function messages(): array
    {
        return [
            'validity.after_or_equal'       => 'A validade da proposta não pode ser uma data passada.',
            'available_date.after_or_equal' => 'A data de disponibilidade não pode ser uma data passada.',
            'available_from.date_format'    => 'A hora de início tem de estar no formato HH:MM (ex: 10:00).',
            'available_until.date_format'   => 'A hora de fim tem de estar no formato HH:MM (ex: 15:00).',
            'available_until.after'         => 'A hora de fim tem de ser depois da hora de início.',
        ];
    }
}