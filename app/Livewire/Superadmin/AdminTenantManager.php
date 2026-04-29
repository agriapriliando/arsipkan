<?php

namespace App\Livewire\Superadmin;

use App\Models\AdminUser;
use App\Models\Tenant;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class AdminTenantManager extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $editingAdminId = null;

    public ?int $resettingAdminId = null;

    public ?int $tenantId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $resetPassword = '';

    public bool $isActive = true;

    protected string $paginationTheme = 'bootstrap';

    public function render(): View
    {
        $this->authorizeSuperadmin();

        return view('livewire.superadmin.admin-tenant-manager', [
            'admins' => AdminUser::query()
                ->tenantAdmin()
                ->with('tenant')
                ->when($this->search !== '', function (Builder $query): void {
                    $query->where(function (Builder $query): void {
                        $query->where('name', 'like', '%'.$this->search.'%')
                            ->orWhere('email', 'like', '%'.$this->search.'%')
                            ->orWhereHas('tenant', function (Builder $query): void {
                                $query->where('name', 'like', '%'.$this->search.'%')
                                    ->orWhere('code', 'like', '%'.$this->search.'%');
                            });
                    });
                })
                ->latest('id')
                ->paginate(10),
            'tenants' => Tenant::query()
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
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

    public function edit(int $adminId): void
    {
        $this->authorizeSuperadmin();

        $admin = AdminUser::query()
            ->tenantAdmin()
            ->findOrFail($adminId);

        $this->editingAdminId = $admin->id;
        $this->tenantId = $admin->tenant_id;
        $this->name = $admin->name;
        $this->email = $admin->email;
        $this->password = '';
        $this->isActive = $admin->is_active;
        $this->resettingAdminId = null;
        $this->resetPassword = '';
    }

    public function save(): void
    {
        $this->authorizeSuperadmin();

        $validated = $this->validatedPayload();

        $payload = [
            'tenant_id' => $validated['tenant_id'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => AdminUser::ROLE_TENANT_ADMIN,
            'is_active' => $validated['is_active'],
        ];

        if (($validated['password'] ?? '') !== '') {
            $payload['password'] = Hash::make($validated['password']);
        }

        AdminUser::query()->updateOrCreate(
            ['id' => $this->editingAdminId],
            $payload,
        );

        $this->resetForm();
        $this->dispatch('tenant-admin-saved');
    }

    public function toggleActive(int $adminId): void
    {
        $this->authorizeSuperadmin();

        $admin = AdminUser::query()
            ->tenantAdmin()
            ->findOrFail($adminId);

        $admin->forceFill([
            'is_active' => ! $admin->is_active,
        ])->save();
    }

    public function preparePasswordReset(int $adminId): void
    {
        $this->authorizeSuperadmin();

        $admin = AdminUser::query()
            ->tenantAdmin()
            ->findOrFail($adminId);

        $this->resettingAdminId = $admin->id;
        $this->resetPassword = '';
        $this->resetValidation();
    }

    public function resetSelectedPassword(): void
    {
        $this->authorizeSuperadmin();

        $validated = Validator::make(
            [
                'resetting_admin_id' => $this->resettingAdminId,
                'reset_password' => $this->resetPassword,
            ],
            [
                'resetting_admin_id' => [
                    'required',
                    'integer',
                    Rule::exists('admin_users', 'id')->where('role', AdminUser::ROLE_TENANT_ADMIN),
                ],
                'reset_password' => ['required', 'string', 'min:8', 'max:255'],
            ],
            [],
            [
                'reset_password' => 'password baru',
            ],
        )->validate();

        AdminUser::query()
            ->tenantAdmin()
            ->findOrFail($validated['resetting_admin_id'])
            ->forceFill([
                'password' => Hash::make($validated['reset_password']),
            ])
            ->save();

        $this->resettingAdminId = null;
        $this->resetPassword = '';
        $this->resetValidation();
        $this->dispatch('tenant-admin-password-reset');
    }

    protected function validatedPayload(): array
    {
        return Validator::make(
            [
                'tenant_id' => $this->tenantId,
                'name' => $this->name,
                'email' => $this->email,
                'password' => $this->password,
                'is_active' => $this->isActive,
            ],
            [
                'tenant_id' => ['required', 'integer', Rule::exists('tenants', 'id')],
                'name' => ['required', 'string', 'max:255'],
                'email' => [
                    'required',
                    'string',
                    'lowercase',
                    'email',
                    'max:255',
                    Rule::unique('admin_users', 'email')
                        ->where(fn ($query) => $query->where('tenant_id', $this->tenantId))
                        ->ignore($this->editingAdminId),
                ],
                'password' => [
                    Rule::requiredIf($this->editingAdminId === null),
                    'nullable',
                    'string',
                    'min:8',
                    'max:255',
                ],
                'is_active' => ['boolean'],
            ],
            [],
            [
                'tenant_id' => 'tenant',
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
            'editingAdminId',
            'resettingAdminId',
            'tenantId',
            'name',
            'email',
            'password',
            'resetPassword',
        ]);

        $this->isActive = true;
        $this->resetValidation();
    }
}
