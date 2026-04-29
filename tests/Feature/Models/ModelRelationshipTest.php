<?php

use App\Models\AdminUser;
use App\Models\Category;
use App\Models\File as FileRecord;
use App\Models\FileDownload;
use App\Models\GuestUploader;
use App\Models\ScoreAdjustment;
use App\Models\ScoreRule;
use App\Models\Tag;
use App\Models\Tenant;
use App\Models\UploadLink;
use App\Models\UserAccount;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('defines the main tenant relationships', function () {
    $tenant = Tenant::create([
        'code' => 'TENANT-A',
        'name' => 'Tenant A',
        'slug' => 'tenant-a',
        'storage_quota_bytes' => 1000,
        'storage_used_bytes' => 0,
        'storage_warning_threshold_percent' => 80,
        'is_active' => true,
    ]);

    $tenantAdmin = AdminUser::create([
        'tenant_id' => $tenant->id,
        'name' => 'Admin Tenant',
        'email' => 'admin@tenant.test',
        'password' => 'hash',
        'role' => AdminUser::ROLE_TENANT_ADMIN,
        'is_active' => true,
    ]);

    $uploader = GuestUploader::create([
        'tenant_id' => $tenant->id,
        'name' => 'Uploader',
        'phone_number' => '08123',
        'phone_number_normalized' => '628123',
    ]);

    $userAccount = UserAccount::create([
        'tenant_id' => $tenant->id,
        'guest_uploader_id' => $uploader->id,
        'password' => 'hash',
        'created_by_admin_id' => $tenantAdmin->id,
    ]);

    $uploadLink = UploadLink::create([
        'tenant_id' => $tenant->id,
        'code' => 'LINK-A',
        'title' => 'Link Upload',
        'created_by_admin_id' => $tenantAdmin->id,
    ]);

    $category = Category::create([
        'tenant_id' => $tenant->id,
        'name' => 'Keuangan',
        'slug' => 'keuangan',
    ]);

    $tag = Tag::create([
        'tenant_id' => $tenant->id,
        'name' => 'Penting',
    ]);

    $file = FileRecord::create([
        'tenant_id' => $tenant->id,
        'guest_uploader_id' => $uploader->id,
        'upload_link_id' => $uploadLink->id,
        'uploaded_via' => FileRecord::UPLOADED_VIA_GUEST_LINK,
        'original_name' => 'laporan.pdf',
        'stored_name' => 'stored.pdf',
        'extension' => 'pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 512,
        'visibility' => FileRecord::VISIBILITY_PUBLIC,
        'status' => FileRecord::STATUS_PENDING_REVIEW,
        'category_id' => $category->id,
        'uploaded_at' => now(),
        'reviewed_by_admin_id' => $tenantAdmin->id,
    ]);

    $file->tags()->attach($tag);

    $download = FileDownload::create([
        'tenant_id' => $tenant->id,
        'file_id' => $file->id,
        'ip_address' => '127.0.0.1',
        'downloaded_at' => now(),
    ]);

    $adjustment = ScoreAdjustment::create([
        'tenant_id' => $tenant->id,
        'guest_uploader_id' => $uploader->id,
        'nilai_sebelum' => 0,
        'nilai_sesudah' => 10,
        'selisih' => 10,
        'updated_by_admin_id' => $tenantAdmin->id,
    ]);

    expect($tenant->guestUploaders()->first()->is($uploader))->toBeTrue()
        ->and($tenant->tenantAdmins()->first()->is($tenantAdmin))->toBeTrue()
        ->and($tenant->userAccounts()->first()->is($userAccount))->toBeTrue()
        ->and($tenant->uploadLinks()->first()->is($uploadLink))->toBeTrue()
        ->and($tenant->files()->first()->is($file))->toBeTrue()
        ->and($tenant->categories()->first()->is($category))->toBeTrue()
        ->and($tenant->tags()->first()->is($tag))->toBeTrue()
        ->and($tenant->fileDownloads()->first()->is($download))->toBeTrue()
        ->and($tenant->scoreAdjustments()->first()->is($adjustment))->toBeTrue();
});

it('defines file, uploader, admin, and score relationships', function () {
    $tenant = Tenant::create([
        'code' => 'TENANT-B',
        'name' => 'Tenant B',
        'slug' => 'tenant-b',
        'storage_quota_bytes' => 1000,
        'storage_used_bytes' => 0,
        'storage_warning_threshold_percent' => 80,
        'is_active' => true,
    ]);

    $superadmin = AdminUser::create([
        'tenant_id' => null,
        'name' => 'Superadmin',
        'email' => 'superadmin@test.local',
        'password' => 'hash',
        'role' => AdminUser::ROLE_SUPERADMIN,
        'is_active' => true,
    ]);

    $tenantAdmin = AdminUser::create([
        'tenant_id' => $tenant->id,
        'name' => 'Admin Tenant',
        'email' => 'admin-b@tenant.test',
        'password' => 'hash',
        'role' => AdminUser::ROLE_TENANT_ADMIN,
        'is_active' => true,
    ]);

    $uploader = GuestUploader::create([
        'tenant_id' => $tenant->id,
        'name' => 'Uploader',
        'phone_number' => '08124',
        'phone_number_normalized' => '628124',
    ]);

    $userAccount = UserAccount::create([
        'tenant_id' => $tenant->id,
        'guest_uploader_id' => $uploader->id,
        'password' => 'hash',
        'created_by_admin_id' => $tenantAdmin->id,
    ]);

    $uploadLink = UploadLink::create([
        'tenant_id' => $tenant->id,
        'code' => 'LINK-B',
        'title' => 'Link Upload B',
        'created_by_admin_id' => $tenantAdmin->id,
    ]);

    $category = Category::create([
        'tenant_id' => $tenant->id,
        'name' => 'Arsip',
        'slug' => 'arsip',
    ]);

    $tag = Tag::create([
        'tenant_id' => $tenant->id,
        'name' => 'Internal',
    ]);

    $file = FileRecord::create([
        'tenant_id' => $tenant->id,
        'guest_uploader_id' => $uploader->id,
        'upload_link_id' => $uploadLink->id,
        'uploaded_via' => FileRecord::UPLOADED_VIA_USER_PORTAL,
        'original_name' => 'dokumen.docx',
        'stored_name' => 'stored.docx',
        'extension' => 'docx',
        'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'file_size' => 128,
        'visibility' => FileRecord::VISIBILITY_INTERNAL,
        'status' => FileRecord::STATUS_VALID,
        'category_id' => $category->id,
        'uploaded_at' => now(),
        'reviewed_by_admin_id' => $tenantAdmin->id,
        'deleted_by_user_account_id' => $userAccount->id,
        'permanently_deleted_by_admin_id' => $tenantAdmin->id,
    ]);

    $file->tags()->attach($tag);

    $download = FileDownload::create([
        'tenant_id' => $tenant->id,
        'file_id' => $file->id,
        'downloaded_at' => now(),
    ]);

    $scoreRule = ScoreRule::create([
        'upload_valid_point' => 10,
        'download_point' => 1,
        'is_active' => true,
        'created_by_superadmin_id' => $superadmin->id,
    ]);

    $adjustment = ScoreAdjustment::create([
        'tenant_id' => $tenant->id,
        'guest_uploader_id' => $uploader->id,
        'nilai_sebelum' => 0,
        'nilai_sesudah' => 20,
        'selisih' => 20,
        'updated_by_admin_id' => $tenantAdmin->id,
    ]);

    expect($uploader->userAccount->is($userAccount))->toBeTrue()
        ->and($uploader->files()->first()->is($file))->toBeTrue()
        ->and($uploader->scoreAdjustments()->first()->is($adjustment))->toBeTrue()
        ->and($userAccount->guestUploader->is($uploader))->toBeTrue()
        ->and($userAccount->createdByAdmin->is($tenantAdmin))->toBeTrue()
        ->and($userAccount->deletedFiles()->first()->is($file))->toBeTrue()
        ->and($uploadLink->createdByAdmin->is($tenantAdmin))->toBeTrue()
        ->and($uploadLink->files()->first()->is($file))->toBeTrue()
        ->and($category->files()->first()->is($file))->toBeTrue()
        ->and($tag->files()->first()->is($file))->toBeTrue()
        ->and($file->guestUploader->is($uploader))->toBeTrue()
        ->and($file->uploadLink->is($uploadLink))->toBeTrue()
        ->and($file->category->is($category))->toBeTrue()
        ->and($file->reviewedByAdmin->is($tenantAdmin))->toBeTrue()
        ->and($file->deletedByUserAccount->is($userAccount))->toBeTrue()
        ->and($file->permanentlyDeletedByAdmin->is($tenantAdmin))->toBeTrue()
        ->and($file->tags()->first()->is($tag))->toBeTrue()
        ->and($file->downloads()->first()->is($download))->toBeTrue()
        ->and($download->file->is($file))->toBeTrue()
        ->and($scoreRule->createdBySuperadmin->is($superadmin))->toBeTrue()
        ->and($adjustment->guestUploader->is($uploader))->toBeTrue()
        ->and($adjustment->updatedByAdmin->is($tenantAdmin))->toBeTrue()
        ->and($tenantAdmin->createdUserAccounts()->first()->is($userAccount))->toBeTrue()
        ->and($tenantAdmin->uploadLinks()->first()->is($uploadLink))->toBeTrue()
        ->and($tenantAdmin->reviewedFiles()->first()->is($file))->toBeTrue()
        ->and($tenantAdmin->permanentlyDeletedFiles()->first()->is($file))->toBeTrue()
        ->and($tenantAdmin->scoreAdjustments()->first()->is($adjustment))->toBeTrue()
        ->and($superadmin->scoreRules()->first()->is($scoreRule))->toBeTrue();
});

it('provides explicit tenant scopes for tenant-bound models', function () {
    $tenantA = Tenant::create([
        'code' => 'TENANT-C',
        'name' => 'Tenant C',
        'slug' => 'tenant-c',
        'storage_quota_bytes' => 1000,
        'storage_used_bytes' => 0,
        'storage_warning_threshold_percent' => 80,
        'is_active' => true,
    ]);

    $tenantB = Tenant::create([
        'code' => 'TENANT-D',
        'name' => 'Tenant D',
        'slug' => 'tenant-d',
        'storage_quota_bytes' => 1000,
        'storage_used_bytes' => 0,
        'storage_warning_threshold_percent' => 80,
        'is_active' => true,
    ]);

    GuestUploader::create([
        'tenant_id' => $tenantA->id,
        'name' => 'Uploader C',
        'phone_number' => '08125',
        'phone_number_normalized' => '628125',
    ]);

    GuestUploader::create([
        'tenant_id' => $tenantB->id,
        'name' => 'Uploader D',
        'phone_number' => '08126',
        'phone_number_normalized' => '628126',
    ]);

    app(TenantContext::class)->set($tenantA);

    expect(GuestUploader::forTenant($tenantA)->pluck('name')->all())->toBe(['Uploader C'])
        ->and(GuestUploader::forCurrentTenant()->pluck('name')->all())->toBe(['Uploader C']);
});

it('fails closed when current tenant scope is used without an active tenant', function () {
    expect(fn () => GuestUploader::forCurrentTenant()->get())->toThrow(LogicException::class);
});
