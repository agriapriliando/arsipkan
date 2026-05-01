@extends('layouts.platform')

@section('content')
    <style>
        .admin-tag-selector {
            border: 1px solid rgba(15, 23, 42, 0.12);
            border-radius: 1rem;
            background: #fbfcff;
            padding: 1rem;
        }

        .admin-tag-selected-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .admin-tag-selected-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.45rem 0.85rem;
            border-radius: 999px;
            background: #e7f0ff;
            color: #1447a6;
            font-size: 0.875rem;
            font-weight: 600;
            line-height: 1;
        }

        .admin-tag-selected-chip button {
            border: 0;
            background: transparent;
            color: inherit;
            padding: 0;
            line-height: 1;
        }

        .admin-tag-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 0.75rem;
            max-height: 18rem;
            overflow-y: auto;
            padding-right: 0.25rem;
        }

        .admin-tag-option {
            position: relative;
        }

        .admin-tag-option input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .admin-tag-option-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            width: 100%;
            min-height: 3rem;
            padding: 0.85rem 1rem;
            border: 1px solid rgba(15, 23, 42, 0.12);
            border-radius: 0.95rem;
            background: #ffffff;
            color: #0f172a;
            cursor: pointer;
            transition: 0.18s ease;
        }

        .admin-tag-option-label:hover {
            border-color: rgba(37, 99, 235, 0.4);
            background: #f8fbff;
        }

        .admin-tag-option input:checked + .admin-tag-option-label {
            border-color: #2563eb;
            background: #eff6ff;
            color: #1d4ed8;
            box-shadow: 0 0 0 1px rgba(37, 99, 235, 0.12);
        }

        .admin-tag-check {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.5rem;
            height: 1.5rem;
            border-radius: 999px;
            background: rgba(37, 99, 235, 0.12);
            color: #2563eb;
            font-size: 0.9rem;
            flex: 0 0 auto;
        }

        .admin-tag-empty {
            border: 1px dashed rgba(15, 23, 42, 0.16);
            border-radius: 0.95rem;
            padding: 1rem;
            color: #64748b;
            text-align: center;
            background: rgba(248, 250, 252, 0.9);
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
    </style>

    <script>
        window.adminTagSelector = function (element) {
            const parseJson = (value, fallback) => {
                try {
                    return JSON.parse(value || "");
                } catch {
                    return fallback;
                }
            };

            return {
                search: "",
                selected: parseJson(element.dataset.selectedTags, []),
                tags: parseJson(element.dataset.tagOptions, []),
                isSelected(id) {
                    return this.selected.includes(String(id));
                },
                toggle(id) {
                    const value = String(id);

                    if (this.isSelected(value)) {
                        this.selected = this.selected.filter((item) => item !== value);
                        return;
                    }

                    this.selected = [...this.selected, value];
                },
                remove(id) {
                    const value = String(id);
                    this.selected = this.selected.filter((item) => item !== value);
                },
                filteredTags() {
                    const keyword = this.search.trim().toLowerCase();

                    if (keyword === "") {
                        return this.tags;
                    }

                    return this.tags.filter((tag) => tag.name.toLowerCase().includes(keyword));
                },
            };
        };

        document.addEventListener('DOMContentLoaded', () => {
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
                    confirmButton.className = `btn ${trigger.dataset.confirmButtonClass || 'btn-danger'} px-4`;

                    confirmModal.show();
                });
            });
        });
    </script>

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3 mb-4">
        <div>
            <span class="eyebrow mb-3">Review File</span>
            <h1 class="h2 fw-bold mb-1">{{ $file->title ?: $file->original_name }}</h1>
            <p class="text-secondary mb-0">Lengkapi metadata dan tentukan status review file organisasi ini.</p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('tenant.admin.files.download', ['tenant_slug' => request()->route('tenant_slug'), 'file' => $file->id]) }}" class="btn btn-brand">Unduh</a>
            <a href="{{ route('tenant.admin.files.index', ['tenant_slug' => request()->route('tenant_slug')]) }}" class="btn btn-outline-brand">Semua Berkas</a>
            @if($file->trashed())
                <form method="POST" action="{{ route('tenant.admin.files.restore', ['tenant_slug' => request()->route('tenant_slug'), 'file' => $file->id]) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-outline-success">Pulihkan</button>
                </form>
                <form method="POST" action="{{ route('tenant.admin.files.destroy', ['tenant_slug' => request()->route('tenant_slug'), 'file' => $file->id]) }}">
                    @csrf
                    @method('DELETE')
                    <button
                        type="button"
                        class="btn btn-outline-danger"
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
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-2">Review file belum bisa disimpan.</div>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-4">
            <section class="panel-box p-4 h-100">
                <h2 class="h5 fw-bold mb-3">Informasi File</h2>
                <dl class="mb-0 text-secondary">
                    <dt class="fw-semibold text-dark mb-1">Nama asli</dt>
                    <dd class="mb-3" x-data="{ editing: false }">
                        <template x-if="!editing">
                            <button
                                type="button"
                                class="btn btn-link p-0 text-start fw-semibold text-decoration-none"
                                @click="editing = true; $nextTick(() => $refs.originalNameInput.focus())"
                            >
                                {{ $file->original_name }}
                            </button>
                        </template>

                        <form
                            method="POST"
                            action="{{ route('tenant.admin.files.original-name', ['tenant_slug' => request()->route('tenant_slug'), 'file' => $file->id]) }}"
                            class="d-flex flex-column gap-2"
                            x-show="editing"
                            x-cloak
                        >
                            @csrf
                            @method('PATCH')
                            <input
                                x-ref="originalNameInput"
                                type="text"
                                name="original_name"
                                value="{{ old('original_name', $file->original_name) }}"
                                class="form-control @error('original_name') is-invalid @enderror"
                            >
                            @error('original_name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-sm btn-brand">Simpan</button>
                                <button type="button" class="btn btn-sm btn-outline-brand" @click="editing = false">Batal</button>
                            </div>
                        </form>
                        <div class="form-text">Klik nama file untuk mengubah nama asli.</div>
                    </dd>

                    <dt class="fw-semibold text-dark mb-1">Uploader</dt>
                    <dd class="mb-3">{{ $file->guestUploader?->name ?? '-' }} <br>{{ $file->guestUploader?->phone_number ?? '-' }}</dd>

                    <dt class="fw-semibold text-dark mb-1">Visibilitas</dt>
                    <dd class="mb-3 text-capitalize">{{ $file->visibility }}</dd>

                    <dt class="fw-semibold text-dark mb-1">Status saat ini</dt>
                    <dd class="mb-3">{{ str_replace('_', ' ', $file->status) }}</dd>

                    <dt class="fw-semibold text-dark mb-1">Ukuran</dt>
                    <dd class="mb-3">{{ number_format(($file->file_size ?? 0) / 1024, 1) }} KB</dd>

                    <dt class="fw-semibold text-dark mb-1">Tipe file</dt>
                    <dd class="mb-3">{{ $file->mime_type ?? '-' }}{{ $file->extension ? ' ('.$file->extension.')' : '' }}</dd>

                    <dt class="fw-semibold text-dark mb-1">Tahun berkas</dt>
                    <dd class="mb-3">{{ $file->document_year ?? '-' }}</dd>

                    <dt class="fw-semibold text-dark mb-1">Link upload</dt>
                    <dd class="mb-3">{{ $file->uploadLink?->title ?? '-' }}{{ $file->uploadLink?->code ? ' / '.$file->uploadLink->code : '' }}</dd>

                    <dt class="fw-semibold text-dark mb-1">Review terakhir</dt>
                    <dd class="mb-0">
                        @if($file->reviewed_at)
                            {{ $file->reviewed_at->translatedFormat('d M Y H:i') }} oleh {{ $file->reviewedByAdmin?->name ?? 'Admin' }}
                        @else
                            Belum direview
                        @endif
                    </dd>

                    @if($file->trashed())
                        <dt class="fw-semibold text-dark mb-1 mt-3">Soft delete</dt>
                        <dd class="mb-0">
                            {{ $file->deleted_at?->translatedFormat('d M Y H:i') ?? '-' }}
                            @if($file->deletedByUserAccount?->guestUploader?->name)
                                oleh {{ $file->deletedByUserAccount->guestUploader->name }}
                            @endif
                        </dd>
                    @endif
                </dl>
            </section>
        </div>

        <div class="col-lg-8">
            <section class="panel-box p-4">
                <h2 class="h5 fw-bold mb-3">Form Review</h2>

                <form method="POST" action="{{ route('tenant.admin.files.update', ['tenant_slug' => request()->route('tenant_slug'), 'file' => $file->id]) }}">
                    @csrf
                    @method('PATCH')

                    <div class="mb-3">
                        <label for="title" class="form-label fw-semibold">Judul</label>
                        <input id="title" type="text" name="title" value="{{ old('title', $file->title) }}" class="form-control @error('title') is-invalid @enderror" placeholder="Judul arsip yang lebih jelas">
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold">Deskripsi</label>
                        <textarea id="description" name="description" rows="5" class="form-control @error('description') is-invalid @enderror" placeholder="Ringkasan isi dan konteks berkas">{{ old('description', $file->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Visibilitas</label>
                            <div class="d-grid gap-2">
                                @php
                                    $selectedVisibility = old('visibility', $file->visibility);
                                @endphp
                                @foreach([
                                    'private' => 'Private',
                                    'internal' => 'Internal',
                                    'public' => 'Public',
                                ] as $value => $label)
                                    <label class="border rounded-3 p-3 d-flex align-items-center gap-2">
                                        <input type="radio" name="visibility" value="{{ $value }}" class="form-check-input mt-0" @checked($selectedVisibility === $value)>
                                        <span>
                                            <span class="fw-semibold d-block">{{ $label }}</span>
                                            <span class="text-secondary small">
                                                @if($value === 'private')
                                                    Hanya pemilik file dan admin organisasi yang dapat mengakses.
                                                @elseif($value === 'internal')
                                                    Dapat diakses di dalam portal organisasi.
                                                @else
                                                    Dapat muncul di katalog publik organisasi.
                                                @endif
                                            </span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            @error('visibility')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="category_id" class="form-label fw-semibold">Kategori</label>
                            <select id="category_id" name="category_id" class="form-select @error('category_id') is-invalid @enderror">
                                <option value="">Tanpa kategori</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) old('category_id', $file->category_id) === (string) $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="final_file_type" class="form-label fw-semibold">Tipe File Final</label>
                            <input
                                id="final_file_type"
                                type="text"
                                name="final_file_type"
                                list="final_file_type_suggestions"
                                value="{{ old('final_file_type', $file->final_file_type) }}"
                                class="form-control @error('final_file_type') is-invalid @enderror"
                                placeholder="contoh: laporan, surat, foto"
                                autocomplete="off"
                            >
                            <datalist id="final_file_type_suggestions">
                                @foreach($finalFileTypeSuggestions as $suggestion)
                                    <option value="{{ $suggestion }}"></option>
                                @endforeach
                            </datalist>
                            <div class="form-text">Pilih dari tipe yang pernah dipakai, atau ketik tipe baru jika belum ada.</div>
                            @error('final_file_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="document_year" class="form-label fw-semibold">Tahun Berkas</label>
                            <input
                                id="document_year"
                                type="number"
                                name="document_year"
                                min="1900"
                                max="2100"
                                step="1"
                                value="{{ old('document_year', $file->document_year ?? now()->year) }}"
                                class="form-control @error('document_year') is-invalid @enderror"
                                placeholder="{{ now()->year }}"
                            >
                            <div class="form-text">Default tahun berjalan, tetapi bisa diubah untuk arsip tahun sebelumnya.</div>
                            @error('document_year')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-3">
                        @php
                            $selectedTagIds = collect(old('tag_ids', $file->tags->pluck('id')->all()))->map(fn ($id) => (string) $id)->values()->all();
                            $tagOptions = $tags->map(fn ($tag) => ['id' => (string) $tag->id, 'name' => $tag->name])->values();
                        @endphp

                        <label for="tag_search" class="form-label fw-semibold">Tag</label>
                        <div
                            x-data="window.adminTagSelector($el)"
                            data-selected-tags='@json($selectedTagIds, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'
                            data-tag-options='@json($tagOptions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'
                            class="admin-tag-selector @error('tag_ids') border-danger @enderror @error('tag_ids.*') border-danger @enderror"
                        >
                            <input
                                id="tag_search"
                                type="text"
                                x-model="search"
                                class="form-control mb-3"
                                placeholder="Cari tag lalu klik untuk memilih"
                            >

                            <div class="admin-tag-selected-list" x-show="selected.length > 0">
                                <template x-for="tagId in selected" :key="`selected-${tagId}`">
                                    <template x-for="tag in tags.filter((item) => item.id === tagId)" :key="tag.id">
                                        <span class="admin-tag-selected-chip">
                                            <span x-text="tag.name"></span>
                                            <button type="button" @click="remove(tag.id)" aria-label="Hapus tag terpilih">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </span>
                                    </template>
                                </template>
                            </div>

                            <div class="admin-tag-grid" x-show="filteredTags().length > 0">
                                <template x-for="tag in filteredTags()" :key="tag.id">
                                    <label class="admin-tag-option">
                                        <input
                                            type="checkbox"
                                            name="tag_ids[]"
                                            :value="tag.id"
                                            :checked="isSelected(tag.id)"
                                            @change="toggle(tag.id)"
                                        >
                                        <span class="admin-tag-option-label">
                                            <span class="fw-semibold" x-text="tag.name"></span>
                                            <span class="admin-tag-check">
                                                <i class="bi" :class='isSelected(tag.id) ? "bi-check-lg" : "bi-plus-lg"'></i>
                                            </span>
                                        </span>
                                    </label>
                                </template>
                            </div>

                            <div class="admin-tag-empty" x-show="filteredTags().length === 0">
                                Tidak ada tag yang cocok dengan pencarian.
                            </div>
                        </div>

                        <div class="form-text">Klik tag untuk memilih atau membatalkan. Gunakan kotak pencarian jika daftar tag banyak.</div>
                        @error('tag_ids')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        @error('tag_ids.*')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mt-3">
                        <label class="form-label fw-semibold">Status Review</label>
                        <div class="d-grid gap-2">
                            @php
                                $selectedStatus = old('status', $file->status);
                            @endphp
                            @foreach([
                                'pending_review' => 'Pending review',
                                'valid' => 'Valid',
                                'suspended' => 'Suspended',
                            ] as $value => $label)
                                <label class="border rounded-3 p-3 d-flex align-items-center gap-2">
                                    <input type="radio" name="status" value="{{ $value }}" class="form-check-input mt-0" @checked($selectedStatus === $value)>
                                    <span>
                                        <span class="fw-semibold d-block">{{ $label }}</span>
                                        <span class="text-secondary small">
                                            @if($value === 'pending_review')
                                                Simpan file tetap di antrean review.
                                            @elseif($value === 'valid')
                                                Nyatakan file layak dipakai sesuai visibilitasnya.
                                            @else
                                                Tahan file agar tidak aktif dipakai.
                                            @endif
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('status')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <button type="submit" class="btn btn-brand">Simpan Review</button>
                        <a href="{{ route('tenant.admin.files.pending', ['tenant_slug' => request()->route('tenant_slug')]) }}" class="btn btn-outline-brand">Kembali ke Pending Review</a>
                    </div>
                </form>
            </section>
        </div>
    </div>

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
