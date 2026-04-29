<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UploadLink extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'code',
        'title',
        'is_active',
        'expires_at',
        'max_usage',
        'usage_count',
        'created_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'expires_at' => 'datetime',
            'max_usage' => 'integer',
            'usage_count' => 'integer',
        ];
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'created_by_admin_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(File::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && ! $this->expires_at->isFuture();
    }

    public function isUsageLimitReached(): bool
    {
        return $this->max_usage !== null && $this->usage_count >= $this->max_usage;
    }

    public function isUsableForGuestUpload(): bool
    {
        return $this->tenant?->is_active === true
            && $this->is_active
            && ! $this->isExpired()
            && ! $this->isUsageLimitReached();
    }
}
