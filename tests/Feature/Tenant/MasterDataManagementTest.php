<?php

use App\Livewire\Tenant\MasterDataManager;
use App\Models\AdminUser;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Tenant;
use App\Services\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function createTenantForMasterData(string $slug = 'master-data-a'): Tenant
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

function createTenantAdminForMasterData(Tenant $tenant, string $email = 'admin-master@test.local'): AdminUser
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

function createSuperadminForMasterData(): AdminUser
{
    return AdminUser::create([
        'tenant_id' => null,
        'name' => 'Superadmin',
        'email' => 'superadmin-master@test.local',
        'password' => Hash::make('secret-password'),
        'role' => AdminUser::ROLE_SUPERADMIN,
        'is_active' => true,
    ]);
}

function setMasterDataTenant(Tenant $tenant): void
{
    app(TenantContext::class)->set($tenant);
}

it('protects the tenant master data page for tenant managers', function () {
    $tenant = createTenantForMasterData();
    $admin = createTenantAdminForMasterData($tenant);

    $this->get('/master-data-a/admin/master-data')
        ->assertRedirect('/master-data-a/admin/login');

    $this->actingAs($admin, 'tenant_admin')
        ->get('/master-data-a/admin/master-data')
        ->assertOk()
        ->assertSee('Kategori dan Tag');
});

it('allows superadmins to access master data only in the entered tenant context', function () {
    $tenant = createTenantForMasterData();
    $superadmin = createSuperadminForMasterData();

    $this->actingAs($superadmin, 'superadmin')
        ->get('/master-data-a/admin/master-data')
        ->assertRedirect('/master-data-a/admin/login');

    $this->actingAs($superadmin, 'superadmin')
        ->withSession(['superadmin_tenant_id' => $tenant->id])
        ->get('/master-data-a/admin/master-data')
        ->assertOk()
        ->assertSee('Kategori dan Tag');
});

it('creates categories and tags inside the active tenant only', function () {
    $tenant = createTenantForMasterData();
    $admin = createTenantAdminForMasterData($tenant);

    setMasterDataTenant($tenant);
    $this->actingAs($admin, 'tenant_admin');

    Livewire::test(MasterDataManager::class)
        ->set('categoryName', 'Keuangan')
        ->set('categorySlug', 'keuangan')
        ->set('categoryDescription', 'Dokumen anggaran dan laporan.')
        ->set('categoryIsActive', true)
        ->call('saveCategory')
        ->assertHasNoErrors()
        ->set('tagName', 'penting')
        ->call('saveTag')
        ->assertHasNoErrors();

    expect(Category::query()->where('tenant_id', $tenant->id)->where('slug', 'keuangan')->exists())->toBeTrue()
        ->and(Tag::query()->where('tenant_id', $tenant->id)->where('name', 'penting')->exists())->toBeTrue();
});

it('updates toggles and deletes tenant categories', function () {
    $tenant = createTenantForMasterData();
    $admin = createTenantAdminForMasterData($tenant);
    $category = Category::create([
        'tenant_id' => $tenant->id,
        'name' => 'Lama',
        'slug' => 'lama',
        'is_active' => true,
    ]);

    setMasterDataTenant($tenant);
    $this->actingAs($admin, 'tenant_admin');

    Livewire::test(MasterDataManager::class)
        ->call('editCategory', $category->id)
        ->set('categoryName', 'Baru')
        ->set('categorySlug', 'baru')
        ->set('categoryDescription', 'Kategori baru.')
        ->set('categoryIsActive', true)
        ->call('saveCategory')
        ->assertHasNoErrors()
        ->call('toggleCategoryActive', $category->id)
        ->call('deleteCategory', $category->id);

    expect(Category::query()->whereKey($category->id)->exists())->toBeFalse();
});

it('updates and deletes tenant tags', function () {
    $tenant = createTenantForMasterData();
    $admin = createTenantAdminForMasterData($tenant);
    $tag = Tag::create([
        'tenant_id' => $tenant->id,
        'name' => 'lama',
    ]);

    setMasterDataTenant($tenant);
    $this->actingAs($admin, 'tenant_admin');

    Livewire::test(MasterDataManager::class)
        ->call('editTag', $tag->id)
        ->set('tagName', 'baru')
        ->call('saveTag')
        ->assertHasNoErrors()
        ->call('deleteTag', $tag->id);

    expect(Tag::query()->whereKey($tag->id)->exists())->toBeFalse();
});

it('keeps category and tag names unique per tenant', function () {
    $tenantA = createTenantForMasterData();
    $tenantB = createTenantForMasterData('master-data-b');
    $admin = createTenantAdminForMasterData($tenantA);

    Category::create([
        'tenant_id' => $tenantA->id,
        'name' => 'Keuangan',
        'slug' => 'keuangan',
    ]);

    Tag::create([
        'tenant_id' => $tenantA->id,
        'name' => 'penting',
    ]);

    Category::create([
        'tenant_id' => $tenantB->id,
        'name' => 'Keuangan',
        'slug' => 'keuangan',
    ]);

    Tag::create([
        'tenant_id' => $tenantB->id,
        'name' => 'penting',
    ]);

    setMasterDataTenant($tenantA);
    $this->actingAs($admin, 'tenant_admin');

    Livewire::test(MasterDataManager::class)
        ->set('categoryName', 'Keuangan')
        ->set('categorySlug', 'keuangan-baru')
        ->call('saveCategory')
        ->assertHasErrors(['category_name'])
        ->set('categoryName', 'Keuangan Baru')
        ->set('categorySlug', 'keuangan')
        ->call('saveCategory')
        ->assertHasErrors(['category_slug'])
        ->set('tagName', 'penting')
        ->call('saveTag')
        ->assertHasErrors(['tag_name']);
});

it('blocks editing master data from another tenant', function () {
    $tenantA = createTenantForMasterData();
    $tenantB = createTenantForMasterData('master-data-b');
    $admin = createTenantAdminForMasterData($tenantA);

    $otherCategory = Category::create([
        'tenant_id' => $tenantB->id,
        'name' => 'Tenant B',
        'slug' => 'tenant-b',
    ]);

    $otherTag = Tag::create([
        'tenant_id' => $tenantB->id,
        'name' => 'tenant-b',
    ]);

    setMasterDataTenant($tenantA);
    $this->actingAs($admin, 'tenant_admin');

    expect(fn () => Livewire::test(MasterDataManager::class)
        ->call('editCategory', $otherCategory->id))
        ->toThrow(ModelNotFoundException::class);

    expect(fn () => Livewire::test(MasterDataManager::class)
        ->call('editTag', $otherTag->id))
        ->toThrow(ModelNotFoundException::class);
});
