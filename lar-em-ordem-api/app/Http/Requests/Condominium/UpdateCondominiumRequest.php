<?php

namespace App\Http\Requests\Condominium;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCondominiumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                => ['sometimes', 'required', 'string', 'max:255'],
            'reserve_fund'        => ['sometimes', 'numeric', 'min:0', 'max:9999999999.99'],
            'regenerate_invite_code' => ['sometimes', 'boolean'],
            'address'             => ['sometimes', 'array'],
            'address.street'      => ['sometimes', 'required', 'string', 'max:255'],
            'address.postal_code' => ['sometimes', 'required', 'string', 'max:20'],
            'address.door'        => ['sometimes', 'nullable', 'string', 'max:10'],
            // concelho e distrito vêm juntos (para validar o par no catálogo de zonas)
            'address.county'      => [
                'required_with:address.district', 'string', 'max:100',
                Rule::exists('service_zones', 'county')
                    ->where('district', $this->input('address.district'))
                    ->whereNull('deleted_at'),
            ],
            'address.location'    => ['sometimes', 'required', 'string', 'max:100'],
            'address.district'    => [
                'required_with:address.county', 'string', 'max:100',
                Rule::exists('service_zones', 'district')->whereNull('deleted_at'),
            ],
            'condominium_admin_id' => [
                Rule::prohibitedIf(fn () => ! $this->user()?->hasRole('SU')),
                'sometimes',
                'ulid',
                Rule::exists('condominium_admins', 'id')->whereNull('deleted_at'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'    => 'Indique o nome do condomínio.',
            'reserve_fund.min' => 'O fundo comum de reserva não pode ser negativo.',
            'regenerate_invite_code.boolean' => 'O campo regenerate_invite_code tem de ser true ou false.',
            'condominium_admin_id.prohibited' => 'Para mudar de administrador use a transferência do condomínio.',
            'condominium_admin_id.exists'     => 'O administrador indicado não existe.',
            'address.county.exists'          => 'O concelho indicado não existe nesse distrito.',
            'address.county.required_with'   => 'Indique o concelho juntamente com o distrito.',
            'address.district.required_with' => 'Indique o distrito juntamente com o concelho.',
            'address.district.exists'        => 'O distrito indicado não existe.',
        ];
    }
}