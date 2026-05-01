<?php

namespace App\Livewire\Tenant;

use App\Models\AdminUser;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Tenant;
use App\Services\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class MasterDataManager extends Component
{
    public string $mode = 'category';

    public ?int $tenantId = null;

    public ?int $editingCategoryId = null;

    public ?int $editingTagId = null;

    public string $categoryName = '';

    public string $categorySlug = '';

    public string $categoryDescription = '';

    public bool $categoryIsActive = true;

    public string $tagName = '';

    public function mount(string $mode = 'category'): void
    {
        abort_unless(in_array($mode, ['category', 'tag'], true), 404);

        $this->mode = $mode;
        $this->tenantId = $this->currentTenantFromContext()->id;
    }

    public function render(): View
    {
        $tenant = $this->authorizeTenantManager();

        return view('livewire.tenant.master-data-manager', [
            'categories' => $this->mode === 'category'
                ? Category::query()
                    ->forTenant($tenant)
                    ->orderBy('name')
                    ->get()
                : collect(),
            'tags' => $this->mode === 'tag'
                ? Tag::query()
                    ->forTenant($tenant)
                    ->orderBy('name')
                    ->get()
                : collect(),
        ]);
    }

    public function editCategory(int $categoryId): void
    {
        $tenant = $this->authorizeTenantManager();

        $category = Category::query()
            ->forTenant($tenant)
            ->findOrFail($categoryId);

        $this->authorizeAction('update', $category);

        $this->editingCategoryId = $category->id;
        $this->categoryName = $category->name;
        $this->categorySlug = $category->slug;
        $this->categoryDescription = $category->description ?? '';
        $this->categoryIsActive = $category->is_active;
    }

    public function saveCategory(): void
    {
        $tenant = $this->authorizeTenantManager();

        if ($this->editingCategoryId !== null) {
            $this->authorizeAction('update', Category::query()
                ->forTenant($tenant)
                ->findOrFail($this->editingCategoryId));
        } else {
            $this->authorizeAction('create', Category::class);
        }

        $validated = $this->validatedCategoryPayload($tenant);

        Category::query()->updateOrCreate(
            [
                'id' => $this->editingCategoryId,
                'tenant_id' => $tenant->id,
            ],
            [
                'tenant_id' => $tenant->id,
                'name' => $validated['category_name'],
                'slug' => Str::slug($validated['category_slug']),
                'description' => $validated['category_description'],
                'is_active' => $validated['category_is_active'],
            ],
        );

        $this->resetCategoryForm();
        $this->dispatch('category-saved');
    }

    public function toggleCategoryActive(int $categoryId): void
    {
        $tenant = $this->authorizeTenantManager();

        $category = Category::query()
            ->forTenant($tenant)
            ->findOrFail($categoryId);

        $this->authorizeAction('update', $category);

        $category->forceFill([
            'is_active' => ! $category->is_active,
        ])->save();
    }

    public function deleteCategory(int $categoryId): void
    {
        $tenant = $this->authorizeTenantManager();

        $category = Category::query()
            ->forTenant($tenant)
            ->findOrFail($categoryId);

        $this->authorizeAction('delete', $category);
        $category->delete();
    }

    public function editTag(int $tagId): void
    {
        $tenant = $this->authorizeTenantManager();

        $tag = Tag::query()
            ->forTenant($tenant)
            ->findOrFail($tagId);

        $this->authorizeAction('update', $tag);

        $this->editingTagId = $tag->id;
        $this->tagName = $tag->name;
    }

    public function saveTag(): void
    {
        $tenant = $this->authorizeTenantManager();

        if ($this->editingTagId !== null) {
            $this->authorizeAction('update', Tag::query()
                ->forTenant($tenant)
                ->findOrFail($this->editingTagId));
        } else {
            $this->authorizeAction('create', Tag::class);
        }

        $validated = $this->validatedTagPayload($tenant);

        Tag::query()->updateOrCreate(
            [
                'id' => $this->editingTagId,
                'tenant_id' => $tenant->id,
            ],
            [
                'tenant_id' => $tenant->id,
                'name' => $validated['tag_name'],
            ],
        );

        $this->resetTagForm();
        $this->dispatch('tag-saved');
    }

    public function deleteTag(int $tagId): void
    {
        $tenant = $this->authorizeTenantManager();

        $tag = Tag::query()
            ->forTenant($tenant)
            ->findOrFail($tagId);

        $this->authorizeAction('delete', $tag);
        $tag->delete();
    }

    public function resetCategoryForm(): void
    {
        $this->reset([
            'editingCategoryId',
            'categoryName',
            'categorySlug',
            'categoryDescription',
        ]);

        $this->categoryIsActive = true;
        $this->resetValidation();
    }

    public function resetTagForm(): void
    {
        $this->reset([
            'editingTagId',
            'tagName',
        ]);

        $this->resetValidation();
    }

    protected function validatedCategoryPayload(Tenant $tenant): array
    {
        return Validator::make(
            [
                'category_name' => $this->categoryName,
                'category_slug' => $this->categorySlug,
                'category_description' => $this->categoryDescription !== '' ? $this->categoryDescription : null,
                'category_is_active' => $this->categoryIsActive,
            ],
            [
                'category_name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('categories', 'name')
                        ->where(fn ($query) => $query->where('tenant_id', $tenant->id))
                        ->ignore($this->editingCategoryId),
                ],
                'category_slug' => [
                    'required',
                    'string',
                    'max:255',
                    'alpha_dash',
                    Rule::unique('categories', 'slug')
                        ->where(fn ($query) => $query->where('tenant_id', $tenant->id))
                        ->ignore($this->editingCategoryId),
                ],
                'category_description' => ['nullable', 'string', 'max:5000'],
                'category_is_active' => ['boolean'],
            ],
            [],
            [
                'category_name' => 'nama kategori',
                'category_slug' => 'slug kategori',
                'category_description' => 'deskripsi kategori',
            ],
        )->validate();
    }

    protected function validatedTagPayload(Tenant $tenant): array
    {
        return Validator::make(
            [
                'tag_name' => $this->tagName,
            ],
            [
                'tag_name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('tags', 'name')
                        ->where(fn ($query) => $query->where('tenant_id', $tenant->id))
                        ->ignore($this->editingTagId),
                ],
            ],
            [],
            [
                'tag_name' => 'nama tag',
            ],
        )->validate();
    }

    protected function authorizeTenantManager(): Tenant
    {
        $tenant = $this->currentTenant();
        $abilityTarget = $this->mode === 'tag' ? Tag::class : Category::class;

        abort_unless($this->currentUser()?->can('create', $abilityTarget), 403);

        return $tenant;
    }

    protected function authorizeAction(string $ability, string|Category|Tag $target): void
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
