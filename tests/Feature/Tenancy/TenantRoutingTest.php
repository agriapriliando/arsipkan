<?php

use App\Models\Tenant;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('resolves the active tenant from the first url segment', function () {
    $tenant = Tenant::create([
        'code' => 'DEMO-A',
        'name' => 'Demo Kabupaten A',
        'slug' => 'demo-kabupaten-a',
        'storage_quota_bytes' => 1024,
        'storage_used_bytes' => 0,
        'storage_warning_threshold_percent' => 80,
        'is_active' => true,
    ]);

    $response = $this->get('/demo-kabupaten-a');

    $response
        ->assertOk()
        ->assertViewIs('tenant.home')
        ->assertViewHas('tenant', fn (Tenant $resolvedTenant): bool => $resolvedTenant->is($tenant))
        ->assertSee('Demo Kabupaten A')
        ->assertSee('/demo-kabupaten-a');
});

it('stores the resolved tenant on the request and in the container context', function () {
    Tenant::create([
        'code' => 'DEMO-B',
        'name' => 'Demo Kota B',
        'slug' => 'demo-kota-b',
        'storage_quota_bytes' => 2048,
        'storage_used_bytes' => 0,
        'storage_warning_threshold_percent' => 75,
        'is_active' => true,
    ]);

    Route::middleware('tenant')->get('/_context/{tenant_slug}', function (Request $request, TenantContext $tenantContext) {
        return response()->json([
            'request_tenant' => $request->attributes->get('tenant')?->slug,
            'context_tenant' => $tenantContext->tenant()?->slug,
        ]);
    })->where('tenant_slug', config('tenancy.route_slug_pattern'));

    $this->get('/_context/demo-kota-b')
        ->assertOk()
        ->assertJson([
            'request_tenant' => 'demo-kota-b',
            'context_tenant' => 'demo-kota-b',
        ]);
});

it('returns not found for inactive tenants', function () {
    Tenant::create([
        'code' => 'DEMO-C',
        'name' => 'Demo Tenant Nonaktif',
        'slug' => 'demo-nonaktif',
        'storage_quota_bytes' => 2048,
        'storage_used_bytes' => 0,
        'storage_warning_threshold_percent' => 75,
        'is_active' => false,
    ]);

    $this->get('/demo-nonaktif')->assertNotFound();
});

it('keeps the superadmin area outside tenant resolution', function () {
    $this->get('/superadmin')
        ->assertRedirect('/superadmin/login');
});

it('rejects reserved slugs when saving tenants', function () {
    expect(fn () => Tenant::create([
        'code' => 'SYS-1',
        'name' => 'Tenant Sistem',
        'slug' => 'superadmin',
        'storage_quota_bytes' => 1024,
        'storage_used_bytes' => 0,
        'storage_warning_threshold_percent' => 80,
        'is_active' => true,
    ]))->toThrow(ValidationException::class);
});
