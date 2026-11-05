<?php

namespace App\Services\Privacy;

use App\Models\User\SocialAccount;
use App\Models\User\User;
use App\Services\Auth\ProfileService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Deletes an account the RGPD way: every profile is emptied by its own module, then the user row
 * is anonymised instead of removed (foreign keys and other people's history keep working) and
 * everything that could still sign in as this user is revoked. The email becomes free to reuse.
 */
class AccountErasureService
{
    public function __construct(private ProfileService $profiles) {}

    /**
     * @throws \Illuminate\Validation\ValidationException when a module refuses (nothing is changed)
     */
    public function erase(User $user): void
    {
        DB::transaction(function () use ($user) {
            $this->profiles->deleteAll($user);
            $this->eraseUser($user);
        });
    }

    private function eraseUser(User $user): void
    {
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();
        DB::table('sessions')->where('user_id', $user->id)->delete();
        DB::table('passkeys')->where('user_id', $user->id)->delete();
        SocialAccount::withTrashed()->where('user_id', $user->id)->forceDelete();
        $user->tokens()->delete();
        $user->syncRoles([]);

        $user->forceFill([
            'name' => PersonalDataEraser::REMOVED_NAME,
            'email' => "removed-{$user->id}@anonymized.invalid",
            'password' => Str::random(64),
            'remember_token' => null,
            'email_verified_at' => null,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'anonymized_at' => now(),
        ])->save();

        $user->delete();
    }
}