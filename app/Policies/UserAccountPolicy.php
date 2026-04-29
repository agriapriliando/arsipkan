<?php

namespace App\Policies;

use App\Models\AdminUser;
use App\Models\UserAccount;
use App\Policies\Concerns\AuthorizesTenantAccess;

class UserAccountPolicy
{
    use AuthorizesTenantAccess;

    public function viewAny(AdminUser|UserAccount $user): bool
    {
        return $this->managesCurrentTenant($user);
    }

    public function view(AdminUser|UserAccount $user, UserAccount $userAccount): bool
    {
        if ($this->isActiveUserAccount($user)) {
            return $this->belongsToTenantContext($user)
                && (int) $user->id === (int) $userAccount->id;
        }

        return $this->managesTenantModel($user, $userAccount);
    }

    public function create(AdminUser|UserAccount $user): bool
    {
        return $this->managesCurrentTenant($user);
    }

    public function update(AdminUser|UserAccount $user, UserAccount $userAccount): bool
    {
        if ($this->isActiveUserAccount($user)) {
            return $this->view($user, $userAccount);
        }

        return $this->managesTenantModel($user, $userAccount);
    }

    public function resetPassword(AdminUser|UserAccount $user, UserAccount $userAccount): bool
    {
        return $this->managesTenantModel($user, $userAccount);
    }

    public function delete(AdminUser|UserAccount $user, UserAccount $userAccount): bool
    {
        return $this->managesTenantModel($user, $userAccount);
    }
}
