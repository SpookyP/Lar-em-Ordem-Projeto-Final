<?php
namespace App\Services\Resident;

use App\Models\User\Resident;
use App\Models\User\User;
use App\Services\Privacy\PersonalDataEraser;
use Illuminate\Support\Facades\DB;

class ResidentService
{
    public function __construct(private PersonalDataEraser $eraser) {}

    /**
     * Creates the user's resident profile and gives them the 'resident' role.
     */
    public function create(array $data, User $owner): Resident
    {
        return DB::transaction(function () use ($data, $owner) {
            if (!$owner->hasRole('resident')) {
                $owner->assignRole('resident');
            }

            return $owner->resident()->create([
                'nif' => $data['nif'],
            ]);
        });
    }

    /**
     * Erases the resident's personal data, soft-deletes the profile and removes the 'resident' role.
     */
    public function delete(Resident $resident): void
    {
        DB::transaction(function () use ($resident) {
            $this->eraser->eraseResident($resident);
            $resident->delete();
            $resident->user->removeRole('resident');
        });
    }

    public function update(Resident $resident, array $data): Resident
    {
        return DB::transaction(function () use ($data, $resident) {
            $resident->update($data);
            return $resident->fresh();
        });
    }
}