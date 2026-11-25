<?php

namespace App\Http\Resources\Condominium;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CondominiumResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        // invite code and number of properties only for whoever manages the condominium
        $canManage = $this->resource->isManagedBy($user);

        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'reserve_fund'     => $this->reserve_fund,
            'can_manage'       => $canManage,
            'invite_code'      => $this->when($canManage, $this->invite_code),
            'properties_count' => $this->when($canManage, $this->whenCounted('properties')),
            'transfer_expires_at' => $this->when($canManage, $this->transfer_expires_at),
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

            // linked fractions (so the admin can remove a property),
            // only shown when the requester manages the condominium AND the properties were loaded;
            'properties'       => $this->when($canManage, fn () => $this->whenLoaded('properties', fn () => $this->properties->map(fn ($property) => [
                'id'       => $property->id,
                'door'     => $property->address?->door,
                'fraction' => $property->address?->fraction,
            ]))),
            'notices'          => CondominiumNoticeResource::collection($this->whenLoaded('notices')),
            'created_at'       => $this->created_at,
        ];
    }
}