<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use App\Services\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

trait BelongsToTenant
{
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeForTenant(Builder $query, Tenant|int $tenant): Builder
    {
        return $query->where($query->qualifyColumn('tenant_id'), $tenant instanceof Tenant ? $tenant->getKey() : $tenant);
    }

    public function scopeForCurrentTenant(Builder $query): Builder
    {
        $tenant = app(TenantContext::class)->tenant();

        if ($tenant === null) {
            throw new LogicException('Tidak ada tenant aktif untuk query tenant-bound.');
        }

        return $this->scopeForTenant($query, $tenant);
    }
}
