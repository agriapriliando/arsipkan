<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class UserAccount extends Authenticatable
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'guest_uploader_id',
        'password',
        'is_active',
        'must_change_password',
        'password_changed_at',
        'last_login_at',
        'created_by_admin_id',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'password_changed_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function guestUploader(): BelongsTo
    {
        return $this->belongsTo(GuestUploader::class);
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'created_by_admin_id');
    }

    public function deletedFiles(): HasMany
    {
        return $this->hasMany(File::class, 'deleted_by_user_account_id');
    }
}
