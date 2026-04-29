<?php

namespace App\Policies;

use App\Models\AdminUser;
use App\Models\Tag;
use App\Models\UserAccount;
use App\Policies\Concerns\AuthorizesTenantAccess;

class TagPolicy
{
    use AuthorizesTenantAccess;

    public function viewAny(AdminUser|UserAccount $user): bool
    {
        return $this->isActiveUserAccount($user)
            ? $this->belongsToTenantContext($user)
            : $this->managesCurrentTenant($user);
    }

    public function view(AdminUser|UserAccount $user, Tag $tag): bool
    {
        return $this->isActiveUserAccount($user)
            ? $this->belongsToTenantContext($user) && (int) $user->tenant_id === (int) $tag->tenant_id
            : $this->managesTenantModel($user, $tag);
    }

    public function create(AdminUser|UserAccount $user): bool
    {
        return $this->managesCurrentTenant($user);
    }

    public function update(AdminUser|UserAccount $user, Tag $tag): bool
    {
        return $this->managesTenantModel($user, $tag);
    }

    public function delete(AdminUser|UserAccount $user, Tag $tag): bool
    {
        return $this->managesTenantModel($user, $tag);
    }
}
