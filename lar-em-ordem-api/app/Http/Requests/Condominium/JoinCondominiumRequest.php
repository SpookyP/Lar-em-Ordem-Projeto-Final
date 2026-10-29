<?php

namespace App\Http\Requests\Condominium;

use Illuminate\Foundation\Http\FormRequest;

class JoinCondominiumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'invite_code' => ['required', 'string', 'size:8'],
            'property_id' => ['required', 'ulid', 'exists:properties,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'invite_code.required' => 'Indique o código de convite.',
            'invite_code.size'     => 'O código de convite tem 8 caracteres.',
            'property_id.required' => 'Indique a casa.',
            'property_id.exists'   => 'A casa indicada não existe.',
        ];
    }
}