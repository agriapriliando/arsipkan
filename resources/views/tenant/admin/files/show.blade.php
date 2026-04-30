@extends('layouts.platform')

@section('content')
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3 mb-4">
        <div>
            <span class="eyebrow mb-3">Review File</span>
            <h1 class="h2 fw-bold mb-1">{{ $file->title ?: $file->original_name }}</h1>
            <p class="text-secondary mb-0">Lengkapi metadata dan tentukan status review file tenant ini.</p>
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
                    <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Hapus permanen file ini? Tindakan ini tidak dapat dibatalkan.')">Hapus Permanen</button>
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
                                                    Hanya pemilik file dan admin tenant yang dapat mengakses.
                                                @elseif($value === 'internal')
                                                    Dapat diakses di dalam portal tenant.
                                                @else
                                                    Dapat muncul di katalog publik tenant.
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
                            <input id="final_file_type" type="text" name="final_file_type" value="{{ old('final_file_type', $file->final_file_type) }}" class="form-control @error('final_file_type') is-invalid @enderror" placeholder="contoh: laporan, surat, foto">
                            @error('final_file_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-3">
                        <label for="tag_ids" class="form-label fw-semibold">Tag</label>
                        <select id="tag_ids" name="tag_ids[]" multiple size="{{ max(3, min(6, $tags->count())) }}" class="form-select @error('tag_ids') is-invalid @enderror @error('tag_ids.*') is-invalid @enderror">
                            @php
                                $selectedTagIds = collect(old('tag_ids', $file->tags->pluck('id')->all()))->map(fn ($id) => (string) $id)->all();
                            @endphp
                            @foreach($tags as $tag)
                                <option value="{{ $tag->id }}" @selected(in_array((string) $tag->id, $selectedTagIds, true))>{{ $tag->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Tekan `Ctrl` atau `Cmd` untuk memilih lebih dari satu tag.</div>
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
@endsection
