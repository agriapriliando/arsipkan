<div
    wire:key="tenant-master-data-{{ $mode }}"
    x-data="{
        confirmOpen: false,
        confirmTitle: 'Konfirmasi tindakan',
        confirmMessage: 'Tindakan ini akan dijalankan.',
        confirmButton: 'Lanjutkan',
        confirmType: null,
        confirmId: null,
        openDeleteCategory(id, name) {
            this.confirmType = 'category';
            this.confirmId = id;
            this.confirmTitle = 'Hapus Kategori';
            this.confirmMessage = `Kategori &quot;${name}&quot; akan dihapus dari master data.`;
            this.confirmButton = 'Ya, hapus kategori';
            this.confirmOpen = true;
        },
        openDeleteTag(id, name) {
            this.confirmType = 'tag';
            this.confirmId = id;
            this.confirmTitle = 'Hapus Tag';
            this.confirmMessage = `Tag &quot;${name}&quot; akan dihapus dari master data.`;
            this.confirmButton = 'Ya, hapus tag';
            this.confirmOpen = true;
        },
        confirmDelete() {
            if (this.confirmType === 'category') {
                $wire.deleteCategory(this.confirmId);
            }

            if (this.confirmType === 'tag') {
                $wire.deleteTag(this.confirmId);
            }

            this.confirmOpen = false;
        }
    }"
>
    <style>
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

        .masterdata-tag-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 0.9rem;
        }

        .masterdata-tag-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.9rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.04);
        }

        .masterdata-tag-name {
            font-weight: 700;
            color: #0f172a;
            word-break: break-word;
        }

        .masterdata-tag-actions {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            flex-shrink: 0;
        }

        .masterdata-tag-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            border: 1px solid #dbe4ee;
            border-radius: 999px;
            min-height: 34px;
            padding: 0.45rem 0.85rem;
            font-size: 0.78rem;
            font-weight: 700;
            line-height: 1;
            background: #fff;
            color: #334155;
            transition: all 0.18s ease;
        }

        .masterdata-tag-btn:hover {
            transform: translateY(-1px);
            border-color: #cbd5e1;
        }

        .masterdata-tag-btn .bi,
        .masterdata-table-btn .bi {
            font-size: 0.82rem;
            line-height: 1;
        }

        .masterdata-tag-btn.is-edit:hover {
            color: #5b21b6;
            border-color: #c4b5fd;
            background: #f5f3ff;
        }

        .masterdata-tag-btn.is-delete {
            color: #b91c1c;
            border-color: #fecaca;
            background: #fff5f5;
        }

        .masterdata-tag-btn.is-delete:hover {
            color: #991b1b;
            border-color: #fca5a5;
            background: #fee2e2;
        }

        .masterdata-table-actions {
            display: inline-flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 0.5rem;
        }

        .masterdata-table-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            border-radius: 999px;
            min-height: 34px;
            padding-inline: 0.85rem;
            font-size: 0.76rem;
            font-weight: 700;
            line-height: 1;
        }

        @media (max-width: 575.98px) {
            .masterdata-tag-card {
                flex-direction: column;
                align-items: stretch;
            }

            .masterdata-tag-actions {
                width: 100%;
            }

            .masterdata-tag-btn {
                flex: 1 1 auto;
                justify-content: center;
                text-align: center;
            }

            .masterdata-table-actions {
                justify-content: flex-start;
            }
        }
    </style>

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
        <div>
            <span class="eyebrow mb-3">Master Data</span>
            <h1 class="h2 fw-bold mb-1">{{ $mode === 'category' ? 'CRUD Kategori' : 'CRUD Tag' }}</h1>
            <p class="text-secondary mb-0">
                {{ $mode === 'category' ? 'Kelola kategori arsip untuk organisasi aktif.' : 'Kelola tag arsip untuk organisasi aktif.' }}
            </p>
        </div>
    </div>

    @if($mode === 'category')
        <div class="row g-4">
            <div class="col-12 col-xl-5">
                <section class="panel-box p-4">
                    <h2 class="h5 fw-bold mb-3">{{ $editingCategoryId ? 'Edit Kategori' : 'Tambah Kategori' }}</h2>

                    <form wire:submit="saveCategory">
                        <div class="mb-3">
                            <label for="categoryName" class="form-label small fw-bold text-secondary">Nama Kategori</label>
                            <input id="categoryName" type="text" class="form-control @error('category_name') is-invalid @enderror" wire:model="categoryName" placeholder="Keuangan">
                            @error('category_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="categorySlug" class="form-label small fw-bold text-secondary">Slug</label>
                            <input id="categorySlug" type="text" class="form-control @error('category_slug') is-invalid @enderror" wire:model="categorySlug" placeholder="keuangan">
                            @error('category_slug')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="categoryDescription" class="form-label small fw-bold text-secondary">Deskripsi</label>
                            <textarea id="categoryDescription" rows="4" class="form-control @error('category_description') is-invalid @enderror" wire:model="categoryDescription" placeholder="Dokumen anggaran, laporan, dan pertanggungjawaban."></textarea>
                            @error('category_description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-check form-switch my-4">
                            <input id="categoryIsActive" type="checkbox" class="form-check-input" wire:model="categoryIsActive">
                            <label for="categoryIsActive" class="form-check-label">Kategori aktif</label>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-brand flex-grow-1">
                                {{ $editingCategoryId ? 'Simpan Perubahan' : 'Buat Kategori' }}
                            </button>

                            @if($editingCategoryId)
                                <button type="button" class="btn btn-light border fw-semibold" wire:click="resetCategoryForm">Batal</button>
                            @endif
                        </div>
                    </form>
                </section>
            </div>

            <div class="col-12 col-xl-7">
                <section class="panel-box p-4 h-100">
                    <h2 class="h5 fw-bold mb-3">Daftar Kategori</h2>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Kategori</th>
                                    <th>Status</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($categories as $category)
                                    <tr wire:key="category-{{ $category->id }}">
                                        <td>
                                            <div class="fw-bold">{{ $category->name }}</div>
                                            <div class="text-secondary small"><code>{{ $category->slug }}</code></div>
                                            @if($category->description)
                                                <div class="text-secondary small mt-1">{{ $category->description }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="status-pill {{ $category->is_active ? 'status-active' : 'status-inactive' }}">
                                                {{ $category->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <div class="masterdata-table-actions">
                                                <button type="button" class="btn btn-sm btn-light border masterdata-table-btn" wire:click="editCategory({{ $category->id }})">
                                                    <i class="bi bi-pencil-square"></i>
                                                    Edit
                                                </button>
                                                <button type="button" class="btn btn-sm btn-light border masterdata-table-btn" wire:click="toggleCategoryActive({{ $category->id }})">
                                                    <i class="bi {{ $category->is_active ? 'bi-pause-circle' : 'bi-play-circle' }}"></i>
                                                    {{ $category->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger masterdata-table-btn" @click="openDeleteCategory({{ $category->id }}, @js($category->name))">
                                                    <i class="bi bi-trash3"></i>
                                                    Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-secondary py-5">Belum ada kategori.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    @endif

    @if($mode === 'tag')
        <div class="row g-4">
            <div class="col-12 col-xl-5">
                <section class="panel-box p-4">
                    <h2 class="h5 fw-bold mb-3">{{ $editingTagId ? 'Edit Tag' : 'Tambah Tag' }}</h2>

                    <form wire:submit="saveTag">
                        <div class="mb-3">
                            <label for="tagInput" class="form-label small fw-bold text-secondary">Nama Tag</label>
                            <input id="tagInput" type="text" class="form-control @error('tag_name') is-invalid @enderror" wire:model="tagName" placeholder="penting">
                            @error('tag_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-brand flex-grow-1">
                                {{ $editingTagId ? 'Simpan Perubahan' : 'Buat Tag' }}
                            </button>

                            @if($editingTagId)
                                <button type="button" class="btn btn-light border fw-semibold" wire:click="resetTagForm">Batal</button>
                            @endif
                        </div>
                    </form>
                </section>
            </div>

            <div class="col-12 col-xl-7">
                <section class="panel-box p-4 h-100">
                    <h2 class="h5 fw-bold mb-3">Daftar Tag</h2>

                    <div class="masterdata-tag-list">
                        @forelse($tags as $tag)
                            <div class="masterdata-tag-card" wire:key="tag-{{ $tag->id }}">
                                <div class="masterdata-tag-name">{{ $tag->name }}</div>
                                <div class="masterdata-tag-actions">
                                <button type="button" class="masterdata-tag-btn is-edit" wire:click="editTag({{ $tag->id }})" aria-label="Edit {{ $tag->name }}">
                                    <i class="bi bi-pencil-square"></i>
                                    Edit
                                </button>
                                <button type="button" class="masterdata-tag-btn is-delete" @click="openDeleteTag({{ $tag->id }}, @js($tag->name))" aria-label="Hapus {{ $tag->name }}">
                                    <i class="bi bi-trash3"></i>
                                    Hapus
                                </button>
                                </div>
                            </div>
                        @empty
                            <div class="text-secondary py-4">Belum ada tag.</div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    @endif

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

                    <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
                        <button type="button" class="btn btn-outline-secondary px-4" @click="confirmOpen = false">Batal</button>
                        <button type="button" class="btn btn-danger px-4" x-text="confirmButton" @click="confirmDelete()"></button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
