<?php

use App\Models\AdminUser;
use App\Models\Category;
use App\Models\File;
use App\Models\GuestUploader;
use App\Models\Tag;
use App\Models\Tenant;
use App\Models\UploadLink;
use App\Models\UserAccount;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function createTenantForPolicy(string $slug): Tenant
{
    return Tenant::create([
        'code' => strtoupper(str_replace('-', '_', $slug)),
        'name' => 'Tenant '.$slug,
        'slug' => $slug,
        'storage_quota_bytes' => 1000,
        'storage_used_bytes' => 0,
        'storage_warning_threshold_percent' => 80,
        'is_active' => true,
    ]);
}

function setPolicyTenant(Tenant $tenant): void
{
    app(TenantContext::class)->set($tenant);
}

function createTenantAdminForPolicy(Tenant $tenant, string $email): AdminUser
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

function createUserAccountForPolicy(Tenant $tenant, string $phone): UserAccount
{
    $uploader = GuestUploader::create([
        'tenant_id' => $tenant->id,
        'name' => 'Uploader '.$phone,
        'phone_number' => $phone,
        'phone_number_normalized' => $phone,
    ]);

    return UserAccount::create([
        'tenant_id' => $tenant->id,
        'guest_uploader_id' => $uploader->id,
        'password' => Hash::make('secret-password'),
        'is_active' => true,
        'must_change_password' => false,
    ]);
}

function createFileForPolicy(Tenant $tenant, UserAccount $owner, string $visibility = File::VISIBILITY_PRIVATE): File
{
    return File::create([
        'tenant_id' => $tenant->id,
        'guest_uploader_id' => $owner->guest_uploader_id,
        'uploaded_via' => File::UPLOADED_VIA_USER_PORTAL,
        'original_name' => 'file.pdf',
        'stored_name' => uniqid('file-', true).'.pdf',
        'extension' => 'pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 10,
        'visibility' => $visibility,
        'status' => File::STATUS_VALID,
        'uploaded_at' => now(),
    ]);
}

it('allows user uploaders to view owned files and valid internal tenant files only', function () {
    $tenant = createTenantForPolicy('policy-a');
    $otherTenant = createTenantForPolicy('policy-b');
    setPolicyTenant($tenant);

    $owner = createUserAccountForPolicy($tenant, '628111');
    $peer = createUserAccountForPolicy($tenant, '628222');
    $otherTenantUser = createUserAccountForPolicy($otherTenant, '628333');

    $ownedPrivateFile = createFileForPolicy($tenant, $owner, File::VISIBILITY_PRIVATE);
    $peerInternalFile = createFileForPolicy($tenant, $peer, File::VISIBILITY_INTERNAL);
    $peerPrivateFile = createFileForPolicy($tenant, $peer, File::VISIBILITY_PRIVATE);
    $otherTenantInternalFile = createFileForPolicy($otherTenant, $otherTenantUser, File::VISIBILITY_INTERNAL);

    expect($owner->can('view', $ownedPrivateFile))->toBeTrue()
        ->and($owner->can('view', $peerInternalFile))->toBeTrue()
        ->and($owner->can('view', $peerPrivateFile))->toBeFalse()
        ->and($owner->can('view', $otherTenantInternalFile))->toBeFalse();
});

it('allows user uploaders to soft delete only their own files', function () {
    $tenant = createTenantForPolicy('policy-c');
    setPolicyTenant($tenant);

    $owner = createUserAccountForPolicy($tenant, '628444');
    $peer = createUserAccountForPolicy($tenant, '628555');

    $ownedFile = createFileForPolicy($tenant, $owner);
    $peerFile = createFileForPolicy($tenant, $peer);

    expect($owner->can('delete', $ownedFile))->toBeTrue()
        ->and($owner->can('delete', $peerFile))->toBeFalse()
        ->and($owner->can('restore', $ownedFile))->toBeFalse()
        ->and($owner->can('forceDelete', $ownedFile))->toBeFalse()
        ->and($owner->can('updateMetadata', $ownedFile))->toBeFalse();
});

it('allows tenant admins to manage only files inside their active tenant context', function () {
    $tenant = createTenantForPolicy('policy-d');
    $otherTenant = createTenantForPolicy('policy-e');
    setPolicyTenant($tenant);

    $admin = createTenantAdminForPolicy($tenant, 'admin-policy-d@test.local');
    $otherAdmin = createTenantAdminForPolicy($otherTenant, 'admin-policy-e@test.local');
    $owner = createUserAccountForPolicy($tenant, '628666');
    $otherOwner = createUserAccountForPolicy($otherTenant, '628777');

    $file = createFileForPolicy($tenant, $owner);
    $otherFile = createFileForPolicy($otherTenant, $otherOwner);

    expect($admin->can('view', $file))->toBeTrue()
        ->and($admin->can('review', $file))->toBeTrue()
        ->and($admin->can('updateMetadata', $file))->toBeTrue()
        ->and($admin->can('restore', $file))->toBeTrue()
        ->and($admin->can('forceDelete', $file))->toBeTrue()
        ->and($admin->can('delete', $file))->toBeFalse()
        ->and($admin->can('view', $otherFile))->toBeFalse()
        ->and($otherAdmin->can('view', $file))->toBeFalse();
});

it('allows superadmins to manage private files only inside an explicit tenant context', function () {
    $tenant = createTenantForPolicy('policy-f');
    $otherTenant = createTenantForPolicy('policy-g');

    $superadmin = AdminUser::create([
        'tenant_id' => null,
        'name' => 'Superadmin',
        'email' => 'super-policy@test.local',
        'password' => Hash::make('secret-password'),
        'role' => AdminUser::ROLE_SUPERADMIN,
        'is_active' => true,
    ]);

    $owner = createUserAccountForPolicy($tenant, '628888');
    $otherOwner = createUserAccountForPolicy($otherTenant, '628999');
    $file = createFileForPolicy($tenant, $owner);
    $otherFile = createFileForPolicy($otherTenant, $otherOwner);

    expect($superadmin->can('view', $file))->toBeFalse();

    setPolicyTenant($tenant);

    expect($superadmin->can('view', $file))->toBeTrue()
        ->and($superadmin->can('forceDelete', $file))->toBeTrue()
        ->and($superadmin->can('view', $otherFile))->toBeFalse();
});

it('limits tenant master data and uploader account management to admins in context', function () {
    $tenant = createTenantForPolicy('policy-h');
    setPolicyTenant($tenant);

    $admin = createTenantAdminForPolicy($tenant, 'admin-policy-h@test.local');
    $user = createUserAccountForPolicy($tenant, '628101');

    $category = Category::create([
        'tenant_id' => $tenant->id,
        'name' => 'Keuangan',
        'slug' => 'keuangan',
    ]);

    $tag = Tag::create([
        'tenant_id' => $tenant->id,
        'name' => 'Penting',
    ]);

    $uploadLink = UploadLink::create([
        'tenant_id' => $tenant->id,
        'code' => 'UPLOAD',
        'title' => 'Upload',
    ]);

    expect($admin->can('update', $category))->toBeTrue()
        ->and($admin->can('delete', $tag))->toBeTrue()
        ->and($admin->can('create', UploadLink::class))->toBeTrue()
        ->and($admin->can('update', $uploadLink))->toBeTrue()
        ->and($admin->can('resetPassword', $user))->toBeTrue()
        ->and($user->can('update', $category))->toBeFalse()
        ->and($user->can('delete', $tag))->toBeFalse()
        ->and($user->can('view', $user))->toBeTrue()
        ->and($user->can('resetPassword', $user))->toBeFalse();
});

it('allows guest uploads only through usable upload links in the active tenant', function () {
    $tenant = createTenantForPolicy('policy-i');
    setPolicyTenant($tenant);

    $activeLink = UploadLink::create([
        'tenant_id' => $tenant->id,
        'code' => 'ACTIVE',
        'title' => 'Active',
        'is_active' => true,
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

    expect(Gate::forUser(null)->allows('uploadAsGuest', $activeLink))->toBeTrue()
        ->and(Gate::forUser(null)->allows('uploadAsGuest', $expiredLink))->toBeFalse()
        ->and(Gate::forUser(null)->allows('uploadAsGuest', $usedUpLink))->toBeFalse();
});
