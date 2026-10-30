<?php

namespace App\Services\Auth;

use App\Models\User\User;
use App\Notifications\ConfirmRegistration;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Two-step registration: the request is stored in the cache and a link is emailed. The account
 * (user + profile) is only created when the link is opened, on any device. The cache entry expires
 * on its own, so there is no table to clean up.
 */
class PendingRegistrationService
{
    private const TTL_MINUTES = 10;

    // opening an already confirmed link keeps answering fine for an hour
    private const DONE_SECONDS = 3600;

    public function __construct(
        private SocialLoginService $social,
        private ProfileService $profiles,
    ) {}

    public static function tokenKey(string $hash): string
    {
        return 'pending_registration:token:' . $hash;
    }

    public static function emailKey(string $email): string
    {
        return 'pending_registration:email:' . sha1(Str::lower($email));
    }

    private static function doneKey(string $hash): string
    {
        return 'pending_registration:done:' . $hash;
    }

    /**
     * Stores the request and emails the link. A new request for the same email replaces the old one,
     * and every request, resends included, gets a fresh TTL_MINUTES window.
     *
     * @param array{name: string, email: string, password: ?string, role: string, profile: array, provider: ?string, provider_user_id: ?string} $data
     *        password is already hashed (null for social registrations)
     */
    public function start(array $data): void
    {
        $this->forget($data['email']);

        $seconds = self::TTL_MINUTES * 60;
        $data['expires_at'] ??= now()->addMinutes(self::TTL_MINUTES)->getTimestamp();

        if ($seconds <= 0) {
            return;
        }

        $token = Str::random(64);
        $hash = hash('sha256', $token);

        Cache::put(self::tokenKey($hash), $data, $seconds);
        Cache::put(self::emailKey($data['email']), $hash, $seconds);

        $this->send($data['email'], $data['name'], $token);
    }

    /**
     * Sends a new link for a registration that is still unconfirmed, without saying whether the email exists.
     */
    public function resend(string $email): void
    {
        $hash = Cache::get(self::emailKey($email));
        $data = is_string($hash) ? Cache::get(self::tokenKey($hash)) : null;

        if (!is_array($data)) {
            return;
        }

        $this->start($data);
    }

    /**
     * Creates the account and returns the user so the caller can sign them in. Opening the same link
     * again is harmless: the account already exists, so it returns null and only the first opener gets a session.
     *
     * @throws ValidationException if the link is invalid or expired, or the email already has an account
     */
    public function confirm(string $token): void
    {
        $hash = hash('sha256', $token);

        if (Cache::has(self::doneKey($hash))) {
            return;
        }

        try {
            Cache::lock('pending_registration:confirm:' . $hash, 10)
                ->block(5, fn () => $this->createAccount($hash));
        } catch (LockTimeoutException) {
            throw $this->invalid('Tente outra vez dentro de instantes.');
        }
    }

    private function createAccount(string $hash): void
    {
        if (Cache::has(self::doneKey($hash))) {
            return;
        }

        $row = Cache::get(self::tokenKey($hash));

        if (!is_array($row) || ($row['expires_at'] ?? 0) <= now()->getTimestamp()) {
            throw $this->invalid('Link inválido ou expirado.');
        }

        if (User::withTrashed()->where('email', $row['email'])->exists()) {
            throw $this->invalid('Já existe uma conta com este email. Inicie sessão.');
        }

        $user = DB::transaction(function () use ($row) {
            if ($row['provider'] !== null) {
                try {
                    $this->social->registerFromPending([
                        'provider' => $row['provider'],
                        'id' => $row['provider_user_id'],
                        'email' => $row['email'],
                        'email_verified' => true,
                        'name' => $row['name'],
                    ], $row['role'], $row['profile'] ?? []);
                } catch (SocialLoginException) {
                    throw $this->invalid('Já existe uma conta com este email. Inicie sessão.');
                }
            }

            // the password is already hashed; the "hashed" cast on User does not hash it again
            $user = new User([
                'name' => $row['name'],
                'email' => $row['email'],
                'password' => $row['password'],
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();

            $this->profiles->create($user, $row['role'], $row['profile'] ?? []);

            return $user;
        });

        $this->forget($row['email']);
        Cache::put(self::doneKey($hash), true, self::DONE_SECONDS);

        return $user;
    }

    // removes the pending request for this email, and with it the link that was valid
    private function forget(string $email): void
    {
        $old = Cache::get(self::emailKey($email));

        if (is_string($old)) {
            Cache::forget(self::tokenKey($old));
        }

        Cache::forget(self::emailKey($email));
    }

    private function send(string $email, string $name, string $token): void
    {
        $url = config('oauth.frontend_url') . '/verify-email?' . http_build_query(['token' => $token]);

        try {
            Notification::route('mail', [$email => $name])
                ->notify(new ConfirmRegistration($name, $url, self::TTL_MINUTES));
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function invalid(string $message): ValidationException
    {
        return ValidationException::withMessages(['token' => [$message]]);
    }
}