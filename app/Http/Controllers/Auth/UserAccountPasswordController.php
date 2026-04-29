<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\UserAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserAccountPasswordController extends Controller
{
    public function edit(): View
    {
        return view('auth.change-password', [
            'title' => 'Ubah Password',
            'action' => route('tenant.password.update', ['tenant_slug' => request()->route('tenant_slug')]),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ], [
            'current_password' => 'password saat ini',
            'password' => 'password baru',
        ]);

        $account = Auth::guard('user_account')->user();
        abort_unless($account instanceof UserAccount, 403);

        if (! Hash::check($validated['current_password'], $account->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini tidak valid.']);
        }

        $account->forceFill([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
            'password_changed_at' => now(),
        ])->save();

        return redirect()->route('tenant.user.dashboard', [
            'tenant_slug' => $request->route('tenant_slug'),
        ]);
    }
}
