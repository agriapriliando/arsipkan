<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\GuestUploader;
use App\Models\UserAccount;
use App\Services\PhoneNumberNormalizer;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserAccountAuthController extends Controller
{
    public function create(TenantContext $tenantContext): View
    {
        return view('auth.login', [
            'title' => 'Login User Uploader',
            'heading' => 'Login User Uploader',
            'description' => 'Masuk ke portal berkas '.$tenantContext->tenant()?->name.'.',
            'action' => route('tenant.login.store', ['tenant_slug' => request()->route('tenant_slug')]),
            'identifierType' => 'tel',
            'identifierName' => 'phone_number',
            'identifierLabel' => 'Nomor HP',
            'identifierPlaceholder' => '08123456789',
            'rememberDefault' => true,
        ]);
    }

    public function store(Request $request, TenantContext $tenantContext, PhoneNumberNormalizer $normalizer): RedirectResponse
    {
        $tenant = $tenantContext->tenant();
        abort_unless($tenant !== null, 404);

        $credentials = $request->validate([
            'phone_number' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string'],
        ]);

        $phoneNumber = $normalizer->normalize($credentials['phone_number']);

        $uploader = GuestUploader::query()
            ->where('tenant_id', $tenant->id)
            ->where('phone_number_normalized', $phoneNumber)
            ->first();

        $account = $uploader === null
            ? null
            : UserAccount::query()
                ->where('tenant_id', $tenant->id)
                ->where('guest_uploader_id', $uploader->id)
                ->where('is_active', true)
                ->first();

        if ($account === null || ! Hash::check($credentials['password'], $account->password)) {
            return back()
                ->withErrors(['phone_number' => 'Nomor HP atau password tidak valid untuk tenant ini.'])
                ->onlyInput('phone_number');
        }

        Auth::guard('user_account')->login($account, true);
        $request->session()->regenerate();

        $account->forceFill(['last_login_at' => now()])->save();

        if ($account->must_change_password) {
            return redirect()->route('tenant.password.edit', ['tenant_slug' => $tenant->slug]);
        }

        return redirect()->intended(route('tenant.user.dashboard', ['tenant_slug' => $tenant->slug]));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $tenantSlug = $request->route('tenant_slug');

        Auth::guard('user_account')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('tenant.login', ['tenant_slug' => $tenantSlug]);
    }
}
