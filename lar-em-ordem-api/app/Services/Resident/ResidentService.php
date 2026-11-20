<?php
namespace App\Services\Resident;

use App\Models\User\Resident;
use App\Models\User\User;
use App\Services\Privacy\PersonalDataEraser;
use Illuminate\Support\Facades\DB;

class ResidentService
{
    public function __construct(private PersonalDataEraser $eraser) {}

    #region CRUD
    /**
     * Cria o perfil de residente de um utilizador e atribui-lhe a role 'resident'. Transação.
     *
     * @param array $data Dados validados (nif).
     * @param User $owner Utilizador dono do perfil.
     * @return Resident
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
     * Remove o perfil de residente (soft delete) e retira a role 'resident'. Transação.
     *
     * @param Resident $resident Perfil a remover.
     * @return void
     */
    public function delete(Resident $resident): void
    {
        DB::transaction(function () use ($resident) {
            $this->eraser->eraseResident($resident);
            $resident->delete();
            $resident->user->removeRole('resident');
        });
    }

    /**
     * Updates the resident profile with the validated data (nif), in a transaction.
     *
     * @param Resident $resident The profile, already found by the ProfileService.
     * @param array $data Validated data.
     * @return Resident
     */
    public function update(Resident $resident, array $data): Resident
    {
        return DB::transaction(function () use ($data, $resident) {
            $resident->update($data);
            return $resident->fresh();
        });
    }
    #endregion
}