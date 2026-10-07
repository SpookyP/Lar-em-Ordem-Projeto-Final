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
            'name'        => 'required|string|max:255',
            'nif'         => 'required|string|digits:9|unique:partners,nif',
            'phone'       => 'required|string|max:15',
            'website'     => 'nullable|string|url|max:255',
            'description' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'nif.unique' => 'Este NIF já se encontra registado.',
        ];
    }
}