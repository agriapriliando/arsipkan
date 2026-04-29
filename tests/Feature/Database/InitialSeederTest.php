<?php

use App\Models\AdminUser;
use App\Models\Category;
use App\Models\ScoreRule;
use App\Models\Tag;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds the initial platform and demo tenant data', function () {
    $this->seed();

    $tenant = Tenant::query()->where('slug', 'demo-dinas')->firstOrFail();

    expect(AdminUser::query()->superadmin()->where('email', 'superadmin@arsipkan.test')->exists())->toBeTrue()
        ->and(AdminUser::query()->tenantAdmin()->where('tenant_id', $tenant->id)->where('email', 'admin@demo-dinas.test')->exists())->toBeTrue()
        ->and(ScoreRule::query()->where('is_active', true)->where('upload_valid_point', 10)->where('download_point', 1)->exists())->toBeTrue()
        ->and(Category::query()->where('tenant_id', $tenant->id)->count())->toBe(3)
        ->and(Tag::query()->where('tenant_id', $tenant->id)->count())->toBe(4);
});

it('can run the initial seeder repeatedly without duplicating records', function () {
    $this->seed();
    $this->seed();

    expect(Tenant::query()->where('slug', 'demo-dinas')->count())->toBe(1)
        ->and(AdminUser::query()->superadmin()->count())->toBe(1)
        ->and(AdminUser::query()->tenantAdmin()->count())->toBe(1)
        ->and(ScoreRule::query()->where('is_active', true)->count())->toBe(1)
        ->and(Category::query()->count())->toBe(3)
        ->and(Tag::query()->count())->toBe(4);
});
