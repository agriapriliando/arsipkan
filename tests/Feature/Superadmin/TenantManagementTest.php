<?php

use App\Livewire\Superadmin\TenantManager;
use App\Livewire\Superadmin\TenantMasterDataEntry;
use App\Livewire\Superadmin\TenantUploadLinkEntry;
use App\Models\AdminUser;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function createSuperadminForTenantManagement(): AdminUser
{
    return AdminUser::create([
        'tenant_id' => null,
        'name' => 'Superadmin',
        'email' => 'superadmin-tenant@test.local',
        'password' => Hash::make('secret-password'),
        'role' => AdminUser::ROLE_SUPERADMIN,
        'is_active' => true,
    ]);
}

it('protects the tenant management page for superadmins', function () {
    $this->get('/superadmin/tenants')
        ->assertRedirect('/superadmin/login');

    $this->actingAs(createSuperadminForTenantManagement(), 'superadmin')
        ->get('/superadmin/tenants')
        ->assertOk()
        ->assertSee('Manajemen Tenant');
});

it('protects the tenant master data entry page for superadmins', function () {
    $this->get('/superadmin/master-data')
        ->assertRedirect('/superadmin/login');

    Tenant::create([
        'code' => 'MD',
        'name' => 'Tenant Master Data',
        'slug' => 'tenant-master-data',
        'storage_quota_bytes' => 10 * 1024 * 1024 * 1024,
        'storage_used_bytes' => 0,
        'storage_warning_threshold_percent' => 80,
        'is_active' => true,
    ]);

    $this->actingAs(createSuperadminForTenantManagement(), 'superadmin')
        ->get('/superadmin/master-data')
        ->assertOk()
        ->assertSee('Master Data Tenant')
        ->assertSee('CRUD Kategori')
        ->assertSee('CRUD Tag');
});

it('protects the tenant upload link entry page for superadmins', function () {
    $this->get('/superadmin/upload-links')
        ->assertRedirect('/superadmin/login');

    Tenant::create([
        'code' => 'UL',
        'name' => 'Tenant Upload Link',
        'slug' => 'tenant-upload-link',
        'storage_quota_bytes' => 10 * 1024 * 1024 * 1024,
        'storage_used_bytes' => 0,
        'storage_warning_threshold_percent' => 80,
        'is_active' => true,
    ]);

    $this->actingAs(createSuperadminForTenantManagement(), 'superadmin')
        ->get('/superadmin/upload-links')
        ->assertOk()
        ->assertSee('Link Upload Tenant')
        ->assertSee('Kelola Link Upload');
});

it('creates tenants from the livewire manager', function () {
    $this->actingAs(createSuperadminForTenantManagement(), 'superadmin');

    Livewire::test(TenantManager::class)
        ->set('code', 'DINAS-A')
        ->set('name', 'Dinas Arsip A')
        ->set('slug', 'dinas-arsip-a')
        ->set('storageQuotaGb', '25')
        ->set('storageWarningThresholdPercent', 75)
        ->set('isActive', true)
        ->call('save')
        ->assertHasNoErrors();

    $tenant = Tenant::query()->where('slug', 'dinas-arsip-a')->firstOrFail();

    expect($tenant->code)->toBe('DINAS-A')
        ->and($tenant->name)->toBe('Dinas Arsip A')
        ->and($tenant->path_prefix)->toBe('/dinas-arsip-a')
        ->and($tenant->storage_quota_bytes)->toBe(25 * 1024 * 1024 * 1024)
        ->and($tenant->storage_warning_threshold_percent)->toBe(75)
        ->and($tenant->is_active)->toBeTrue();
});

it('rejects reserved tenant slugs in the livewire manager', function () {
    $this->actingAs(createSuperadminForTenantManagement(), 'superadmin');

    Livewire::test(TenantManager::class)
        ->set('code', 'SYS')
        ->set('name', 'Sistem')
        ->set('slug', 'superadmin')
        ->set('storageQuotaGb', '10')
        ->set('storageWarningThresholdPercent', 80)
        ->call('save')
        ->assertHasErrors(['slug']);
});

it('updates tenant data and toggles active status from the livewire manager', function () {
    $this->actingAs(createSuperadminForTenantManagement(), 'superadmin');

    $tenant = Tenant::create([
        'code' => 'OLD',
        'name' => 'Tenant Lama',
        'slug' => 'tenant-lama',
        'storage_quota_bytes' => 10 * 1024 * 1024 * 1024,
        'storage_used_bytes' => 0,
        'storage_warning_threshold_percent' => 80,
        'is_active' => true,
    ]);

    Livewire::test(TenantManager::class)
        ->call('edit', $tenant->id)
        ->set('code', 'NEW')
        ->set('name', 'Tenant Baru')
        ->set('slug', 'tenant-baru')
        ->set('storageQuotaGb', '15.5')
        ->set('storageWarningThresholdPercent', 90)
        ->set('isActive', true)
        ->call('save')
        ->assertHasNoErrors()
        ->call('toggleActive', $tenant->id);

    $tenant->refresh();

    expect($tenant->code)->toBe('NEW')
        ->and($tenant->name)->toBe('Tenant Baru')
        ->and($tenant->slug)->toBe('tenant-baru')
        ->and($tenant->storage_quota_bytes)->toBe((int) round(15.5 * 1024 * 1024 * 1024))
        ->and($tenant->storage_warning_threshold_percent)->toBe(90)
        ->and($tenant->is_active)->toBeFalse();
});

it('stores superadmin tenant context when entering an active tenant', function () {
    $this->actingAs(createSuperadminForTenantManagement(), 'superadmin');

    $tenant = Tenant::create([
        'code' => 'CTX',
        'name' => 'Tenant Context',
        'slug' => 'tenant-context',
        'storage_quota_bytes' => 10 * 1024 * 1024 * 1024,
        'storage_used_bytes' => 0,
        'storage_warning_threshold_percent' => 80,
        'is_active' => true,
    ]);

    Livewire::test(TenantManager::class)
        ->call('enterTenant', $tenant->id)
        ->assertRedirect('/tenant-context');

    $this->assertSame($tenant->id, session('superadmin_tenant_id'));
});

it('stores superadmin tenant context when entering tenant master data', function () {
    $this->actingAs(createSuperadminForTenantManagement(), 'superadmin');

    $tenant = Tenant::create([
        'code' => 'MD',
        'name' => 'Tenant Master Data',
        'slug' => 'tenant-master-data',
        'storage_quota_bytes' => 10 * 1024 * 1024 * 1024,
        'storage_used_bytes' => 0,
        'storage_warning_threshold_percent' => 80,
        'is_active' => true,
    ]);

    Livewire::test(TenantMasterDataEntry::class)
        ->call('enterMasterData', $tenant->id, 'tag')
        ->assertRedirect('/tenant-master-data/admin/master-data#tag');

    $this->assertSame($tenant->id, session('superadmin_tenant_id'));
});

it('stores superadmin tenant context when entering tenant upload links', function () {
    $this->actingAs(createSuperadminForTenantManagement(), 'superadmin');

    $tenant = Tenant::create([
        'code' => 'UL',
        'name' => 'Tenant Upload Link',
        'slug' => 'tenant-upload-link',
        'storage_quota_bytes' => 10 * 1024 * 1024 * 1024,
        'storage_used_bytes' => 0,
        'storage_warning_threshold_percent' => 80,
        'is_active' => true,
    ]);

    Livewire::test(TenantUploadLinkEntry::class)
        ->call('enterUploadLinks', $tenant->id)
        ->assertRedirect('/tenant-upload-link/admin/upload-links');

    $this->assertSame($tenant->id, session('superadmin_tenant_id'));
});
