<?php

use App\Http\Controllers\Auth\SuperadminAuthController;
use App\Http\Controllers\Auth\TenantAdminAuthController;
use App\Http\Controllers\Auth\UserAccountAuthController;
use App\Http\Controllers\Auth\UserAccountPasswordController;
use App\Http\Controllers\Tenant\UserPortalController;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

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
        Route::get('/', function (Request $request, TenantContext $tenantContext) {
            $tenant = $tenantContext->tenant() ?? $request->attributes->get('tenant');

            abort_unless($tenant !== null, 404);

            return view('tenant.home', [
                'tenant' => $tenant,
            ]);
        })->name('home');

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
                    Route::view('/master-data', 'tenant.admin.master-data.index')->name('master-data.index');
                    Route::view('/upload-links', 'tenant.admin.upload-links.index')->name('upload-links.index');
                    Route::view('/user-accounts', 'tenant.admin.user-accounts.index')->name('user-accounts.index');
                });

                Route::middleware('auth.tenant_admin')->group(function (): void {
                    Route::view('/', 'tenant.admin.dashboard')->name('dashboard');
                    Route::post('/logout', [TenantAdminAuthController::class, 'destroy'])->name('logout');
                });
            });
    });
