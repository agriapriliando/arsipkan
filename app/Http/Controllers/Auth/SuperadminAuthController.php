<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SuperadminAuthController extends Controller
{
    public function create(): View
    {
        return view('auth.login', [
            'title' => 'Login Superadmin',
            'heading' => 'Login Superadmin',
            'description' => 'Masuk ke area platform Arsipkan.',
            'action' => route('superadmin.login.store'),
            'identifierType' => 'email',
            'identifierName' => 'email',
            'identifierLabel' => 'Email',
            'identifierPlaceholder' => 'superadmin@arsipkan.test',
            'rememberDefault' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $rateLimiterKey = Str::transliterate(Str::lower((string) $request->input('email')).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($rateLimiterKey, 5)) {
            return back()
                ->withErrors(['email' => 'Terlalu banyak percobaan login. Coba lagi dalam '.RateLimiter::availableIn($rateLimiterKey).' detik.'])
                ->onlyInput('email');
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $admin = AdminUser::query()
            ->superadmin()
            ->where('email', $credentials['email'])
            ->where('is_active', true)
            ->first();

        if ($admin === null || ! Hash::check($credentials['password'], $admin->password)) {
            RateLimiter::hit($rateLimiterKey, 60);

            return back()
                ->withErrors(['email' => 'Email atau password tidak valid.'])
                ->onlyInput('email');
        }

        RateLimiter::clear($rateLimiterKey);

        Auth::guard('superadmin')->login($admin, $request->boolean('remember'));
        $request->session()->regenerate();

        $admin->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('superadmin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('superadmin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('superadmin.login');
    }
}
