<?php

use App\Http\Middleware\PrivateResponses;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/app/login');
        $middleware->append(PrivateResponses::class);
        $proxies = array_values(array_filter(array_map('trim', explode(',', env('TRUSTED_PROXIES', '')))));
        if (in_array('*', $proxies, true)) {
            throw new RuntimeException('TRUSTED_PROXIES no admite confianza global.');
        }
        if ($proxies) {
            $middleware->trustProxies(at: $proxies, headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_PORT);
        }
        $hosts = array_values(array_filter(array_map('trim', explode(',', env('TRUSTED_HOSTS', '')))));
        if ($hosts) {
            $middleware->trustHosts(at: array_map(fn ($host) => '^'.preg_quote($host, '/').'$', $hosts), subdomains: false);
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['current_password', 'currentPassword', 'password', 'password_confirmation', 'app_authentication_secret', 'app_authentication_recovery_codes']);
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
