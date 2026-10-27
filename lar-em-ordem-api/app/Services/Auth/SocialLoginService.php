<?php

namespace App\Services\Auth;

use App\Models\User\SocialAccount;
use App\Models\User\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Decide que utilizador entra quando alguem faz login com Google/Microsoft.
 * Regra de ouro: a conta e identificada pelo id do fornecedor ("sub"), nunca so pelo email.
 */
class SocialLoginService
{
    /**
     * @param array{id: string, email: ?string, email_verified: bool, name: string} $profile
     *
     * @throws SocialLoginException
     */
    public function resolveUser(string $provider, array $profile): ?User
    {
        $linked = SocialAccount::where('provider', $provider)
            ->where('provider_user_id', $profile['id'])
            ->first();

        if ($linked) {
            $user = User::withTrashed()->find($linked->user_id);

            if (!$user || $user->trashed()) {
                throw new SocialLoginException('conta_desativada');
            }

            return $user;
        }
        if ($profile['email'] === null) {
            throw new SocialLoginException('sem_email');
        }

        $existing = User::withTrashed()->where('email', $profile['email'])->first();

        if (!$existing) {
            return null;
        }

        if ($existing->trashed()) {
            throw new SocialLoginException('conta_desativada');
        }

        if (!$profile['email_verified']) {
            throw new SocialLoginException('conta_existente');
        }

        return DB::transaction(function () use ($existing, $provider, $profile) {
            if ($existing->email_verified_at === null) {
                $this->takeOver($existing);
            }

            $this->link($existing, $provider, $profile);

            return $existing;
        });
    }

    /**
     * Fim do registo de quem entrou com Google/Microsoft e ainda nao tinha conta.
     * So agora a conta nasce, ja com o perfil escolhido. O nome e o email vem da identidade
     * guardada no servidor ($pending), nunca do que o browser envia.
     *
     * @param array{provider: string, id: string, email: string, email_verified: bool, name: string} $pending
     * @param array $data campos do perfil, ja validados
     *
     * @throws SocialLoginException se, entretanto, esse email ou essa conta Google/Microsoft ja ficaram registados
     */
    public function registerFromPending(array $pending, string $role, array $data): User
    {
        return DB::transaction(function () use ($pending, $role, $data) {
            $emailTaken = User::withTrashed()->where('email', $pending['email'])->exists();
            $linkTaken = SocialAccount::withTrashed()
                ->where('provider', $pending['provider'])
                ->where('provider_user_id', $pending['id'])
                ->exists();

            if ($emailTaken || $linkTaken) {
                throw new SocialLoginException('conta_existente');
            }

            $user = new User([
                'name' => $pending['name'],
                'email' => $pending['email'],
                'password' => Str::random(64),
            ]);
            $user->forceFill(['email_verified_at' => $pending['email_verified'] ? now() : null]);
            $user->save();

            $this->link($user, $pending['provider'], $pending);
            $user->assignRole($role);
            $this->createProfile($user, $role, $data);

            return $user;
        });
    }

    /**
     * Os mesmos campos que o registo normal (CreateNewUser) guarda para cada tipo de conta.
     */
    private function createProfile(User $user, string $role, array $data): void
    {
        match ($role) {
            'resident' => $user->resident()->create([
                'nif' => $data['nif'],
            ]),

            'partner' => $user->partner()->create([
                'company_name' => $data['company_name'],
                'nif' => $data['nif'],
                'phone' => $data['phone'],
                'website' => $data['website'] ?? null,
                'description' => $data['description'],
            ]),

            'service_provider' => $user->service_provider()->create([
                'company_name' => $data['company_name'],
                'nif' => $data['nif'],
                'phone' => $data['phone'],
                'provider_email' => $data['provider_email'] ?? null,
                'description' => $data['description'],
            ]),
        };
    }

    /**
     * A app nao verifica emails no registo, por isso quem criou a conta com password
     * pode nao ser o dono do email. Quando o dono verificado aparece, fica com a conta
     * e tudo o que foi criado antes (password, sessoes, 2FA, chaves, ligacoes) deixa de valer.
     */
    private function takeOver(User $user): void
    {
        $user->forceFill([
            'password' => Str::random(64),
            'remember_token' => Str::random(60),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'email_verified_at' => now(),
        ])->save();

        SocialAccount::withTrashed()->where('user_id', $user->id)->forceDelete();
        DB::table('passkeys')->where('user_id', $user->id)->delete();
        DB::table('sessions')->where('user_id', $user->id)->delete();
        $user->tokens()->delete();
    }

    private function link(User $user, string $provider, array $profile): void
    {
        SocialAccount::create([
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_user_id' => $profile['id'],
            'email' => $profile['email'],
        ]);
    }
}