<?php
namespace App\Services\Resident;

use App\Models\User\Resident;
use App\Models\User\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Uid\Ulid;

class ResidentService
{
    #region CRUD
    #region Get Resident
    public function getResidentByUserId(Ulid $userId){
        // o Ulid do Symfony escreve-se em MAIÚSCULAS; os ids gravados pelo Laravel estão em minúsculas
        $resident = Resident::query()
            ->where('user_id', strtolower((string) $userId))
            ->first();

        if(!$resident){
            abort(404, 'Residente não encontrado no perfil do utilizador.');
        }
        return $resident;
    }
    #endregion

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
            $resident->delete();
            $resident->user->removeRole('resident');
        });
    }

    public function updateResident(string|Ulid $userId, array $data): Resident
    {
        $ulid = is_string($userId) ? new Ulid($userId) : $userId;
        $resident = $this->getResidentByUserId($ulid);
        return DB::transaction(function () use ($data, $resident) {
            $resident->update($data);
            return $resident->fresh();
        });
    }
    #endregion
}