<?php

use App\Http\Middleware\TokenIngestao;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'token.ingestao' => TokenIngestao::class,
        ]);
        // Hostinger fica atrás de proxy/CDN: confia no cabeçalho X-Forwarded-* para HTTPS e IP real.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // A API sempre responde JSON, mesmo sem o cabeçalho Accept.
        $exceptions->shouldRenderJsonWhen(fn ($request) => $request->is('api/*') || $request->expectsJson());
    })->create();
