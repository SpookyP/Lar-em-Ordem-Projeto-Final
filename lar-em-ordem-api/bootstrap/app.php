<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();

        $middleware->redirectGuestsTo(fn () => config('oauth.frontend_url') . '/login');

        $middleware->redirectUsersTo(function (Request $request) {
            if ($request->expectsJson()) {
                abort(response()->json(['message' => 'Já existe uma sessão iniciada.'], 409));
            }

            return config('oauth.frontend_url') . '/';
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
