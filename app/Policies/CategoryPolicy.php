<?php

namespace App\Policies;

use App\Models\AdminUser;
use App\Models\Category;
use App\Models\UserAccount;
use App\Policies\Concerns\AuthorizesTenantAccess;

class CategoryPolicy
{
    use AuthorizesTenantAccess;

    public function viewAny(AdminUser|UserAccount $user): bool
    {
        return $this->isActiveUserAccount($user)
            ? $this->belongsToTenantContext($user)
            : $this->managesCurrentTenant($user);
    }

    public function view(AdminUser|UserAccount $user, Category $category): bool
    {
        return $this->isActiveUserAccount($user)
            ? $this->belongsToTenantContext($user) && (int) $user->tenant_id === (int) $category->tenant_id
            : $this->managesTenantModel($user, $category);
    }

    public function create(AdminUser|UserAccount $user): bool
    {
        return $this->managesCurrentTenant($user);
    }

    public function update(AdminUser|UserAccount $user, Category $category): bool
    {
        return $this->managesTenantModel($user, $category);
    }

    public function delete(AdminUser|UserAccount $user, Category $category): bool
    {
        return $this->managesTenantModel($user, $category);
    }
}
