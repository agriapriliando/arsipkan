<?php

use App\Livewire\Tenant\GuestUploadForm;
use App\Models\File;
use App\Models\GuestUploader;
use App\Models\Tenant;
use App\Models\UploadLink;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function createTenantForGuestUpload(array $attributes = []): Tenant
{
    return Tenant::create(array_merge([
        'code' => 'GUEST',
        'name' => 'Tenant Guest',
        'slug' => 'tenant-guest',
        'storage_quota_bytes' => 10 * 1024 * 1024,
        'storage_used_bytes' => 0,
        'storage_warning_threshold_percent' => 80,
        'is_active' => true,
    ], $attributes));
}

function createUploadLinkForGuestUpload(Tenant $tenant, array $attributes = []): UploadLink
{
    return UploadLink::create(array_merge([
        'tenant_id' => $tenant->id,
        'code' => 'GUEST-UPLOAD',
        'title' => 'Upload Guest',
        'is_active' => true,
        'expires_at' => now()->addDay(),
        'max_usage' => 10,
        'usage_count' => 0,
    ], $attributes));
}

function setGuestUploadTenant(Tenant $tenant): void
{
    app(TenantContext::class)->set($tenant);
}

it('shows the guest upload page for usable upload links', function () {
    $tenant = createTenantForGuestUpload();
    createUploadLinkForGuestUpload($tenant);

    $this->get('/tenant-guest/upload/GUEST-UPLOAD')
        ->assertOk()
        ->assertSee('Upload Guest')
        ->assertSee('Unggah File');
});

it('rejects missing inactive expired and used up upload links', function () {
    $tenant = createTenantForGuestUpload();
    createUploadLinkForGuestUpload($tenant, [
        'code' => 'INACTIVE',
        'is_active' => false,
    ]);
    createUploadLinkForGuestUpload($tenant, [
        'code' => 'EXPIRED',
        'expires_at' => now()->subMinute(),
    ]);
    createUploadLinkForGuestUpload($tenant, [
        'code' => 'USED-UP',
        'max_usage' => 1,
        'usage_count' => 1,
    ]);

    $this->get('/tenant-guest/upload/MISSING')->assertNotFound();
    $this->get('/tenant-guest/upload/INACTIVE')->assertNotFound();
    $this->get('/tenant-guest/upload/EXPIRED')->assertNotFound();
    $this->get('/tenant-guest/upload/USED-UP')->assertNotFound();
});

it('stores guest uploads on private storage and creates file records', function () {
    Storage::fake('local');

    $tenant = createTenantForGuestUpload();
    $uploadLink = createUploadLinkForGuestUpload($tenant);
    setGuestUploadTenant($tenant);

    $uploadedFile = UploadedFile::fake()->create('arsip.pdf', 20, 'application/pdf');

    Livewire::test(GuestUploadForm::class, ['code' => 'GUEST-UPLOAD'])
        ->set('name', 'Budi Pengunggah')
        ->set('phoneNumber', '0812 3456 789')
        ->set('visibility', File::VISIBILITY_PUBLIC)
        ->set('uploadedFile', $uploadedFile)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSee('File berhasil diunggah.');

    $guestUploader = GuestUploader::query()->firstOrFail();
    $file = File::query()->firstOrFail();

    expect($guestUploader->tenant_id)->toBe($tenant->id)
        ->and($guestUploader->phone_number_normalized)->toBe('628123456789')
        ->and($guestUploader->guest_token)->not->toBeNull()
        ->and($file->tenant_id)->toBe($tenant->id)
        ->and($file->guest_uploader_id)->toBe($guestUploader->id)
        ->and($file->upload_link_id)->toBe($uploadLink->id)
        ->and($file->uploaded_via)->toBe(File::UPLOADED_VIA_GUEST_LINK)
        ->and($file->original_name)->toBe('arsip.pdf')
        ->and($file->visibility)->toBe(File::VISIBILITY_PUBLIC)
        ->and($file->status)->toBe(File::STATUS_PENDING_REVIEW)
        ->and($tenant->refresh()->storage_used_bytes)->toBe($file->file_size)
        ->and($uploadLink->refresh()->usage_count)->toBe(1);

    Storage::disk('local')->assertExists($file->stored_name);
});

it('tracks the selected file name for the upload form', function () {
    Storage::fake('local');

    $tenant = createTenantForGuestUpload();
    createUploadLinkForGuestUpload($tenant);
    setGuestUploadTenant($tenant);

    Livewire::test(GuestUploadForm::class, ['code' => 'GUEST-UPLOAD'])
        ->set('uploadedFile', UploadedFile::fake()->create('nama-muncul.pdf', 20, 'application/pdf'))
        ->assertSet('uploadedFileName', 'nama-muncul.pdf');
});

it('sets internal and private guest uploads as valid immediately', function (string $visibility) {
    Storage::fake('local');

    $tenant = createTenantForGuestUpload([
        'slug' => 'tenant-'.$visibility,
    ]);
    createUploadLinkForGuestUpload($tenant);
    setGuestUploadTenant($tenant);

    Livewire::test(GuestUploadForm::class, ['code' => 'GUEST-UPLOAD'])
        ->set('name', 'Siti Pengunggah')
        ->set('phoneNumber', '8123456789')
        ->set('visibility', $visibility)
        ->set('uploadedFile', UploadedFile::fake()->create('arsip.txt', 5, 'text/plain'))
        ->call('submit')
        ->assertHasNoErrors();

    expect(File::query()->firstOrFail()->status)->toBe(File::STATUS_VALID);
})->with([
    File::VISIBILITY_INTERNAL,
    File::VISIBILITY_PRIVATE,
]);

it('reuses guest uploaders by normalized phone number and updates the browser token', function () {
    Storage::fake('local');

    $tenant = createTenantForGuestUpload();
    createUploadLinkForGuestUpload($tenant);
    setGuestUploadTenant($tenant);

    $guestUploader = GuestUploader::create([
        'tenant_id' => $tenant->id,
        'name' => 'Nama Lama',
        'phone_number' => '08123456789',
        'phone_number_normalized' => '628123456789',
        'guest_token' => 'old-token',
    ]);

    Livewire::withCookie('arsipkan_guest_token_'.$tenant->id, 'browser-token')
        ->test(GuestUploadForm::class, ['code' => 'GUEST-UPLOAD'])
        ->set('name', 'Nama Baru')
        ->set('phoneNumber', '0812 3456 789')
        ->set('visibility', File::VISIBILITY_PRIVATE)
        ->set('uploadedFile', UploadedFile::fake()->create('arsip.pdf', 5, 'application/pdf'))
        ->call('submit')
        ->assertHasNoErrors();

    expect(GuestUploader::query()->count())->toBe(1)
        ->and($guestUploader->refresh()->name)->toBe('Nama Baru')
        ->and($guestUploader->guest_token)->toBe('browser-token');
});

it('prefills uploader identity from a saved cookie across upload codes in the same tenant', function () {
    $tenant = createTenantForGuestUpload();
    createUploadLinkForGuestUpload($tenant, [
        'code' => 'UPLOAD-A',
        'title' => 'Upload A',
    ]);
    createUploadLinkForGuestUpload($tenant, [
        'code' => 'UPLOAD-B',
        'title' => 'Upload B',
    ]);
    setGuestUploadTenant($tenant);

    Livewire::withCookie('arsipkan_guest_identity_'.$tenant->id, json_encode([
        'name' => 'Budi Tersimpan',
        'phoneNumber' => '08123456789',
        'visibility' => File::VISIBILITY_PUBLIC,
    ]))
        ->test(GuestUploadForm::class, ['code' => 'UPLOAD-B'])
        ->assertSet('name', 'Budi Tersimpan')
        ->assertSet('phoneNumber', '08123456789')
        ->assertSet('visibility', File::VISIBILITY_PUBLIC);
});

it('stores uploader identity automatically when the form values change', function () {
    $tenant = createTenantForGuestUpload();
    createUploadLinkForGuestUpload($tenant);
    setGuestUploadTenant($tenant);

    Livewire::test(GuestUploadForm::class, ['code' => 'GUEST-UPLOAD'])
        ->set('name', 'Budi Simpan')
        ->set('phoneNumber', '0812 3456 789')
        ->set('visibility', File::VISIBILITY_PRIVATE);

    $cookie = Cookie::queued('arsipkan_guest_identity_'.$tenant->id);

    expect($cookie)->not->toBeNull()
        ->and(json_decode(urldecode($cookie->getValue()), true))->toMatchArray([
            'name' => 'Budi Simpan',
            'phoneNumber' => '0812 3456 789',
            'visibility' => File::VISIBILITY_PRIVATE,
        ]);
});

it('rejects uploads that exceed tenant storage quota', function () {
    Storage::fake('local');

    $tenant = createTenantForGuestUpload([
        'storage_quota_bytes' => 10 * 1024,
        'storage_used_bytes' => 8 * 1024,
    ]);
    $uploadLink = createUploadLinkForGuestUpload($tenant);
    setGuestUploadTenant($tenant);

    Livewire::test(GuestUploadForm::class, ['code' => 'GUEST-UPLOAD'])
        ->set('name', 'Budi Pengunggah')
        ->set('phoneNumber', '08123456789')
        ->set('visibility', File::VISIBILITY_PRIVATE)
        ->set('uploadedFile', UploadedFile::fake()->create('besar.pdf', 5, 'application/pdf'))
        ->call('submit')
        ->assertHasErrors(['uploadedFile']);

    expect(File::query()->exists())->toBeFalse()
        ->and(GuestUploader::query()->exists())->toBeFalse()
        ->and($tenant->refresh()->storage_used_bytes)->toBe(8 * 1024)
        ->and($uploadLink->refresh()->usage_count)->toBe(0);

    Storage::disk('local')->assertDirectoryEmpty('/');
});
