<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\File;
use App\Models\Tag;
use App\Models\Tenant;
use App\Services\Scoring\ScoreService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantPublicCatalogController extends Controller
{
    public function index(Request $request, TenantContext $tenantContext): View
    {
        $tenant = $this->currentTenant($tenantContext);

        $filters = [
            'search' => trim((string) $request->string('search')),
            'category_id' => $request->integer('category_id') ?: null,
            'tag_id' => $request->integer('tag_id') ?: null,
            'file_type' => trim((string) $request->string('file_type')),
        ];

        $query = File::query()
            ->with(['category', 'tags'])
            ->where('tenant_id', $tenant->id)
            ->where('visibility', File::VISIBILITY_PUBLIC)
            ->where('status', File::STATUS_VALID);

        if ($filters['search'] !== '') {
            $search = $filters['search'];

            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('original_name', 'like', '%'.$search.'%')
                    ->orWhere('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        if ($filters['category_id'] !== null) {
            $query->where('category_id', $filters['category_id']);
        }

        if ($filters['tag_id'] !== null) {
            $query->whereHas('tags', fn (Builder $builder) => $builder->whereKey($filters['tag_id']));
        }

        if ($filters['file_type'] !== '') {
            $fileType = $filters['file_type'];

            $query->where(function (Builder $builder) use ($fileType): void {
                $builder
                    ->where('final_file_type', $fileType)
                    ->orWhere('detected_file_type', $fileType)
                    ->orWhere('mime_type', $fileType)
                    ->orWhere('extension', $fileType);
            });
        }

        $catalogBaseQuery = File::query()
            ->where('tenant_id', $tenant->id)
            ->where('visibility', File::VISIBILITY_PUBLIC)
            ->where('status', File::STATUS_VALID);

        return view('tenant.catalog.index', [
            'tenant' => $tenant,
            'filters' => $filters,
            'files' => $query
                ->latest('uploaded_at')
                ->paginate(12)
                ->withQueryString(),
            'categories' => Category::query()
                ->where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->whereIn('id', (clone $catalogBaseQuery)->whereNotNull('category_id')->select('category_id'))
                ->orderBy('name')
                ->get(),
            'tags' => Tag::query()
                ->where('tenant_id', $tenant->id)
                ->whereHas('files', function (Builder $builder) use ($tenant): void {
                    $builder
                        ->where('files.tenant_id', $tenant->id)
                        ->where('files.visibility', File::VISIBILITY_PUBLIC)
                        ->where('files.status', File::STATUS_VALID);
                })
                ->orderBy('name')
                ->get(),
            'fileTypes' => (clone $catalogBaseQuery)
                ->selectRaw("
                    DISTINCT COALESCE(NULLIF(final_file_type, ''), NULLIF(detected_file_type, ''), NULLIF(mime_type, ''), NULLIF(extension, '')) as file_type
                ")
                ->pluck('file_type')
                ->filter()
                ->values(),
        ]);
    }

    public function show(TenantContext $tenantContext, string $tenant_slug, int $file): View
    {
        $tenant = $this->currentTenant($tenantContext);

        $file = $this->publicFileQuery($tenant)->findOrFail($file);

        return view('tenant.catalog.show', [
            'tenant' => $tenant,
            'file' => $file,
        ]);
    }

    public function download(Request $request, TenantContext $tenantContext, string $tenant_slug, int $file, ScoreService $scoreService): StreamedResponse
    {
        $tenant = $this->currentTenant($tenantContext);

        $file = $this->publicFileQuery($tenant)->findOrFail($file);

        abort_unless(Storage::disk('local')->exists($file->stored_name), 404);

        $scoreService->recordPublicDownload($file, $request, true);
        $scoreService->recalculateUploaderScore($file->guestUploader);

        $headers = [
            'Content-Type' => $file->mime_type ?: 'application/octet-stream',
        ];

        if ($this->isPdf($file)) {
            return response()->stream(
                static function () use ($file): void {
                    echo Storage::disk('local')->get($file->stored_name);
                },
                200,
                $headers + [
                    'Content-Disposition' => 'inline; filename="'.$file->original_name.'"',
                ],
            );
        }

        return response()->streamDownload(
            static function () use ($file): void {
                echo Storage::disk('local')->get($file->stored_name);
            },
            $file->original_name,
            $headers,
        );
    }

    protected function publicFileQuery(Tenant $tenant): Builder
    {
        return File::query()
            ->with(['category', 'tags', 'guestUploader'])
            ->where('tenant_id', $tenant->id)
            ->where('visibility', File::VISIBILITY_PUBLIC)
            ->where('status', File::STATUS_VALID);
    }

    protected function currentTenant(TenantContext $tenantContext): Tenant
    {
        $tenant = $tenantContext->tenant();

        abort_unless($tenant instanceof Tenant, 404);

        return $tenant;
    }

    protected function isPdf(File $file): bool
    {
        return strtolower((string) $file->extension) === 'pdf'
            || strtolower((string) $file->mime_type) === 'application/pdf';
    }
}
