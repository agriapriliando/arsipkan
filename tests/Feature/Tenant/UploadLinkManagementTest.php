<?php

use App\Livewire\Tenant\UploadLinkManager;
use App\Models\AdminUser;
use App\Models\Tenant;
use App\Models\UploadLink;
use App\Services\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function createTenantForUploadLinks(string $slug = 'upload-link-a', bool $active = true): Tenant
{
    return Tenant::create([
        'code' => strtoupper(str_replace('-', '_', $slug)),
        'name' => 'Tenant '.$slug,
        'slug' => $slug,
        'storage_quota_bytes' => 10 * 1024 * 1024 * 1024,
        'storage_used_bytes' => 0,
        'storage_warning_threshold_percent' => 80,
        'is_active' => $active,
    ]);
}

function createTenantAdminForUploadLinks(Tenant $tenant, string $email = 'admin-upload-link@test.local'): AdminUser
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

function createSuperadminForUploadLinks(): AdminUser
{
    return AdminUser::create([
        'tenant_id' => null,
        'name' => 'Superadmin',
        'email' => 'superadmin-upload-link@test.local',
        'password' => Hash::make('secret-password'),
        'role' => AdminUser::ROLE_SUPERADMIN,
        'is_active' => true,
    ]);
}

function setUploadLinkTenant(Tenant $tenant): void
{
    app(TenantContext::class)->set($tenant);
}

it('protects the tenant upload link page for tenant managers', function () {
    $tenant = createTenantForUploadLinks();
    $admin = createTenantAdminForUploadLinks($tenant);

    $this->get('/upload-link-a/admin/upload-links')
        ->assertRedirect('/upload-link-a/admin/login');

    $this->actingAs($admin, 'tenant_admin')
        ->get('/upload-link-a/admin/upload-links')
        ->assertOk()
        ->assertSee('Manajemen Link Upload');
});

it('allows superadmins to access upload links only in the entered tenant context', function () {
    $tenant = createTenantForUploadLinks();
    $superadmin = createSuperadminForUploadLinks();

    $this->actingAs($superadmin, 'superadmin')
        ->get('/upload-link-a/admin/upload-links')
        ->assertRedirect('/upload-link-a/admin/login');

    $this->actingAs($superadmin, 'superadmin')
        ->withSession(['superadmin_tenant_id' => $tenant->id])
        ->get('/upload-link-a/admin/upload-links')
        ->assertOk()
        ->assertSee('Manajemen Link Upload');
});

it('creates upload links inside the active tenant only', function () {
    $tenant = createTenantForUploadLinks();
    $admin = createTenantAdminForUploadLinks($tenant);

    setUploadLinkTenant($tenant);
    $this->actingAs($admin, 'tenant_admin');

    Livewire::test(UploadLinkManager::class)
        ->set('code', 'UPLOAD-A')
        ->set('title', 'Upload Arsip A')
        ->set('isActive', true)
        ->set('expiresAt', now()->addDay()->format('Y-m-d\TH:i'))
        ->set('maxUsage', '10')
        ->call('save')
        ->assertHasNoErrors();

    $uploadLink = UploadLink::query()->where('code', 'UPLOAD-A')->firstOrFail();

    expect($uploadLink->tenant_id)->toBe($tenant->id)
        ->and($uploadLink->title)->toBe('Upload Arsip A')
        ->and($uploadLink->max_usage)->toBe(10)
        ->and($uploadLink->usage_count)->toBe(0)
        ->and($uploadLink->created_by_admin_id)->toBe($admin->id);
});

it('keeps tenant context when livewire upload link actions run on later requests', function () {
    $tenant = createTenantForUploadLinks();
    $admin = createTenantAdminForUploadLinks($tenant);

    setUploadLinkTenant($tenant);
    $this->actingAs($admin, 'tenant_admin');

    $component = Livewire::test(UploadLinkManager::class)
        ->set('code', 'LATER-REQUEST')
        ->set('title', 'Later Request Link');

    app()->forgetInstance(TenantContext::class);

    $component
        ->call('save')
        ->assertHasNoErrors();

    expect(UploadLink::query()
        ->where('tenant_id', $tenant->id)
        ->where('code', 'LATER-REQUEST')
        ->exists())->toBeTrue();
});

it('updates toggles and deletes tenant upload links', function () {
    $tenant = createTenantForUploadLinks();
    $admin = createTenantAdminForUploadLinks($tenant);
    $uploadLink = UploadLink::create([
        'tenant_id' => $tenant->id,
        'code' => 'OLD',
        'title' => 'Link Lama',
        'is_active' => true,
        'max_usage' => 5,
        'created_by_admin_id' => $admin->id,
    ]);

    setUploadLinkTenant($tenant);
    $this->actingAs($admin, 'tenant_admin');

    Livewire::test(UploadLinkManager::class)
        ->call('edit', $uploadLink->id)
        ->set('code', 'NEW')
        ->set('title', 'Link Baru')
        ->set('maxUsage', '15')
        ->call('save')
        ->assertHasNoErrors()
        ->call('toggleActive', $uploadLink->id)
        ->call('delete', $uploadLink->id);

    expect(UploadLink::query()->whereKey($uploadLink->id)->exists())->toBeFalse();
});

it('keeps upload link codes unique per tenant', function () {
    $tenantA = createTenantForUploadLinks();
    $tenantB = createTenantForUploadLinks('upload-link-b');
    $admin = createTenantAdminForUploadLinks($tenantA);

    UploadLink::create([
        'tenant_id' => $tenantA->id,
        'code' => 'SHARED',
        'title' => 'Tenant A',
    ]);

    UploadLink::create([
        'tenant_id' => $tenantB->id,
        'code' => 'SHARED',
        'title' => 'Tenant B',
    ]);

    setUploadLinkTenant($tenantA);
    $this->actingAs($admin, 'tenant_admin');

    Livewire::test(UploadLinkManager::class)
        ->set('code', 'SHARED')
        ->set('title', 'Duplicate')
        ->call('save')
        ->assertHasErrors(['code']);
});

it('blocks editing upload links from another tenant', function () {
    $tenantA = createTenantForUploadLinks();
    $tenantB = createTenantForUploadLinks('upload-link-b');
    $admin = createTenantAdminForUploadLinks($tenantA);

    $otherUploadLink = UploadLink::create([
        'tenant_id' => $tenantB->id,
        'code' => 'OTHER',
        'title' => 'Other Link',
    ]);

    setUploadLinkTenant($tenantA);
    $this->actingAs($admin, 'tenant_admin');

    expect(fn () => Livewire::test(UploadLinkManager::class)
        ->call('edit', $otherUploadLink->id))
        ->toThrow(ModelNotFoundException::class);
});

it('validates guest upload link usability', function () {
    $tenant = createTenantForUploadLinks();
    setUploadLinkTenant($tenant);

    $usableLink = UploadLink::create([
        'tenant_id' => $tenant->id,
        'code' => 'USABLE',
        'title' => 'Usable',
        'is_active' => true,
        'expires_at' => now()->addDay(),
        'max_usage' => 2,
        'usage_count' => 1,
    ]);

    $inactiveLink = UploadLink::create([
        'tenant_id' => $tenant->id,
        'code' => 'INACTIVE',
        'title' => 'Inactive',
        'is_active' => false,
    ]);

    $expiredLink = UploadLink::create([
        'tenant_id' => $tenant->id,
        'code' => 'EXPIRED',
        'title' => 'Expired',
        'is_active' => true,
        'expires_at' => now()->subMinute(),
    ]);

    $usedUpLink = UploadLink::create([
        'tenant_id' => $tenant->id,
        'code' => 'USED',
        'title' => 'Used',
        'is_active' => true,
        'max_usage' => 1,
        'usage_count' => 1,
    ]);

    expect(Gate::forUser(null)->allows('uploadAsGuest', $usableLink))->toBeTrue()
        ->and(Gate::forUser(null)->allows('uploadAsGuest', $inactiveLink))->toBeFalse()
        ->and(Gate::forUser(null)->allows('uploadAsGuest', $expiredLink))->toBeFalse()
        ->and(Gate::forUser(null)->allows('uploadAsGuest', $usedUpLink))->toBeFalse();
});

it('rejects guest upload links for inactive tenants', function () {
    $tenant = createTenantForUploadLinks('upload-link-inactive', false);
    setUploadLinkTenant($tenant);

    $uploadLink = UploadLink::create([
        'tenant_id' => $tenant->id,
        'code' => 'INACTIVE-TENANT',
        'title' => 'Inactive Tenant',
        'is_active' => true,
    ]);

    expect(Gate::forUser(null)->allows('uploadAsGuest', $uploadLink))->toBeFalse();
});
