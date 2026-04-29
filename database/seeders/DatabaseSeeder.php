<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use App\Models\Category;
use App\Models\ScoreRule;
use App\Models\Tag;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $superadmin = AdminUser::query()->updateOrCreate(
            [
                'tenant_id' => null,
                'email' => env('ARSIPKAN_SUPERADMIN_EMAIL', 'superadmin@arsipkan.test'),
                'role' => AdminUser::ROLE_SUPERADMIN,
            ],
            [
                'name' => env('ARSIPKAN_SUPERADMIN_NAME', 'Superadmin Arsipkan'),
                'password' => Hash::make(env('ARSIPKAN_SUPERADMIN_PASSWORD', 'ChangeMe!12345')),
                'is_active' => true,
            ],
        );

        ScoreRule::query()->updateOrCreate(
            ['is_active' => true],
            [
                'upload_valid_point' => (int) env('ARSIPKAN_SCORE_UPLOAD_VALID_POINT', 10),
                'download_point' => (int) env('ARSIPKAN_SCORE_DOWNLOAD_POINT', 1),
                'created_by_superadmin_id' => $superadmin->id,
            ],
        );

        $tenant = Tenant::query()->updateOrCreate(
            ['slug' => env('ARSIPKAN_DEMO_TENANT_SLUG', 'demo-dinas')],
            [
                'code' => env('ARSIPKAN_DEMO_TENANT_CODE', 'DEMO-DINAS'),
                'name' => env('ARSIPKAN_DEMO_TENANT_NAME', 'Demo Dinas Arsip'),
                'storage_quota_bytes' => (int) env('ARSIPKAN_DEMO_TENANT_STORAGE_QUOTA_BYTES', 10 * 1024 * 1024 * 1024),
                'storage_used_bytes' => 0,
                'storage_warning_threshold_percent' => (int) env('ARSIPKAN_DEMO_TENANT_STORAGE_WARNING_THRESHOLD_PERCENT', 80),
                'is_active' => true,
            ],
        );

        AdminUser::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'email' => env('ARSIPKAN_TENANT_ADMIN_EMAIL', 'admin@demo-dinas.test'),
                'role' => AdminUser::ROLE_TENANT_ADMIN,
            ],
            [
                'name' => env('ARSIPKAN_TENANT_ADMIN_NAME', 'Admin Demo Dinas'),
                'password' => Hash::make(env('ARSIPKAN_TENANT_ADMIN_PASSWORD', 'ChangeMe!12345')),
                'is_active' => true,
            ],
        );

        $categories = [
            ['name' => 'Keuangan', 'slug' => 'keuangan', 'description' => 'Dokumen anggaran, laporan, dan pertanggungjawaban.'],
            ['name' => 'Kepegawaian', 'slug' => 'kepegawaian', 'description' => 'Dokumen pegawai, surat tugas, dan administrasi SDM.'],
            ['name' => 'Kegiatan', 'slug' => 'kegiatan', 'description' => 'Dokumentasi kegiatan, agenda, dan laporan pelaksanaan.'],
        ];

        foreach ($categories as $category) {
            Category::query()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'slug' => $category['slug'],
                ],
                [
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'is_active' => true,
                ],
            );
        }

        foreach (['penting', 'laporan', 'publik', 'internal'] as $tag) {
            Tag::query()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'name' => $tag,
                ],
            );
        }
    }
}
