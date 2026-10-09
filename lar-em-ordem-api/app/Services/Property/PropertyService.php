<?php

namespace App\Services\Property;

use App\Models\Property\Property;
use App\Models\Property\Address;
use App\Models\User\User;
use App\Models\Property\PropertyType;
use App\Models\Property\PropertyTypology;
use App\Models\User\ResidentType;
use App\Services\Resident\ResidentService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class PropertyService
{
    public function __construct(protected ResidentService $residentService) {}

    #region CRUD
    #region Get Types
    public function getPropertyForms(): array
    {
        return Cache::remember('properties.form_options', now()->addDay(), function () {
            return [
                'propertyTypes' => PropertyType::select('id', 'type')->get(),
                'propertyTypologies' => PropertyTypology::select('id', 'typology')->get(),
                'residentTypes' => ResidentType::select('id', 'type')->get(),
            ];
        });
    }
    #endregion

    #region Get Properties
    public function getResidentProperties(User $user, int $perPage = 5): LengthAwarePaginator
    {
        return Property::query()
            ->whereHas('contracts', function ($query) use ($user) {
                $query->where('resident_id', $user->resident?->id)
                    ->where('is_active', true);
            })
            ->with([
                'address:id,street,location',
                'propertyType:id,type',
                'propertyTypology:id,typology',
                'contracts' => fn($query) => $query->where('is_active', true)->with('residentType:id,type'),
            ])
            ->latest() // order by latest
            ->paginate($perPage);
    }

    #endregion
    public function createResidentProperty(User $user, array $propertyData, array $contractData, array $addressData): Property
    {
        return DB::transaction(function () use ($propertyData, $contractData, $addressData, $user) {
            $address = Address::create($addressData);
            $propertyData['address_id'] = $address->id;
            $property = Property::create($propertyData);
            $property->contracts()->create([
                'resident_id'      => $user->resident->id,
                'resident_type_id' => $contractData['resident_type_id'],
                'start_date'       => $contractData['start_date'] ?? now(),
                'end_date'         => $contractData['end_date'] ?? null,
                'is_active'        => true,
            ]);

            return $property->load([
                'address',
                'propertyType',
                'propertyTypology',
                'contracts' => fn ($q) => $q->where('is_active', true)->with('residentType'),
            ]);
        });
    }

    public function updateResidentProperty(Property $property, array $propertyData, array $contractData, ?array $addressData = null): Property
    {
        return DB::transaction(function () use ($propertyData, $contractData, $addressData, $property) {

            if (!empty($addressData) && $property->address) {
                $property->address->update($addressData);
            }

            if (!empty($propertyData)) {
                $property->update($propertyData);
            }

            if (!empty($contractData)) {
                $property->contracts()
                    ->where('is_active', true)
                    ->first()
                    ?->update($contractData);
            }


            return $property->fresh([
                'address',
                'propertyType',
                'propertyTypology',
                'contracts' => fn($query) => $query->where('is_active', true)->with('residentType'),
            ]);
        });
    }

    public function deleteResidentProperty(Property $property): bool
    {
        return DB::transaction(function () use ($property) {
            $property->contracts()
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'end_date' => now()
                ]);
            return $property->delete();
        });
    }

    public function terminateContract(Property $property, User $user): bool
    {
        return DB::transaction(function () use ($property, $user) {
            $contract = $property->contracts()
                ->where('resident_id', $user->resident->id)
                ->where('is_active', true)
                ->first();
            if (!$contract) {
                return false;
            }
            $contract->update([
                'is_active' => false,
                'end_date' => now()
            ]);
            return $contract->delete();
        });
    }
    #endregion
}
