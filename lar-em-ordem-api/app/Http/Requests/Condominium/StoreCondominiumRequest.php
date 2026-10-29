<?php

namespace App\Http\Requests\Condominium;

use Illuminate\Foundation\Http\FormRequest;

class StoreCondominiumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                => ['required', 'string', 'max:255'],
            'reserve_fund'        => ['sometimes', 'numeric', 'min:0', 'max:9999999999.99'],
            'address'             => ['required', 'array'],
            'address.street'      => ['required', 'string', 'max:255'],
            'address.postal_code' => ['required', 'string', 'max:20'],
            'address.door'        => ['nullable', 'string', 'max:10'],
            'address.county'      => ['required', 'string', 'max:100'],
            'address.location'    => ['required', 'string', 'max:100'],
            'address.district'    => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'    => 'Indique o nome do condomínio.',
            'reserve_fund.min' => 'O fundo comum de reserva não pode ser negativo.',
            'address.required' => 'Indique a morada do prédio.',
        ];
    }
}