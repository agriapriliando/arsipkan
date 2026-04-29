<?php

use App\Livewire\Superadmin\AdminTenantManager;
use App\Models\AdminUser;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function createSuperadminForAdminTenantManagement(): AdminUser
{
    return AdminUser::create([
        'tenant_id' => null,
        'name' => 'Superadmin',
        'email' => 'superadmin-admin-tenant@test.local',
        'password' => Hash::make('secret-password'),
        'role' => AdminUser::ROLE_SUPERADMIN,
        'is_active' => true,
    ]);
}

function createTenantForAdminTenantManagement(array $attributes = []): Tenant
{
    return Tenant::create(array_merge([
        'code' => 'TENANT-A',
        'name' => 'Tenant A',
        'slug' => 'tenant-a',
        'storage_quota_bytes' => 10 * 1024 * 1024 * 1024,
        'storage_used_bytes' => 0,
        'storage_warning_threshold_percent' => 80,
        'is_active' => true,
    ], $attributes));
}

it('protects the admin tenant management page for superadmins', function () {
    $this->get('/superadmin/admins')
        ->assertRedirect('/superadmin/login');

    $this->actingAs(createSuperadminForAdminTenantManagement(), 'superadmin')
        ->get('/superadmin/admins')
        ->assertOk()
        ->assertSee('Manajemen Admin Tenant');
});

it('creates tenant admins from the livewire manager', function () {
    $this->actingAs(createSuperadminForAdminTenantManagement(), 'superadmin');

    $tenant = createTenantForAdminTenantManagement();

    Livewire::test(AdminTenantManager::class)
        ->set('tenantId', $tenant->id)
        ->set('name', 'Admin Tenant A')
        ->set('email', 'admin-a@tenant.test')
        ->set('password', 'password-admin-a')
        ->set('isActive', true)
        ->call('save')
        ->assertHasNoErrors();

    $admin = AdminUser::query()->where('email', 'admin-a@tenant.test')->firstOrFail();

    expect($admin->tenant_id)->toBe($tenant->id)
        ->and($admin->role)->toBe(AdminUser::ROLE_TENANT_ADMIN)
        ->and($admin->is_active)->toBeTrue()
        ->and(Hash::check('password-admin-a', $admin->password))->toBeTrue();
});

it('requires tenant admins to belong to exactly one tenant', function () {
    $this->actingAs(createSuperadminForAdminTenantManagement(), 'superadmin');

    Livewire::test(AdminTenantManager::class)
        ->set('tenantId', null)
        ->set('name', 'Admin Tanpa Tenant')
        ->set('email', 'admin-without-tenant@test.local')
        ->set('password', 'password-admin')
        ->call('save')
        ->assertHasErrors(['tenant_id']);
});

it('allows the same tenant admin email on different tenants but not within the same tenant', function () {
    $this->actingAs(createSuperadminForAdminTenantManagement(), 'superadmin');

    $tenantA = createTenantForAdminTenantManagement();
    $tenantB = createTenantForAdminTenantManagement([
        'code' => 'TENANT-B',
        'name' => 'Tenant B',
        'slug' => 'tenant-b',
    ]);

    AdminUser::create([
        'tenant_id' => $tenantA->id,
        'name' => 'Admin Existing',
        'email' => 'admin-shared@tenant.test',
        'password' => Hash::make('password-admin'),
        'role' => AdminUser::ROLE_TENANT_ADMIN,
        'is_active' => true,
    ]);

    Livewire::test(AdminTenantManager::class)
        ->set('tenantId', $tenantA->id)
        ->set('name', 'Admin Duplicate')
        ->set('email', 'admin-shared@tenant.test')
        ->set('password', 'password-admin')
        ->call('save')
        ->assertHasErrors(['email']);

    Livewire::test(AdminTenantManager::class)
        ->set('tenantId', $tenantB->id)
        ->set('name', 'Admin Shared')
        ->set('email', 'admin-shared@tenant.test')
        ->set('password', 'password-admin')
        ->call('save')
        ->assertHasNoErrors();

    expect(AdminUser::query()->tenantAdmin()->where('email', 'admin-shared@tenant.test')->count())->toBe(2);
});

it('updates tenant admins and toggles active status from the livewire manager', function () {
    $this->actingAs(createSuperadminForAdminTenantManagement(), 'superadmin');

    $tenantA = createTenantForAdminTenantManagement();
    $tenantB = createTenantForAdminTenantManagement([
        'code' => 'TENANT-B',
        'name' => 'Tenant B',
        'slug' => 'tenant-b',
    ]);

    $admin = AdminUser::create([
        'tenant_id' => $tenantA->id,
        'name' => 'Admin Lama',
        'email' => 'admin-lama@tenant.test',
        'password' => Hash::make('old-password'),
        'role' => AdminUser::ROLE_TENANT_ADMIN,
        'is_active' => true,
    ]);

    Livewire::test(AdminTenantManager::class)
        ->call('edit', $admin->id)
        ->set('tenantId', $tenantB->id)
        ->set('name', 'Admin Baru')
        ->set('email', 'admin-baru@tenant.test')
        ->set('password', '')
        ->set('isActive', true)
        ->call('save')
        ->assertHasNoErrors()
        ->call('toggleActive', $admin->id);

    $admin->refresh();

    expect($admin->tenant_id)->toBe($tenantB->id)
        ->and($admin->name)->toBe('Admin Baru')
        ->and($admin->email)->toBe('admin-baru@tenant.test')
        ->and(Hash::check('old-password', $admin->password))->toBeTrue()
        ->and($admin->is_active)->toBeFalse();
});

it('resets tenant admin passwords from the livewire manager', function () {
    $this->actingAs(createSuperadminForAdminTenantManagement(), 'superadmin');

    $tenant = createTenantForAdminTenantManagement();

    $admin = AdminUser::create([
        'tenant_id' => $tenant->id,
        'name' => 'Admin Tenant',
        'email' => 'admin-reset@tenant.test',
        'password' => Hash::make('old-password'),
        'role' => AdminUser::ROLE_TENANT_ADMIN,
        'is_active' => true,
    ]);

    Livewire::test(AdminTenantManager::class)
        ->call('preparePasswordReset', $admin->id)
        ->set('resetPassword', 'new-password')
        ->call('resetSelectedPassword')
        ->assertHasNoErrors();

    expect(Hash::check('new-password', $admin->refresh()->password))->toBeTrue();
});
