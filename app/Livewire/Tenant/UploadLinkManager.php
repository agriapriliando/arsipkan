<?php

namespace App\Livewire\Tenant;

use App\Models\AdminUser;
use App\Models\Tenant;
use App\Models\UploadLink;
use App\Services\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class UploadLinkManager extends Component
{
    public ?int $tenantId = null;

    public ?int $editingUploadLinkId = null;

    public string $code = '';

    public string $title = '';

    public bool $isActive = true;

    public string $expiresAt = '';

    public string $maxUsage = '';

    public function mount(): void
    {
        $this->tenantId = $this->currentTenantFromContext()->id;
    }

    public function render(): View
    {
        $tenant = $this->authorizeTenantManager();

        return view('livewire.tenant.upload-link-manager', [
            'uploadLinks' => UploadLink::query()
                ->forTenant($tenant)
                ->with('createdByAdmin', 'tenant')
                ->latest('id')
                ->get(),
        ]);
    }

    public function create(): void
    {
        $this->authorizeTenantManager();
        $this->resetForm();
    }

    public function edit(int $uploadLinkId): void
    {
        $tenant = $this->authorizeTenantManager();

        $uploadLink = UploadLink::query()
            ->forTenant($tenant)
            ->findOrFail($uploadLinkId);

        $this->authorizeAction('update', $uploadLink);

        $this->editingUploadLinkId = $uploadLink->id;
        $this->code = $uploadLink->code;
        $this->title = $uploadLink->title;
        $this->isActive = $uploadLink->is_active;
        $this->expiresAt = $uploadLink->expires_at?->format('Y-m-d\TH:i') ?? '';
        $this->maxUsage = $uploadLink->max_usage !== null ? (string) $uploadLink->max_usage : '';
    }

    public function save(): void
    {
        $tenant = $this->authorizeTenantManager();

        if ($this->editingUploadLinkId !== null) {
            $this->authorizeAction('update', UploadLink::query()
                ->forTenant($tenant)
                ->findOrFail($this->editingUploadLinkId));
        } else {
            $this->authorizeAction('create', UploadLink::class);
        }

        $validated = $this->validatedPayload($tenant);
        $currentUser = $this->currentUser();

        if ($this->editingUploadLinkId !== null) {
            UploadLink::query()
                ->forTenant($tenant)
                ->findOrFail($this->editingUploadLinkId)
                ->update([
                    'code' => Str::upper($validated['code']),
                    'title' => $validated['title'],
                    'is_active' => $validated['is_active'],
                    'expires_at' => $validated['expires_at'],
                    'max_usage' => $validated['max_usage'],
                ]);
        } else {
            UploadLink::query()->create([
                'tenant_id' => $tenant->id,
                'code' => Str::upper($validated['code']),
                'title' => $validated['title'],
                'is_active' => $validated['is_active'],
                'expires_at' => $validated['expires_at'],
                'max_usage' => $validated['max_usage'],
                'created_by_admin_id' => $currentUser?->id,
            ]);
        }

        $this->resetForm();
        $this->dispatch('upload-link-saved');
    }

    public function toggleActive(int $uploadLinkId): void
    {
        $tenant = $this->authorizeTenantManager();

        $uploadLink = UploadLink::query()
            ->forTenant($tenant)
            ->findOrFail($uploadLinkId);

        $this->authorizeAction('update', $uploadLink);

        $uploadLink->forceFill([
            'is_active' => ! $uploadLink->is_active,
        ])->save();
    }

    public function delete(int $uploadLinkId): void
    {
        $tenant = $this->authorizeTenantManager();

        $uploadLink = UploadLink::query()
            ->forTenant($tenant)
            ->findOrFail($uploadLinkId);

        $this->authorizeAction('delete', $uploadLink);
        $uploadLink->delete();
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingUploadLinkId',
            'code',
            'title',
            'expiresAt',
            'maxUsage',
        ]);

        $this->isActive = true;
        $this->resetValidation();
    }

    protected function validatedPayload(Tenant $tenant): array
    {
        return Validator::make(
            [
                'code' => $this->code,
                'title' => $this->title,
                'is_active' => $this->isActive,
                'expires_at' => $this->expiresAt !== '' ? $this->expiresAt : null,
                'max_usage' => $this->maxUsage !== '' ? $this->maxUsage : null,
            ],
            [
                'code' => [
                    'required',
                    'string',
                    'max:100',
                    'alpha_dash',
                    Rule::unique('upload_links', 'code')
                        ->where(fn ($query) => $query->where('tenant_id', $tenant->id))
                        ->ignore($this->editingUploadLinkId),
                ],
                'title' => ['required', 'string', 'max:255'],
                'is_active' => ['boolean'],
                'expires_at' => ['nullable', 'date'],
                'max_usage' => ['nullable', 'integer', 'min:1', 'max:4294967295'],
            ],
            [],
            [
                'max_usage' => 'batas penggunaan',
                'expires_at' => 'masa berlaku',
            ],
        )->validate();
    }

    protected function authorizeTenantManager(): Tenant
    {
        $tenant = $this->currentTenant();
        abort_unless($this->currentUser()?->can('create', UploadLink::class), 403);

        return $tenant;
    }

    protected function authorizeAction(string $ability, string|UploadLink $target): void
    {
        abort_unless($this->currentUser()?->can($ability, $target), 403);
    }

    protected function currentUser(): ?AdminUser
    {
        $tenant = $this->currentTenantOrNull();
        $tenantAdmin = Auth::guard('tenant_admin')->user();

        if ($tenantAdmin instanceof AdminUser && $tenantAdmin->isTenantAdmin()) {
            return $tenantAdmin;
        }

        $superadmin = Auth::guard('superadmin')->user();

        if (
            $superadmin instanceof AdminUser
            && $superadmin->isSuperadmin()
            && $tenant !== null
            && (int) session('superadmin_tenant_id') === (int) $tenant->id
        ) {
            return $superadmin;
        }

        return null;
    }

    protected function currentTenant(): Tenant
    {
        $tenant = $this->currentTenantOrNull();

        abort_unless($tenant instanceof Tenant, 404);

        return $tenant;
    }

    protected function currentTenantOrNull(): ?Tenant
    {
        $tenant = app(TenantContext::class)->tenant();

        if ($tenant instanceof Tenant) {
            $this->tenantId = $tenant->id;

            return $tenant;
        }

        if ($this->tenantId === null) {
            return null;
        }

        $tenant = Tenant::query()
            ->whereKey($this->tenantId)
            ->where('is_active', true)
            ->first();

        if ($tenant instanceof Tenant) {
            app(TenantContext::class)->set($tenant);
        }

        return $tenant;
    }

    protected function currentTenantFromContext(): Tenant
    {
        $tenant = app(TenantContext::class)->tenant();

        abort_unless($tenant instanceof Tenant, 404);

        return $tenant;
    }
}
