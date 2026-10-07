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
        return $this->user()?->resident !== null;
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
            'address_id'           => ['nullable', 'string', 'exists:addresses,id'],
            'condominium_id'       => ['nullable', 'string', 'exists:condominia,id'],
            'area'                 => ['required', 'integer', 'min:1'],
            'fraction'             => ['required', 'string', 'max:50'],

            // Contract
            'resident_type_id'     => ['required', 'integer', 'exists:resident_types,id'],
            'start_date'           => ['required', 'date'],
            'end_date'             => ['nullable', 'date', 'after_or_equal:start_date'],

            // Address (Required if address_id is not provided)
            'address'              => ['required_without:address_id', 'array'],
            'address.street'       => ['required_with:address', 'string', 'max:255'],
            'address.postal_code'  => ['required_with:address', 'string', 'max:20'],
            'address.door'         => ['nullable', 'string', 'max:10'],
            'address.county'       => ['required', 'string', 'max:100'],
            'address.location'     => ['required', 'string', 'max:100'],
            'address.district'     => ['required', 'string', 'max:100'],
        ];
    }

    /**
     * Extract only Property data.
     */
    public function propertyData(): array
    {
        return $this->safe()->only([
            'property_type_id',
            'property_typology_id',
            'address_id',
            'condominium_id',
            'area',
            'fraction',
        ]);
    }

    /**
     * Extract only Contract data.
     */
    public function contractData(): array
    {
        return $this->safe()->only([
            'resident_type_id',
            'start_date',
            'end_date',
        ]);
    }

    /**
     * Extract only Address data.
     */
    public function addressData(): ?array
    {
        return $this->validated('address');
    }
}
