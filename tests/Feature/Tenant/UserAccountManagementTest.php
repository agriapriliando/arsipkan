<?php

use App\Livewire\Tenant\UserAccountManager;
use App\Models\AdminUser;
use App\Models\GuestUploader;
use App\Models\Tenant;
use App\Models\UserAccount;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function createTenantForUserAccounts(string $slug = 'user-account-a'): Tenant
{
    return Tenant::create([
        'code' => strtoupper(str_replace('-', '_', $slug)),
        'name' => 'Tenant '.$slug,
        'slug' => $slug,
        'storage_quota_bytes' => 10 * 1024 * 1024 * 1024,
        'storage_used_bytes' => 0,
        'storage_warning_threshold_percent' => 80,
        'is_active' => true,
    ]);
}

function createTenantAdminForUserAccounts(Tenant $tenant, string $email = 'admin-user-account@test.local'): AdminUser
{
    return AdminUser::create([
        'tenant_id' => $tenant->id,
        'name' => 'Admin '.$tenant->slug,
        'email' => $email,
        'password' => Hash::make('secret-password'),
        'role' => AdminUser::ROLE_TENANT_ADMIN,
        'is_active' => true,
    ]);
}

function setUserAccountTenant(Tenant $tenant): void
{
    app(TenantContext::class)->set($tenant);
}

it('protects the tenant user account page for tenant managers', function () {
    $tenant = createTenantForUserAccounts();
    $admin = createTenantAdminForUserAccounts($tenant);

    GuestUploader::create([
        'tenant_id' => $tenant->id,
        'name' => 'Uploader Awal',
        'phone_number' => '08123456789',
        'phone_number_normalized' => '628123456789',
    ]);

    $this->get('/user-account-a/admin/user-accounts')
        ->assertRedirect('/user-account-a/admin/login');

    $this->actingAs($admin, 'tenant_admin')
        ->get('/user-account-a/admin/user-accounts')
        ->assertOk()
        ->assertSee('Manajemen Akun User Uploader')
        ->assertSee('08123456789')
        ->assertSee(route('tenant.login', ['tenant_slug' => $tenant->slug]), false);
});

it('creates a user account from an existing guest uploader with a generated password', function () {
    $tenant = createTenantForUserAccounts();
    $admin = createTenantAdminForUserAccounts($tenant);
    $guestUploader = GuestUploader::create([
        'tenant_id' => $tenant->id,
        'name' => 'Uploader Buat Akun',
        'phone_number' => '08111111111',
        'phone_number_normalized' => '628111111111',
    ]);

    setUserAccountTenant($tenant);
    $this->actingAs($admin, 'tenant_admin');

    $component = Livewire::test(UserAccountManager::class)
        ->call('createAccount', $guestUploader->id)
        ->assertHasNoErrors()
        ->assertSet('generatedPasswordLabel', 'Password sementara untuk Uploader Buat Akun (08111111111)');

    $password = $component->get('generatedPassword');
    $account = UserAccount::query()->where('guest_uploader_id', $guestUploader->id)->firstOrFail();

    expect($password)->toMatch('/^[abcdefghjkmnpqrstuvwxyz23456789]{10}$/')
        ->and(Hash::check($password, $account->password))->toBeTrue()
        ->and($account->tenant_id)->toBe($tenant->id)
        ->and($account->is_active)->toBeTrue()
        ->and($account->must_change_password)->toBeTrue()
        ->and($account->created_by_admin_id)->toBe($admin->id);
});

it('resets passwords and forces uploader accounts to change them again', function () {
    $tenant = createTenantForUserAccounts();
    $admin = createTenantAdminForUserAccounts($tenant);
    $guestUploader = GuestUploader::create([
        'tenant_id' => $tenant->id,
        'name' => 'Uploader Reset',
        'phone_number' => '08222222222',
        'phone_number_normalized' => '628222222222',
    ]);
    $account = UserAccount::create([
        'tenant_id' => $tenant->id,
        'guest_uploader_id' => $guestUploader->id,
        'password' => Hash::make('old-password'),
        'is_active' => false,
        'must_change_password' => false,
        'password_changed_at' => now(),
    ]);

    setUserAccountTenant($tenant);
    $this->actingAs($admin, 'tenant_admin');

    $component = Livewire::test(UserAccountManager::class)
        ->call('resetPassword', $account->id)
        ->assertHasNoErrors();

    $password = $component->get('generatedPassword');

    expect(Hash::check($password, $account->fresh()->password))->toBeTrue()
        ->and($account->fresh()->is_active)->toBeTrue()
        ->and($account->fresh()->must_change_password)->toBeTrue()
        ->and($account->fresh()->password_changed_at)->toBeNull();
});

it('toggles uploader account activation inside the active tenant only', function () {
    $tenant = createTenantForUserAccounts();
    $admin = createTenantAdminForUserAccounts($tenant);
    $guestUploader = GuestUploader::create([
        'tenant_id' => $tenant->id,
        'name' => 'Uploader Toggle',
        'phone_number' => '08333333333',
        'phone_number_normalized' => '628333333333',
    ]);
    $account = UserAccount::create([
        'tenant_id' => $tenant->id,
        'guest_uploader_id' => $guestUploader->id,
        'password' => Hash::make('old-password'),
        'is_active' => true,
        'must_change_password' => true,
    ]);

    setUserAccountTenant($tenant);
    $this->actingAs($admin, 'tenant_admin');

    Livewire::test(UserAccountManager::class)
        ->call('toggleActive', $account->id)
        ->assertHasNoErrors();

    expect($account->fresh()->is_active)->toBeFalse();
});
