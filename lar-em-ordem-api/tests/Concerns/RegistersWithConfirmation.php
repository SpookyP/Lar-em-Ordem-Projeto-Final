<?php

namespace Tests\Concerns;

use App\Notifications\ConfirmRegistration;
use Illuminate\Support\Facades\Notification;

/**
 * Registering no longer creates the account straight away: it stores the request and emails a link.
 * These helpers do both steps (register, then open the link) like a user would.
 */
trait RegistersWithConfirmation
{
    /** POSTs /api/auth/register and returns the token from the emailed link. */
    protected function registerWithoutConfirming(array $payload): string
    {
        Notification::fake();

        $this->postJson('/api/auth/register', $payload)->assertStatus(202);

        return $this->sentToken();
    }

    /** Registers and opens the link, so the account exists and the client is signed in at the end. */
    protected function registerAndConfirm(array $payload): void
    {
        $token = $this->registerWithoutConfirming($payload);

        // confirming signs the user in, which needs the session; Sanctum only starts it for requests from the SPA's domain
        $this->withHeaders(['Referer' => 'http://' . config('sanctum.stateful')[0]])
            ->postJson('/api/auth/register/confirm', ['token' => $token])
            ->assertOk();
    }

    /** Reads the token from the link in the last email sent (needs Notification::fake() before the request). */
    protected function sentToken(): string
    {
        $token = null;

        Notification::assertSentOnDemand(ConfirmRegistration::class, function ($notification) use (&$token) {
            parse_str((string) parse_url($notification->url, PHP_URL_QUERY), $query);
            $token = $query['token'] ?? null;

            return $token !== null;
        });

        return $token;
    }
}