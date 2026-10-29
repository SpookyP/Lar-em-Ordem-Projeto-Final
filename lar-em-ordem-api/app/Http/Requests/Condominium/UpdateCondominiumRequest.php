<?php

namespace App\Http\Requests\Condominium;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCondominiumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                => ['sometimes', 'required', 'string', 'max:255'],
            'reserve_fund'        => ['sometimes', 'numeric', 'min:0', 'max:9999999999.99'],
            'regenerate_invite_code' => ['sometimes', 'boolean'],
            'address'             => ['sometimes', 'array'],
            'address.street'      => ['sometimes', 'required', 'string', 'max:255'],
            'address.postal_code' => ['sometimes', 'required', 'string', 'max:20'],
            'address.door'        => ['sometimes', 'nullable', 'string', 'max:10'],
            'address.county'      => ['sometimes', 'required', 'string', 'max:100'],
            'address.location'    => ['sometimes', 'required', 'string', 'max:100'],
            'address.district'    => ['sometimes', 'required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'    => 'Indique o nome do condomínio.',
            'reserve_fund.min' => 'O fundo comum de reserva não pode ser negativo.',
            'regenerate_invite_code.boolean' => 'O campo regenerate_invite_code tem de ser true ou false.'
        ];
    }
}