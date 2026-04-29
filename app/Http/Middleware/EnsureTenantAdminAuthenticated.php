<?php

namespace App\Http\Middleware;

use App\Models\AdminUser;
use App\Services\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantAdminAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = app(TenantContext::class)->tenant();
        $user = Auth::guard('tenant_admin')->user();

        if (
            ! $user instanceof AdminUser
            || ! $user->isTenantAdmin()
            || ! $user->is_active
            || $tenant === null
            || (int) $user->tenant_id !== (int) $tenant->id
        ) {
            return redirect()->route('tenant.admin.login', [
                'tenant_slug' => $request->route('tenant_slug'),
            ]);
        }

        return $next($request);
    }
}
