<?php

namespace App\Policies\Concerns;

use App\Models\AdminUser;
use App\Models\Tenant;
use App\Models\UserAccount;
use App\Services\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;

trait AuthorizesTenantAccess
{
    protected function isActiveSuperadmin(AdminUser|UserAccount $user): bool
    {
        return $user instanceof AdminUser && $user->isSuperadmin() && $user->is_active;
    }

    protected function isActiveTenantAdmin(AdminUser|UserAccount $user): bool
    {
        return $user instanceof AdminUser && $user->isTenantAdmin() && $user->is_active;
    }

    protected function isActiveUserAccount(AdminUser|UserAccount $user): bool
    {
        return $user instanceof UserAccount && $user->is_active;
    }

    protected function currentTenant(): ?Tenant
    {
        return app(TenantContext::class)->tenant();
    }

    protected function tenantIdMatchesCurrentContext(int|string|null $tenantId): bool
    {
        $tenant = $this->currentTenant();

        return $tenant !== null && (int) $tenant->id === (int) $tenantId;
    }

    protected function managesTenant(AdminUser|UserAccount $user, int|string|null $tenantId): bool
    {
        if ($this->isActiveTenantAdmin($user)) {
            return (int) $user->tenant_id === (int) $tenantId
                && $this->tenantIdMatchesCurrentContext($tenantId);
        }

        if ($this->isActiveSuperadmin($user)) {
            return $this->tenantIdMatchesCurrentContext($tenantId);
        }

        return false;
    }

    protected function managesCurrentTenant(AdminUser|UserAccount $user): bool
    {
        $tenant = $this->currentTenant();

        if ($tenant === null) {
            return false;
        }

        if ($this->isActiveTenantAdmin($user)) {
            return (int) $user->tenant_id === (int) $tenant->id;
        }

        return $this->isActiveSuperadmin($user);
    }

    protected function managesTenantModel(AdminUser|UserAccount $user, Model $model): bool
    {
        return $this->managesTenant($user, $model->getAttribute('tenant_id'));
    }

    protected function belongsToTenantContext(UserAccount $user): bool
    {
        return $this->tenantIdMatchesCurrentContext($user->tenant_id);
    }
}
