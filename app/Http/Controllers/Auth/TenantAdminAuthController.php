<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class TenantAdminAuthController extends Controller
{
    public function create(TenantContext $tenantContext): View
    {
        return view('auth.login', [
            'title' => 'Login Admin Tenant',
            'heading' => 'Login Admin Tenant',
            'description' => 'Masuk untuk mengelola arsip '.$tenantContext->tenant()?->name.'.',
            'action' => route('tenant.admin.login.store', ['tenant_slug' => request()->route('tenant_slug')]),
            'identifierType' => 'email',
            'identifierName' => 'email',
            'identifierLabel' => 'Email',
            'identifierPlaceholder' => 'admin@demo-dinas.test',
            'rememberDefault' => false,
        ]);
    }

    public function store(Request $request, TenantContext $tenantContext): RedirectResponse
    {
        $tenant = $tenantContext->tenant();
        abort_unless($tenant !== null, 404);

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $admin = AdminUser::query()
            ->tenantAdmin()
            ->where('tenant_id', $tenant->id)
            ->where('email', $credentials['email'])
            ->where('is_active', true)
            ->first();

        if ($admin === null || ! Hash::check($credentials['password'], $admin->password)) {
            return back()
                ->withErrors(['email' => 'Email atau password tidak valid untuk tenant ini.'])
                ->onlyInput('email');
        }

        Auth::guard('tenant_admin')->login($admin, $request->boolean('remember'));
        $request->session()->regenerate();

        $admin->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('tenant.admin.dashboard', ['tenant_slug' => $tenant->slug]));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $tenantSlug = $request->route('tenant_slug');

        Auth::guard('tenant_admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('tenant.admin.login', ['tenant_slug' => $tenantSlug]);
    }
}
