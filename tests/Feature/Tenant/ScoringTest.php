<?php

use App\Models\AdminUser;
use App\Models\File;
use App\Models\FileDownload;
use App\Models\GuestUploader;
use App\Models\ScoreAdjustment;
use App\Models\ScoreRule;
use App\Models\Tenant;
use App\Models\UploadLink;
use App\Models\UserAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function createTenantForScoring(string $slug = 'skor-a', array $overrides = []): Tenant
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

function createTenantAdminForScoring(Tenant $tenant): AdminUser
{
    return AdminUser::create([
        'tenant_id' => $tenant->id,
        'name' => 'Admin '.$tenant->slug,
        'email' => $tenant->slug.'@test.local',
        'password' => Hash::make('secret-password'),
        'role' => AdminUser::ROLE_TENANT_ADMIN,
        'is_active' => true,
    ]);
}

function createGuestUploaderForScoring(Tenant $tenant, string $phone, string $name): GuestUploader
{
    return GuestUploader::create([
        'tenant_id' => $tenant->id,
        'name' => $name,
        'phone_number' => $phone,
        'phone_number_normalized' => '62'.ltrim($phone, '0'),
    ]);
}

function createUserAccountForScoring(Tenant $tenant, GuestUploader $uploader): UserAccount
{
    return UserAccount::create([
        'tenant_id' => $tenant->id,
        'guest_uploader_id' => $uploader->id,
        'password' => Hash::make('secret-password'),
        'is_active' => true,
        'must_change_password' => false,
    ]);
}

function createPublicFileForScoring(Tenant $tenant, GuestUploader $uploader, array $overrides = []): File
{
    $uploadLink = UploadLink::create([
        'tenant_id' => $tenant->id,
        'code' => uniqid('score'),
        'title' => 'Link Score',
    ]);

    return File::create(array_merge([
        'tenant_id' => $tenant->id,
        'guest_uploader_id' => $uploader->id,
        'upload_link_id' => $uploadLink->id,
        'uploaded_via' => File::UPLOADED_VIA_GUEST_LINK,
        'original_name' => 'arsip-skor.pdf',
        'stored_name' => uniqid('score-file-', true).'.pdf',
        'extension' => 'pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 1024,
        'visibility' => File::VISIBILITY_PUBLIC,
        'status' => File::STATUS_VALID,
        'uploaded_at' => now(),
    ], $overrides));
}

it('records public catalog downloads and updates uploader score using the default platform rule', function () {
    Storage::fake('local');

    $tenant = createTenantForScoring();
    $uploader = createGuestUploaderForScoring($tenant, '08111111111', 'Uploader Skor');
    $file = createPublicFileForScoring($tenant, $uploader, [
        'stored_name' => 'tenant-'.$tenant->id.'/catalog-score.pdf',
    ]);

    Storage::disk('local')->put($file->stored_name, 'score');

    $this->get('/skor-a/catalog/'.$file->id.'/download')
        ->assertOk();

    expect(FileDownload::query()->count())->toBe(1)
        ->and(FileDownload::query()->first()->is_counted_for_score)->toBeTrue()
        ->and(ScoreRule::query()->where('is_active', true)->exists())->toBeTrue()
        ->and($uploader->fresh()->last_score)->toBe('11.00');
});

it('does not count self-download from the user portal for score', function () {
    Storage::fake('local');

    $tenant = createTenantForScoring('skor-b');
    $uploader = createGuestUploaderForScoring($tenant, '08122222222', 'Uploader Portal');
    $account = createUserAccountForScoring($tenant, $uploader);
    $file = createPublicFileForScoring($tenant, $uploader, [
        'stored_name' => 'tenant-'.$tenant->id.'/portal-score.pdf',
    ]);

    Storage::disk('local')->put($file->stored_name, 'score');

    $this->actingAs($account, 'user_account')
        ->get('/skor-b/files/'.$file->id.'/download')
        ->assertOk();

    expect(FileDownload::query()->count())->toBe(1)
        ->and(FileDownload::query()->first()->is_counted_for_score)->toBeFalse()
        ->and($uploader->fresh()->last_score)->toBe('10.00');
});

it('shows tenant leaderboards and allows manual score adjustments by tenant admin', function () {
    $tenant = createTenantForScoring('skor-c');
    $admin = createTenantAdminForScoring($tenant);
    $uploaderA = createGuestUploaderForScoring($tenant, '08133333333', 'Uploader A');
    $uploaderB = createGuestUploaderForScoring($tenant, '08144444444', 'Uploader B');

    $fileA = createPublicFileForScoring($tenant, $uploaderA, [
        'uploaded_at' => now()->subDay(),
    ]);
    $fileB = createPublicFileForScoring($tenant, $uploaderB, [
        'uploaded_at' => now()->subDays(2),
    ]);

    FileDownload::create([
        'tenant_id' => $tenant->id,
        'file_id' => $fileA->id,
        'downloaded_at' => now()->subHours(3),
        'is_counted_for_score' => true,
    ]);
    FileDownload::create([
        'tenant_id' => $tenant->id,
        'file_id' => $fileA->id,
        'downloaded_at' => now()->subHours(2),
        'is_counted_for_score' => true,
    ]);
    FileDownload::create([
        'tenant_id' => $tenant->id,
        'file_id' => $fileB->id,
        'downloaded_at' => now()->subHours(1),
        'is_counted_for_score' => true,
    ]);

    $uploaderA->forceFill(['last_score' => '12.00'])->save();
    $uploaderB->forceFill(['last_score' => '11.00'])->save();

    $this->actingAs($admin, 'tenant_admin')
        ->get('/skor-c/admin')
        ->assertOk()
        ->assertSee('Leaderboard Mingguan')
        ->assertSee('Leaderboard Bulanan')
        ->assertSee('Uploader A')
        ->assertSee('Uploader B')
        ->assertSee('Penyesuaian Skor Manual');

    $this->actingAs($admin, 'tenant_admin')
        ->post('/skor-c/admin/score-adjustments', [
            'guest_uploader_id' => $uploaderA->id,
            'delta' => 5,
        ])
        ->assertRedirect('/skor-c/admin');

    expect(ScoreAdjustment::query()->count())->toBe(1)
        ->and($uploaderA->fresh()->last_score)->toBe('17.00');
});

it('rebuilds leaderboard scores for all uploaders in a tenant via artisan command', function () {
    $tenant = createTenantForScoring('skor-d');
    $uploaderA = createGuestUploaderForScoring($tenant, '08155555555', 'Uploader Rebuild A');
    $uploaderB = createGuestUploaderForScoring($tenant, '08166666666', 'Uploader Rebuild B');

    $fileA = createPublicFileForScoring($tenant, $uploaderA);
    $fileB = createPublicFileForScoring($tenant, $uploaderB);

    FileDownload::create([
        'tenant_id' => $tenant->id,
        'file_id' => $fileA->id,
        'downloaded_at' => now(),
        'is_counted_for_score' => true,
    ]);
    FileDownload::create([
        'tenant_id' => $tenant->id,
        'file_id' => $fileB->id,
        'downloaded_at' => now(),
        'is_counted_for_score' => true,
    ]);
    FileDownload::create([
        'tenant_id' => $tenant->id,
        'file_id' => $fileB->id,
        'downloaded_at' => now(),
        'is_counted_for_score' => true,
    ]);

    $uploaderA->forceFill(['last_score' => '0.00'])->save();
    $uploaderB->forceFill(['last_score' => '0.00'])->save();

    $this->artisan('tenant:rebuild-leaderboard', ['tenant_slug' => 'skor-d'])
        ->expectsOutputToContain('skor-d')
        ->assertSuccessful();

    expect($uploaderA->fresh()->last_score)->toBe('11.00')
        ->and($uploaderB->fresh()->last_score)->toBe('12.00');
});
