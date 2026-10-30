<?php

use App\Domain\Lisensi\Middlewares\EnforceLicenseReadOnly;
use App\Http\Middleware\EnsureSystemIsConfigured;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeadersMiddleware::class,
            HandleInertiaRequests::class,
            EnforceLicenseReadOnly::class,
            EnsureSystemIsConfigured::class,
        ]);
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'license.readonly' => EnforceLicenseReadOnly::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function ($response) {
            if ($response->getStatusCode() === 419) {
                return back()->with([
                    'error' => 'Sesi Anda telah kedaluwarsa. Silakan coba masuk kembali.',
                ]);
            }

            return $response;
        });
    })->create();
