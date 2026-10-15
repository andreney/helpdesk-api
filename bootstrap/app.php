<?php

use App\Exceptions\InvalidTicketStatusTransitionException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // API sem tela de login: nunca redirecionar visitantes
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (
            AuthenticationException $exception,
            Request $request
        ) {
            return response()->json([
                'message' => 'Não autenticado.',
            ], 401);
        });

        $exceptions->render(function (
            AccessDeniedHttpException $exception,
            Request $request
        ) {
            return response()->json([
                'message' => 'Você não tem permissão para realizar esta ação.',
            ], 403);
        });

        $exceptions->render(function (
            InvalidTicketStatusTransitionException $exception,
            Request $request
        ) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        });

        $exceptions->render(function (
            ThrottleRequestsException $exception,
            Request $request
        ) {
            return response()->json([
                'message' => 'Muitas tentativas. Aguarde e tente novamente.',
                'retry_after' => $exception->getHeaders()['Retry-After'] ?? null,
            ], 429, $exception->getHeaders());
        });
    })->create();
