<div>
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
        <div>
            <span class="eyebrow mb-3">Superadmin</span>
            <h1 class="h2 fw-bold mb-1">Manajemen Organisasi</h1>
            <p class="text-secondary mb-0">Kelola organisasi, status aktif, kuota storage, dan akses konteks organisasi.</p>
        </div>

        <button type="button" class="btn btn-brand" wire:click="create">Tambah Organisasi</button>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-4">
            <section class="panel-box p-4">
                <h2 class="h5 fw-bold mb-3">{{ $editingTenantId ? 'Edit Organisasi' : 'Tambah Organisasi' }}</h2>

                <form wire:submit="save">
                    <div class="mb-3">
                        <label for="code" class="form-label small fw-bold text-secondary">Kode</label>
                        <input id="code" type="text" class="form-control @error('code') is-invalid @enderror" wire:model="code" placeholder="PEMDA-A">
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label small fw-bold text-secondary">Nama Organisasi</label>
                        <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" wire:model="name" placeholder="Pemerintah Daerah A">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="slug" class="form-label small fw-bold text-secondary">Slug URL</label>
                        <input id="slug" type="text" class="form-control @error('slug') is-invalid @enderror" wire:model="slug" placeholder="pemda-a">
                        @error('slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-sm-6">
                            <label for="storageQuotaGb" class="form-label small fw-bold text-secondary">Kuota GB</label>
                            <input id="storageQuotaGb" type="number" min="0.01" step="0.01" class="form-control @error('storage_quota_gb') is-invalid @enderror" wire:model="storageQuotaGb">
                            @error('storage_quota_gb')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-sm-6">
                            <label for="storageWarningThresholdPercent" class="form-label small fw-bold text-secondary">Peringatan %</label>
                            <input id="storageWarningThresholdPercent" type="number" min="1" max="100" class="form-control @error('storage_warning_threshold_percent') is-invalid @enderror" wire:model="storageWarningThresholdPercent">
                            @error('storage_warning_threshold_percent')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-check form-switch my-4">
                        <input id="isActive" type="checkbox" class="form-check-input" wire:model="isActive">
                        <label for="isActive" class="form-check-label">Organisasi aktif</label>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-brand flex-grow-1" wire:loading.attr="disabled">
                            {{ $editingTenantId ? 'Simpan Perubahan' : 'Buat Organisasi' }}
                        </button>

                        @if($editingTenantId)
                            <button type="button" class="btn btn-light border fw-semibold" wire:click="create">Batal</button>
                        @endif
                    </div>
                </form>
            </section>
        </div>

        <div class="col-12 col-xl-8">
            <section class="panel-box p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
                    <h2 class="h5 fw-bold mb-0">Daftar Organisasi</h2>
                    <input type="search" class="form-control tenant-search" wire:model.live.debounce.350ms="search" placeholder="Cari organisasi...">
                </div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Organisasi</th>
                                <th>Path</th>
                                <th>Storage</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tenants as $tenant)
                                <tr wire:key="tenant-{{ $tenant->id }}">
                                    <td>
                                        <div class="fw-bold">{{ $tenant->name }}</div>
                                        <div class="text-secondary small">{{ $tenant->code }}</div>
                                    </td>
                                    <td><code>{{ $tenant->path_prefix }}</code></td>
                                    <td>
                                        <div class="fw-semibold">{{ number_format($tenant->storage_quota_bytes / 1024 / 1024 / 1024, 2) }} GB</div>
                                        <div class="text-secondary small">Peringatan {{ $tenant->storage_warning_threshold_percent }}%</div>
                                    </td>
                                    <td>
                                        <span class="status-pill {{ $tenant->is_active ? 'status-active' : 'status-inactive' }}">
                                            {{ $tenant->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-light border fw-semibold" wire:click="edit({{ $tenant->id }})">Edit</button>
                                            <button type="button" class="btn btn-sm btn-light border fw-semibold" wire:click="toggleActive({{ $tenant->id }})">
                                                {{ $tenant->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                            <button type="button" class="btn btn-sm btn-brand" wire:click="enterTenant({{ $tenant->id }})" @disabled(! $tenant->is_active)>Masuk</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-secondary py-5">Belum ada organisasi.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $tenants->links() }}
                </div>
            </section>
        </div>
    </div>
</div>
