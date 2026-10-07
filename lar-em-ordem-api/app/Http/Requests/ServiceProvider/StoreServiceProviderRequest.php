<?php

namespace App\Http\Requests\ServiceProvider;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => 'required|string|max:255',
            'nif'          => 'required|string|digits:9',
            'phone'        => 'required|string|max:15',
            'email'        => 'required|email|max:255|unique:service_providers,email',
            'description'  => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Já existe um prestador de serviços com este email.',
        ];
    }
}