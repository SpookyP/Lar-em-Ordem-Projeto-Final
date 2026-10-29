<?php

namespace App\Services\Auth;

use App\Models\User\User;
use App\Services\PartnerOffer\PartnerService;
use App\Services\Resident\ResidentService;
use App\Services\ServiceProvider\ServiceProviderService;
use App\Services\Condominium\CondominiumAdminService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * Ponto único para criar, atualizar e remover os perfis de um utilizador (residente, prestador, parceiro, administrador de condomínio).
 * Este service decide QUE perfil; o service de cada módulo decide COMO (role, relações, transações).
 */
class ProfileService
{
    /** Roles com perfil próprio  */
    public const ROLES =  ['resident', 'service_provider', 'partner', 'condominium_admin'];

    public function __construct(
        private ResidentService $residentService,
        private ServiceProviderService $serviceProviderService,
        private PartnerService $partnerService,
        private CondominiumAdminService $condominiumAdminService,
    ) {}

    /**
     * Devolve o perfil do utilizador para um role, ou null se não o tiver.
     *
     * @param User $user Utilizador.
     * @param string $role Um dos ROLES.
     * @return Model|null
     */
    public function find(User $user, string $role): ?Model
    {
        return match ($role) {
            'resident'         => $user->resident()->first(),
            'service_provider' => $user->service_provider()->first(),
            'partner'          => $user->partner()->first(),
            'condominium_admin' => $user->condominium_admin()->first(),
        };
    }

    /**
     * Cria o perfil (e atribui a role). Falha (422) se o utilizador já o tiver.
     *
     * @param User $user Dono do perfil.
     * @param string $role Um dos ROLES.
     * @param array $data Dados validados do perfil.
     * @return Model O perfil criado.
     */
    public function create(User $user, string $role, array $data): Model
    {
        if ($this->find($user, $role)) {
            throw ValidationException::withMessages([
                'role' => ["Esta conta já tem um perfil de {$role} associado."],
            ]);
        }

        return match ($role) {
            'resident'         => $this->residentService->create($data,$user),
            'service_provider' => $this->serviceProviderService->create($data, $user),
            'partner'          => $this->partnerService->create($data, $user),
            'condominium_admin' => $this->condominiumAdminService->create($data, $user),
        };
    }

    /**
     * Atualiza o perfil com os dados validados. Falha (422) se o utilizador não o tiver.
     *
     * @param User $user Dono do perfil.
     * @param string $role Um dos ROLES.
     * @param array $data Dados validados do perfil (sem name, email, role).
     * @return Model O perfil atualizado.
     */
    public function update(User $user, string $role, array $data): Model
    {
        $profile = $this->findOrFail($user, $role);

        return match ($role) {
            'resident'         =>$this->residentService->updateResident($user->id, $data),
            'service_provider' => $this->serviceProviderService->update($profile, $data),
            'partner'          => $this->partnerService->update($profile, $data),
            'condominium_admin' => $this->condominiumAdminService->update($profile, $data),
        };
    }

    /**
     * Remove o perfil (e retira a role). Falha (422) se não o tiver ou se for o único perfil da conta.
     *
     * @param User $user Dono do perfil.
     * @param string $role Um dos ROLES.
     * @return void
     */
    public function delete(User $user, string $role): void
    {
        $profile = $this->findOrFail($user, $role);

        if ($user->roles()->count() <= 1) {
            throw ValidationException::withMessages([
                'role' => ['Não pode remover o seu único perfil. Elimine a conta.'],
            ]);
        }

        match ($role) {
            'resident'         => $this->residentService->delete($profile),
            'service_provider' => $this->serviceProviderService->delete($profile),
            'partner'          => $this->partnerService->delete($profile),
            'condominium_admin' => $this->condominiumAdminService->delete($profile),
        };
    }

    private function findOrFail(User $user, string $role): Model
    {
        return $this->find($user, $role) ?? throw ValidationException::withMessages([
            'role' => ["Não tem um perfil de {$role} associado a esta conta."],
        ]);
    }
}