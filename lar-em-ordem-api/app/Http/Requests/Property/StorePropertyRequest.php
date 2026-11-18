<?php

namespace App\Http\Requests\Property;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'address_id'           => ['prohibited'],
            'area'                 => ['required', 'integer', 'min:1'],

            // Contract
            'resident_type_id'     => ['required', 'integer', 'exists:resident_types,id'],
            'start_date'           => ['required', 'date'],
            'end_date'             => ['nullable', 'date', 'after_or_equal:start_date'],

            // Address (Required if address_id is not provided)
            'address'              => ['required', 'array'],
            'address.street'       => ['required_with:address', 'string', 'max:255'],
            'address.postal_code'  => ['required_with:address', 'string', 'max:20'],
            'address.door'         => ['nullable', 'string', 'max:10'],
            'address.fraction'             => ['nullable', 'string', 'max:50'],
            'address.county'       => [
                'required', 'string', 'max:100',
                Rule::exists('service_zones', 'county')
                    ->where('district', $this->input('address.district'))
                    ->whereNull('deleted_at'),
            ],
            'address.location'     => ['required', 'string', 'max:100'],
            'address.district'     => [
                'required', 'string', 'max:100',
                Rule::exists('service_zones', 'district')->whereNull('deleted_at'),
            ],
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
            'area',
        ]);
    }

    public function messages(): array
    {
        return [
            'address_id.prohibited' => 'A morada não pode ser escolhida: envia os dados da morada em "address".',
            'end_date.after_or_equal' => 'A data de fim não pode ser anterior à data de início.',
            'address.county.exists' => 'O concelho indicado não existe nesse distrito.',
            'address.district.exists' => 'O distrito indicado não existe.',
        ];
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
