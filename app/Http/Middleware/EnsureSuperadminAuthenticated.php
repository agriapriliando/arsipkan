<?php

namespace App\Http\Middleware;

use App\Models\AdminUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperadminAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('superadmin')->user();

        if (! $user instanceof AdminUser || ! $user->isSuperadmin() || ! $user->is_active) {
            return redirect()->route('superadmin.login');
        }

        return $next($request);
    }
}
