<?php

namespace Tests\Feature\Security;

use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Guards the route table itself, without touching the database: a new endpoint that is added
 * outside the authenticated group, or a public one that loses its rate limit, fails here.
 */
class RouteProtectionTest extends TestCase
{
    /** @return Collection<int, LaravelRoute> */
    private function routes(): Collection
    {
        return collect(Route::getRoutes()->getRoutes());
    }

    private function middlewareOf(LaravelRoute $route): array
    {
        return array_values(array_filter($route->gatherMiddleware(), 'is_string'));
    }

    private function find(string $method, string $uri): LaravelRoute
    {
        $route = $this->routes()->first(
            fn (LaravelRoute $r) => $r->uri() === $uri && in_array($method, $r->methods(), true)
        );

        $this->assertNotNull($route, "Route {$method} {$uri} is not registered.");

        return $route;
    }

    public function test_every_v1_route_requires_authentication(): void
    {
        $v1 = $this->routes()->filter(fn (LaravelRoute $r) => str_starts_with($r->uri(), 'api/v1'));

        $this->assertGreaterThan(20, $v1->count(), 'The v1 route group looks empty.');

        foreach ($v1 as $route) {
            $middleware = $this->middlewareOf($route);

            $this->assertContains('auth:sanctum', $middleware, "{$route->uri()} is not behind auth:sanctum");
        }
    }

    public function test_password_registration_only_goes_through_the_confirmation_flow(): void
    {
        // Fortify's own POST /register creates the account straight away, so it must stay disabled
        $fortifyRegister = $this->routes()->first(
            fn (LaravelRoute $r) => $r->uri() === 'register' && in_array('POST', $r->methods(), true)
        );

        $this->assertNull($fortifyRegister);
        $this->find('POST', 'api/auth/register');
        $this->find('POST', 'api/auth/register/confirm');
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function publicEndpoints(): array
    {
        return [
            'register'              => ['POST', 'api/auth/register'],
            'register confirm'      => ['POST', 'api/auth/register/confirm'],
            'register resend'       => ['POST', 'api/auth/register/resend'],
            'social pending'        => ['GET', 'api/auth/social/pending'],
            'social register'       => ['POST', 'api/auth/social/register'],
            'email verify'          => ['POST', 'api/email/verify/{id}/{hash}'],
            'email resend'          => ['POST', 'api/email/verification-notification'],
            'oauth redirect'        => ['GET', 'auth/{provider}/redirect'],
            'oauth callback'        => ['GET', 'auth/{provider}/callback'],
            'login'                 => ['POST', 'login'],
        ];
    }

    #[DataProvider('publicEndpoints')]
    public function test_public_endpoints_are_rate_limited(string $method, string $uri): void
    {
        $throttled = array_filter(
            $this->middlewareOf($this->find($method, $uri)),
            fn (string $m) => str_starts_with($m, 'throttle:')
        );

        $this->assertNotEmpty($throttled, "{$method} {$uri} has no rate limit");
    }

    public function test_email_verification_link_must_be_signed(): void
    {
        $this->assertContains('signed', $this->middlewareOf($this->find('POST', 'api/email/verify/{id}/{hash}')));
    }

    public function test_oauth_routes_only_accept_known_providers(): void
    {
        foreach (['auth/{provider}/redirect', 'auth/{provider}/callback'] as $uri) {
            $this->assertSame('google|microsoft', $this->find('GET', $uri)->wheres['provider'] ?? null);
        }
    }
}