<?php

namespace App\Http\Requests\Property;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->resident !== null;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        // a date left out of the request is compared with the one saved in the active contract
        $contract = $this->route('property')?->contracts()->where('is_active', true)->first();
        $start = $this->filled('start_date') ? 'start_date' : $contract?->start_date?->toDateString();
        $end = $this->filled('end_date') ? 'end_date' : $contract?->end_date?->toDateString();

        return [
            // Property
            'property_type_id'     => ['sometimes', 'nullable', 'integer', 'exists:property_types,id'],
            'property_typology_id' => ['sometimes', 'nullable', 'integer', 'exists:property_typologies,id'],
            'address_id'           => ['prohibited'],
            'area'                 => ['sometimes', 'nullable', 'integer', 'min:1'],

            // Contract
            'resident_type_id'     => ['sometimes', 'nullable', 'integer', 'exists:resident_types,id'],
            'start_date'           => ['sometimes', 'nullable', 'date', ...($end ? ["before_or_equal:{$end}"] : [])],
            'end_date'             => ['sometimes', 'nullable', 'date', ...($start ? ["after_or_equal:{$start}"] : [])],

            // Address
            'address'              => ['sometimes', 'array'],
            'address.street'       => ['required_with:address', 'string', 'max:255'],
            'address.postal_code'  => ['required_with:address', 'string', 'max:20'],
            'address.door'         => ['nullable', 'string', 'max:10'],
            'address.fraction'     => ['nullable', 'string', 'max:50'],
            'address.county'       => [
                'sometimes', 'required', 'string', 'max:100',
                Rule::exists('service_zones', 'county')
                    ->where('district', $this->input('address.district'))
                    ->whereNull('deleted_at'),
            ],
            'address.location'     => ['sometimes', 'required', 'string', 'max:100'],
            'address.district'     => [
                'required_with:address.county', 'string', 'max:100',
                Rule::exists('service_zones', 'district')->whereNull('deleted_at'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'address_id.prohibited' => 'A morada não pode ser escolhida: envia os dados da morada em "address".',
            'start_date.before_or_equal' => 'A data de início não pode ser depois da data de fim.',
            'end_date.after_or_equal' => 'A data de fim não pode ser anterior à data de início.',
            'address.county.exists' => 'O concelho indicado não existe nesse distrito.',
            'address.district.exists' => 'O distrito indicado não existe.',
        ];
    }

    /**
     * Only the fields that were sent, so a missing one doesn't overwrite what is saved.
     */
    public function propertyData(): array
    {
        return array_filter($this->safe()->only([
            'property_type_id',
            'property_typology_id',
            'area',
        ]), fn($value) => $value !== null);
    }

    /**
     * Only the fields that were sent, so a missing one doesn't overwrite what is saved.
     */
    public function contractData(): array
    {
        return array_filter($this->safe()->only([
            'resident_type_id',
            'start_date',
            'end_date',
        ]), fn($value) => $value !== null);
    }

    public function addressData(): ?array
    {
        return $this->validated('address');
    }
}