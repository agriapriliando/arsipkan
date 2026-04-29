<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileDownload extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'file_id',
        'ip_address',
        'user_agent',
        'downloaded_at',
        'is_counted_for_score',
    ];

    protected function casts(): array
    {
        return [
            'downloaded_at' => 'datetime',
            'is_counted_for_score' => 'boolean',
        ];
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }
}
