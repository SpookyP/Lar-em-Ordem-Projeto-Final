<?php

namespace App\Http\Requests\PartnerOffer;

use Illuminate\Foundation\Http\FormRequest;

class StorePartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => 'required|string|max:255',
            'nif'         => 'required|string|digits:9',
            'phone'       => 'required|string|max:15',
            'website'     => 'nullable|string|url|max:255',
            'description' => '  required|string|max:2000'
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required' => 'Indique o nome da empresa.',
            'nif.digits'            => 'O NIF tem de ter 9 dígitos.',
            'website.url'           => 'Indique um endereço válido (ex.: https://empresa.pt).',
            'description.required'  => 'Escreva uma descrição da empresa.',
            'description.max'       => 'A descrição não pode ter mais de 2000 caracteres.',
        ];
    }
}