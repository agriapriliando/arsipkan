<?php

namespace App\Policies;

use App\Models\AdminUser;
use App\Models\UploadLink;
use App\Models\UserAccount;
use App\Policies\Concerns\AuthorizesTenantAccess;

class UploadLinkPolicy
{
    use AuthorizesTenantAccess;

    public function viewAny(AdminUser|UserAccount $user): bool
    {
        return $this->managesCurrentTenant($user);
    }

    public function view(AdminUser|UserAccount $user, UploadLink $uploadLink): bool
    {
        return $this->managesTenantModel($user, $uploadLink);
    }

    public function create(AdminUser|UserAccount $user): bool
    {
        return $this->managesCurrentTenant($user);
    }

    public function update(AdminUser|UserAccount $user, UploadLink $uploadLink): bool
    {
        return $this->managesTenantModel($user, $uploadLink);
    }

    public function delete(AdminUser|UserAccount $user, UploadLink $uploadLink): bool
    {
        return $this->managesTenantModel($user, $uploadLink);
    }

    public function uploadAsGuest(?AdminUser $user, UploadLink $uploadLink): bool
    {
        return $this->tenantIdMatchesCurrentContext($uploadLink->tenant_id)
            && $uploadLink->isUsableForGuestUpload();
    }
}
