<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\RegisterResponse;
use Laravel\Fortify\Contracts\PasswordUpdateResponse;
use Illuminate\Auth\Notifications\ResetPassword;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(RegisterResponse::class, function () {
            return new class implements RegisterResponse {
                public function toResponse($request)
                {
                    $user = $request->user();

                    return response()->json([
                        'message' => 'Operation successful.',
                        'user' => $user ? $user->load(['roles', 'resident', 'service_provider', 'partner']) : null,
                    ], 201);
                }
            };
        });

        $this->app->singleton(PasswordUpdateResponse::class, function () {
            return new class implements PasswordUpdateResponse {
                public function toResponse($request)
                {
                    return response()->noContent();
                }
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            $frontend = env('FRONTEND_URL', 'http://localhost:5173');

            return $frontend . '/reset-password?token=' . $token
                . '&email=' . urlencode($notifiable->getEmailForPasswordReset());
        });
    }
}
