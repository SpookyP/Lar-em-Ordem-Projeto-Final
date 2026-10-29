<?php

namespace App\Services\Condominium;

use App\Models\Condominium\CondominiumAdmin;
use App\Models\User\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CondominiumAdminService
{
    /**
     * Procura o perfil de administrador de condomínio de um utilizador.
     *
     * @param User $user Utilizador a procurar.
     * @return CondominiumAdmin|null
     */
    public function findByUser(User $user): ?CondominiumAdmin
    {
        return CondominiumAdmin::where('user_id', $user->id)->first();
    }

    /**
     * Devolve o perfil de administrador do utilizador (com o nº de condomínios); 403 se não existir.
     *
     * @param User $user Utilizador a procurar.
     * @return CondominiumAdmin
     */
    public function getByUser(User $user): CondominiumAdmin
    {
        $admin = $this->findByUser($user)
            ?? abort(403, 'Este utilizador não tem um perfil de administrador de condomínio.');

        return $admin->loadCount('condominia');
    }
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
            $admin->delete();
            $admin->user->removeRole('condominium_admin');
        });
    }
}