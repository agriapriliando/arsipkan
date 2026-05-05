<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Tenant extends Model
{
    public const DEFAULT_MAX_UPLOAD_SIZE_KB = 20480;

    protected $fillable = [
        'code',
        'name',
        'slug',
        'path_prefix',
        'storage_quota_bytes',
        'storage_used_bytes',
        'storage_warning_threshold_percent',
        'max_upload_size_kb',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'storage_quota_bytes' => 'integer',
            'storage_used_bytes' => 'integer',
            'storage_warning_threshold_percent' => 'integer',
            'max_upload_size_kb' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Tenant $tenant): void {
            $tenant->slug = Str::slug($tenant->slug);

            if ($tenant->slug === '') {
                throw ValidationException::withMessages([
                    'slug' => 'Slug tenant wajib valid.',
                ]);
            }

            if (static::isReservedSlug($tenant->slug)) {
                throw ValidationException::withMessages([
                    'slug' => 'Slug tenant memakai nama sistem yang dicadangkan.',
                ]);
            }

            $tenant->path_prefix = '/'.$tenant->slug;
        });
    }

    public static function reservedSlugs(): array
    {
        return array_map(
            static fn (string $slug): string => Str::lower($slug),
            config('tenancy.reserved_slugs', []),
        );
    }

    public static function isReservedSlug(string $slug): bool
    {
        return in_array(Str::lower($slug), static::reservedSlugs(), true);
    }

    public function guestUploaders(): HasMany
    {
        return $this->hasMany(GuestUploader::class);
    }

    public function adminUsers(): HasMany
    {
        return $this->hasMany(AdminUser::class);
    }

    public function tenantAdmins(): HasMany
    {
        return $this->adminUsers()->where('role', AdminUser::ROLE_TENANT_ADMIN);
    }

    public function userAccounts(): HasMany
    {
        return $this->hasMany(UserAccount::class);
    }

    public function uploadLinks(): HasMany
    {
        return $this->hasMany(UploadLink::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(File::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    public function fileDownloads(): HasMany
    {
        return $this->hasMany(FileDownload::class);
    }

    public function scoreAdjustments(): HasMany
    {
        return $this->hasMany(ScoreAdjustment::class);
    }

    public function storageUsagePercent(): float
    {
        if ($this->storage_quota_bytes <= 0) {
            return 0.0;
        }

        return min(100, round(($this->storage_used_bytes / $this->storage_quota_bytes) * 100, 2));
    }

    public function storageRemainingBytes(): int
    {
        return max(0, $this->storage_quota_bytes - $this->storage_used_bytes);
    }

    public function isStorageNearLimit(): bool
    {
        return $this->storageUsagePercent() >= $this->storage_warning_threshold_percent;
    }

    public function maxUploadSizeMb(): float
    {
        return round($this->resolvedMaxUploadSizeKb() / 1024, 2);
    }

    public function resolvedMaxUploadSizeKb(): int
    {
        return max(1, (int) ($this->max_upload_size_kb ?: static::DEFAULT_MAX_UPLOAD_SIZE_KB));
    }
}
