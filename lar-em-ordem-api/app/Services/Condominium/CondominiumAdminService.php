<?php

namespace App\Services\Condominium;

use App\Models\Condominium\CondominiumAdmin;
use App\Models\User\User;
use App\Services\Privacy\PersonalDataEraser;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CondominiumAdminService
{
    public function __construct(private PersonalDataEraser $eraser) {}

    /**
     * Cria o perfil de administrador e atribui a role 'condominium_admin'.
     * O "já tem este perfil" é verificado no ProfileService.
     *
     * @param array $data Dados validados (contact, admin_type).
     * @param User $owner Utilizador dono do perfil.
     * @return CondominiumAdmin
     */
    public function create(array $data, User $owner): CondominiumAdmin
    {
        return DB::transaction(function () use ($data, $owner) {
            $owner->assignRole('condominium_admin');

            return CondominiumAdmin::create(['user_id' => $owner->id] + $data);
        });
    }

    /**
     * Atualiza os dados do administrador.
     *
     * @param CondominiumAdmin $admin Perfil a atualizar.
     * @param array $data Dados validados.
     * @return CondominiumAdmin
     */
    public function update(CondominiumAdmin $admin, array $data): CondominiumAdmin
    {
        $admin->update($data);

        return $admin;
    }

    /**
     * Remove o perfil e retira a role. Falha (422) se ainda administrar condomínios:
     * o condomínio não pode ficar sem administrador — tem de ser transferido primeiro.
     *
     * @param CondominiumAdmin $admin Perfil a remover.
     * @return void
     */
    public function delete(CondominiumAdmin $admin): void
    {
        if ($admin->condominia()->exists()) {
            throw ValidationException::withMessages([
                'role' => ['Transfira primeiro os condomínios que administra.'],
            ]);
        }

        DB::transaction(function () use ($admin) {
            $this->eraser->eraseCondominiumAdmin($admin);
            $admin->delete();
            $admin->user->removeRole('condominium_admin');
        });
    }
}