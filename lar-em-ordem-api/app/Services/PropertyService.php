<?php
namespace App\Services;

use App\Models\Property;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PropertyService
{
    public function getUserProperties(int $residentId, int $perPage = 5):LengthAwarePaginatior
    {
        return Property::query()
            ->whereHas('contracts', function ($query) use ($residentId) {
                $query->where('resident_id', $residentId)
                      ->where('is_active',true);
            })
            ->with(['address','propertyType','propertyTypology'])
            ->latest()
            ->paginate($perPage);
    }
}