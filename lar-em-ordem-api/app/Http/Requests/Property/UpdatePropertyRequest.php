<?php

namespace App\Http\Requests\Property;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePropertyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // middlewareHandled
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Property
            'property_type_id'     => ['required', 'integer', 'exists:property_types,id'],
            'property_typology_id' => ['required', 'integer', 'exists:property_typologies,id'],
            'address_id'           => ['required', 'integer', 'exists:addresses,id'],
            'condominium_id'       => ['nullable', 'integer', 'exists:condominia,id'],
            'area'                 => ['required', 'integer', 'min:1'],
            'fraction'             => ['nullable', 'string', 'max:50'],

            // Contract
            'resident_type_id'     => ['required', 'integer', 'exists:resident_types,id'],
            'start_date'           => ['required', 'date'],
            'end_date'             => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }
}
