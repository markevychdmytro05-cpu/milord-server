<?php

use App\Http\Middleware\NoIndexPrivateAreas;
use App\Http\Middleware\RedirectToCanonicalPath;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\StatelessPublicPages;
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
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend([RedirectToCanonicalPath::class, StatelessPublicPages::class]);
        $middleware->append([NoIndexPrivateAreas::class, SecurityHeaders::class]);

        $middleware->preventRequestForgery(except: ['contact']);

        if (env('APP_ENV') === 'local') {
            $middleware->trustProxies(at: '*');
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
