<?php

namespace App\Policies;

use App\Models\AdminUser;
use App\Models\File;
use App\Models\UserAccount;
use App\Policies\Concerns\AuthorizesTenantAccess;

class FilePolicy
{
    use AuthorizesTenantAccess;

    public function viewAny(AdminUser|UserAccount $user): bool
    {
        if ($this->isActiveUserAccount($user)) {
            return $this->belongsToTenantContext($user);
        }

        return $this->managesCurrentTenant($user);
    }

    public function view(AdminUser|UserAccount $user, File $file): bool
    {
        if (! $this->tenantIdMatchesCurrentContext($file->tenant_id)) {
            return false;
        }

        if ($this->managesTenantModel($user, $file)) {
            return true;
        }

        if (! $this->isActiveUserAccount($user) || (int) $user->tenant_id !== (int) $file->tenant_id) {
            return false;
        }

        if ((int) $user->guest_uploader_id === (int) $file->guest_uploader_id) {
            return true;
        }

        return $file->visibility === File::VISIBILITY_INTERNAL
            && $file->status === File::STATUS_VALID
            && $file->deleted_at === null;
    }

    public function create(AdminUser|UserAccount $user): bool
    {
        if ($this->isActiveUserAccount($user)) {
            return $this->belongsToTenantContext($user);
        }

        return $this->managesCurrentTenant($user);
    }

    public function update(AdminUser|UserAccount $user, File $file): bool
    {
        return $this->managesTenantModel($user, $file);
    }

    public function updateMetadata(AdminUser|UserAccount $user, File $file): bool
    {
        return $this->update($user, $file);
    }

    public function review(AdminUser|UserAccount $user, File $file): bool
    {
        return $this->update($user, $file);
    }

    public function delete(AdminUser|UserAccount $user, File $file): bool
    {
        return $this->isActiveUserAccount($user)
            && $this->tenantIdMatchesCurrentContext($file->tenant_id)
            && (int) $user->tenant_id === (int) $file->tenant_id
            && (int) $user->guest_uploader_id === (int) $file->guest_uploader_id;
    }

    public function restore(AdminUser|UserAccount $user, File $file): bool
    {
        return $this->managesTenantModel($user, $file);
    }

    public function forceDelete(AdminUser|UserAccount $user, File $file): bool
    {
        return $this->managesTenantModel($user, $file);
    }
}
