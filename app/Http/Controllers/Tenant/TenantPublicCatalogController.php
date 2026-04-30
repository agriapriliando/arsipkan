<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\File;
use App\Models\Tag;
use App\Models\Tenant;
use App\Services\Scoring\ScoreService;
use App\Services\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantPublicCatalogController extends Controller
{
    public function index(Request $request, TenantContext $tenantContext): View
    {
        $tenant = $this->currentTenant($tenantContext);
        $perPageOptions = [8, 12, 24, 48];

        $filters = [
            'search' => trim((string) $request->string('search')),
            'category_id' => $request->integer('category_id') ?: null,
            'tag_id' => $request->integer('tag_id') ?: null,
            'file_type' => trim((string) $request->string('file_type')),
            'per_page' => $request->integer('per_page') ?: 12,
        ];

        if (! in_array($filters['per_page'], $perPageOptions, true)) {
            $filters['per_page'] = 12;
        }

        $query = File::query()
            ->with(['category', 'tags', 'guestUploader'])
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
                ->paginate($filters['per_page'])
                ->withQueryString(),
            'perPageOptions' => $perPageOptions,
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

    public function leaderboard(Request $request, TenantContext $tenantContext, ScoreService $scoreService): View
    {
        $tenant = $this->currentTenant($tenantContext);
        $period = $request->string('period')->value();
        $selectedPeriod = in_array($period, ['weekly', 'monthly'], true) ? $period : 'monthly';

        [$start, $end, $heading] = match ($selectedPeriod) {
            'weekly' => [
                CarbonImmutable::now()->startOfWeek(),
                CarbonImmutable::now()->endOfWeek(),
                'minggu ini',
            ],
            default => [
                CarbonImmutable::now()->startOfMonth(),
                CarbonImmutable::now()->endOfMonth(),
                CarbonImmutable::now()->translatedFormat('F Y'),
            ],
        };

        $leaderboard = $scoreService->leaderboardForTenant($tenant, $start, $end, 25)
            ->map(function ($uploader) {
                $uploader->total_upload_count = File::query()
                    ->where('tenant_id', $uploader->tenant_id)
                    ->where('guest_uploader_id', $uploader->id)
                    ->count();

                return $uploader;
            })
            ->values();
        $podium = collect([
            2 => $leaderboard->get(1),
            1 => $leaderboard->get(0),
            3 => $leaderboard->get(2),
        ]);

        return view('tenant.catalog.leaderboard', [
            'tenant' => $tenant,
            'selectedPeriod' => $selectedPeriod,
            'periodHeading' => $heading,
            'leaderboard' => $leaderboard,
            'podium' => $podium,
        ]);
    }

    public function download(Request $request, TenantContext $tenantContext, string $tenant_slug, int $file, ScoreService $scoreService): StreamedResponse|BinaryFileResponse
    {
        $tenant = $this->currentTenant($tenantContext);

        $file = $this->publicFileQuery($tenant)->findOrFail($file);

        abort_unless(Storage::disk('local')->exists($file->stored_name), 404);

        $scoreService->recordPublicDownload($file, $request, true);
        $scoreService->recalculateUploaderScore($file->guestUploader);

        $headers = [
            'Content-Type' => $file->mime_type ?: 'application/octet-stream',
            'Cache-Control' => 'private, max-age=300, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ];

        if ($this->isPdf($file)) {
            $response = response()->file(
                Storage::disk('local')->path($file->stored_name),
                $headers + [
                    'Content-Disposition' => 'inline; filename="'.$file->original_name.'"',
                    'Content-Length' => (string) Storage::disk('local')->size($file->stored_name),
                    'Accept-Ranges' => 'bytes',
                ],
            );

            $response->setAutoEtag();
            $response->setAutoLastModified();

            return $response;
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
