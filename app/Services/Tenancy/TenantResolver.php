<?php

namespace App\Services\Tenancy;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

class TenantResolver
{
    public function resolve(string $slug): Tenant
    {
        $tenant = Tenant::query()
            ->where('slug', Str::lower($slug))
            ->where('is_active', true)
            ->first();

        if ($tenant === null) {
            $exception = new ModelNotFoundException;
            $exception->setModel(Tenant::class, [$slug]);

            throw $exception;
        }

        return $tenant;
    }
}
