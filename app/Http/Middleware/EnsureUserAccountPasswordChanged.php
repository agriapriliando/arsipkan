<?php

namespace App\Http\Middleware;

use App\Models\UserAccount;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserAccountPasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('user_account')->user();

        if (
            $user instanceof UserAccount
            && $user->must_change_password
            && ! $request->routeIs('tenant.password.*')
        ) {
            return redirect()->route('tenant.password.edit', [
                'tenant_slug' => $request->route('tenant_slug'),
            ]);
        }

        return $next($request);
    }
}
