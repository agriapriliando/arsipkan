<?php

use App\Models\AdminUser;
use App\Models\File;
use App\Models\GuestUploader;
use App\Models\Tenant;
use App\Models\UploadLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function createTenantForStorage(string $slug = 'storage-a', array $overrides = []): Tenant
{
    return Tenant::create(array_merge([
        'code' => strtoupper(str_replace('-', '_', $slug)),
        'name' => 'Tenant '.$slug,
        'slug' => $slug,
        'storage_quota_bytes' => 10 * 1024 * 1024 * 1024,
        'storage_used_bytes' => 8 * 1024 * 1024 * 1024,
        'storage_warning_threshold_percent' => 80,
        'is_active' => true,
    ], $overrides));
}

function createTenantAdminForStorage(Tenant $tenant): AdminUser
{
    return AdminUser::create([
        'tenant_id' => $tenant->id,
        'name' => 'Admin '.$tenant->slug,
        'email' => $tenant->slug.'-storage@test.local',
        'password' => Hash::make('secret-password'),
        'role' => AdminUser::ROLE_TENANT_ADMIN,
        'is_active' => true,
    ]);
}

function createGuestUploaderForStorage(Tenant $tenant): GuestUploader
{
    return GuestUploader::create([
        'tenant_id' => $tenant->id,
        'name' => 'Uploader Storage',
        'phone_number' => '08155555555',
        'phone_number_normalized' => '628155555555',
    ]);
}

function createFileForStorage(Tenant $tenant, GuestUploader $uploader, int $size, string $name): File
{
    $link = UploadLink::create([
        'tenant_id' => $tenant->id,
        'code' => uniqid('storage'),
        'title' => 'Link Storage',
    ]);

    return File::create([
        'tenant_id' => $tenant->id,
        'guest_uploader_id' => $uploader->id,
        'upload_link_id' => $link->id,
        'uploaded_via' => File::UPLOADED_VIA_GUEST_LINK,
        'original_name' => $name,
        'stored_name' => uniqid('storage-file-', true).'.pdf',
        'extension' => 'pdf',
        'mime_type' => 'application/pdf',
        'file_size' => $size,
        'visibility' => File::VISIBILITY_PUBLIC,
        'status' => File::STATUS_VALID,
        'uploaded_at' => now(),
    ]);
}

it('shows tenant storage quota usage remaining capacity and warning state on the admin dashboard', function () {
    $tenant = createTenantForStorage();
    $admin = createTenantAdminForStorage($tenant);

    $this->actingAs($admin, 'tenant_admin')
        ->get('/storage-a/admin')
        ->assertOk()
        ->assertSee('Kuota Storage Tenant')
        ->assertSee('Total kuota')
        ->assertSee('Terpakai')
        ->assertSee('Sisa')
        ->assertSee('Ambang 80%')
        ->assertSee('mendekati atau melewati batas peringatan');
});

it('recalculates tenant storage usage from file records via artisan command', function () {
    $tenant = createTenantForStorage('storage-b', [
        'storage_used_bytes' => 0,
    ]);
    $uploader = createGuestUploaderForStorage($tenant);

    createFileForStorage($tenant, $uploader, 2048, 'a.pdf');
    $softDeleted = createFileForStorage($tenant, $uploader, 1024, 'b.pdf');
    $softDeleted->delete();

    $this->artisan('tenant:recalculate-storage', ['tenant_slug' => 'storage-b'])
        ->expectsOutputToContain('storage-b')
        ->assertSuccessful();

    expect($tenant->fresh()->storage_used_bytes)->toBe(3072);
});
