<?php

namespace Tests\Feature\Security;

use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Each rate limit counts only its own route: requests to one endpoint never use up another's limit.
 */
class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_other_auth_requests_do_not_use_up_the_resend_limit(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->getJson('/api/auth/social/pending');
        }

        $this->assertNotSame(429, $this->postJson('/api/auth/register/resend', ['email' => 'ninguem@example.com'])->status());
    }

    public function test_using_the_app_does_not_use_up_the_verification_email_limit(): void
    {
        $user = User::where('email', 'morador@example.com')->firstOrFail();

        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($user)->getJson('/api/v1/properties')->assertOk();
        }

        $this->assertNotSame(429, $this->actingAs($user)->postJson('/api/email/verification-notification')->status());
    }

    public function test_a_limit_still_applies_to_its_own_route(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/auth/register/resend', ['email' => 'ninguem@example.com']);
        }

        $this->postJson('/api/auth/register/resend', ['email' => 'ninguem@example.com'])->assertStatus(429);
    }
}