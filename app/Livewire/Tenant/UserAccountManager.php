<?php

namespace App\Livewire\Tenant;

use App\Models\AdminUser;
use App\Models\GuestUploader;
use App\Models\Tenant;
use App\Models\UserAccount;
use App\Services\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class UserAccountManager extends Component
{
    public ?int $tenantId = null;

    public string $search = '';

    public ?string $generatedPassword = null;

    public ?string $generatedPasswordLabel = null;

    public function mount(): void
    {
        $this->tenantId = $this->currentTenantFromContext()->id;
    }

    public function render(): View
    {
        $tenant = $this->authorizeTenantManager();
        $search = trim($this->search);

        return view('livewire.tenant.user-account-manager', [
            'guestUploaders' => GuestUploader::query()
                ->forTenant($tenant)
                ->with('userAccount')
                ->withCount('files')
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($innerQuery) use ($search): void {
                        $innerQuery
                            ->where('name', 'like', '%'.$search.'%')
                            ->orWhere('phone_number', 'like', '%'.$search.'%')
                            ->orWhere('phone_number_normalized', 'like', '%'.$search.'%');
                    });
                })
                ->latest('id')
                ->get(),
            'userLoginUrl' => route('tenant.login', ['tenant_slug' => $tenant->slug]),
        ]);
    }

    public function createAccount(int $guestUploaderId): void
    {
        $tenant = $this->authorizeTenantManager();
        $this->authorizeAction('create', UserAccount::class);

        $guestUploader = GuestUploader::query()
            ->forTenant($tenant)
            ->with('userAccount')
            ->findOrFail($guestUploaderId);

        abort_if($guestUploader->userAccount !== null, 422, 'Akun uploader sudah ada.');

        $temporaryPassword = $this->generateTemporaryPassword();

        UserAccount::query()->create([
            'tenant_id' => $tenant->id,
            'guest_uploader_id' => $guestUploader->id,
            'password' => Hash::make($temporaryPassword),
            'is_active' => true,
            'must_change_password' => true,
            'password_changed_at' => null,
            'created_by_admin_id' => $this->currentUser()?->id,
        ]);

        $this->generatedPassword = $temporaryPassword;
        $this->generatedPasswordLabel = 'Password sementara untuk '.$guestUploader->name.' ('.$guestUploader->phone_number.')';
    }

    public function resetPassword(int $userAccountId): void
    {
        $tenant = $this->authorizeTenantManager();

        $userAccount = UserAccount::query()
            ->where('tenant_id', $tenant->id)
            ->with('guestUploader')
            ->findOrFail($userAccountId);

        $this->authorizeAction('resetPassword', $userAccount);

        $temporaryPassword = $this->generateTemporaryPassword();

        $userAccount->forceFill([
            'password' => Hash::make($temporaryPassword),
            'is_active' => true,
            'must_change_password' => true,
            'password_changed_at' => null,
            'remember_token' => null,
        ])->save();

        $this->generatedPassword = $temporaryPassword;
        $this->generatedPasswordLabel = 'Password baru untuk '.$userAccount->guestUploader->name.' ('.$userAccount->guestUploader->phone_number.')';
    }

    public function toggleActive(int $userAccountId): void
    {
        $tenant = $this->authorizeTenantManager();

        $userAccount = UserAccount::query()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($userAccountId);

        $this->authorizeAction('update', $userAccount);

        $userAccount->forceFill([
            'is_active' => ! $userAccount->is_active,
        ])->save();
    }

    protected function authorizeTenantManager(): Tenant
    {
        $tenant = $this->currentTenant();
        abort_unless($this->currentUser()?->can('viewAny', UserAccount::class), 403);

        return $tenant;
    }

    protected function authorizeAction(string $ability, string|UserAccount $target): void
    {
        abort_unless($this->currentUser()?->can($ability, $target), 403);
    }

    protected function currentUser(): ?AdminUser
    {
        $tenant = $this->currentTenantOrNull();
        $tenantAdmin = Auth::guard('tenant_admin')->user();

        if ($tenantAdmin instanceof AdminUser && $tenantAdmin->isTenantAdmin()) {
            return $tenantAdmin;
        }

        $superadmin = Auth::guard('superadmin')->user();

        if (
            $superadmin instanceof AdminUser
            && $superadmin->isSuperadmin()
            && $tenant !== null
            && (int) session('superadmin_tenant_id') === (int) $tenant->id
        ) {
            return $superadmin;
        }

        return null;
    }

    protected function currentTenant(): Tenant
    {
        $tenant = $this->currentTenantOrNull();

        abort_unless($tenant instanceof Tenant, 404);

        return $tenant;
    }

    protected function currentTenantOrNull(): ?Tenant
    {
        $tenant = app(TenantContext::class)->tenant();

        if ($tenant instanceof Tenant) {
            $this->tenantId = $tenant->id;

            return $tenant;
        }

        if ($this->tenantId === null) {
            return null;
        }

        $tenant = Tenant::query()
            ->whereKey($this->tenantId)
            ->where('is_active', true)
            ->first();

        if ($tenant instanceof Tenant) {
            app(TenantContext::class)->set($tenant);
        }

        return $tenant;
    }

    protected function currentTenantFromContext(): Tenant
    {
        $tenant = app(TenantContext::class)->tenant();

        abort_unless($tenant instanceof Tenant, 404);

        return $tenant;
    }

    protected function generateTemporaryPassword(): string
    {
        $alphabet = 'abcdefghjkmnpqrstuvwxyz23456789';

        return collect(range(1, 10))
            ->map(fn () => $alphabet[random_int(0, strlen($alphabet) - 1)])
            ->implode('');
    }
}
