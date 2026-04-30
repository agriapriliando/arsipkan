<div
    x-data="{
        refreshIcons() {
            this.$nextTick(() => {
                if (window.lucide) {
                    window.lucide.createIcons();
                }
            });
        }
    }"
    x-init="refreshIcons()"
    x-on:refresh-lucide-icons.window="refreshIcons()"
>
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
        <div>
            <span class="eyebrow mb-3">Link Upload</span>
            <h1 class="h2 fw-bold mb-1">Manajemen Link Upload</h1>
            <p class="text-secondary mb-0">Kelola jalur upload guest berdasarkan kode, masa berlaku, dan batas penggunaan.</p>
        </div>

        <button type="button" class="btn btn-brand" wire:click="create">Tambah Link</button>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-4">
            <section class="panel-box p-4">
                <h2 class="h5 fw-bold mb-3">{{ $editingUploadLinkId ? 'Edit Link Upload' : 'Tambah Link Upload' }}</h2>

                <form wire:submit="save">
                    <div class="mb-3">
                        <label for="code" class="form-label small fw-bold text-secondary">Kode</label>
                        <input id="code" type="text" class="form-control @error('code') is-invalid @enderror" wire:model="code" placeholder="misalnya upload-a">
                        @if(! $editingUploadLinkId)
                            <div class="form-text">Kosongkan jika ingin dibuat otomatis dengan huruf kecil acak.</div>
                        @endif
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="title" class="form-label small fw-bold text-secondary">Judul</label>
                        <input id="title" type="text" class="form-control @error('title') is-invalid @enderror" wire:model="title" placeholder="Upload Arsip Kegiatan">
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="expiresAt" class="form-label small fw-bold text-secondary">Masa Berlaku</label>
                        <input id="expiresAt" type="datetime-local" class="form-control @error('expires_at') is-invalid @enderror" wire:model="expiresAt">
                        @error('expires_at')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="maxUsage" class="form-label small fw-bold text-secondary">Batas Penggunaan</label>
                        <input id="maxUsage" type="number" min="1" class="form-control @error('max_usage') is-invalid @enderror" wire:model="maxUsage" placeholder="Kosongkan untuk tanpa batas">
                        @error('max_usage')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-check form-switch my-4">
                        <input id="isActive" type="checkbox" class="form-check-input" wire:model="isActive">
                        <label for="isActive" class="form-check-label">Link aktif</label>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-brand flex-grow-1" wire:loading.attr="disabled">
                            {{ $editingUploadLinkId ? 'Simpan Perubahan' : 'Buat Link' }}
                        </button>

                        @if($editingUploadLinkId)
                            <button type="button" class="btn btn-light border fw-semibold" wire:click="resetForm">Batal</button>
                        @endif
                    </div>
                </form>
            </section>
        </div>

        <div class="col-12 col-xl-8">
            <section class="panel-box p-4">
                <h2 class="h5 fw-bold mb-3">Daftar Link Upload</h2>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Link</th>
                                <th>Aturan</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($uploadLinks as $uploadLink)
                                <tr wire:key="upload-link-{{ $uploadLink->id }}">
                                    <td>
                                        <div class="fw-bold">{{ $uploadLink->title }}</div>
                                        <div class="text-secondary small"><code>{{ $uploadLink->code }}</code></div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">
                                            {{ $uploadLink->usage_count }} / {{ $uploadLink->max_usage ?? 'tanpa batas' }}
                                        </div>
                                        <div class="text-secondary small">
                                            Berlaku sampai {{ $uploadLink->expires_at?->translatedFormat('d M Y H:i') ?? 'tanpa batas waktu' }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-pill {{ $uploadLink->isUsableForGuestUpload() ? 'status-active' : 'status-inactive' }}">
                                            {{ $uploadLink->isUsableForGuestUpload() ? 'Siap dipakai' : 'Tidak tersedia' }}
                                        </span>
                                    </td>
                                    <td class="text-end" x-data="{ copied: false, async copyLink(url) { try { if (navigator.clipboard?.writeText) { await navigator.clipboard.writeText(url); } else { const input = document.createElement('input'); input.value = url; document.body.appendChild(input); input.select(); document.execCommand('copy'); input.remove(); } this.copied = true; setTimeout(() => this.copied = false, 2000); } catch (error) { window.prompt('Salin link ini:', url); } } }">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-light border fw-semibold" data-copy-url="{{ route('tenant.upload.show', ['tenant_slug' => $uploadLink->tenant->slug, 'code' => $uploadLink->code]) }}" x-on:click="copyLink($el.dataset.copyUrl)" x-bind:title="copied ? 'Link tersalin' : 'Copy link'" x-bind:aria-label="copied ? 'Link tersalin' : 'Copy link'">
                                                <i data-lucide="copy" style="width: 16px; height: 16px"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-light border fw-semibold" wire:click="edit({{ $uploadLink->id }})">Edit</button>
                                            <button type="button" class="btn btn-sm btn-light border fw-semibold" wire:click="toggleActive({{ $uploadLink->id }})">
                                                {{ $uploadLink->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger fw-semibold" wire:click="delete({{ $uploadLink->id }})" wire:confirm="Hapus link upload ini?">Hapus</button>
                                        </div>
                                        <div class="small text-success mt-1" x-show="copied">Link tersalin</div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-secondary py-5">Belum ada link upload.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</div>
