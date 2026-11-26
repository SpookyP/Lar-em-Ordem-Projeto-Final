<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['prefix' => 'api', 'middleware' => ['api', 'auth:sanctum']],
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();
    
        $middleware->trustProxies(at:'*');

        $middleware->redirectGuestsTo(fn () => config('oauth.frontend_url') . '/login');

        $middleware->redirectUsersTo(function (Request $request) {
            if ($request->expectsJson()) {
                abort(response()->json(['message' => 'Já existe uma sessão iniciada.'], 409));
            }

            return config('oauth.frontend_url') . '/';
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($e->getPrevious() instanceof ModelNotFoundException) {
                return response()->json(['message' => 'Registo não encontrado.'], 404);
            }
        });
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->expectsJson() && in_array($e->getMessage(), ['', 'This action is unauthorized.'], true)) {
                return response()->json(['message' => 'Não tem permissão para esta ação.'], 403);
            }
        });
        // a signed link (email verification) that was tampered with or has expired
        $exceptions->render(function (InvalidSignatureException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Link inválido ou expirado.'], 403);
            }
        });
    })->create();
