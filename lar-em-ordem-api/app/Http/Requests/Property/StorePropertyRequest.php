<?php

namespace App\Http\Requests\Property;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePropertyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; //middleware handled
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
            'property_type_id'     => ['required', 'integer', 'exists:tipo_habitacao,id'],
            'property_typology_id' => ['required', 'integer', 'exists:tipologia_habitacao,id'],
            'address_id'           => ['required', 'integer', 'exists:morada,id'],
            'condominium_id'       => ['nullable', 'integer', 'exists:condominio,id'],
            'area'                 => ['required', 'integer', 'min:1'],
            'fraction'             => ['nullable', 'string', 'max:50'], //max?? or min??

            // Contract
            'resident_type_id'     => ['required', 'integer', 'exists:tipo_morador,id'],
            'start_date'           => ['nullable', 'date'],
            'end_date'             => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }
}
