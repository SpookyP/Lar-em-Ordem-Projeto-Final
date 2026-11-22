<?php

namespace App\Http\Requests\Resident;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateResidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->resident !== null;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'nif' => ['sometimes', 'string', 'digits:9'],
        ];
    }

    public function messages(): array
    {
        return [
            'nif.digits' => 'O NIF tem de ter 9 dígitos.',
        ];
    }
}