<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Tenant;
use App\Models\UserAccount;
use App\Services\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserPortalController extends Controller
{
    public function dashboard(TenantContext $tenantContext): View
    {
        $tenant = $this->currentTenant($tenantContext);
        $account = $this->currentAccount();

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
            'pendingReviewCount' => File::query()
                ->where('tenant_id', $tenant->id)
                ->where('guest_uploader_id', $account->guest_uploader_id)
                ->where('status', File::STATUS_PENDING_REVIEW)
                ->count(),
            'uploadLinkCount' => $tenant->uploadLinks()
                ->where('is_active', true)
                ->count(),
        ]);
    }

    public function myFiles(TenantContext $tenantContext): View
    {
        $tenant = $this->currentTenant($tenantContext);
        $account = $this->currentAccount();

        return view('tenant.user.files.index', [
            'heading' => 'Berkas Saya',
            'description' => 'Daftar semua berkas milik Anda di tenant ini.',
            'files' => File::query()
                ->with('category')
                ->where('tenant_id', $tenant->id)
                ->where('guest_uploader_id', $account->guest_uploader_id)
                ->latest('uploaded_at')
                ->get(),
            'mode' => 'mine',
        ]);
    }

    public function tenantFiles(TenantContext $tenantContext): View
    {
        $tenant = $this->currentTenant($tenantContext);

        return view('tenant.user.files.index', [
            'heading' => 'Arsip '.$tenant->name,
            'description' => 'Berkas internal dan publik milik '.$tenant->name.' yang valid dan dapat Anda lihat.',
            'files' => File::query()
                ->with('category')
                ->where('tenant_id', $tenant->id)
                ->whereIn('visibility', [
                    File::VISIBILITY_INTERNAL,
                    File::VISIBILITY_PUBLIC,
                ])
                ->where('status', File::STATUS_VALID)
                ->latest('uploaded_at')
                ->get(),
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

    public function download(TenantContext $tenantContext, string $tenant_slug, int $file): StreamedResponse
    {
        $tenant = $this->currentTenant($tenantContext);
        $account = $this->currentAccount();

        $archivedFile = File::query()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($file);

        abort_unless($account->can('view', $archivedFile), 403);

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

        return redirect()
            ->route('tenant.user.files.mine', ['tenant_slug' => $tenant->slug])
            ->with('status', 'File berhasil dipindahkan ke arsip terhapus.');
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

        return redirect()
            ->route('tenant.user.files.mine', ['tenant_slug' => $tenant->slug])
            ->with('status', 'Visibilitas berkas berhasil diperbarui.');
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
