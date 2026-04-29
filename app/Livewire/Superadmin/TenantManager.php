<?php

namespace App\Livewire\Superadmin;

use App\Models\AdminUser;
use App\Models\Tenant;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class TenantManager extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $editingTenantId = null;

    public string $code = '';

    public string $name = '';

    public string $slug = '';

    public string $storageQuotaGb = '10';

    public int $storageWarningThresholdPercent = 80;

    public bool $isActive = true;

    protected string $paginationTheme = 'bootstrap';

    public function render(): View
    {
        $this->authorizeSuperadmin();

        return view('livewire.superadmin.tenant-manager', [
            'tenants' => Tenant::query()
                ->when($this->search !== '', function (Builder $query): void {
                    $query->where(function (Builder $query): void {
                        $query->where('code', 'like', '%'.$this->search.'%')
                            ->orWhere('name', 'like', '%'.$this->search.'%')
                            ->orWhere('slug', 'like', '%'.$this->search.'%');
                    });
                })
                ->latest('id')
                ->paginate(10),
        ]);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorizeSuperadmin();

        $this->resetForm();
    }

    public function edit(int $tenantId): void
    {
        $this->authorizeSuperadmin();

        $tenant = Tenant::query()->findOrFail($tenantId);

        $this->editingTenantId = $tenant->id;
        $this->code = $tenant->code;
        $this->name = $tenant->name;
        $this->slug = $tenant->slug;
        $this->storageQuotaGb = $this->bytesToGbString($tenant->storage_quota_bytes);
        $this->storageWarningThresholdPercent = $tenant->storage_warning_threshold_percent;
        $this->isActive = $tenant->is_active;
    }

    public function save(): void
    {
        $this->authorizeSuperadmin();

        $validated = $this->validatedPayload();

        Tenant::query()->updateOrCreate(
            ['id' => $this->editingTenantId],
            [
                'code' => Str::upper($validated['code']),
                'name' => $validated['name'],
                'slug' => Str::slug($validated['slug']),
                'storage_quota_bytes' => $this->gbToBytes($validated['storage_quota_gb']),
                'storage_warning_threshold_percent' => $validated['storage_warning_threshold_percent'],
                'is_active' => $validated['is_active'],
            ],
        );

        $this->resetForm();
        $this->dispatch('tenant-saved');
    }

    public function toggleActive(int $tenantId): void
    {
        $this->authorizeSuperadmin();

        $tenant = Tenant::query()->findOrFail($tenantId);

        $tenant->forceFill([
            'is_active' => ! $tenant->is_active,
        ])->save();
    }

    public function enterTenant(int $tenantId): mixed
    {
        $this->authorizeSuperadmin();

        $tenant = Tenant::query()->where('is_active', true)->findOrFail($tenantId);

        session([
            'superadmin_tenant_id' => $tenant->id,
            'superadmin_tenant_entered_at' => now()->toISOString(),
        ]);

        return $this->redirectRoute('tenant.home', [
            'tenant_slug' => $tenant->slug,
        ], navigate: true);
    }

    protected function validatedPayload(): array
    {
        return Validator::make(
            [
                'code' => $this->code,
                'name' => $this->name,
                'slug' => $this->slug,
                'storage_quota_gb' => $this->storageQuotaGb,
                'storage_warning_threshold_percent' => $this->storageWarningThresholdPercent,
                'is_active' => $this->isActive,
            ],
            [
                'code' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('tenants', 'code')->ignore($this->editingTenantId),
                ],
                'name' => ['required', 'string', 'max:255'],
                'slug' => [
                    'required',
                    'string',
                    'max:255',
                    'alpha_dash',
                    Rule::notIn(Tenant::reservedSlugs()),
                    Rule::unique('tenants', 'slug')->ignore($this->editingTenantId),
                ],
                'storage_quota_gb' => ['required', 'numeric', 'min:0.01', 'max:1048576'],
                'storage_warning_threshold_percent' => ['required', 'integer', 'min:1', 'max:100'],
                'is_active' => ['boolean'],
            ],
            [],
            [
                'storage_quota_gb' => 'kuota storage',
                'storage_warning_threshold_percent' => 'ambang peringatan storage',
            ],
        )->validate();
    }

    protected function authorizeSuperadmin(): void
    {
        $user = Auth::guard('superadmin')->user();

        abort_unless($user instanceof AdminUser && $user->isSuperadmin() && $user->is_active, 403);
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editingTenantId',
            'code',
            'name',
            'slug',
            'storageQuotaGb',
            'storageWarningThresholdPercent',
        ]);

        $this->storageQuotaGb = '10';
        $this->storageWarningThresholdPercent = 80;
        $this->isActive = true;
        $this->resetValidation();
    }

    protected function gbToBytes(string|float|int $value): int
    {
        return (int) round((float) $value * 1024 * 1024 * 1024);
    }

    protected function bytesToGbString(int $bytes): string
    {
        $gb = $bytes / 1024 / 1024 / 1024;

        return rtrim(rtrim(number_format($gb, 2, '.', ''), '0'), '.');
    }
}
