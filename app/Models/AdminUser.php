<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class AdminUser extends Authenticatable
{
    use BelongsToTenant;

    public const ROLE_SUPERADMIN = 'superadmin';

    public const ROLE_TENANT_ADMIN = 'tenant_admin';

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function createdUserAccounts(): HasMany
    {
        return $this->hasMany(UserAccount::class, 'created_by_admin_id');
    }

    public function uploadLinks(): HasMany
    {
        return $this->hasMany(UploadLink::class, 'created_by_admin_id');
    }

    public function reviewedFiles(): HasMany
    {
        return $this->hasMany(File::class, 'reviewed_by_admin_id');
    }

    public function permanentlyDeletedFiles(): HasMany
    {
        return $this->hasMany(File::class, 'permanently_deleted_by_admin_id');
    }

    public function scoreRules(): HasMany
    {
        return $this->hasMany(ScoreRule::class, 'created_by_superadmin_id');
    }

    public function scoreAdjustments(): HasMany
    {
        return $this->hasMany(ScoreAdjustment::class, 'updated_by_admin_id');
    }

    public function scopeSuperadmin(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_SUPERADMIN);
    }

    public function scopeTenantAdmin(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_TENANT_ADMIN);
    }

    public function isSuperadmin(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }

    public function isTenantAdmin(): bool
    {
        return $this->role === self::ROLE_TENANT_ADMIN;
    }
}
