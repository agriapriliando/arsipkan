<?php

namespace App\Livewire\Superadmin;

use App\Models\AdminUser;
use App\Models\Tenant;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class TenantMasterDataEntry extends Component
{
    use WithPagination;

    public string $search = '';

    protected string $paginationTheme = 'bootstrap';

    public function render(): View
    {
        $this->authorizeSuperadmin();

        return view('livewire.superadmin.tenant-master-data-entry', [
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

    public function enterMasterData(int $tenantId, string $section = 'kategori'): mixed
    {
        $this->authorizeSuperadmin();

        $tenant = Tenant::query()
            ->where('is_active', true)
            ->findOrFail($tenantId);

        $section = in_array($section, ['kategori', 'tag'], true) ? $section : 'kategori';

        session([
            'superadmin_tenant_id' => $tenant->id,
            'superadmin_tenant_entered_at' => now()->toISOString(),
        ]);

        return $this->redirect(
            route('tenant.admin.master-data.index', ['tenant_slug' => $tenant->slug]).'#'.$section,
            navigate: true,
        );
    }

    protected function authorizeSuperadmin(): void
    {
        $user = Auth::guard('superadmin')->user();

        abort_unless($user instanceof AdminUser && $user->isSuperadmin() && $user->is_active, 403);
    }
}
