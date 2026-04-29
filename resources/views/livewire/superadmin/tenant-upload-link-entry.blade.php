<div>
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
        <div>
            <span class="eyebrow mb-3">Superadmin</span>
            <h1 class="h2 fw-bold mb-1">Link Upload Tenant</h1>
            <p class="text-secondary mb-0">Pilih tenant aktif untuk mengelola link upload guest.</p>
        </div>

        <input type="search" class="form-control tenant-search" wire:model.live.debounce.350ms="search" placeholder="Cari tenant...">
    </div>

    <section class="panel-box p-4">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Tenant</th>
                        <th>Path</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tenants as $tenant)
                        <tr wire:key="tenant-upload-link-{{ $tenant->id }}">
                            <td>
                                <div class="fw-bold">{{ $tenant->name }}</div>
                                <div class="text-secondary small">{{ $tenant->code }}</div>
                            </td>
                            <td><code>{{ $tenant->path_prefix }}</code></td>
                            <td>
                                <span class="status-pill {{ $tenant->is_active ? 'status-active' : 'status-inactive' }}">
                                    {{ $tenant->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-brand" wire:click="enterUploadLinks({{ $tenant->id }})" @disabled(! $tenant->is_active)>Kelola Link Upload</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-secondary py-5">Belum ada tenant.</td>
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
