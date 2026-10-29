<?php

namespace App\Http\Requests\Condominium;

use Illuminate\Foundation\Http\FormRequest;

class LeaveCondominiumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'property_id' => ['required', 'ulid', 'exists:properties,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'property_id.required' => 'Indique a casa.',
            'property_id.exists'   => 'A casa indicada não existe.',
        ];
    }
}