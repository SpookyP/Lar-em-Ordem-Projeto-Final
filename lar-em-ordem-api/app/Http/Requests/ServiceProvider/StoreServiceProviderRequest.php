<?php

namespace App\Http\Requests\ServiceProvider;


use Illuminate\Foundation\Http\FormRequest;
use Override;

class StoreServiceProviderRequest extends FormRequest
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
            'company_name' => 'required|string|max:255',
            'nif'          => 'required|string|max:9|unique:service_providers,nif',
            'phone'        => 'required|string|max:15',
            'email'        => 'required|email|max:255',
            'description'  => 'required|string',  
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'nif.unique' => 'Já existe um prestador de serviços com este NIF.',
        ];
    }
}
