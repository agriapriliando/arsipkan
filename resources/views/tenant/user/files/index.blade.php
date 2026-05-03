@extends('layouts.platform')

@section('content')
    <div
        x-data="{
            confirmOpen: false,
            confirmAction: '#',
            confirmMethod: 'DELETE',
            confirmTitle: 'Konfirmasi tindakan',
            confirmMessage: 'Tindakan ini akan dijalankan.',
            confirmButton: 'Lanjutkan',
            openDeleteModal(action, fileLabel) {
                this.confirmAction = action;
                this.confirmMethod = 'DELETE';
                this.confirmTitle = 'Pindahkan ke Arsip Terhapus';
                this.confirmMessage = `File &quot;${fileLabel}&quot; akan dipindahkan ke arsip terhapus.`;
                this.confirmButton = 'Ya, pindahkan';
                this.confirmOpen = true;
            }
        }"
    >
    <style>
        .tenant-file-filter-panel {
            padding: 1rem 1.1rem;
        }

        .tenant-file-filter-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 1rem;
            align-items: end;
        }

        .tenant-file-filter-field .form-label {
            margin-bottom: 0.45rem;
            font-size: 0.92rem;
        }

        .tenant-file-filter-field .form-control {
            height: 2.9rem;
            border-radius: 0.9rem;
        }

        .tenant-file-filter-actions {
            display: flex;
            justify-content: flex-end;
            align-items: flex-end;
            gap: 0.75rem;
            height: 100%;
        }

        .tenant-file-filter-actions .btn {
            min-width: 6.5rem;
            height: 2.9rem;
            border-radius: 0.9rem;
        }

        .app-action-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(3px);
            z-index: 1050;
        }

        .app-action-modal {
            position: fixed;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            z-index: 1051;
        }

        .app-action-modal-card {
            width: min(100%, 28rem);
            border-radius: 1.25rem;
            background: #fff;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22);
            padding: 1.5rem;
        }

        .app-action-modal-icon {
            width: 3.5rem;
            height: 3.5rem;
            border-radius: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%);
            color: #be123c;
            font-size: 1.35rem;
        }

        @media (max-width: 575.98px) {
            .tenant-file-filter-panel {
                padding: 0.95rem;
            }

            .tenant-file-filter-grid {
                grid-template-columns: 1fr;
                gap: 0.85rem;
            }

            .tenant-file-filter-actions {
                justify-content: stretch;
            }

            .tenant-file-filter-actions .btn,
            .tenant-file-filter-actions a {
                width: 100%;
            }
        }
    </style>

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
        <div>
            <span class="eyebrow mb-3">{{ $mode === 'mine' ? 'Berkas Saya' : $heading }}</span>
            <h1 class="h2 fw-bold mb-1">{{ $heading }}</h1>
            <p class="text-secondary mb-0">{{ $description }}</p>
        </div>

        @if($mode === 'mine')
            <span class="text-secondary small">Upload file dilakukan melalui link upload organisasi.</span>
        @endif
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if(in_array($mode, ['mine', 'tenant'], true))
        <section class="panel-box tenant-file-filter-panel mb-4">
            <form
                method="GET"
                action="{{ route($mode === 'mine' ? 'tenant.user.files.mine' : 'tenant.user.files.tenant', ['tenant_slug' => request()->route('tenant_slug')]) }}"
                class="tenant-file-filter-grid"
            >
                <div class="tenant-file-filter-field">
                    <label for="search" class="form-label fw-semibold">Pencarian</label>
                    <input
                        id="search"
                        type="search"
                        name="search"
                        value="{{ $filters['search'] ?? '' }}"
                        class="form-control"
                        placeholder="Cari nama file, judul, deskripsi, atau kategori"
                    >
                </div>
                <div class="tenant-file-filter-actions">
                    <button type="submit" class="btn btn-brand">Cari</button>
                    <a href="{{ route($mode === 'mine' ? 'tenant.user.files.mine' : 'tenant.user.files.tenant', ['tenant_slug' => request()->route('tenant_slug')]) }}" class="btn btn-outline-brand">Reset</a>
                </div>
            </form>
        </section>
    @endif

    <section class="panel-box p-4">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Berkas</th>
                        <th>Metadata</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($files as $file)
                        <tr>
                            <td>
                                <div class="fw-bold">{{ $file->title ?: $file->original_name }}</div>
                                <div class="text-secondary small">{{ $file->original_name }}</div>
                            </td>
                            <td>
                                @if($mode === 'mine')
                                    <form method="POST" action="{{ route('tenant.user.files.visibility', ['tenant_slug' => request()->route('tenant_slug'), 'file' => $file->id]) }}" class="mb-1">
                                        @csrf
                                        @method('PATCH')
                                        <select
                                            name="visibility"
                                            class="form-select form-select-sm"
                                            onchange="if(this.value === 'public' && !confirm('Ubah ke public? File akan masuk antrean review admin.')) { this.value = '{{ $file->visibility }}'; return; } this.form.submit();"
                                        >
                                            <option value="private" @selected($file->visibility === 'private')>private</option>
                                            <option value="internal" @selected($file->visibility === 'internal')>internal</option>
                                            <option value="public" @selected($file->visibility === 'public')>public</option>
                                        </select>
                                    </form>
                                    <div class="text-secondary small">Jika diubah ke public, file akan direview ulang admin.</div>
                                @else
                                    <div class="fw-semibold text-capitalize">{{ $file->visibility }}</div>
                                @endif
                                <div class="text-secondary small">
                                    @if($mode === 'tenant')
                                        {{ $file->guestUploader?->name ?? 'Uploader tidak diketahui' }} |
                                    @endif
                                    {{ $file->category?->name ?? 'Tanpa kategori' }} | {{ $file->uploaded_at?->translatedFormat('d M Y H:i') ?? '-' }}
                                </div>
                            </td>
                            <td>
                                <span class="status-pill {{ $file->status === 'valid' ? 'status-active' : 'status-inactive' }}">
                                    {{ str_replace('_', ' ', $file->status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('tenant.user.files.show', ['tenant_slug' => request()->route('tenant_slug'), 'file' => $file->id]) }}" class="btn btn-sm btn-light border fw-semibold">Detail</a>
                                    <a href="{{ route('tenant.user.files.download', ['tenant_slug' => request()->route('tenant_slug'), 'file' => $file->id]) }}" class="btn btn-sm btn-light border fw-semibold">Unduh</a>
                                    @if($mode === 'mine')
                                        <form method="POST" action="{{ route('tenant.user.files.destroy', ['tenant_slug' => request()->route('tenant_slug'), 'file' => $file->id]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger fw-semibold"
                                                @click.prevent="openDeleteModal('{{ route('tenant.user.files.destroy', ['tenant_slug' => request()->route('tenant_slug'), 'file' => $file->id]) }}', @js($file->title ?: $file->original_name))"
                                            >Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-secondary py-5">Belum ada berkas untuk ditampilkan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($files, 'links'))
            <div class="mt-4">
                {{ $files->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </section>

    <template x-if="confirmOpen">
        <div>
            <div class="app-action-modal-backdrop" @click="confirmOpen = false"></div>
            <div class="app-action-modal">
                <div class="app-action-modal-card">
                    <div class="text-center">
                        <div class="app-action-modal-icon mx-auto mb-3">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                        <h2 class="h4 fw-bold mb-2" x-text="confirmTitle"></h2>
                        <p class="text-secondary mb-4" x-html="confirmMessage"></p>
                    </div>

                    <form :action="confirmAction" method="POST" class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
                        @csrf
                        <input type="hidden" name="_method" :value="confirmMethod">
                        <button type="button" class="btn btn-outline-secondary px-4" @click="confirmOpen = false">Batal</button>
                        <button type="submit" class="btn btn-danger px-4" x-text="confirmButton"></button>
                    </form>
                </div>
            </div>
        </div>
    </template>
    </div>
@endsection
