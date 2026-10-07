<?php

namespace App\Http\Requests\ServiceProvider;


use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceProviderRequest extends FormRequest
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
            'company_name' => 'sometimes|required|string|max:255',
            'nif'          => 'sometimes|required|string|digits:9',
            'phone'       => 'sometimes|required|string|max:15',
            'email' => [
                'sometimes', 'required', 'email', 'max:255',
                Rule::unique('service_providers', 'email')->ignore($this->route('service_provider')),
            ],
            'description' => 'sometimes|required|string',
            'active'      => 'sometimes|boolean',
        ];    
    }
     public function messages(): array
    {
        return [
            'email.unique' => 'Já existe um prestador de serviços com este email.',
        ];
    }
}
