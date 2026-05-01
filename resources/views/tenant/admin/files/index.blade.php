@extends('layouts.platform')

@section('content')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const filterForm = document.getElementById('adminFileFilterForm');
            let searchDebounceTimer = null;

            if (filterForm) {
                const autoSubmitFields = filterForm.querySelectorAll('[data-auto-submit="change"]');
                const delayedSearchField = filterForm.querySelector('[data-auto-submit="search"]');

                autoSubmitFields.forEach((field) => {
                    field.addEventListener('change', () => {
                        filterForm.requestSubmit();
                    });
                });

                if (delayedSearchField) {
                    delayedSearchField.addEventListener('input', () => {
                        window.clearTimeout(searchDebounceTimer);
                        searchDebounceTimer = window.setTimeout(() => {
                            filterForm.requestSubmit();
                        }, 700);
                    });
                }
            }

            const confirmModalElement = document.getElementById('fileActionConfirmModal');
            const confirmForm = document.getElementById('fileActionConfirmForm');
            const confirmFormMethod = document.getElementById('fileActionConfirmFormMethod');
            const confirmTitle = document.getElementById('fileActionConfirmTitle');
            const confirmMessage = document.getElementById('fileActionConfirmMessage');
            const confirmButton = document.getElementById('fileActionConfirmButton');

            if (!confirmModalElement || !confirmForm || !confirmFormMethod || !window.bootstrap) {
                return;
            }

            const confirmModal = new window.bootstrap.Modal(confirmModalElement);

            document.querySelectorAll('[data-file-action-confirm]').forEach((trigger) => {
                trigger.addEventListener('click', (event) => {
                    event.preventDefault();

                    confirmForm.setAttribute('action', trigger.dataset.formAction || '#');
                    confirmFormMethod.value = trigger.dataset.formMethod || 'DELETE';
                    confirmTitle.textContent = trigger.dataset.confirmTitle || 'Konfirmasi tindakan';
                    confirmMessage.textContent = trigger.dataset.confirmMessage || 'Tindakan ini akan dijalankan.';
                    confirmButton.textContent = trigger.dataset.confirmButton || 'Lanjutkan';
                    confirmButton.className = `btn ${trigger.dataset.confirmButtonClass || 'btn-danger'}`;

                    confirmModal.show();
                });
            });
        });
    </script>
    <style>
        .admin-file-filter-panel {
            padding: 1rem 1.1rem;
        }

        .admin-file-filter-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.8fr) minmax(0, 1fr) minmax(0, 1fr) auto;
            gap: 1rem;
            align-items: end;
        }

        .admin-file-filter-field {
            min-width: 0;
        }

        .admin-file-filter-field .form-label {
            margin-bottom: 0.45rem;
            font-size: 0.92rem;
        }

        .admin-file-filter-field .form-control,
        .admin-file-filter-field .form-select {
            height: 2.9rem;
            border-radius: 0.9rem;
        }

        .admin-file-filter-actions {
            display: flex;
            justify-content: flex-end;
            align-items: flex-end;
            height: 100%;
        }

        .admin-file-filter-actions .btn {
            min-width: 6.5rem;
            height: 2.9rem;
            border-radius: 0.9rem;
        }

        .admin-file-filter-hint {
            margin-top: 0.45rem;
            font-size: 0.8rem;
        }

        .file-action-confirm-icon {
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

        @media (max-width: 991.98px) {
            .admin-file-filter-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 575.98px) {
            .admin-file-filter-panel {
                padding: 0.95rem;
            }

            .admin-file-filter-grid {
                grid-template-columns: 1fr;
                gap: 0.85rem;
            }

            .admin-file-filter-actions {
                justify-content: stretch;
            }

            .admin-file-filter-actions .btn {
                width: 100%;
            }
        }
    </style>

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
        <div>
            <span class="eyebrow mb-3">Manajemen File</span>
            <h1 class="h2 fw-bold mb-1">{{ $heading }}</h1>
            <p class="text-secondary mb-0">{{ $description }}</p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('tenant.admin.files.pending', ['tenant_slug' => request()->route('tenant_slug')]) }}" class="btn {{ $mode === 'pending' ? 'btn-brand' : 'btn-outline-brand' }}">Pending Review</a>
            <a href="{{ route('tenant.admin.files.index', ['tenant_slug' => request()->route('tenant_slug')]) }}" class="btn {{ $mode === 'all' ? 'btn-brand' : 'btn-outline-brand' }}">Semua Berkas</a>
            <a href="{{ route('tenant.admin.files.deleted', ['tenant_slug' => request()->route('tenant_slug')]) }}" class="btn {{ $mode === 'deleted' ? 'btn-brand' : 'btn-outline-brand' }}">Berkas Terhapus</a>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if($mode === 'all')
        <section class="panel-box admin-file-filter-panel mb-4">
            <form
                id="adminFileFilterForm"
                method="GET"
                action="{{ route('tenant.admin.files.index', ['tenant_slug' => request()->route('tenant_slug')]) }}"
                class="admin-file-filter-grid"
            >
                <div class="admin-file-filter-field">
                    <label for="search" class="form-label fw-semibold">Pencarian</label>
                    <input
                        id="search"
                        type="text"
                        name="search"
                        data-auto-submit="search"
                        value="{{ $filters['search'] ?? '' }}"
                        class="form-control"
                        placeholder="Cari nama file, uploader, HP, atau kode link"
                    >
                </div>
                <div class="admin-file-filter-field">
                    <label for="visibility" class="form-label fw-semibold">Visibilitas</label>
                    <select id="visibility" name="visibility" class="form-select" data-auto-submit="change">
                        <option value="">Semua visibilitas</option>
                        <option value="public" @selected(($filters['visibility'] ?? '') === 'public')>public</option>
                        <option value="internal" @selected(($filters['visibility'] ?? '') === 'internal')>internal</option>
                        <option value="private" @selected(($filters['visibility'] ?? '') === 'private')>private</option>
                    </select>
                </div>
                <div class="admin-file-filter-field">
                    <label for="category_id" class="form-label fw-semibold">Kategori</label>
                    <select id="category_id" name="category_id" class="form-select" data-auto-submit="change">
                        <option value="">Semua kategori</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="admin-file-filter-actions">
                    <div class="d-flex gap-2">
                        <a href="{{ route('tenant.admin.files.index', ['tenant_slug' => request()->route('tenant_slug')]) }}" class="btn btn-outline-brand">Reset</a>
                    </div>
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
                        <th>Uploader</th>
                        <th>Visibilitas</th>
                        <th>Status</th>
                        <th>Metadata</th>
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
                                <div class="fw-semibold">{{ $file->guestUploader?->name ?? 'Uploader tidak diketahui' }}</div>
                                <div class="text-secondary small">{{ $file->guestUploader?->phone_number ?? '-' }}</div>
                            </td>
                            <td>
                                <span class="status-pill {{
                                    $file->visibility === 'public'
                                        ? 'status-active'
                                        : ($file->visibility === 'private' ? 'status-pending' : 'status-inactive')
                                }}">
                                    {{ $file->visibility }}
                                </span>
                            </td>
                            <td>
                                <span class="status-pill {{ $file->status === 'valid' ? 'status-active' : 'status-inactive' }}">
                                    {{ str_replace('_', ' ', $file->status) }}
                                </span>
                            </td>
                            <td class="text-secondary small">
                                <div>{{ $file->category?->name ?? 'Tanpa kategori' }}</div>
                                <div>{{ $file->uploaded_at?->translatedFormat('d M Y H:i') ?? '-' }}</div>
                                <div>{{ $file->uploadLink?->code ? 'Link '.$file->uploadLink->code : 'Tanpa link' }}</div>
                                @if($mode === 'deleted')
                                    <div>Dihapus: {{ $file->deleted_at?->translatedFormat('d M Y H:i') ?? '-' }}</div>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('tenant.admin.files.show', ['tenant_slug' => request()->route('tenant_slug'), 'file' => $file->id]) }}" class="btn btn-sm btn-light border fw-semibold">Detail</a>
                                    <a href="{{ route('tenant.admin.files.download', ['tenant_slug' => request()->route('tenant_slug'), 'file' => $file->id]) }}" class="btn btn-sm btn-light border fw-semibold">Unduh</a>
                                    @if($mode === 'all' || $mode === 'pending')
                                        <form method="POST" action="{{ route('tenant.admin.files.archive', ['tenant_slug' => request()->route('tenant_slug'), 'file' => $file->id]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger fw-semibold"
                                                data-file-action-confirm
                                                data-form-action="{{ route('tenant.admin.files.archive', ['tenant_slug' => request()->route('tenant_slug'), 'file' => $file->id]) }}"
                                                data-form-method="DELETE"
                                                data-confirm-title="Pindahkan ke Berkas Terhapus"
                                                data-confirm-message="File ini akan dipindahkan ke daftar berkas terhapus dan masih bisa dipulihkan nanti."
                                                data-confirm-button="Ya, pindahkan"
                                                data-confirm-button-class="btn-danger"
                                            >Hapus</button>
                                        </form>
                                    @endif
                                    @if($mode === 'deleted')
                                        <form method="POST" action="{{ route('tenant.admin.files.restore', ['tenant_slug' => request()->route('tenant_slug'), 'file' => $file->id]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-success fw-semibold"
                                                data-file-action-confirm
                                                data-form-action="{{ route('tenant.admin.files.restore', ['tenant_slug' => request()->route('tenant_slug'), 'file' => $file->id]) }}"
                                                data-form-method="PATCH"
                                                data-confirm-title="Pulihkan File"
                                                data-confirm-message="File ini akan dikembalikan ke daftar aktif dan dapat dikelola kembali."
                                                data-confirm-button="Ya, pulihkan"
                                                data-confirm-button-class="btn-success"
                                            >Pulihkan</button>
                                        </form>
                                        <form method="POST" action="{{ route('tenant.admin.files.destroy', ['tenant_slug' => request()->route('tenant_slug'), 'file' => $file->id]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger fw-semibold"
                                                data-file-action-confirm
                                                data-form-action="{{ route('tenant.admin.files.destroy', ['tenant_slug' => request()->route('tenant_slug'), 'file' => $file->id]) }}"
                                                data-form-method="DELETE"
                                                data-confirm-title="Hapus Permanen File"
                                                data-confirm-message="Tindakan ini tidak dapat dibatalkan. File akan dihapus permanen dari sistem."
                                                data-confirm-button="Ya, hapus permanen"
                                                data-confirm-button-class="btn-danger"
                                            >Hapus Permanen</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-5">Belum ada file untuk ditampilkan.</td>
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

    <div class="modal fade" id="fileActionConfirmModal" tabindex="-1" aria-labelledby="fileActionConfirmTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 1.25rem;">
                <div class="modal-body p-4 p-lg-4 text-center">
                    <div class="file-action-confirm-icon mx-auto mb-3">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <h2 id="fileActionConfirmTitle" class="h4 fw-bold mb-2">Konfirmasi tindakan</h2>
                    <p id="fileActionConfirmMessage" class="text-secondary mb-4">Tindakan ini akan dijalankan.</p>

                    <form id="fileActionConfirmForm" method="POST" class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
                        @csrf
                        <input id="fileActionConfirmFormMethod" type="hidden" name="_method" value="DELETE">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Batal</button>
                        <button id="fileActionConfirmButton" type="submit" class="btn btn-danger px-4">Lanjutkan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
