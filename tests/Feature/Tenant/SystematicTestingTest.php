<?php

use App\Livewire\Superadmin\TenantManager;
use App\Livewire\Tenant\GuestUploadForm;
use App\Models\AdminUser;
use App\Models\File;
use App\Models\GuestUploader;
use App\Models\Tenant;
use App\Models\UploadLink;
use App\Models\UserAccount;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function createTenantForSystematicTesting(string $slug, array $overrides = []): Tenant
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

function createTenantAdminForSystematicTesting(Tenant $tenant, string $email): AdminUser
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

function createSuperadminForSystematicTesting(): AdminUser
{
    return AdminUser::create([
        'tenant_id' => null,
        'name' => 'Superadmin',
        'email' => 'systematic-superadmin@test.local',
        'password' => Hash::make('secret-password'),
        'role' => AdminUser::ROLE_SUPERADMIN,
        'is_active' => true,
    ]);
}

function createUploaderForSystematicTesting(Tenant $tenant, string $phone, string $name): GuestUploader
{
    return GuestUploader::create([
        'tenant_id' => $tenant->id,
        'name' => $name,
        'phone_number' => $phone,
        'phone_number_normalized' => '62'.ltrim($phone, '0'),
    ]);
}

function createUserAccountForSystematicTesting(Tenant $tenant, GuestUploader $uploader): UserAccount
{
    return UserAccount::create([
        'tenant_id' => $tenant->id,
        'guest_uploader_id' => $uploader->id,
        'password' => Hash::make('secret-password'),
        'is_active' => true,
        'must_change_password' => false,
    ]);
}

function createUploadLinkForSystematicTesting(Tenant $tenant, array $overrides = []): UploadLink
{
    return UploadLink::create(array_merge([
        'tenant_id' => $tenant->id,
        'code' => 'SYSTEMATIC-UPLOAD',
        'title' => 'Upload Systematic',
        'is_active' => true,
        'expires_at' => now()->addDay(),
        'max_usage' => 10,
        'usage_count' => 0,
    ], $overrides));
}

function createFileForSystematicTesting(Tenant $tenant, GuestUploader $uploader, array $overrides = []): File
{
    $link = createUploadLinkForSystematicTesting($tenant, [
        'code' => uniqid('systematic'),
    ]);

    return File::create(array_merge([
        'tenant_id' => $tenant->id,
        'guest_uploader_id' => $uploader->id,
        'upload_link_id' => $link->id,
        'uploaded_via' => File::UPLOADED_VIA_GUEST_LINK,
        'original_name' => 'systematic.pdf',
        'stored_name' => uniqid('systematic-file-', true).'.pdf',
        'extension' => 'pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 2048,
        'visibility' => File::VISIBILITY_PUBLIC,
        'status' => File::STATUS_PENDING_REVIEW,
        'uploaded_at' => now(),
    ], $overrides));
}

function setTenantForSystematicTesting(Tenant $tenant): void
{
    app(TenantContext::class)->set($tenant);
}

it('covers the documented minimum systematic test checklist', function () {
    Storage::fake('local');

    $tenantA = createTenantForSystematicTesting('systematic-a');
    $tenantB = createTenantForSystematicTesting('systematic-b');
    $tenantAdminA = createTenantAdminForSystematicTesting($tenantA, 'admin-a@test.local');
    $superadmin = createSuperadminForSystematicTesting();

    $samePhoneA = createUploaderForSystematicTesting($tenantA, '08123456789', 'Uploader A');
    $samePhoneB = createUploaderForSystematicTesting($tenantB, '08123456789', 'Uploader B');

    expect($samePhoneA->phone_number_normalized)->toBe($samePhoneB->phone_number_normalized)
        ->and($samePhoneA->tenant_id)->not->toBe($samePhoneB->tenant_id);

    $otherTenantFile = createFileForSystematicTesting($tenantB, $samePhoneB, [
        'status' => File::STATUS_VALID,
    ]);

    $this->actingAs($tenantAdminA, 'tenant_admin')
        ->get('/systematic-a/admin/files/'.$otherTenantFile->id)
        ->assertNotFound();

    $activeLink = createUploadLinkForSystematicTesting($tenantA);
    setTenantForSystematicTesting($tenantA);

    Livewire::test(GuestUploadForm::class, ['code' => 'SYSTEMATIC-UPLOAD'])
        ->set('name', 'Guest Aktif')
        ->set('phoneNumber', '081277788899')
        ->set('visibility', File::VISIBILITY_PUBLIC)
        ->set('uploadedFile', UploadedFile::fake()->create('aktif.pdf', 20, 'application/pdf'))
        ->call('submit')
        ->assertHasNoErrors();

    $publicUpload = File::query()->where('tenant_id', $tenantA->id)->latest('id')->firstOrFail();
    expect($publicUpload->status)->toBe(File::STATUS_PENDING_REVIEW);

    $inactiveLink = createUploadLinkForSystematicTesting($tenantA, [
        'code' => 'INACTIVE-UPLOAD',
        'is_active' => false,
    ]);
    $expiredLink = createUploadLinkForSystematicTesting($tenantA, [
        'code' => 'EXPIRED-UPLOAD',
        'expires_at' => now()->subMinute(),
    ]);

    $this->get('/systematic-a/upload/'.$inactiveLink->code)->assertNotFound();
    $this->get('/systematic-a/upload/'.$expiredLink->code)->assertNotFound();

    $quotaTenant = createTenantForSystematicTesting('systematic-quota', [
        'storage_quota_bytes' => 10 * 1024,
        'storage_used_bytes' => 8 * 1024,
    ]);
    createUploadLinkForSystematicTesting($quotaTenant);
    setTenantForSystematicTesting($quotaTenant);

    Livewire::test(GuestUploadForm::class, ['code' => 'SYSTEMATIC-UPLOAD'])
        ->set('name', 'Guest Quota')
        ->set('phoneNumber', '081299999999')
        ->set('visibility', File::VISIBILITY_PRIVATE)
        ->set('uploadedFile', UploadedFile::fake()->create('besar.pdf', 5, 'application/pdf'))
        ->call('submit')
        ->assertHasErrors(['uploadedFile']);

    $internalTenant = createTenantForSystematicTesting('systematic-internal');
    createUploadLinkForSystematicTesting($internalTenant);
    setTenantForSystematicTesting($internalTenant);

    Livewire::test(GuestUploadForm::class, ['code' => 'SYSTEMATIC-UPLOAD'])
        ->set('name', 'Guest Internal')
        ->set('phoneNumber', '081211122233')
        ->set('visibility', File::VISIBILITY_INTERNAL)
        ->set('uploadedFile', UploadedFile::fake()->create('internal.txt', 5, 'text/plain'))
        ->call('submit')
        ->assertHasNoErrors();

    expect(File::query()->where('tenant_id', $internalTenant->id)->latest('id')->firstOrFail()->status)
        ->toBe(File::STATUS_VALID);

    $privateTenant = createTenantForSystematicTesting('systematic-private');
    createUploadLinkForSystematicTesting($privateTenant);
    setTenantForSystematicTesting($privateTenant);

    Livewire::test(GuestUploadForm::class, ['code' => 'SYSTEMATIC-UPLOAD'])
        ->set('name', 'Guest Private')
        ->set('phoneNumber', '081244455566')
        ->set('visibility', File::VISIBILITY_PRIVATE)
        ->set('uploadedFile', UploadedFile::fake()->create('private.txt', 5, 'text/plain'))
        ->call('submit')
        ->assertHasNoErrors();

    expect(File::query()->where('tenant_id', $privateTenant->id)->latest('id')->firstOrFail()->status)
        ->toBe(File::STATUS_VALID);

    $portalTenant = createTenantForSystematicTesting('systematic-portal');
    $ownerUploader = createUploaderForSystematicTesting($portalTenant, '081355566677', 'Owner Portal');
    $peerUploader = createUploaderForSystematicTesting($portalTenant, '081366677788', 'Peer Portal');
    $ownerAccount = createUserAccountForSystematicTesting($portalTenant, $ownerUploader);
    $ownerFile = createFileForSystematicTesting($portalTenant, $ownerUploader, [
        'visibility' => File::VISIBILITY_PRIVATE,
        'status' => File::STATUS_VALID,
    ]);
    $peerFile = createFileForSystematicTesting($portalTenant, $peerUploader, [
        'visibility' => File::VISIBILITY_PRIVATE,
        'status' => File::STATUS_VALID,
    ]);

    $this->actingAs($ownerAccount, 'user_account')
        ->delete('/systematic-portal/files/'.$ownerFile->id)
        ->assertRedirect('/systematic-portal/my-files');
    $this->actingAs($ownerAccount, 'user_account')
        ->delete('/systematic-portal/files/'.$peerFile->id)
        ->assertForbidden();

    expect($ownerFile->fresh()->trashed())->toBeTrue();

    $restoreTenant = createTenantForSystematicTesting('systematic-restore', [
        'storage_used_bytes' => 4096,
    ]);
    $restoreAdmin = createTenantAdminForSystematicTesting($restoreTenant, 'restore-admin@test.local');
    $restoreUploader = createUploaderForSystematicTesting($restoreTenant, '081377788899', 'Restore Uploader');
    $restoreFile = createFileForSystematicTesting($restoreTenant, $restoreUploader, [
        'stored_name' => 'tenant-'.$restoreTenant->id.'/restore.pdf',
        'file_size' => 2048,
    ]);
    Storage::disk('local')->put($restoreFile->stored_name, 'restore');
    $restoreFile->delete();

    $this->actingAs($restoreAdmin, 'tenant_admin')
        ->patch('/systematic-restore/admin/files/'.$restoreFile->id.'/restore')
        ->assertRedirect('/systematic-restore/admin/files/deleted');

    expect($restoreFile->fresh()->trashed())->toBeFalse();

    $restoreFile->delete();

    $this->actingAs($restoreAdmin, 'tenant_admin')
        ->delete('/systematic-restore/admin/files/'.$restoreFile->id)
        ->assertRedirect('/systematic-restore/admin/files/deleted');

    expect(File::withTrashed()->find($restoreFile->id))->toBeNull()
        ->and($restoreTenant->fresh()->storage_used_bytes)->toBe(2048);

    $this->actingAs($superadmin, 'superadmin');
    Livewire::test(TenantManager::class)
        ->call('enterTenant', $tenantA->id)
        ->assertRedirect('/systematic-a');

    $this->artisan('superadmin:reset-password', [
        'email' => $superadmin->email,
        '--password' => 'new-secret-password',
    ])->assertSuccessful();

    $this->artisan('superadmin:reset-password', [
        'email' => 'missing-superadmin@test.local',
        '--password' => 'new-secret-password',
    ])->assertFailed();
});
