<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePartnerRequest extends FormRequest
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
            'nif'         => 'required|string|max:9|unique:partners,nif,' . $this->route('partner')->id,
            'phone'       => 'required|string|max:15',
            'website'     => 'nullable|string|max:255',
            'description' => 'required|string',
            'active'      => 'sometimes|boolean'
        ];
    }

    public function messages(): array
        {
            return [
                'nif.unique' => 'Este NIF já se encontra registado.',
            ];
        }
}
