<?php

namespace App\Http\Middleware;

use App\Models\AdminUser;
use App\Services\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantManagerAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = app(TenantContext::class)->tenant();
        $tenantAdmin = Auth::guard('tenant_admin')->user();
        $superadmin = Auth::guard('superadmin')->user();

        if (
            $tenantAdmin instanceof AdminUser
            && $tenantAdmin->isTenantAdmin()
            && $tenantAdmin->is_active
            && $tenant !== null
            && (int) $tenantAdmin->tenant_id === (int) $tenant->id
        ) {
            return $next($request);
        }

        if (
            $superadmin instanceof AdminUser
            && $superadmin->isSuperadmin()
            && $superadmin->is_active
            && $tenant !== null
            && (int) session('superadmin_tenant_id') === (int) $tenant->id
        ) {
            return $next($request);
        }

        return redirect()->route('tenant.admin.login', [
            'tenant_slug' => $request->route('tenant_slug'),
        ]);
    }
}
