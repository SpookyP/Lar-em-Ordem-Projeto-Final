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
            'provider_email'        => 'nullable|email|max:255',
            'description'  => 'required|string',
        ];
    }
}