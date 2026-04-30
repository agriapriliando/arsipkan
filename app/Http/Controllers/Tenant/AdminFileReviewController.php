<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\Category;
use App\Models\File;
use App\Models\Tag;
use App\Models\Tenant;
use App\Services\Scoring\ScoreService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminFileReviewController extends Controller
{
    public function pending(TenantContext $tenantContext): View
    {
        $tenant = $this->currentTenant($tenantContext);

        return view('tenant.admin.files.index', [
            'heading' => 'Pending Review',
            'description' => 'Daftar file publik yang masih menunggu pemeriksaan admin tenant.',
            'files' => File::query()
                ->with(['category', 'guestUploader', 'uploadLink'])
                ->where('tenant_id', $tenant->id)
                ->where('status', File::STATUS_PENDING_REVIEW)
                ->latest('uploaded_at')
                ->get(),
            'mode' => 'pending',
            'filters' => [
                'search' => '',
                'visibility' => '',
                'category_id' => '',
            ],
            'categories' => collect(),
        ]);
    }

    public function index(Request $request, TenantContext $tenantContext): View
    {
        $tenant = $this->currentTenant($tenantContext);
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'visibility' => ['nullable', Rule::in([
                File::VISIBILITY_PUBLIC,
                File::VISIBILITY_INTERNAL,
            ])],
            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('tenant_id', $tenant->id)),
            ],
        ], [], [
            'visibility' => 'visibilitas',
            'category_id' => 'kategori',
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $selectedVisibility = $validated['visibility'] ?? null;
        $selectedCategoryId = $validated['category_id'] ?? null;

        $files = File::query()
            ->with(['category', 'guestUploader', 'uploadLink'])
            ->where('tenant_id', $tenant->id)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('title', 'like', '%'.$search.'%')
                        ->orWhere('original_name', 'like', '%'.$search.'%')
                        ->orWhereHas('guestUploader', function ($guestUploaderQuery) use ($search): void {
                            $guestUploaderQuery
                                ->where('name', 'like', '%'.$search.'%')
                                ->orWhere('phone_number', 'like', '%'.$search.'%');
                        })
                        ->orWhereHas('uploadLink', function ($uploadLinkQuery) use ($search): void {
                            $uploadLinkQuery
                                ->where('code', 'like', '%'.$search.'%')
                                ->orWhere('title', 'like', '%'.$search.'%');
                        });
                });
            })
            ->when($selectedVisibility !== null, fn ($query) => $query->where('visibility', $selectedVisibility))
            ->when($selectedCategoryId !== null, fn ($query) => $query->where('category_id', $selectedCategoryId))
            ->latest('uploaded_at')
            ->paginate(10)
            ->withQueryString();

        return view('tenant.admin.files.index', [
            'heading' => 'Semua Berkas',
            'description' => 'Kelola seluruh berkas tenant, termasuk file valid, pending review, dan suspended.',
            'files' => $files,
            'mode' => 'all',
            'filters' => [
                'search' => $search,
                'visibility' => $selectedVisibility ?? '',
                'category_id' => $selectedCategoryId !== null ? (string) $selectedCategoryId : '',
            ],
            'categories' => Category::query()
                ->where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function deleted(TenantContext $tenantContext): View
    {
        $tenant = $this->currentTenant($tenantContext);

        return view('tenant.admin.files.index', [
            'heading' => 'Berkas Terhapus',
            'description' => 'Daftar berkas yang sudah dihapus oleh uploader dan masih bisa dipulihkan admin tenant.',
            'files' => File::query()
                ->with(['category', 'guestUploader', 'uploadLink', 'deletedByUserAccount'])
                ->onlyTrashed()
                ->where('tenant_id', $tenant->id)
                ->latest('deleted_at')
                ->get(),
            'mode' => 'deleted',
            'filters' => [
                'search' => '',
                'visibility' => '',
                'category_id' => '',
            ],
            'categories' => collect(),
        ]);
    }

    public function show(TenantContext $tenantContext, string $tenant_slug, int $file): View
    {
        $tenant = $this->currentTenant($tenantContext);
        $manager = $this->currentManager();

        $archivedFile = File::query()
            ->withTrashed()
            ->with(['category', 'guestUploader', 'uploadLink', 'tags', 'reviewedByAdmin', 'deletedByUserAccount'])
            ->where('tenant_id', $tenant->id)
            ->findOrFail($file);

        abort_unless($manager->can('view', $archivedFile), 403);

        return view('tenant.admin.files.show', [
            'file' => $archivedFile,
            'categories' => Category::query()
                ->where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'tags' => Tag::query()
                ->where('tenant_id', $tenant->id)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function download(TenantContext $tenantContext, string $tenant_slug, int $file): StreamedResponse
    {
        $tenant = $this->currentTenant($tenantContext);
        $manager = $this->currentManager();

        $archivedFile = File::query()
            ->withTrashed()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($file);

        abort_unless($manager->can('view', $archivedFile), 403);

        return Storage::disk('local')->download(
            $archivedFile->stored_name,
            $archivedFile->original_name,
        );
    }

    public function update(Request $request, TenantContext $tenantContext, string $tenant_slug, int $file, ScoreService $scoreService): RedirectResponse
    {
        $tenant = $this->currentTenant($tenantContext);
        $manager = $this->currentManager();

        $archivedFile = File::query()
            ->withTrashed()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($file);

        abort_unless($manager->can('review', $archivedFile), 403);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'visibility' => ['required', Rule::in([
                File::VISIBILITY_PUBLIC,
                File::VISIBILITY_INTERNAL,
                File::VISIBILITY_PRIVATE,
            ])],
            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('tenant_id', $tenant->id)),
            ],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => [
                'integer',
                Rule::exists('tags', 'id')->where(fn ($query) => $query->where('tenant_id', $tenant->id)),
            ],
            'final_file_type' => ['nullable', 'string', 'max:100'],
            'status' => ['required', Rule::in([
                File::STATUS_PENDING_REVIEW,
                File::STATUS_VALID,
                File::STATUS_SUSPENDED,
            ])],
        ], [], [
            'visibility' => 'visibilitas',
            'category_id' => 'kategori',
            'tag_ids' => 'tag',
            'final_file_type' => 'tipe file final',
            'status' => 'status review',
        ]);

        $archivedFile->forceFill([
            'title' => $validated['title'] ?: null,
            'description' => $validated['description'] ?: null,
            'visibility' => $validated['visibility'],
            'category_id' => $validated['category_id'] ?: null,
            'final_file_type' => $validated['final_file_type'] ?: null,
            'status' => $validated['status'],
            'reviewed_at' => now(),
            'reviewed_by_admin_id' => $manager->id,
        ])->save();

        $archivedFile->tags()->sync($validated['tag_ids'] ?? []);
        $scoreService->recalculateUploaderScore($archivedFile->guestUploader()->firstOrFail());

        $request->session()->flash('status', 'Review file berhasil disimpan.');

        return new RedirectResponse(route('tenant.admin.files.show', [
            'tenant_slug' => $tenant->slug,
            'file' => $archivedFile->id,
        ]));
    }

    public function updateOriginalName(Request $request, TenantContext $tenantContext, string $tenant_slug, int $file): RedirectResponse
    {
        $tenant = $this->currentTenant($tenantContext);
        $manager = $this->currentManager();

        $archivedFile = File::query()
            ->withTrashed()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($file);

        abort_unless($manager->can('updateMetadata', $archivedFile), 403);

        $validated = $request->validate([
            'original_name' => ['required', 'string', 'max:255'],
        ], [], [
            'original_name' => 'nama asli file',
        ]);

        $archivedFile->forceFill([
            'original_name' => trim($validated['original_name']),
        ])->save();

        $request->session()->flash('status', 'Nama asli file berhasil diperbarui.');

        return new RedirectResponse(route('tenant.admin.files.show', [
            'tenant_slug' => $tenant->slug,
            'file' => $archivedFile->id,
        ]));
    }

    public function restore(TenantContext $tenantContext, string $tenant_slug, int $file): RedirectResponse
    {
        $tenant = $this->currentTenant($tenantContext);
        $manager = $this->currentManager();

        $archivedFile = File::query()
            ->onlyTrashed()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($file);

        abort_unless($manager->can('restore', $archivedFile), 403);

        $archivedFile->restore();

        request()->session()->flash('status', 'File berhasil dipulihkan.');

        return new RedirectResponse(route('tenant.admin.files.deleted', ['tenant_slug' => $tenant->slug]));
    }

    public function archive(TenantContext $tenantContext, string $tenant_slug, int $file): RedirectResponse
    {
        $tenant = $this->currentTenant($tenantContext);
        $manager = $this->currentManager();

        $archivedFile = File::query()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($file);

        abort_unless($manager->can('archive', $archivedFile), 403);

        $archivedFile->delete();

        request()->session()->flash('status', 'File berhasil dipindahkan ke berkas terhapus.');

        return new RedirectResponse(route('tenant.admin.files.index', ['tenant_slug' => $tenant->slug]));
    }

    public function destroy(TenantContext $tenantContext, string $tenant_slug, int $file): RedirectResponse
    {
        $tenant = $this->currentTenant($tenantContext);
        $manager = $this->currentManager();

        $archivedFile = File::query()
            ->onlyTrashed()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($file);

        abort_unless($manager->can('forceDelete', $archivedFile), 403);

        if ($archivedFile->stored_name !== '') {
            Storage::disk('local')->delete($archivedFile->stored_name);
        }

        $tenant->decrement('storage_used_bytes', min($tenant->storage_used_bytes, (int) $archivedFile->file_size));

        $archivedFile->forceFill([
            'permanently_deleted_by_admin_id' => $manager->id,
        ])->save();

        $archivedFile->forceDelete();

        request()->session()->flash('status', 'File berhasil dihapus permanen.');

        return new RedirectResponse(route('tenant.admin.files.deleted', ['tenant_slug' => $tenant->slug]));
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
