<?php

namespace App\Livewire\Tenant;

use App\Models\Category;
use App\Models\File;
use App\Models\Tag;
use App\Models\Tenant;
use App\Models\UserAccount;
use App\Services\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class UserFileBrowser extends Component
{
    use WithPagination;

    public ?int $tenantId = null;

    public string $tenantSlug = '';

    public string $mode = 'mine';

    public string $heading = '';

    public string $description = '';

    #[Url(as: 'search', except: '')]
    public string $search = '';

    #[Url(as: 'category_id', except: '')]
    public string $categoryId = '';

    #[Url(as: 'tag_id', except: '')]
    public string $tagId = '';

    protected string $paginationTheme = 'bootstrap';

    public function mount(string $mode, string $heading, string $description): void
    {
        $tenant = $this->currentTenant();

        $this->tenantId = $tenant->id;
        $this->tenantSlug = $tenant->slug;
        $this->mode = $mode;
        $this->heading = $heading;
        $this->description = $description;
    }

    public function render(): View
    {
        $tenant = $this->currentTenant();
        $account = $this->currentAccount();

        return view('livewire.tenant.user-file-browser', [
            'files' => $this->fileQuery($tenant, $account)
                ->latest('uploaded_at')
                ->paginate(10),
            'categories' => Category::query()
                ->where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'tags' => Tag::query()
                ->where('tenant_id', $tenant->id)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryId(): void
    {
        $this->resetPage();
    }

    public function updatedTagId(): void
    {
        $this->resetPage();
    }

    protected function fileQuery(Tenant $tenant, UserAccount $account): Builder
    {
        $query = File::query()
            ->with($this->mode === 'tenant' ? ['category', 'guestUploader', 'tags'] : ['category', 'tags'])
            ->where('tenant_id', $tenant->id);

        if ($this->mode === 'mine') {
            $query->where('guest_uploader_id', $account->guest_uploader_id);
        }

        if ($this->mode === 'tenant') {
            $query
                ->whereIn('visibility', [
                    File::VISIBILITY_INTERNAL,
                    File::VISIBILITY_PUBLIC,
                ])
                ->where('status', File::STATUS_VALID);
        }

        if ($this->search !== '') {
            $search = trim($this->search);

            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('original_name', 'like', '%'.$search.'%')
                    ->orWhere('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhereHas('category', function (Builder $categoryQuery) use ($search): void {
                        $categoryQuery->where('name', 'like', '%'.$search.'%');
                    });
            });
        }

        if ($this->categoryId !== '' && ctype_digit($this->categoryId)) {
            $query->where('category_id', (int) $this->categoryId);
        }

        if ($this->tagId !== '' && ctype_digit($this->tagId)) {
            $query->whereHas('tags', function (Builder $builder): void {
                $builder->where('tags.id', (int) $this->tagId);
            });
        }

        return $query;
    }

    protected function currentTenant(): Tenant
    {
        $tenant = app(TenantContext::class)->tenant();

        if ($tenant instanceof Tenant) {
            $this->tenantId = $tenant->id;
            $this->tenantSlug = $tenant->slug;

            return $tenant;
        }

        if ($this->tenantId !== null) {
            $tenant = Tenant::query()
                ->whereKey($this->tenantId)
                ->where('is_active', true)
                ->first();

            if ($tenant instanceof Tenant) {
                app(TenantContext::class)->set($tenant);
                $this->tenantSlug = $tenant->slug;

                return $tenant;
            }
        }

        abort(404);

        return $tenant;
    }

    protected function currentAccount(): UserAccount
    {
        $account = Auth::guard('user_account')->user();

        abort_unless($account instanceof UserAccount, 403);

        return $account;
    }
}
