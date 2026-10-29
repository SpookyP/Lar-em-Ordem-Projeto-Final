<?php

namespace App\Http\Requests\Condominium;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCondominiumAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contact'    => ['required', 'string', 'max:255'],
            'admin_type' => ['required', 'string', Rule::in(['professional', 'resident'])],
        ];
    }

    public function messages(): array
    {
        return [
            'contact.required'    => 'Indique um contacto (telefone ou email).',
            'admin_type.required' => 'Indique o tipo de administrador.',
            'admin_type.in'       => 'O tipo de administrador tem de ser "professional" (empresa) ou "resident" (condómino).',
        ];
    }
}