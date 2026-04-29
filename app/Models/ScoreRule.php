<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScoreRule extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'upload_valid_point',
        'download_point',
        'is_active',
        'created_by_superadmin_id',
    ];

    protected function casts(): array
    {
        return [
            'upload_valid_point' => 'integer',
            'download_point' => 'integer',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function createdBySuperadmin(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'created_by_superadmin_id');
    }
}
