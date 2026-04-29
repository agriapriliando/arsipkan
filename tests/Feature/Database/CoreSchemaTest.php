<?php

use App\Models\Tenant;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates all core arsipkan tables', function () {
    $tables = [
        'tenants',
        'guest_uploaders',
        'admin_users',
        'user_accounts',
        'upload_links',
        'categories',
        'tags',
        'files',
        'file_tag_map',
        'file_downloads',
        'score_rules',
        'score_adjustments',
    ];

    foreach ($tables as $table) {
        expect(Schema::hasTable($table))->toBeTrue();
    }
});

it('enforces tenant-bound uniqueness constraints for the core tables', function () {
    $tenantA = Tenant::create([
        'code' => 'TENANT-A',
        'name' => 'Tenant A',
        'slug' => 'tenant-a',
        'storage_quota_bytes' => 1000,
        'storage_used_bytes' => 0,
        'storage_warning_threshold_percent' => 80,
        'is_active' => true,
    ]);

    $tenantB = Tenant::create([
        'code' => 'TENANT-B',
        'name' => 'Tenant B',
        'slug' => 'tenant-b',
        'storage_quota_bytes' => 1000,
        'storage_used_bytes' => 0,
        'storage_warning_threshold_percent' => 80,
        'is_active' => true,
    ]);

    DB::table('guest_uploaders')->insert([
        'tenant_id' => $tenantA->id,
        'name' => 'Uploader A',
        'phone_number' => '08123',
        'phone_number_normalized' => '628123',
        'last_score' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => DB::table('guest_uploaders')->insert([
        'tenant_id' => $tenantA->id,
        'name' => 'Uploader A2',
        'phone_number' => '08123',
        'phone_number_normalized' => '628123',
        'last_score' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);

    DB::table('guest_uploaders')->insert([
        'tenant_id' => $tenantB->id,
        'name' => 'Uploader B',
        'phone_number' => '08123',
        'phone_number_normalized' => '628123',
        'last_score' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('admin_users')->insert([
        'tenant_id' => $tenantA->id,
        'name' => 'Admin A',
        'email' => 'admin@tenant-a.test',
        'password' => 'hash',
        'role' => 'tenant_admin',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => DB::table('admin_users')->insert([
        'tenant_id' => $tenantA->id,
        'name' => 'Admin A2',
        'email' => 'admin@tenant-a.test',
        'password' => 'hash',
        'role' => 'tenant_admin',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);

    DB::table('admin_users')->insert([
        'tenant_id' => null,
        'name' => 'Superadmin 1',
        'email' => 'superadmin@test.local',
        'password' => 'hash',
        'role' => 'superadmin',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => DB::table('admin_users')->insert([
        'tenant_id' => null,
        'name' => 'Superadmin 2',
        'email' => 'superadmin@test.local',
        'password' => 'hash',
        'role' => 'superadmin',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);

    DB::table('upload_links')->insert([
        'tenant_id' => $tenantA->id,
        'code' => 'LINK-A',
        'title' => 'Link A',
        'is_active' => true,
        'usage_count' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => DB::table('upload_links')->insert([
        'tenant_id' => $tenantA->id,
        'code' => 'LINK-A',
        'title' => 'Link A2',
        'is_active' => true,
        'usage_count' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);

    DB::table('categories')->insert([
        'tenant_id' => $tenantA->id,
        'name' => 'Keuangan',
        'slug' => 'keuangan',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => DB::table('categories')->insert([
        'tenant_id' => $tenantA->id,
        'name' => 'Keuangan',
        'slug' => 'keuangan-lain',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);

    DB::table('tags')->insert([
        'tenant_id' => $tenantA->id,
        'name' => 'Penting',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => DB::table('tags')->insert([
        'tenant_id' => $tenantA->id,
        'name' => 'Penting',
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});
