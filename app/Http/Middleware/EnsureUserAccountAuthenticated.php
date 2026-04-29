<?php

namespace App\Http\Middleware;

use App\Models\UserAccount;
use App\Services\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserAccountAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = app(TenantContext::class)->tenant();
        $user = Auth::guard('user_account')->user();

        if (
            ! $user instanceof UserAccount
            || ! $user->is_active
            || $tenant === null
            || (int) $user->tenant_id !== (int) $tenant->id
        ) {
            return redirect()->route('tenant.login', [
                'tenant_slug' => $request->route('tenant_slug'),
            ]);
        }

        return $next($request);
    }
}
