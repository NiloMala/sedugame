<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate;
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

        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);

        // App 100% API — não existe rota nomeada "login" (nenhuma tela de
        // login web). Sem isso, o comportamento padrão do Laravel tenta
        // route('login') pra montar o redirect de convidado sempre que a
        // requisição não "parece" pedir JSON (ex.: sem header Accept:
        // application/json — qualquer curl cru, bot ou monitor de uptime) e
        // estoura RouteNotFoundException → 500, em vez do 401 esperado.
        Authenticate::redirectUsing(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Reforça o ponto acima: mesmo com redirectTo() neutralizado, o
        // tratamento padrão de AuthenticationException ainda cai pra
        // route('login') como fallback quando expectsJson() é false. Como
        // esta API nunca serve HTML, resposta de não-autenticado é sempre
        // JSON 401, independente do header Accept recebido.
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return response()->json(['message' => 'Não autenticado.'], 401);
        });
    })->create();
