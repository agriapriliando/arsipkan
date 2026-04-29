<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GuestUploader extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'phone_number',
        'phone_number_normalized',
        'guest_token',
        'last_score',
        'first_ip',
        'last_ip',
    ];

    protected function casts(): array
    {
        return [
            'last_score' => 'decimal:2',
        ];
    }

    public function userAccount(): HasOne
    {
        return $this->hasOne(UserAccount::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(File::class);
    }

    public function scoreAdjustments(): HasMany
    {
        return $this->hasMany(ScoreAdjustment::class);
    }
}
