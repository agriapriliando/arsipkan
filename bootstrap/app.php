<?php

use App\Http\Middleware\EnsureSuperadminAuthenticated;
use App\Http\Middleware\EnsureTenantAdminAuthenticated;
use App\Http\Middleware\EnsureTenantManagerAuthenticated;
use App\Http\Middleware\EnsureUserAccountAuthenticated;
use App\Http\Middleware\EnsureUserAccountPasswordChanged;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth.superadmin' => EnsureSuperadminAuthenticated::class,
            'auth.tenant_admin' => EnsureTenantAdminAuthenticated::class,
            'auth.tenant_manager' => EnsureTenantManagerAuthenticated::class,
            'auth.user_account' => EnsureUserAccountAuthenticated::class,
            'user.password.changed' => EnsureUserAccountPasswordChanged::class,
            'tenant' => ResolveTenant::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
