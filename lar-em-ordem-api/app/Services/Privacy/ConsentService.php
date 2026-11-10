<?php

namespace App\Services\Privacy;

use App\Models\User\User;
use App\Models\User\UserConsent;

/**
 * Consent (RGPD art. 7): the privacy policy is accepted at sign-up, sharing consumption data for
 * the benchmarks is opt-in, and every answer is stored with the policy version and where it came from.
 */
class ConsentService
{
    public const PRIVACY_POLICY = 'privacy_policy';
    public const DATA_SHARING = 'data_sharing';

    public const TYPES = [self::PRIVACY_POLICY, self::DATA_SHARING];

    /** Fields the sign-up forms send; they never reach the profile. */
    public const SIGN_UP_FIELDS = ['accept_privacy', 'data_sharing'];

    /**
     * Sign-up rules. A new account must accept the policy; an existing account adding a profile
     * already did, so for it both fields are optional.
     */
    public static function signUpRules(bool $existingAccount = false): array
    {
        return [
            'accept_privacy' => $existingAccount ? ['sometimes', 'accepted'] : ['accepted'],
            'data_sharing' => ['sometimes', 'boolean'],
        ];
    }

    public static function signUpMessages(): array
    {
        return [
            'accept_privacy.accepted' => 'Tem de aceitar a Política de Privacidade para criar a conta.',
            'data_sharing.boolean' => 'Resposta inválida à partilha de dados.',
        ];
    }

    /**
     * The sign-up answers in a form that can wait in the cache until the account exists
     * (pending registrations), with the policy version that was shown at that moment.
     */
    public static function fromSignUp(array $validated): array
    {
        return [
            'version' => config('privacy.policy_version'),
            'privacy_policy' => (bool) ($validated['accept_privacy'] ?? false),
            'data_sharing' => array_key_exists('data_sharing', $validated) ? (bool) $validated['data_sharing'] : null,
        ];
    }

    /** Records the answers given at sign-up. Data sharing is only recorded when it was answered. */
    public function recordSignUp(User $user, array $answers, string $source): void
    {
        $version = $answers['version'] ?? config('privacy.policy_version');

        if ($answers['privacy_policy'] ?? false) {
            $this->record($user, self::PRIVACY_POLICY, true, $version, $source);
        }

        if (($answers['data_sharing'] ?? null) !== null) {
            $this->record($user, self::DATA_SHARING, (bool) $answers['data_sharing'], $version, $source);
        }
    }

    /** Changes one answer. Giving the same answer again adds nothing to the history. */
    public function set(User $user, string $type, bool $granted, string $source): void
    {
        $version = config('privacy.policy_version');
        $latest = $this->latest($user, $type);

        if ($latest && $latest->granted === $granted && $latest->policy_version === $version) {
            return;
        }

        $this->record($user, $type, $granted, $version, $source);
    }

    /**
     * The current answer for each consent, or null when it was never answered.
     *
     * @return array<string, array{granted: bool, version: string, at: ?string}|null>
     */
    public function current(User $user): array
    {
        $answers = [];

        foreach (self::TYPES as $type) {
            $latest = $this->latest($user, $type);

            $answers[$type] = $latest ? [
                'granted' => $latest->granted,
                'version' => $latest->policy_version,
                'at' => $latest->created_at?->toIso8601String(),
            ] : null;
        }

        return $answers;
    }

    private function latest(User $user, string $type): ?UserConsent
    {
        // id breaks ties between answers given in the same second (ULIDs grow with time)
        return $user->consents()->where('type', $type)->latest()->latest('id')->first();
    }

    private function record(User $user, string $type, bool $granted, string $version, string $source): UserConsent
    {
        return $user->consents()->create([
            'type' => $type,
            'granted' => $granted,
            'policy_version' => $version,
            'source' => $source,
        ]);
    }
}