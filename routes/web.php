<?php

use App\Http\Controllers\Auth\SuperadminAuthController;
use App\Http\Controllers\Auth\TenantAdminAuthController;
use App\Http\Controllers\Auth\UserAccountAuthController;
use App\Http\Controllers\Auth\UserAccountPasswordController;
use App\Http\Controllers\Tenant\AdminDashboardController;
use App\Http\Controllers\Tenant\AdminFileReviewController;
use App\Http\Controllers\Tenant\TenantPublicCatalogController;
use App\Http\Controllers\Tenant\UserPortalController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::guard('superadmin')->check()) {
        return redirect()->route('superadmin.dashboard');
    }

    if (Auth::guard('tenant_admin')->check()) {
        $admin = Auth::guard('tenant_admin')->user()?->loadMissing('tenant');
        $tenantSlug = $admin?->tenant?->slug;

        if (is_string($tenantSlug) && $tenantSlug !== '') {
            return redirect()->route('tenant.admin.dashboard', ['tenant_slug' => $tenantSlug]);
        }
    }

    if (Auth::guard('user_account')->check()) {
        $account = Auth::guard('user_account')->user()?->loadMissing('tenant');
        $tenantSlug = $account?->tenant?->slug;

        if (is_string($tenantSlug) && $tenantSlug !== '') {
            if ($account->must_change_password) {
                return redirect()->route('tenant.password.edit', ['tenant_slug' => $tenantSlug]);
            }

            return redirect()->route('tenant.user.dashboard', ['tenant_slug' => $tenantSlug]);
        }
    }

    return view('home');
})->name('home');

Route::prefix('superadmin')
    ->name('superadmin.')
    ->group(function (): void {
        Route::middleware('guest:superadmin')->group(function (): void {
            Route::get('/login', [SuperadminAuthController::class, 'create'])->name('login');
            Route::post('/login', [SuperadminAuthController::class, 'store'])->name('login.store');
        });

        Route::middleware('auth.superadmin')->group(function (): void {
            Route::view('/', 'superadmin.dashboard')->name('dashboard');
            Route::view('/tenants', 'superadmin.tenants.index')->name('tenants.index');
            Route::view('/admins', 'superadmin.admins.index')->name('admins.index');
            Route::view('/master-data', 'superadmin.master-data.index')->name('master-data.index');
            Route::view('/upload-links', 'superadmin.upload-links.index')->name('upload-links.index');
            Route::post('/logout', [SuperadminAuthController::class, 'destroy'])->name('logout');
        });
    });

Route::prefix('{tenant_slug}')
    ->where(['tenant_slug' => config('tenancy.route_slug_pattern')])
    ->middleware('tenant')
    ->name('tenant.')
    ->group(function (): void {
        Route::get('/', [TenantPublicCatalogController::class, 'index'])->name('home');
        Route::get('/leaderboard', [TenantPublicCatalogController::class, 'leaderboard'])->name('leaderboard');
        Route::get('/catalog/{file}', [TenantPublicCatalogController::class, 'show'])->name('catalog.show');
        Route::get('/catalog/{file}/download', [TenantPublicCatalogController::class, 'download'])->name('catalog.download');

        Route::get('/upload/{code}', fn (string $tenant_slug, string $code) => view('tenant.upload.show', [
            'code' => $code,
        ]))->name('upload.show');

        Route::middleware('guest:user_account')->group(function (): void {
            Route::get('/login', [UserAccountAuthController::class, 'create'])->name('login');
            Route::post('/login', [UserAccountAuthController::class, 'store'])->name('login.store');
        });

        Route::middleware(['auth.user_account', 'user.password.changed'])->group(function (): void {
            Route::get('/dashboard', [UserPortalController::class, 'dashboard'])->name('user.dashboard');
            Route::get('/my-files', [UserPortalController::class, 'myFiles'])->name('user.files.mine');
            Route::get('/tenant-files', [UserPortalController::class, 'tenantFiles'])->name('user.files.tenant');
            Route::get('/files/{file}', [UserPortalController::class, 'show'])->name('user.files.show');
            Route::get('/files/{file}/download', [UserPortalController::class, 'download'])->name('user.files.download');
            Route::patch('/files/{file}/visibility', [UserPortalController::class, 'updateVisibility'])->name('user.files.visibility');
            Route::get('/profile', [UserPortalController::class, 'profile'])->name('user.profile');
            Route::delete('/files/{file}', [UserPortalController::class, 'destroy'])->name('user.files.destroy');
            Route::post('/logout', [UserAccountAuthController::class, 'destroy'])->name('logout');
        });

        Route::middleware('auth.user_account')->group(function (): void {
            Route::get('/password/change', [UserAccountPasswordController::class, 'edit'])->name('password.edit');
            Route::put('/password/change', [UserAccountPasswordController::class, 'update'])->name('password.update');
        });

        Route::prefix('admin')
            ->name('admin.')
            ->group(function (): void {
                Route::middleware('guest:tenant_admin')->group(function (): void {
                    Route::get('/login', [TenantAdminAuthController::class, 'create'])->name('login');
                    Route::post('/login', [TenantAdminAuthController::class, 'store'])->name('login.store');
                });

                Route::middleware('auth.tenant_manager')->group(function (): void {
                    Route::get('/master-data', fn (string $tenant_slug) => redirect()->route('tenant.admin.master-data.categories', ['tenant_slug' => $tenant_slug]))->name('master-data.index');
                    Route::view('/master-data/categories', 'tenant.admin.master-data.categories')->name('master-data.categories');
                    Route::view('/master-data/tags', 'tenant.admin.master-data.tags')->name('master-data.tags');
                    Route::view('/upload-links', 'tenant.admin.upload-links.index')->name('upload-links.index');
                    Route::view('/user-accounts', 'tenant.admin.user-accounts.index')->name('user-accounts.index');
                    Route::get('/files/pending', [AdminFileReviewController::class, 'pending'])->name('files.pending');
                    Route::get('/files', [AdminFileReviewController::class, 'index'])->name('files.index');
                    Route::get('/files/deleted', [AdminFileReviewController::class, 'deleted'])->name('files.deleted');
                    Route::get('/files/{file}', [AdminFileReviewController::class, 'show'])->name('files.show');
                    Route::get('/files/{file}/download', [AdminFileReviewController::class, 'download'])->name('files.download');
                    Route::patch('/files/{file}/original-name', [AdminFileReviewController::class, 'updateOriginalName'])->name('files.original-name');
                    Route::patch('/files/{file}', [AdminFileReviewController::class, 'update'])->name('files.update');
                    Route::delete('/files/{file}/archive', [AdminFileReviewController::class, 'archive'])->name('files.archive');
                    Route::patch('/files/{file}/restore', [AdminFileReviewController::class, 'restore'])->name('files.restore');
                    Route::delete('/files/{file}', [AdminFileReviewController::class, 'destroy'])->name('files.destroy');
                });

                Route::middleware('auth.tenant_admin')->group(function (): void {
                    Route::get('/', [AdminDashboardController::class, 'show'])->name('dashboard');
                    Route::get('/settings', [AdminDashboardController::class, 'settings'])->name('settings');
                    Route::post('/score-adjustments', [AdminDashboardController::class, 'adjust'])->name('score-adjustments.store');
                    Route::post('/logout', [TenantAdminAuthController::class, 'destroy'])->name('logout');
                });
            });
    });
