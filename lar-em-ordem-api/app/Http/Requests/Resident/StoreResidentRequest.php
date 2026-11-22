<?php

namespace App\Http\Requests\Resident;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreResidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'nif' => ['required', 'string', 'digits:9'],
        ];
    }

    public function messages(): array
    {
        return [
            'nif.required' => 'Indique o NIF.',
            'nif.digits'   => 'O NIF tem de ter 9 dígitos.',
        ];
    }
}