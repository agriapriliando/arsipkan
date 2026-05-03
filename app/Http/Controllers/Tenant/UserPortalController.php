<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Tenant;
use App\Models\UploadLink;
use App\Models\UserAccount;
use App\Services\Scoring\ScoreService;
use App\Services\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserPortalController extends Controller
{
    public function dashboard(TenantContext $tenantContext, ScoreService $scoreService): View
    {
        $tenant = $this->currentTenant($tenantContext);
        $account = $this->currentAccount();
        $activeUploadLinks = UploadLink::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->get()
            ->filter(fn (UploadLink $uploadLink): bool => $uploadLink->isUsableForGuestUpload())
            ->values();

        return view('tenant.user.dashboard', [
            'myFileCount' => File::query()
                ->where('tenant_id', $tenant->id)
                ->where('guest_uploader_id', $account->guest_uploader_id)
                ->count(),
            'tenantInternalCount' => File::query()
                ->where('tenant_id', $tenant->id)
                ->where('visibility', File::VISIBILITY_INTERNAL)
                ->where('status', File::STATUS_VALID)
                ->count(),
            'tenantFileCount' => File::query()
                ->where('tenant_id', $tenant->id)
                ->count(),
            'pendingReviewCount' => File::query()
                ->where('tenant_id', $tenant->id)
                ->where('guest_uploader_id', $account->guest_uploader_id)
                ->where('status', File::STATUS_PENDING_REVIEW)
                ->count(),
            'uploadLinkCount' => $activeUploadLinks->count(),
            'activeUploadLinks' => $activeUploadLinks,
            'currentScore' => $scoreService->recalculateUploaderScore($account->guestUploader),
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

    public function myFiles(Request $request, TenantContext $tenantContext): View
    {
        $tenant = $this->currentTenant($tenantContext);
        $account = $this->currentAccount();
        $search = trim((string) $request->string('search'));

        $query = File::query()
            ->with('category')
            ->where('tenant_id', $tenant->id)
            ->where('guest_uploader_id', $account->guest_uploader_id);

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('original_name', 'like', '%'.$search.'%')
                    ->orWhere('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhereHas('category', function (Builder $categoryQuery) use ($search): void {
                        $categoryQuery->where('name', 'like', '%'.$search.'%');
                    });
            });
        }

        return view('tenant.user.files.index', [
            'heading' => 'Berkas Saya',
            'description' => 'Daftar semua berkas milik Anda di tenant ini.',
            'files' => $query
                ->latest('uploaded_at')
                ->paginate(10)
                ->withQueryString(),
            'filters' => [
                'search' => $search,
            ],
            'mode' => 'mine',
        ]);
    }

    public function tenantFiles(Request $request, TenantContext $tenantContext): View
    {
        $tenant = $this->currentTenant($tenantContext);
        $search = trim((string) $request->string('search'));

        $query = File::query()
            ->with(['category', 'guestUploader'])
            ->where('tenant_id', $tenant->id)
            ->whereIn('visibility', [
                File::VISIBILITY_INTERNAL,
                File::VISIBILITY_PUBLIC,
            ])
            ->where('status', File::STATUS_VALID);

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('original_name', 'like', '%'.$search.'%')
                    ->orWhere('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhereHas('category', function (Builder $categoryQuery) use ($search): void {
                        $categoryQuery->where('name', 'like', '%'.$search.'%');
                    });
            });
        }

        return view('tenant.user.files.index', [
            'heading' => 'Arsip '.$tenant->name,
            'description' => 'Berkas internal dan publik milik '.$tenant->name.' yang valid dan dapat Anda lihat.',
            'files' => $query
                ->latest('uploaded_at')
                ->paginate(10)
                ->withQueryString(),
            'filters' => [
                'search' => $search,
            ],
            'mode' => 'tenant',
        ]);
    }

    public function show(TenantContext $tenantContext, string $tenant_slug, int $file): View
    {
        $tenant = $this->currentTenant($tenantContext);
        $account = $this->currentAccount();

        $archivedFile = File::query()
            ->with(['category', 'guestUploader', 'tags'])
            ->where('tenant_id', $tenant->id)
            ->findOrFail($file);

        abort_unless($account->can('view', $archivedFile), 403);

        return view('tenant.user.files.show', [
            'file' => $archivedFile,
        ]);
    }

    public function profile(TenantContext $tenantContext): View
    {
        $tenant = $this->currentTenant($tenantContext);
        $account = $this->currentAccount()->load('guestUploader');

        return view('tenant.user.profile.show', [
            'tenant' => $tenant,
            'account' => $account,
        ]);
    }

    public function download(TenantContext $tenantContext, string $tenant_slug, int $file, Request $request, ScoreService $scoreService): StreamedResponse
    {
        $tenant = $this->currentTenant($tenantContext);
        $account = $this->currentAccount();

        $archivedFile = File::query()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($file);

        abort_unless($account->can('view', $archivedFile), 403);

        if (
            $archivedFile->visibility === File::VISIBILITY_PUBLIC
            && $archivedFile->status === File::STATUS_VALID
        ) {
            $isCountedForScore = (int) $account->guest_uploader_id !== (int) $archivedFile->guest_uploader_id;

            $scoreService->recordPublicDownload($archivedFile, $request, $isCountedForScore);
            $scoreService->recalculateUploaderScore($archivedFile->guestUploader()->firstOrFail());
        }

        return Storage::disk('local')->download(
            $archivedFile->stored_name,
            $archivedFile->original_name,
        );
    }

    public function destroy(Request $request, TenantContext $tenantContext, string $tenant_slug, int $file): RedirectResponse
    {
        $tenant = $this->currentTenant($tenantContext);
        $account = $this->currentAccount();

        $archivedFile = File::query()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($file);

        abort_unless($account->can('delete', $archivedFile), 403);

        $archivedFile->forceFill([
            'deleted_by_user_account_id' => $account->id,
        ])->save();

        $archivedFile->delete();

        $request->session()->flash('status', 'File berhasil dipindahkan ke arsip terhapus.');

        return new RedirectResponse(route('tenant.user.files.mine', ['tenant_slug' => $tenant->slug]));
    }

    public function updateVisibility(Request $request, TenantContext $tenantContext, string $tenant_slug, int $file): RedirectResponse
    {
        $tenant = $this->currentTenant($tenantContext);
        $account = $this->currentAccount();

        $archivedFile = File::query()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($file);

        abort_unless($account->can('changeVisibility', $archivedFile), 403);

        $validated = $request->validate([
            'visibility' => ['required', Rule::in([
                File::VISIBILITY_PUBLIC,
                File::VISIBILITY_INTERNAL,
                File::VISIBILITY_PRIVATE,
            ])],
        ], [], [
            'visibility' => 'visibilitas',
        ]);

        $nextVisibility = $validated['visibility'];

        $archivedFile->forceFill([
            'visibility' => $nextVisibility,
            'status' => $nextVisibility === File::VISIBILITY_PUBLIC
                ? File::STATUS_PENDING_REVIEW
                : File::STATUS_VALID,
        ])->save();

        $request->session()->flash('status', 'Visibilitas berkas berhasil diperbarui.');

        return new RedirectResponse(route('tenant.user.files.mine', ['tenant_slug' => $tenant->slug]));
    }

    protected function currentTenant(TenantContext $tenantContext): Tenant
    {
        $tenant = $tenantContext->tenant();

        abort_unless($tenant instanceof Tenant, 404);

        return $tenant;
    }

    protected function currentAccount(): UserAccount
    {
        $account = Auth::guard('user_account')->user();

        abort_unless($account instanceof UserAccount, 403);

        return $account;
    }
}
