<?php

namespace App\Services\Auth;

use App\Http\Requests\Condominium\StoreCondominiumAdminRequest;
use App\Http\Requests\Condominium\UpdateCondominiumAdminRequest;
use App\Http\Requests\PartnerOffer\StorePartnerRequest;
use App\Http\Requests\PartnerOffer\UpdatePartnerRequest;
use App\Http\Requests\Resident\StoreResidentRequest;
use App\Http\Requests\Resident\UpdateResidentRequest;
use App\Http\Requests\ServiceProvider\StoreServiceProviderRequest;
use App\Http\Requests\ServiceProvider\UpdateServiceProviderRequest;
use App\Models\User\User;
use Illuminate\Foundation\Http\FormRequest;
use App\Services\PartnerOffer\PartnerService;
use App\Services\Resident\ResidentService;
use App\Services\ServiceProvider\ServiceProviderService;
use App\Services\Condominium\CondominiumAdminService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

/**
 * Ponto único para criar, atualizar e remover os perfis de um utilizador (residente, prestador, parceiro, administrador de condomínio).
 * Este service decide QUE perfil; o service de cada módulo decide COMO (role, relações, transações).
 */
class ProfileService
{
    /** Roles com perfil próprio  */
    public const ROLES =  ['resident', 'service_provider', 'partner', 'condominium_admin'];

    /** Perfil estritamente profissional: não pode existir com nenhum outro perfil na mesma conta. */
    public const EXCLUSIVE_ROLE = 'partner';

    /** Portuguese name of each profile, for the 403 in get(). */
    private const LABELS = [
        'resident'          => 'residente',
        'service_provider'  => 'prestador de serviços',
        'partner'           => 'parceiro',
        'condominium_admin' => 'administrador de condomínio',
    ];

    public function __construct(
        private ResidentService $residentService,
        private ServiceProviderService $serviceProviderService,
        private PartnerService $partnerService,
        private CondominiumAdminService $condominiumAdminService,
    ) {}

    /**
     * The profile's FormRequest, used outside a request for its rules and its Portuguese messages:
     * the sign-up, the Google/Microsoft sign-up, adding a profile and editing it all share them.
     *
     * @param string $role One of the ROLES (anything else gives null, so the "role" rule fails on its own).
     * @param bool $update The edit rules instead of the creation ones.
     * @param User|null $user Who the rules are for (some rules ignore the user's own record).
     */
    public static function requestFor(string $role, bool $update = false, ?User $user = null): ?FormRequest
    {
        $class = match ($role) {
            'resident'          => $update ? UpdateResidentRequest::class : StoreResidentRequest::class,
            'service_provider'  => $update ? UpdateServiceProviderRequest::class : StoreServiceProviderRequest::class,
            'partner'           => $update ? UpdatePartnerRequest::class : StorePartnerRequest::class,
            'condominium_admin' => $update ? UpdateCondominiumAdminRequest::class : StoreCondominiumAdminRequest::class,
            default             => null,
        };

        if (!$class) {
            return null;
        }

        $request = new $class();

        if ($user) {
            $request->setUserResolver(fn () => $user);
        }

        return $request;
    }

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
     * The user's profile for a role, for controllers that act as that profile; 403 if they don't have it.
     *
     * @param User $user Authenticated user.
     * @param string $role One of the ROLES.
     * @return Model
     */
    public function get(User $user, string $role): Model
    {
        return $this->find($user, $role)
            ?? abort(403, 'Este utilizador não tem um perfil de ' . self::LABELS[$role] . '.');
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

        $this->assertExclusiveRole($user, $role);

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
            'resident'         => $this->residentService->update($profile, $data),
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

        $this->matchDeleteProfile($role, $profile);
    }
    
    /**
     * Remove todos os perfis da conta (usado ao apagar a conta): cada módulo faz a sua limpeza.
     * Não tem a regra do "único perfil". Se um módulo recusar (ex.: administrador com condomínios), nada é apagado.
     *
     * @param User $user Conta a limpar.
     * @return void
     */
    public function deleteAll(User $user): void
    {
        DB::transaction(function () use ($user) {
            // reversed so condominium_admin (the only profile that can refuse) is checked
            // before any other profile's data is erased
            foreach (array_reverse(self::ROLES) as $role) {
                if ($profile = $this->find($user, $role)) {
                    $this->matchDeleteProfile($role, $profile);
                }
            }
        });
    }

    /** O service de cada módulo decide COMO o perfil é removido. */
    private function matchDeleteProfile(string $role, Model $profile): void
    {
        match ($role) {
            'resident'          => $this->residentService->delete($profile),
            'service_provider'  => $this->serviceProviderService->delete($profile),
            'partner'           => $this->partnerService->delete($profile),
            'condominium_admin' => $this->condominiumAdminService->delete($profile),
        };
    }

    private function findOrFail(User $user, string $role): Model
    {
        return $this->find($user, $role) ?? throw ValidationException::withMessages([
            'role' => ["Não tem um perfil de {$role} associado a esta conta."],
        ]);
    }

     /**
     * O perfil de parceiro é exclusivo: uma conta de parceiro não pode ter outros perfis,
     * e uma conta com outros perfis não pode passar a ter o de parceiro (422).
     */
    private function assertExclusiveRole(User $user, string $role): void
    {
        // only counts profiles (ignores roles that aren't profiles)
        $profiles = $user->getRoleNames()->intersect(self::ROLES);

        if ($role === self::EXCLUSIVE_ROLE && $profiles->isNotEmpty()) {
            throw ValidationException::withMessages([
                'role' => ['O perfil de parceiro é exclusivo e esta conta já tem outros perfis. Crie uma conta separada para o parceiro.'],
            ]);
        }

        if ($role !== self::EXCLUSIVE_ROLE && $profiles->contains(self::EXCLUSIVE_ROLE)) {
            throw ValidationException::withMessages([
                'role' => ['Uma conta de parceiro não pode ter outros perfis. Use uma conta separada.'],
            ]);
        }
    }
}