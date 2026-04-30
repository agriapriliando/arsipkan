<div>
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
        <div>
            <span class="eyebrow mb-3">Superadmin</span>
            <h1 class="h2 fw-bold mb-1">Manajemen Admin Organisasi</h1>
            <p class="text-secondary mb-0">Kelola akun operasional organisasi, status akses, dan reset password.</p>
        </div>

        <button type="button" class="btn btn-brand" wire:click="create">Tambah Admin</button>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-4">
            <section class="panel-box p-4 mb-4">
                <h2 class="h5 fw-bold mb-3">{{ $editingAdminId ? 'Edit Admin Organisasi' : 'Tambah Admin Organisasi' }}</h2>

                <form wire:submit="save">
                    <div class="mb-3">
                        <label for="tenantId" class="form-label small fw-bold text-secondary">Organisasi</label>
                        <select id="tenantId" class="form-select form-control @error('tenant_id') is-invalid @enderror" wire:model="tenantId">
                            <option value="">Pilih organisasi</option>
                            @foreach($tenants as $tenant)
                                <option value="{{ $tenant->id }}">{{ $tenant->name }} ({{ $tenant->code }})</option>
                            @endforeach
                        </select>
                        @error('tenant_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label small fw-bold text-secondary">Nama</label>
                        <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" wire:model="name" placeholder="Admin Dinas Arsip">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label small fw-bold text-secondary">Email</label>
                        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" wire:model="email" placeholder="admin@tenant.test">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label small fw-bold text-secondary">
                            {{ $editingAdminId ? 'Password Baru (opsional)' : 'Password Awal' }}
                        </label>
                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" wire:model="password" autocomplete="new-password">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-check form-switch my-4">
                        <input id="isActive" type="checkbox" class="form-check-input" wire:model="isActive">
                        <label for="isActive" class="form-check-label">Akun aktif</label>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-brand flex-grow-1" wire:loading.attr="disabled">
                            {{ $editingAdminId ? 'Simpan Perubahan' : 'Buat Admin' }}
                        </button>

                        @if($editingAdminId)
                            <button type="button" class="btn btn-light border fw-semibold" wire:click="create">Batal</button>
                        @endif
                    </div>
                </form>
            </section>

            @if($resettingAdminId)
                <section class="panel-box p-4">
                    <h2 class="h5 fw-bold mb-3">Reset Password</h2>

                    <form wire:submit="resetSelectedPassword">
                        <div class="mb-3">
                            <label for="resetPassword" class="form-label small fw-bold text-secondary">Password Baru</label>
                            <input id="resetPassword" type="password" class="form-control @error('reset_password') is-invalid @enderror" wire:model="resetPassword" autocomplete="new-password">
                            @error('reset_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-brand flex-grow-1" wire:loading.attr="disabled">Reset Password</button>
                            <button type="button" class="btn btn-light border fw-semibold" wire:click="$set('resettingAdminId', null)">Batal</button>
                        </div>
                    </form>
                </section>
            @endif
        </div>

        <div class="col-12 col-xl-8">
            <section class="panel-box p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
                    <h2 class="h5 fw-bold mb-0">Daftar Admin Organisasi</h2>
                    <input type="search" class="form-control tenant-search" wire:model.live.debounce.350ms="search" placeholder="Cari admin...">
                </div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Admin</th>
                                <th>Organisasi</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($admins as $admin)
                                <tr wire:key="tenant-admin-{{ $admin->id }}">
                                    <td>
                                        <div class="fw-bold">{{ $admin->name }}</div>
                                        <div class="text-secondary small">{{ $admin->email }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $admin->tenant?->name ?? 'Organisasi dihapus' }}</div>
                                        <div class="text-secondary small">{{ $admin->tenant?->code ?? '-' }}</div>
                                    </td>
                                    <td>
                                        <span class="status-pill {{ $admin->is_active ? 'status-active' : 'status-inactive' }}">
                                            {{ $admin->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-light border fw-semibold" wire:click="edit({{ $admin->id }})">Edit</button>
                                            <button type="button" class="btn btn-sm btn-light border fw-semibold" wire:click="toggleActive({{ $admin->id }})">
                                                {{ $admin->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                            <button type="button" class="btn btn-sm btn-brand" wire:click="preparePasswordReset({{ $admin->id }})">Reset</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-secondary py-5">Belum ada admin organisasi.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $admins->links() }}
                </div>
            </section>
        </div>
    </div>
</div>
