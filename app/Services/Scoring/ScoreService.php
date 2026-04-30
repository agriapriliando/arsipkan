<?php

namespace App\Services\Scoring;

use App\Models\AdminUser;
use App\Models\File;
use App\Models\FileDownload;
use App\Models\GuestUploader;
use App\Models\ScoreAdjustment;
use App\Models\ScoreRule;
use App\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class ScoreService
{
    public function activeRule(): ScoreRule
    {
        return ScoreRule::query()
            ->where('is_active', true)
            ->latest('created_at')
            ->first()
            ?? ScoreRule::create([
                'upload_valid_point' => 10,
                'download_point' => 1,
                'is_active' => true,
            ]);
    }

    public function recordPublicDownload(File $file, Request $request, bool $isCountedForScore = true): FileDownload
    {
        return FileDownload::create([
            'tenant_id' => $file->tenant_id,
            'file_id' => $file->id,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'downloaded_at' => now(),
            'is_counted_for_score' => $isCountedForScore,
        ]);
    }

    public function recalculateUploaderScore(GuestUploader $uploader): string
    {
        $rule = $this->activeRule();

        $validUploadCount = File::query()
            ->where('tenant_id', $uploader->tenant_id)
            ->where('guest_uploader_id', $uploader->id)
            ->where('status', File::STATUS_VALID)
            ->count();

        $countedDownloadCount = FileDownload::query()
            ->where('tenant_id', $uploader->tenant_id)
            ->where('is_counted_for_score', true)
            ->whereHas('file', function ($query) use ($uploader): void {
                $query
                    ->where('guest_uploader_id', $uploader->id)
                    ->where('visibility', File::VISIBILITY_PUBLIC)
                    ->where('status', File::STATUS_VALID);
            })
            ->count();

        $adjustments = (string) ScoreAdjustment::query()
            ->where('tenant_id', $uploader->tenant_id)
            ->where('guest_uploader_id', $uploader->id)
            ->sum('selisih');

        $score = bcmul((string) $validUploadCount, (string) $rule->upload_valid_point, 2);
        $score = bcadd($score, bcmul((string) $countedDownloadCount, (string) $rule->download_point, 2), 2);
        $score = bcadd($score, $adjustments, 2);

        $uploader->forceFill([
            'last_score' => $score,
        ])->save();

        return $score;
    }

    public function createAdjustment(GuestUploader $uploader, AdminUser $admin, float $delta): ScoreAdjustment
    {
        $before = $this->recalculateUploaderScore($uploader);
        $after = bcadd($before, number_format($delta, 2, '.', ''), 2);

        $adjustment = ScoreAdjustment::create([
            'tenant_id' => $uploader->tenant_id,
            'guest_uploader_id' => $uploader->id,
            'nilai_sebelum' => $before,
            'nilai_sesudah' => $after,
            'selisih' => number_format($delta, 2, '.', ''),
            'updated_by_admin_id' => $admin->id,
        ]);

        $this->recalculateUploaderScore($uploader->fresh());

        return $adjustment;
    }

    public function leaderboardForTenant(Tenant $tenant, CarbonImmutable $start, CarbonImmutable $end, int $limit = 10): Collection
    {
        $rule = $this->activeRule();
        $startAt = $start->startOfDay();
        $endAt = $end->endOfDay();

        return GuestUploader::query()
            ->where('tenant_id', $tenant->id)
            ->get()
            ->map(function (GuestUploader $uploader) use ($rule, $startAt, $endAt) {
                $uploader->valid_upload_count = File::query()
                    ->where('tenant_id', $uploader->tenant_id)
                    ->where('guest_uploader_id', $uploader->id)
                    ->where('status', File::STATUS_VALID)
                    ->whereBetween('uploaded_at', [$startAt, $endAt])
                    ->count();

                $uploader->counted_download_count = FileDownload::query()
                    ->where('tenant_id', $uploader->tenant_id)
                    ->where('is_counted_for_score', true)
                    ->whereBetween('downloaded_at', [$startAt, $endAt])
                    ->whereHas('file', function ($query) use ($uploader): void {
                        $query
                            ->where('guest_uploader_id', $uploader->id)
                            ->where('visibility', File::VISIBILITY_PUBLIC)
                            ->where('status', File::STATUS_VALID);
                    })
                    ->count();

                $uploader->adjustment_total = (string) ScoreAdjustment::query()
                    ->where('tenant_id', $uploader->tenant_id)
                    ->where('guest_uploader_id', $uploader->id)
                    ->whereBetween('created_at', [$startAt, $endAt])
                    ->sum('selisih');

                $periodScore = bcmul((string) $uploader->valid_upload_count, (string) $rule->upload_valid_point, 2);
                $periodScore = bcadd($periodScore, bcmul((string) $uploader->counted_download_count, (string) $rule->download_point, 2), 2);
                $periodScore = bcadd($periodScore, (string) $uploader->adjustment_total, 2);

                $uploader->period_score = $periodScore;

                return $uploader;
            })
            ->sortByDesc(fn (GuestUploader $uploader) => [(float) $uploader->period_score, (float) $uploader->last_score, $uploader->name])
            ->take($limit)
            ->values();
    }
}
