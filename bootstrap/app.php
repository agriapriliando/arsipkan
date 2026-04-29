<?php

use App\Http\Middleware\EnsureSuperadminAuthenticated;
use App\Http\Middleware\EnsureTenantAdminAuthenticated;
use App\Http\Middleware\EnsureTenantManagerAuthenticated;
use App\Http\Middleware\EnsureUserAccountAuthenticated;
use App\Http\Middleware\EnsureUserAccountPasswordChanged;
use App\Models\UserAccount;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $middleware->redirectUsersTo(function (Request $request): string {
            $tenantSlug = $request->route('tenant_slug');

            if (
                is_string($tenantSlug)
                && $request->routeIs('tenant.admin.login')
                && Auth::guard('tenant_admin')->check()
            ) {
                return route('tenant.admin.dashboard', ['tenant_slug' => $tenantSlug]);
            }

            if (
                is_string($tenantSlug)
                && $request->routeIs('tenant.login')
                && Auth::guard('user_account')->check()
            ) {
                $user = Auth::guard('user_account')->user();

                if ($user instanceof UserAccount && $user->must_change_password) {
                    return route('tenant.password.edit', ['tenant_slug' => $tenantSlug]);
                }

                return route('tenant.user.dashboard', ['tenant_slug' => $tenantSlug]);
            }

            if ($request->routeIs('superadmin.login') && Auth::guard('superadmin')->check()) {
                return route('superadmin.dashboard');
            }

            return route('home');
        });

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
