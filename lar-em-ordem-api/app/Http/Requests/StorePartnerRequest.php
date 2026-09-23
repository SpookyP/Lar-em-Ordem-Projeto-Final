<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePartnerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'nif'         => 'required|string|max:9|unique:partners,nif',
            'contact'     => 'required|string|max:255',
            'website'     => 'nullable|string|max:255',
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
