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
 * Single entry point to create, update and remove a user's profiles (resident, service provider, partner,
 * condominium admin). This service decides WHICH profile; each module's service decides HOW
 * (role, relations, transactions).
 */
class ProfileService
{
    /** Roles that have a profile of their own. */
    public const ROLES =  ['resident', 'service_provider', 'partner', 'condominium_admin'];

    /** Strictly professional profile: it can't share an account with any other profile. */
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
     * The user's profile for one of the ROLES, or null if they don't have it.
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
     */
    public function get(User $user, string $role): Model
    {
        return $this->find($user, $role)
            ?? abort(403, 'Este utilizador não tem um perfil de ' . self::LABELS[$role] . '.');
    }

    /**
     * Creates the profile and assigns its role; 422 if the user already has it.
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
     * Updates the profile with validated data (without name, email or role); 422 if the user doesn't have it.
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
     * Removes the profile and its role; 422 if the user doesn't have it or it's the account's only profile.
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
     * Removes every profile of the account (used when the account is deleted), each module doing its own clean-up.
     * The "only profile" rule doesn't apply. If a module refuses (e.g. an admin who still manages condominiums),
     * nothing is deleted.
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

    /** Each module's service decides how its profile is removed. */
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
     * The partner profile is exclusive: a partner account can't have other profiles,
     * and an account with other profiles can't become a partner (422).
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