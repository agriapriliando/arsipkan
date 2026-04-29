<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScoreAdjustment extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'guest_uploader_id',
        'nilai_sebelum',
        'nilai_sesudah',
        'selisih',
        'updated_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'nilai_sebelum' => 'decimal:2',
            'nilai_sesudah' => 'decimal:2',
            'selisih' => 'decimal:2',
        ];
    }

    public function guestUploader(): BelongsTo
    {
        return $this->belongsTo(GuestUploader::class);
    }

    public function updatedByAdmin(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'updated_by_admin_id');
    }
}
