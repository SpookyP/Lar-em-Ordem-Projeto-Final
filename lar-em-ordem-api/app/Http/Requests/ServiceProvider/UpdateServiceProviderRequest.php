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
        return false;
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
            'nif' => [
                'sometimes', 'required', 'string', 'max:9',
                Rule::unique('service_providers', 'nif')->ignore($this->route('service_provider')),
            ],
            'phone'       => 'sometimes|required|string|max:30',
            'email'       => 'sometimes|required|email|max:255',
            'description' => 'sometimes|required|string',
            'active'      => 'sometimes|boolean',
        ];
    }
}
