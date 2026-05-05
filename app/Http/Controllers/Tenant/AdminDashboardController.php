<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\File;
use App\Models\GuestUploader;
use App\Models\Tenant;
use App\Models\UserAccount;
use App\Services\Scoring\ScoreService;
use App\Services\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AdminDashboardController extends Controller
{
    public function show(TenantContext $tenantContext, ScoreService $scoreService): View
    {
        $tenant = $this->currentTenant($tenantContext);

        return view('tenant.admin.dashboard', [
            'tenant' => $tenant,
            'pendingReviewCount' => File::query()
                ->where('tenant_id', $tenant->id)
                ->where('status', File::STATUS_PENDING_REVIEW)
                ->count(),
            'activeFileCount' => File::query()
                ->where('tenant_id', $tenant->id)
                ->count(),
            'deletedFileCount' => File::onlyTrashed()
                ->where('tenant_id', $tenant->id)
                ->count(),
            'activeUploaderAccountCount' => UserAccount::query()
                ->where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->count(),
            'weeklyLeaderboard' => $scoreService->leaderboardForTenant(
                $tenant,
                CarbonImmutable::now()->startOfWeek(),
                CarbonImmutable::now()->endOfWeek(),
            ),
            'monthlyLeaderboard' => $scoreService->leaderboardForTenant(
                $tenant,
                CarbonImmutable::now()->startOfMonth(),
                CarbonImmutable::now()->endOfMonth(),
            ),
        ]);
    }

    public function settings(TenantContext $tenantContext, ScoreService $scoreService): View
    {
        $tenant = $this->currentTenant($tenantContext);

        return view('tenant.admin.settings', [
            'tenant' => $tenant,
            'storageUsagePercent' => $tenant->storageUsagePercent(),
            'storageRemainingBytes' => $tenant->storageRemainingBytes(),
            'storageNearLimit' => $tenant->isStorageNearLimit(),
            'guestUploaders' => GuestUploader::query()
                ->where('tenant_id', $tenant->id)
                ->orderBy('name')
                ->get(),
            'scoreRule' => $scoreService->activeRule(),
        ]);
    }

    public function updateSettings(Request $request, TenantContext $tenantContext): RedirectResponse
    {
        $tenant = $this->currentTenant($tenantContext);
        $this->currentManager();

        $validated = $request->validate([
            'max_upload_size_mb' => ['required', 'integer', 'between:1,100'],
        ], [], [
            'max_upload_size_mb' => 'maksimal ukuran upload',
        ]);

        $tenant->forceFill([
            'max_upload_size_kb' => (int) $validated['max_upload_size_mb'] * 1024,
        ])->save();

        return redirect()
            ->route('tenant.admin.settings', ['tenant_slug' => $tenant->slug])
            ->with('status', 'Pengaturan batas ukuran upload berhasil disimpan.');
    }

    public function adjust(Request $request, TenantContext $tenantContext, ScoreService $scoreService): RedirectResponse
    {
        $tenant = $this->currentTenant($tenantContext);
        $manager = $this->currentManager();

        $validated = $request->validate([
            'guest_uploader_id' => [
                'required',
                Rule::exists('guest_uploaders', 'id')->where(fn ($query) => $query->where('tenant_id', $tenant->id)),
            ],
            'delta' => ['required', 'numeric', 'between:-999999.99,999999.99', 'not_in:0'],
        ], [], [
            'guest_uploader_id' => 'uploader',
            'delta' => 'penyesuaian skor',
        ]);

        $uploader = GuestUploader::query()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($validated['guest_uploader_id']);

        $scoreService->createAdjustment($uploader, $manager, (float) $validated['delta']);

        return redirect()
            ->route('tenant.admin.settings', ['tenant_slug' => $tenant->slug])
            ->with('status', 'Penyesuaian skor berhasil disimpan.');
    }

    protected function currentTenant(TenantContext $tenantContext): Tenant
    {
        $tenant = $tenantContext->tenant();

        abort_unless($tenant instanceof Tenant, 404);

        return $tenant;
    }

    protected function currentManager(): AdminUser
    {
        $manager = Auth::guard('tenant_admin')->user() ?? Auth::guard('superadmin')->user();

        abort_unless($manager instanceof AdminUser, 403);

        return $manager;
    }
}
