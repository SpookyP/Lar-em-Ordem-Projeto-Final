<?php

namespace App\Services\Property;

use App\Models\Property\Property;
use App\Models\Property\Address;
use App\Models\User\User;
use App\Models\Property\PropertyType;
use App\Models\Property\PropertyTypology;
use App\Models\User\ResidentType;
use App\Services\Privacy\PersonalDataEraser;
use App\Services\ServiceProvider\AssistanceRequestService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class PropertyService
{
    public function __construct(
        private PersonalDataEraser $eraser,
        private AssistanceRequestService $requests,
    ) {}

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
    /**
     * Loads what the home screens show: address, type, typology and the active contract with its type.
     */
    public function withDetails(Property $property): Property
    {
        return $property->load([
            'address',
            'propertyType',
            'propertyTypology',
            'contracts' => fn ($query) => $query->active()->with('residentType'),
        ]);
    }

    public function getResidentProperties(User $user, int $perPage = 5): LengthAwarePaginator
    {
        return Property::query()
            ->whereHas('contracts', function ($query) use ($user) {
                $query->where('resident_id', $user->resident?->id)
                    ->active();
            })
            ->with([
                'address:id,fraction,street,location',
                'propertyType:id,type',
                'propertyTypology:id,typology',
                'contracts' => fn($query) => $query->active()->with('residentType:id,type'),
            ])
            ->latest() // order by latest
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
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

            return $this->withDetails($property);
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
                    ->active()
                    ->first()
                    ?->update($contractData);
            }

            return $this->withDetails($property->refresh());
        });
    }

    /**
     * Deletes a resident's property (RGPD): the contract ends, open assistance requests are
     * cancelled, and the eraser removes the invoices, vault documents and alerts (with their
     * files) and anonymises the address before the soft delete.
     */
    public function deleteResidentProperty(Property $property): void
    {
        DB::transaction(function () use ($property) {
            $property->contracts()
                ->active()
                ->update([
                    'is_active' => false,
                    'end_date' => now()
                ]);
            $this->requests->cancelOpenForProperty($property);
            $this->eraser->eraseProperty($property);
        });
    }
    #endregion
}