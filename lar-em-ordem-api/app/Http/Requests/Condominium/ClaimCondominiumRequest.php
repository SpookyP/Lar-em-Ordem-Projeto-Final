<?php

namespace App\Http\Requests\Condominium;

use Illuminate\Foundation\Http\FormRequest;

class ClaimCondominiumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transfer_code' => ['required', 'string', 'size:12'],
        ];
    }

    public function messages(): array
    {
        return [
            'transfer_code.required' => 'Indique o código de transferência.',
            'transfer_code.size'     => 'O código de transferência tem 12 caracteres.',
        ];
    }
}