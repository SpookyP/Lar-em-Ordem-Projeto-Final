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
            'property_type_id'     => ['sometimes', 'nullable', 'integer', 'exists:property_types,id'],
            'property_typology_id' => ['sometimes', 'nullable', 'integer', 'exists:property_typologies,id'],
            'address_id'           => ['sometimes', 'nullable', 'integer', 'exists:addresses,id'],
            'condominium_id'       => ['sometimes', 'nullable', 'integer', 'exists:condominia,id'],
            'area'                 => ['sometimes', 'nullable', 'integer', 'min:1'],
            'fraction'             => ['sometimes', 'nullable', 'string', 'max:50'],

            // Contract
            'resident_type_id'     => ['sometimes', 'nullable', 'integer', 'exists:resident_types,id'],
            'start_date'           => ['sometimes', 'nullable', 'date'],
            'end_date'             => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],

            // Address
            'address'              => ['sometimes', 'array'],
            'address.street'       => ['required_with:address', 'string', 'max:255'],
            'address.postal_code'  => ['required_with:address', 'string', 'max:20'],
            'address.door'         => ['nullable', 'string', 'max:10'],
            'address.county'       => ['nullable', 'string', 'max:100'],
            'address.location'     => ['nullable', 'string', 'max:100'],
            'address.district'     => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Extract only present Property data for update.
     */
    public function propertyData(): array
    {
        return array_filter($this->safe()->only([
            'property_type_id',
            'property_typology_id',
            'address_id',
            'condominium_id',
            'area',
            'fraction',
        ]), fn($value) => $value !== null);
    }

    /**
     * Extract only present Contract data for update.
     */
    public function contractData(): array
    {
        return array_filter($this->safe()->only([
            'resident_type_id',
            'start_date',
            'end_date',
        ]), fn($value) => $value !== null);
    }

    /**
     * Extract only Address data.
     */
    public function addressData(): ?array
    {
        return $this->validated('address');
    }
}
