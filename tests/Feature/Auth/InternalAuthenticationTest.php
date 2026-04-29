<?php

use App\Models\AdminUser;
use App\Models\GuestUploader;
use App\Models\Tenant;
use App\Models\UserAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function createTenantForAuth(string $slug = 'demo-auth'): Tenant
{
    return Tenant::create([
        'code' => strtoupper(str_replace('-', '_', $slug)),
        'name' => 'Tenant '.$slug,
        'slug' => $slug,
        'storage_quota_bytes' => 1000,
        'storage_used_bytes' => 0,
        'storage_warning_threshold_percent' => 80,
        'is_active' => true,
    ]);
}

it('authenticates active superadmins only into the superadmin area', function () {
    AdminUser::create([
        'tenant_id' => null,
        'name' => 'Superadmin',
        'email' => 'superadmin@test.local',
        'password' => Hash::make('secret-password'),
        'role' => AdminUser::ROLE_SUPERADMIN,
        'is_active' => true,
    ]);

    $this->post('/superadmin/login', [
        'email' => 'superadmin@test.local',
        'password' => 'secret-password',
    ])->assertRedirect('/superadmin');

    $this->assertAuthenticated('superadmin');

    $this->get('/superadmin')
        ->assertOk()
        ->assertViewIs('superadmin.dashboard');
});

it('authenticates tenant admins only for the active tenant', function () {
    $tenantA = createTenantForAuth('tenant-admin-a');
    $tenantB = createTenantForAuth('tenant-admin-b');

    AdminUser::create([
        'tenant_id' => $tenantA->id,
        'name' => 'Tenant Admin',
        'email' => 'admin@test.local',
        'password' => Hash::make('secret-password'),
        'role' => AdminUser::ROLE_TENANT_ADMIN,
        'is_active' => true,
    ]);

    $this->post('/tenant-admin-b/admin/login', [
        'email' => 'admin@test.local',
        'password' => 'secret-password',
    ])->assertSessionHasErrors('email');

    $this->post('/tenant-admin-a/admin/login', [
        'email' => 'admin@test.local',
        'password' => 'secret-password',
    ])->assertRedirect('/tenant-admin-a/admin');

    $this->assertAuthenticated('tenant_admin');

    $this->get('/tenant-admin-a/admin')->assertOk();
    $this->get('/tenant-admin-b/admin')->assertRedirect('/tenant-admin-b/admin/login');
});

it('authenticates user uploaders by normalized phone number and forces password changes', function () {
    $tenant = createTenantForAuth('tenant-user-a');

    $uploader = GuestUploader::create([
        'tenant_id' => $tenant->id,
        'name' => 'Uploader',
        'phone_number' => '08123456789',
        'phone_number_normalized' => '628123456789',
    ]);

    $account = UserAccount::create([
        'tenant_id' => $tenant->id,
        'guest_uploader_id' => $uploader->id,
        'password' => Hash::make('secret-password'),
        'is_active' => true,
        'must_change_password' => true,
    ]);

    $this->post('/tenant-user-a/login', [
        'phone_number' => '0812 3456 789',
        'password' => 'secret-password',
    ])->assertRedirect('/tenant-user-a/password/change');

    $this->assertAuthenticated('user_account');

    $this->get('/tenant-user-a/dashboard')
        ->assertRedirect('/tenant-user-a/password/change');

    $this->put('/tenant-user-a/password/change', [
        'current_password' => 'secret-password',
        'password' => 'new-secret-password',
        'password_confirmation' => 'new-secret-password',
    ])->assertRedirect('/tenant-user-a/dashboard');

    expect($account->fresh()->must_change_password)->toBeFalse();

    $this->get('/tenant-user-a/dashboard')->assertOk();
});

it('resets a superadmin password through artisan only for superadmin accounts', function () {
    $superadmin = AdminUser::create([
        'tenant_id' => null,
        'name' => 'Superadmin',
        'email' => 'superadmin-reset@test.local',
        'password' => Hash::make('old-password'),
        'role' => AdminUser::ROLE_SUPERADMIN,
        'is_active' => true,
    ]);

    $this->artisan('superadmin:reset-password', [
        'email' => 'superadmin-reset@test.local',
        '--password' => 'new-secret-password',
    ])->assertSuccessful();

    expect(Hash::check('new-secret-password', $superadmin->fresh()->password))->toBeTrue();

    $this->artisan('superadmin:reset-password', [
        'email' => 'missing@test.local',
        '--password' => 'new-secret-password',
    ])->assertFailed();
});
