<?php

use App\Models\AdminUser;
use App\Models\Category;
use App\Models\File;
use App\Models\GuestUploader;
use App\Models\Tag;
use App\Models\Tenant;
use App\Models\UploadLink;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function createTenantForAdminFiles(string $slug = 'admin-files-a', array $overrides = []): Tenant
{
    return Tenant::create(array_merge([
        'code' => strtoupper(str_replace('-', '_', $slug)),
        'name' => 'Tenant '.$slug,
        'slug' => $slug,
        'storage_quota_bytes' => 10 * 1024 * 1024,
        'storage_used_bytes' => 0,
        'storage_warning_threshold_percent' => 80,
        'is_active' => true,
    ], $overrides));
}

function createTenantAdminForAdminFiles(Tenant $tenant, string $email = 'admin-files@test.local'): AdminUser
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

function createGuestUploaderForAdminFiles(Tenant $tenant, string $phone = '08123456789', string $name = 'Guest Review'): GuestUploader
{
    return GuestUploader::create([
        'tenant_id' => $tenant->id,
        'name' => $name,
        'phone_number' => $phone,
        'phone_number_normalized' => '62'.ltrim($phone, '0'),
    ]);
}

function createFileForAdminFiles(Tenant $tenant, GuestUploader $uploader, array $overrides = []): File
{
    $uploadLink = UploadLink::create([
        'tenant_id' => $tenant->id,
        'code' => uniqid('link'),
        'title' => 'Link Review',
    ]);

    return File::create(array_merge([
        'tenant_id' => $tenant->id,
        'guest_uploader_id' => $uploader->id,
        'upload_link_id' => $uploadLink->id,
        'uploaded_via' => File::UPLOADED_VIA_GUEST_LINK,
        'original_name' => 'dokumen.pdf',
        'stored_name' => uniqid('admin-file-', true).'.pdf',
        'extension' => 'pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 2048,
        'visibility' => File::VISIBILITY_PUBLIC,
        'status' => File::STATUS_PENDING_REVIEW,
        'uploaded_at' => now(),
    ], $overrides));
}

function setTenantForAdminFiles(Tenant $tenant): void
{
    app(TenantContext::class)->set($tenant);
}

it('shows pending review and all files pages for tenant admins', function () {
    $tenant = createTenantForAdminFiles();
    $admin = createTenantAdminForAdminFiles($tenant);
    $uploader = createGuestUploaderForAdminFiles($tenant);

    createFileForAdminFiles($tenant, $uploader, [
        'original_name' => 'pending-public.pdf',
        'visibility' => File::VISIBILITY_PUBLIC,
        'status' => File::STATUS_PENDING_REVIEW,
    ]);
    createFileForAdminFiles($tenant, $uploader, [
        'original_name' => 'valid-internal.pdf',
        'visibility' => File::VISIBILITY_INTERNAL,
        'status' => File::STATUS_VALID,
    ]);

    $this->actingAs($admin, 'tenant_admin')
        ->get('/admin-files-a/admin/files/pending')
        ->assertOk()
        ->assertSee('Pending Review')
        ->assertSee('pending-public.pdf')
        ->assertDontSee('valid-internal.pdf');

    $this->actingAs($admin, 'tenant_admin')
        ->get('/admin-files-a/admin/files')
        ->assertOk()
        ->assertSee('Semua Berkas')
        ->assertSee('pending-public.pdf')
        ->assertSee('valid-internal.pdf');
});

it('paginates the all files page for tenant admins', function () {
    $tenant = createTenantForAdminFiles();
    $admin = createTenantAdminForAdminFiles($tenant);
    $uploader = createGuestUploaderForAdminFiles($tenant);

    for ($i = 1; $i <= 11; $i++) {
        createFileForAdminFiles($tenant, $uploader, [
            'original_name' => 'file-'.$i.'.pdf',
            'uploaded_at' => now()->subMinutes($i),
        ]);
    }

    $this->actingAs($admin, 'tenant_admin')
        ->get('/admin-files-a/admin/files')
        ->assertOk()
        ->assertSee('file-1.pdf')
        ->assertSee('file-10.pdf')
        ->assertDontSee('file-11.pdf');

    $this->actingAs($admin, 'tenant_admin')
        ->get('/admin-files-a/admin/files?page=2')
        ->assertOk()
        ->assertSee('file-11.pdf')
        ->assertDontSee('file-1.pdf');
});

it('filters the all files page by visibility and category for tenant admins', function () {
    $tenant = createTenantForAdminFiles();
    $admin = createTenantAdminForAdminFiles($tenant);
    $uploader = createGuestUploaderForAdminFiles($tenant);
    $finance = Category::create([
        'tenant_id' => $tenant->id,
        'name' => 'Keuangan',
        'slug' => 'keuangan',
        'is_active' => true,
    ]);
    $hr = Category::create([
        'tenant_id' => $tenant->id,
        'name' => 'Kepegawaian',
        'slug' => 'kepegawaian',
        'is_active' => true,
    ]);

    createFileForAdminFiles($tenant, $uploader, [
        'original_name' => 'public-keuangan.pdf',
        'visibility' => File::VISIBILITY_PUBLIC,
        'category_id' => $finance->id,
    ]);
    createFileForAdminFiles($tenant, $uploader, [
        'original_name' => 'internal-keuangan.pdf',
        'visibility' => File::VISIBILITY_INTERNAL,
        'category_id' => $finance->id,
        'status' => File::STATUS_VALID,
    ]);
    createFileForAdminFiles($tenant, $uploader, [
        'original_name' => 'public-kepegawaian.pdf',
        'visibility' => File::VISIBILITY_PUBLIC,
        'category_id' => $hr->id,
    ]);

    $this->actingAs($admin, 'tenant_admin')
        ->get('/admin-files-a/admin/files?visibility=public')
        ->assertOk()
        ->assertSee('public-keuangan.pdf')
        ->assertSee('public-kepegawaian.pdf')
        ->assertDontSee('internal-keuangan.pdf');

    $this->actingAs($admin, 'tenant_admin')
        ->get('/admin-files-a/admin/files?visibility=internal')
        ->assertOk()
        ->assertSee('internal-keuangan.pdf')
        ->assertDontSee('public-keuangan.pdf')
        ->assertDontSee('public-kepegawaian.pdf');

    $this->actingAs($admin, 'tenant_admin')
        ->get('/admin-files-a/admin/files?category_id='.$finance->id)
        ->assertOk()
        ->assertSee('public-keuangan.pdf')
        ->assertSee('internal-keuangan.pdf')
        ->assertDontSee('public-kepegawaian.pdf');

    $this->actingAs($admin, 'tenant_admin')
        ->get('/admin-files-a/admin/files?visibility=public&category_id='.$finance->id)
        ->assertOk()
        ->assertSee('public-keuangan.pdf')
        ->assertDontSee('internal-keuangan.pdf')
        ->assertDontSee('public-kepegawaian.pdf');
});

it('searches files on the all files page for tenant admins', function () {
    $tenant = createTenantForAdminFiles();
    $admin = createTenantAdminForAdminFiles($tenant);
    $uploader = createGuestUploaderForAdminFiles($tenant, '08123456789', 'Uploader Keuangan');

    createFileForAdminFiles($tenant, $uploader, [
        'original_name' => 'laporan-keuangan-2026.pdf',
    ]);
    createFileForAdminFiles($tenant, $uploader, [
        'original_name' => 'notulen-rapat.pdf',
    ]);

    $this->actingAs($admin, 'tenant_admin')
        ->get('/admin-files-a/admin/files?search=laporan-keuangan-2026')
        ->assertOk()
        ->assertSee('laporan-keuangan-2026.pdf')
        ->assertDontSee('notulen-rapat.pdf');
});

it('allows tenant admins to review file metadata and change status', function () {
    $tenant = createTenantForAdminFiles();
    $admin = createTenantAdminForAdminFiles($tenant);
    $uploader = createGuestUploaderForAdminFiles($tenant);
    $category = Category::create([
        'tenant_id' => $tenant->id,
        'name' => 'Keuangan',
        'slug' => 'keuangan',
        'is_active' => true,
    ]);
    $tagA = Tag::create([
        'tenant_id' => $tenant->id,
        'name' => 'penting',
    ]);
    $tagB = Tag::create([
        'tenant_id' => $tenant->id,
        'name' => '2026',
    ]);
    $file = createFileForAdminFiles($tenant, $uploader);

    setTenantForAdminFiles($tenant);

    $this->actingAs($admin, 'tenant_admin')
        ->patch('/admin-files-a/admin/files/'.$file->id, [
            'title' => 'Laporan Keuangan Triwulan',
            'description' => 'Dokumen hasil review admin tenant.',
            'visibility' => File::VISIBILITY_INTERNAL,
            'category_id' => $category->id,
            'tag_ids' => [$tagA->id, $tagB->id],
            'final_file_type' => 'laporan',
            'status' => File::STATUS_VALID,
        ])
        ->assertRedirect('/admin-files-a/admin/files/'.$file->id);

    expect($file->fresh()->title)->toBe('Laporan Keuangan Triwulan')
        ->and($file->fresh()->description)->toBe('Dokumen hasil review admin tenant.')
        ->and($file->fresh()->visibility)->toBe(File::VISIBILITY_INTERNAL)
        ->and($file->fresh()->category_id)->toBe($category->id)
        ->and($file->fresh()->final_file_type)->toBe('laporan')
        ->and($file->fresh()->status)->toBe(File::STATUS_VALID)
        ->and($file->fresh()->reviewed_by_admin_id)->toBe($admin->id)
        ->and($file->fresh()->reviewed_at)->not()->toBeNull()
        ->and($file->fresh()->tags()->pluck('tags.id')->all())->toBe([$tagA->id, $tagB->id])
        ->and($uploader->fresh()->last_score)->toBe('10.00');
});

it('shows and allows changing file visibility from the tenant admin detail page', function () {
    $tenant = createTenantForAdminFiles();
    $admin = createTenantAdminForAdminFiles($tenant);
    $uploader = createGuestUploaderForAdminFiles($tenant);
    $file = createFileForAdminFiles($tenant, $uploader, [
        'visibility' => File::VISIBILITY_INTERNAL,
        'status' => File::STATUS_VALID,
    ]);

    $this->actingAs($admin, 'tenant_admin')
        ->get('/admin-files-a/admin/files/'.$file->id)
        ->assertOk()
        ->assertSee('Visibilitas')
        ->assertSee('Private')
        ->assertSee('Internal')
        ->assertSee('Public');

    $this->actingAs($admin, 'tenant_admin')
        ->patch('/admin-files-a/admin/files/'.$file->id, [
            'title' => $file->title,
            'description' => $file->description,
            'visibility' => File::VISIBILITY_PRIVATE,
            'category_id' => $file->category_id,
            'tag_ids' => [],
            'final_file_type' => $file->final_file_type,
            'status' => File::STATUS_VALID,
        ])
        ->assertRedirect('/admin-files-a/admin/files/'.$file->id);

    expect($file->fresh()->visibility)->toBe(File::VISIBILITY_PRIVATE);
});

it('allows tenant admins to rename the original file name from the detail page', function () {
    $tenant = createTenantForAdminFiles();
    $admin = createTenantAdminForAdminFiles($tenant);
    $uploader = createGuestUploaderForAdminFiles($tenant);
    $file = createFileForAdminFiles($tenant, $uploader, [
        'original_name' => 'dokumen-lama.pdf',
    ]);

    $this->actingAs($admin, 'tenant_admin')
        ->patch('/admin-files-a/admin/files/'.$file->id.'/original-name', [
            'original_name' => 'dokumen-baru-final.pdf',
        ])
        ->assertRedirect('/admin-files-a/admin/files/'.$file->id);

    expect($file->fresh()->original_name)->toBe('dokumen-baru-final.pdf');
});

it('blocks tenant admins from reviewing files from another tenant', function () {
    $tenantA = createTenantForAdminFiles();
    $tenantB = createTenantForAdminFiles('admin-files-b');
    $admin = createTenantAdminForAdminFiles($tenantA);
    $uploaderB = createGuestUploaderForAdminFiles($tenantB, '08100000000', 'Uploader B');
    $otherFile = createFileForAdminFiles($tenantB, $uploaderB);

    $this->actingAs($admin, 'tenant_admin')
        ->get('/admin-files-a/admin/files/'.$otherFile->id)
        ->assertNotFound();
});

it('allows tenant admins to download managed files', function () {
    Storage::fake('local');

    $tenant = createTenantForAdminFiles();
    $admin = createTenantAdminForAdminFiles($tenant);
    $uploader = createGuestUploaderForAdminFiles($tenant);
    $file = createFileForAdminFiles($tenant, $uploader, [
        'stored_name' => 'tenant-'.$tenant->id.'/review-file.pdf',
    ]);

    Storage::disk('local')->put($file->stored_name, 'reviewed');

    $this->actingAs($admin, 'tenant_admin')
        ->get('/admin-files-a/admin/files/'.$file->id.'/download')
        ->assertOk();
});

it('allows tenant admins to restore soft deleted files', function () {
    $tenant = createTenantForAdminFiles();
    $admin = createTenantAdminForAdminFiles($tenant);
    $uploader = createGuestUploaderForAdminFiles($tenant);
    $file = createFileForAdminFiles($tenant, $uploader);

    $file->delete();

    expect($file->fresh()->trashed())->toBeTrue();

    $this->actingAs($admin, 'tenant_admin')
        ->patch('/admin-files-a/admin/files/'.$file->id.'/restore')
        ->assertRedirect('/admin-files-a/admin/files/deleted');

    expect($file->fresh()->trashed())->toBeFalse();
});

it('allows tenant admins to soft delete active files without reducing storage usage', function () {
    $tenant = createTenantForAdminFiles(overrides: [
        'storage_used_bytes' => 4096,
    ]);
    $admin = createTenantAdminForAdminFiles($tenant);
    $uploader = createGuestUploaderForAdminFiles($tenant);
    $file = createFileForAdminFiles($tenant, $uploader, [
        'file_size' => 2048,
    ]);

    $this->actingAs($admin, 'tenant_admin')
        ->delete('/admin-files-a/admin/files/'.$file->id.'/archive')
        ->assertRedirect('/admin-files-a/admin/files');

    expect($file->fresh()->trashed())->toBeTrue()
        ->and($tenant->fresh()->storage_used_bytes)->toBe(4096);
});

it('allows tenant admins to permanently delete soft deleted files and reduce storage usage', function () {
    Storage::fake('local');

    $tenant = createTenantForAdminFiles(overrides: [
        'storage_used_bytes' => 4096,
    ]);
    $admin = createTenantAdminForAdminFiles($tenant);
    $uploader = createGuestUploaderForAdminFiles($tenant);
    $file = createFileForAdminFiles($tenant, $uploader, [
        'stored_name' => 'tenant-'.$tenant->id.'/force-delete.pdf',
        'file_size' => 2048,
    ]);

    Storage::disk('local')->put($file->stored_name, 'to-delete');

    $file->delete();

    $this->actingAs($admin, 'tenant_admin')
        ->delete('/admin-files-a/admin/files/'.$file->id)
        ->assertRedirect('/admin-files-a/admin/files/deleted');

    expect(File::withTrashed()->find($file->id))->toBeNull()
        ->and(Storage::disk('local')->exists($file->stored_name))->toBeFalse()
        ->and($tenant->fresh()->storage_used_bytes)->toBe(2048);
});
