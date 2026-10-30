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
 * Registo em dois tempos: primeiro guarda-se o pedido (na cache, com prazo) e envia-se um link por email;
 * a conta (utilizador + perfil) so e criada quando o link e aberto (em qualquer dispositivo).
 * O pedido vive na cache e expira sozinho: nao ha tabela nem limpeza a fazer.
 */
class PendingRegistrationService
{
    private const TTL_MINUTES = 10;

    // depois de confirmado, abrir o mesmo link outra vez continua a responder bem durante 1 hora
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
     * Guarda o pedido e envia o link. Um pedido novo para o mesmo email substitui o anterior (o link antigo deixa de valer).
     *
     * @param array{name: string, email: string, password: ?string, role: string, profile: array, provider: ?string, provider_user_id: ?string, expires_at?: int} $data
     *        password: ja com hash (ou null no registo social)
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
     * Novo link para um registo ainda nao confirmado. Nao diz se o email existe.
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
     * Cria a conta. Abrir o mesmo link outra vez nao faz mal: a conta ja existe e responde bem.
     *
     * @throws ValidationException link invalido/expirado, ou o email ja tem conta
     */
    public function confirm(string $token): void
    {
        $hash = hash('sha256', $token);

        if (Cache::has(self::doneKey($hash))) {
            return;
        }

        try {
            // o lock evita que dois pedidos ao mesmo tempo com o mesmo link criem a conta duas vezes
            Cache::lock('pending_registration:confirm:' . $hash, 10)
                ->block(5, fn () => $this->createAccount($hash));
        } catch (LockTimeoutException) {
            throw $this->invalid('Tente outra vez dentro de instantes.');
        }
    }

    private function createAccount(string $hash): void
    {
        // outro pedido acabou de criar a conta enquanto este esperava pelo lock
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

        DB::transaction(function () use ($row) {
            if ($row['provider'] !== null) {
                // registo com Microsoft: a conta fica ligada a essa identidade
                try {
                    $this->social->registerFromPending([
                        'provider' => $row['provider'],
                        'id' => $row['provider_user_id'],
                        'email' => $row['email'],
                        'email_verified' => true, // acabou de ser provado pelo link
                        'name' => $row['name'],
                    ], $row['role'], $row['profile'] ?? []);
                } catch (SocialLoginException) {
                    throw $this->invalid('Já existe uma conta com este email. Inicie sessão.');
                }

                return;
            }

            // a password ja vem com hash: o cast "hashed" do User nao a volta a encriptar
            $user = new User([
                'name' => $row['name'],
                'email' => $row['email'],
                'password' => $row['password'],
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();

            $this->profiles->create($user, $row['role'], $row['profile'] ?? []);
        });

        // os dados do pedido saem da cache logo; fica so a marca de "confirmado" durante 1 hora
        $this->forget($row['email']);
        Cache::put(self::doneKey($hash), true, self::DONE_SECONDS);
    }

    // apaga o pedido pendente deste email (e, com ele, o link que estava em vigor)
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
        $url = config('oauth.frontend_url') . '/verificar-email?' . http_build_query(['token' => $token]);

        try {
            Notification::route('mail', [$email => $name])
                ->notify(new ConfirmRegistration($name, $url, self::TTL_MINUTES));
        } catch (Throwable $e) {
            // o pedido fica guardado: a pessoa pode pedir outro email no ecra de confirmacao
            report($e);
        }
    }

    private function invalid(string $message): ValidationException
    {
        return ValidationException::withMessages(['token' => [$message]]);
    }
}