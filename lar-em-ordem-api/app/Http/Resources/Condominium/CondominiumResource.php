<?php

namespace App\Http\Resources\Condominium;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CondominiumResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        // código de convite e nº de casas só para quem gere o condomínio
        $canManage = $this->resource->isManagedBy($user) || (bool) $user?->hasRole('SU');

        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'reserve_fund'     => $this->reserve_fund,
            'can_manage'       => $canManage,
            'invite_code'      => $this->when($canManage, $this->invite_code),
            'properties_count' => $this->when($canManage, $this->whenCounted('properties')),
            'address'          => $this->whenLoaded('address', fn () => [
                'street'      => $this->address->street,
                'postal_code' => $this->address->postal_code,
                'door'        => $this->address->door,
                'county'      => $this->address->county,
                'location'    => $this->address->location,
                'district'    => $this->address->district,
            ]),
            'admin'            => $this->whenLoaded('admin', fn () => [
                'name'    => $this->admin->user?->name,
                'contact' => $this->admin->contact,
            ]),

            // frações ligadas (para o administrador poder retirar uma casa),
            // só aparece se quem pede gere o condomínio E as casas foram carregadas;
            'properties'       => $this->when($canManage, fn () => $this->whenLoaded('properties', fn () => $this->properties->map(fn ($property) => [
                'id'       => $property->id,
                'door'     => $property->address?->door,
                'fraction' => $property->address?->fraction,
            ]))),
            'created_at'       => $this->created_at,
        ];
    }
}