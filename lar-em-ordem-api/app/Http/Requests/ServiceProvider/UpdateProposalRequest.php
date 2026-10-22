<?php

namespace App\Http\Requests\ServiceProvider;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $availableFrom = $this->input('available_from')
            ?? substr($this->route('proposal')->available_from, 0, 5);

        return [
            'estimated_value' => 'sometimes|required|numeric|min:0.01|max:9999999999.99',
            'description'     => 'sometimes|required|string|max:5000',
            'validity'        => 'sometimes|required|date|after_or_equal:today',
            'available_date'  => 'sometimes|required|date|after_or_equal:today',
            'available_from'  => 'sometimes|required|date_format:H:i',
            'available_until' => 'sometimes|required|date_format:H:i|after:' . $availableFrom,
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
