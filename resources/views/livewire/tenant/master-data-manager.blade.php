<div>
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
        <div>
            <span class="eyebrow mb-3">Master Data</span>
            <h1 class="h2 fw-bold mb-1">Kategori dan Tag</h1>
            <p class="text-secondary mb-0">Kelola klasifikasi arsip untuk organisasi aktif.</p>
        </div>
    </div>

    <div id="kategori" class="row g-4">
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
                        <button type="submit" class="btn btn-brand flex-grow-1" wire:loading.attr="disabled">
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
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-light border fw-semibold" wire:click="editCategory({{ $category->id }})">Edit</button>
                                            <button type="button" class="btn btn-sm btn-light border fw-semibold" wire:click="toggleCategoryActive({{ $category->id }})">
                                                {{ $category->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger fw-semibold" wire:click="deleteCategory({{ $category->id }})" wire:confirm="Hapus kategori ini?">Hapus</button>
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

    <div id="tag" class="row g-4 mt-1">
        <div class="col-12 col-xl-5">
            <section class="panel-box p-4">
                <h2 class="h5 fw-bold mb-3">{{ $editingTagId ? 'Edit Tag' : 'Tambah Tag' }}</h2>

                <form wire:submit="saveTag">
                    <div class="mb-3">
                        <label for="tagName" class="form-label small fw-bold text-secondary">Nama Tag</label>
                        <input id="tagName" type="text" class="form-control @error('tag_name') is-invalid @enderror" wire:model="tagName" placeholder="penting">
                        @error('tag_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-brand flex-grow-1" wire:loading.attr="disabled">
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

                <div class="d-flex flex-wrap gap-2">
                    @forelse($tags as $tag)
                        <div class="tag-chip" wire:key="tag-{{ $tag->id }}">
                            <span>{{ $tag->name }}</span>
                            <button type="button" class="tag-chip-action" wire:click="editTag({{ $tag->id }})" aria-label="Edit {{ $tag->name }}">
                                <i data-lucide="pencil"></i>
                            </button>
                            <button type="button" class="tag-chip-action text-danger" wire:click="deleteTag({{ $tag->id }})" wire:confirm="Hapus tag ini?" aria-label="Hapus {{ $tag->name }}">
                                <i data-lucide="x"></i>
                            </button>
                        </div>
                    @empty
                        <div class="text-secondary py-4">Belum ada tag.</div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</div>
