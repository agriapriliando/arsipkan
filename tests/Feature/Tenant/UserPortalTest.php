<?php

use App\Models\File;
use App\Models\GuestUploader;
use App\Models\Tenant;
use App\Models\UploadLink;
use App\Models\UserAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function createTenantForUserPortal(string $slug = 'portal-a', array $overrides = []): Tenant
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

function createUserForUserPortal(Tenant $tenant, string $phone = '08123456789', string $name = 'Uploader Portal'): UserAccount
{
    $guestUploader = GuestUploader::create([
        'tenant_id' => $tenant->id,
        'name' => $name,
        'phone_number' => $phone,
        'phone_number_normalized' => '62'.ltrim($phone, '0'),
    ]);

    return UserAccount::create([
        'tenant_id' => $tenant->id,
        'guest_uploader_id' => $guestUploader->id,
        'password' => Hash::make('secret-password'),
        'is_active' => true,
        'must_change_password' => false,
    ]);
}

function createFileForUserPortal(Tenant $tenant, UserAccount $owner, array $overrides = []): File
{
    return File::create(array_merge([
        'tenant_id' => $tenant->id,
        'guest_uploader_id' => $owner->guest_uploader_id,
        'uploaded_via' => File::UPLOADED_VIA_USER_PORTAL,
        'original_name' => 'arsip.pdf',
        'stored_name' => uniqid('portal-', true).'.pdf',
        'extension' => 'pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 1024,
        'visibility' => File::VISIBILITY_PRIVATE,
        'status' => File::STATUS_VALID,
        'uploaded_at' => now(),
    ], $overrides));
}

it('shows the user portal pages for authenticated user uploaders', function () {
    $tenant = createTenantForUserPortal();
    $account = createUserForUserPortal($tenant);

    UploadLink::create([
        'tenant_id' => $tenant->id,
        'code' => 'AKTIF-PORTAL',
        'title' => 'Link Aktif Portal',
        'is_active' => true,
        'expires_at' => now()->addDay(),
        'max_usage' => 10,
        'usage_count' => 1,
    ]);
    UploadLink::create([
        'tenant_id' => $tenant->id,
        'code' => 'EXPIRED-PORTAL',
        'title' => 'Link Expired Portal',
        'is_active' => true,
        'expires_at' => now()->subMinute(),
        'max_usage' => 10,
        'usage_count' => 1,
    ]);

    $this->actingAs($account, 'user_account')
        ->get('/portal-a/dashboard')
        ->assertOk()
        ->assertSee('Portal Uploader')
        ->assertSee('Daftar Link Upload Aktif')
        ->assertSee('Link Aktif Portal')
        ->assertSee('/portal-a/upload/AKTIF-PORTAL')
        ->assertDontSee('Link Expired Portal');

    $this->actingAs($account, 'user_account')
        ->get('/portal-a/my-files')
        ->assertOk()
        ->assertSee('Berkas Saya');

    $this->actingAs($account, 'user_account')
        ->get('/portal-a/tenant-files')
        ->assertOk()
        ->assertSee('Arsip Tenant portal-a');

    $this->actingAs($account, 'user_account')
        ->get('/portal-a/profile')
        ->assertOk()
        ->assertSee('Uploader Portal');
});

it('shows only owned files and valid internal tenant files in the right pages', function () {
    $tenant = createTenantForUserPortal();
    $account = createUserForUserPortal($tenant, '08123456789', 'Owner Portal');
    $peer = createUserForUserPortal($tenant, '08111111111', 'Peer Portal');

    $ownedFile = createFileForUserPortal($tenant, $account, [
        'original_name' => 'owned.pdf',
        'visibility' => File::VISIBILITY_PRIVATE,
    ]);
    createFileForUserPortal($tenant, $peer, [
        'original_name' => 'peer-internal.pdf',
        'visibility' => File::VISIBILITY_INTERNAL,
        'status' => File::STATUS_VALID,
    ]);
    createFileForUserPortal($tenant, $peer, [
        'original_name' => 'peer-private.pdf',
        'visibility' => File::VISIBILITY_PRIVATE,
        'status' => File::STATUS_VALID,
    ]);
    createFileForUserPortal($tenant, $peer, [
        'original_name' => 'peer-public.pdf',
        'visibility' => File::VISIBILITY_PUBLIC,
        'status' => File::STATUS_VALID,
    ]);

    $this->actingAs($account, 'user_account')
        ->get('/portal-a/my-files')
        ->assertOk()
        ->assertSee('owned.pdf')
        ->assertDontSee('peer-internal.pdf');

    $this->actingAs($account, 'user_account')
        ->get('/portal-a/tenant-files')
        ->assertOk()
        ->assertSee('peer-internal.pdf')
        ->assertSee('peer-public.pdf')
        ->assertSee('internal')
        ->assertSee('public')
        ->assertDontSee('peer-private.pdf');

    $this->actingAs($account, 'user_account')
        ->get('/portal-a/files/'.$ownedFile->id)
        ->assertOk()
        ->assertSee('owned.pdf');
});

it('allows user uploaders to soft delete only their own files', function () {
    $tenant = createTenantForUserPortal();
    $account = createUserForUserPortal($tenant);
    $peer = createUserForUserPortal($tenant, '08100000000', 'Peer Hapus');

    $ownedFile = createFileForUserPortal($tenant, $account);
    $peerFile = createFileForUserPortal($tenant, $peer, ['original_name' => 'peer.pdf']);

    $this->actingAs($account, 'user_account')
        ->delete('/portal-a/files/'.$ownedFile->id)
        ->assertRedirect('/portal-a/my-files');

    expect($ownedFile->fresh()->trashed())->toBeTrue()
        ->and($ownedFile->fresh()->deleted_by_user_account_id)->toBe($account->id);

    $this->actingAs($account, 'user_account')
        ->delete('/portal-a/files/'.$peerFile->id)
        ->assertForbidden();
});

it('allows user uploaders to change visibility of their own files only', function () {
    $tenant = createTenantForUserPortal();
    $account = createUserForUserPortal($tenant);
    $peer = createUserForUserPortal($tenant, '08100000000', 'Peer Visibilitas');

    $ownedFile = createFileForUserPortal($tenant, $account, [
        'visibility' => File::VISIBILITY_PRIVATE,
        'status' => File::STATUS_VALID,
    ]);
    $peerFile = createFileForUserPortal($tenant, $peer, [
        'visibility' => File::VISIBILITY_PRIVATE,
        'status' => File::STATUS_VALID,
    ]);

    $this->actingAs($account, 'user_account')
        ->patch('/portal-a/files/'.$ownedFile->id.'/visibility', [
            'visibility' => File::VISIBILITY_PUBLIC,
        ])
        ->assertRedirect('/portal-a/my-files');

    expect($ownedFile->fresh()->visibility)->toBe(File::VISIBILITY_PUBLIC)
        ->and($ownedFile->fresh()->status)->toBe(File::STATUS_PENDING_REVIEW);

    $this->actingAs($account, 'user_account')
        ->patch('/portal-a/files/'.$peerFile->id.'/visibility', [
            'visibility' => File::VISIBILITY_INTERNAL,
        ])
        ->assertForbidden();
});

it('allows user uploaders to download their own private files plus internal and public tenant files', function () {
    Storage::fake('local');

    $tenant = createTenantForUserPortal();
    $account = createUserForUserPortal($tenant);
    $peer = createUserForUserPortal($tenant, '08188888888', 'Peer Unduh');

    $ownedPrivate = createFileForUserPortal($tenant, $account, [
        'original_name' => 'private-owned.pdf',
        'stored_name' => 'tenant-'.$tenant->id.'/private-owned.pdf',
        'visibility' => File::VISIBILITY_PRIVATE,
        'status' => File::STATUS_VALID,
    ]);
    $internalFile = createFileForUserPortal($tenant, $peer, [
        'original_name' => 'internal-tenant.pdf',
        'stored_name' => 'tenant-'.$tenant->id.'/internal-tenant.pdf',
        'visibility' => File::VISIBILITY_INTERNAL,
        'status' => File::STATUS_VALID,
    ]);
    $publicFile = createFileForUserPortal($tenant, $peer, [
        'original_name' => 'public-tenant.pdf',
        'stored_name' => 'tenant-'.$tenant->id.'/public-tenant.pdf',
        'visibility' => File::VISIBILITY_PUBLIC,
        'status' => File::STATUS_VALID,
    ]);
    $peerPrivate = createFileForUserPortal($tenant, $peer, [
        'original_name' => 'peer-private.pdf',
        'stored_name' => 'tenant-'.$tenant->id.'/peer-private.pdf',
        'visibility' => File::VISIBILITY_PRIVATE,
        'status' => File::STATUS_VALID,
    ]);

    Storage::disk('local')->put($ownedPrivate->stored_name, 'private');
    Storage::disk('local')->put($internalFile->stored_name, 'internal');
    Storage::disk('local')->put($publicFile->stored_name, 'public');
    Storage::disk('local')->put($peerPrivate->stored_name, 'peer-private');

    $this->actingAs($account, 'user_account')
        ->get('/portal-a/files/'.$ownedPrivate->id.'/download')
        ->assertOk();

    $this->actingAs($account, 'user_account')
        ->get('/portal-a/files/'.$internalFile->id.'/download')
        ->assertOk();

    $this->actingAs($account, 'user_account')
        ->get('/portal-a/files/'.$publicFile->id.'/download')
        ->assertOk();

    $this->actingAs($account, 'user_account')
        ->get('/portal-a/files/'.$peerPrivate->id.'/download')
        ->assertForbidden();
});
