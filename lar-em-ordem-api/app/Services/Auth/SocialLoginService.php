<?php

namespace App\Services\Auth;

use App\Models\User\SocialAccount;
use App\Models\User\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Decides which user signs in with Google/Microsoft.
 * Golden rule: the account is identified by the provider's id ("sub"), never by the email alone.
 */
class SocialLoginService
{
    public function __construct(private ProfileService $profiles) {}

    /**
     * The account for this identity: the linked one, or an existing one with the same verified email
     * (which gets linked). Null when there is no account yet, so the registration can be finished.
     *
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
     * Last step for someone who signed in with Google/Microsoft without an account: only now is the account
     * created, with the chosen profile. Name and email come from the identity kept on the server ($pending),
     * never from what the browser sends.
     *
     * @param array{provider: string, id: string, email: string, email_verified: bool, name: string} $pending
     * @param array<string, mixed> $data validated profile fields
     *
     * @throws SocialLoginException when that email or that Google/Microsoft account got registered in the meantime
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
            $this->profiles->create($user, $role, $data);

            return $user;
        });
    }

    /**
     * An account whose email isn't verified (e.g. changed in the profile and not confirmed yet) may not
     * belong to the email's owner. When the verified owner signs in, they get the account and everything
     * set up before (password, sessions, 2FA, passkeys, links) stops working.
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