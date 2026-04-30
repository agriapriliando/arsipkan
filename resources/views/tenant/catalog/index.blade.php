@extends('layouts.platform')

@php
    $title = 'Katalog Publik '.$tenant->name;
@endphp

@section('content')
    <section class="hero-card p-4 p-lg-5 mb-4">
        <div class="row g-4 align-items-end">
            <div class="col-12 col-lg-8">
                <span class="eyebrow mb-3">Katalog Publik Tenant</span>
                <h1 class="display-6 fw-bold mb-3">{{ $tenant->name }}</h1>
                <p class="text-secondary fs-5 mb-0">
                    Arsip publik yang telah lolos validasi tenant dan dapat diakses umum.
                </p>
            </div>

            <div class="col-12 col-lg-4">
                <div class="info-card p-4">
                    <div class="muted-label mb-2">Total Arsip Publik</div>
                    <div class="fs-3 fw-bold mb-2">{{ number_format($files->total()) }}</div>
                    <div class="text-secondary">Pencarian dan filter hanya menampilkan berkas publik yang valid.</div>
                </div>
            </div>
        </div>
    </section>

    <section class="panel-box p-4 mb-4">
        <form method="GET" action="{{ route('tenant.home', ['tenant_slug' => $tenant->slug]) }}">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-4">
                    <label for="search" class="form-label fw-semibold">Pencarian</label>
                    <input
                        type="search"
                        id="search"
                        name="search"
                        value="{{ $filters['search'] }}"
                        class="form-control"
                        placeholder="Judul, nama file, atau deskripsi"
                    >
                </div>
                <div class="col-12 col-md-4 col-lg-2">
                    <label for="category_id" class="form-label fw-semibold">Kategori</label>
                    <select id="category_id" name="category_id" class="form-select">
                        <option value="">Semua kategori</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) $filters['category_id'] === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4 col-lg-2">
                    <label for="tag_id" class="form-label fw-semibold">Tag</label>
                    <select id="tag_id" name="tag_id" class="form-select">
                        <option value="">Semua tag</option>
                        @foreach($tags as $tag)
                            <option value="{{ $tag->id }}" @selected((string) $filters['tag_id'] === (string) $tag->id)>{{ $tag->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4 col-lg-2">
                    <label for="file_type" class="form-label fw-semibold">Tipe file</label>
                    <select id="file_type" name="file_type" class="form-select">
                        <option value="">Semua tipe</option>
                        @foreach($fileTypes as $fileType)
                            <option value="{{ $fileType }}" @selected($filters['file_type'] === $fileType)>{{ $fileType }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-brand flex-grow-1">Terapkan</button>
                    <a href="{{ route('tenant.home', ['tenant_slug' => $tenant->slug]) }}" class="btn btn-outline-brand">Reset</a>
                </div>
            </div>
        </form>
    </section>

    <section class="row g-4">
        @forelse($files as $file)
            <div class="col-12 col-md-6 col-xl-4">
                <article class="panel-box p-4 h-100 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                        <div>
                            <div class="muted-label mb-1">{{ $file->category?->name ?? 'Tanpa kategori' }}</div>
                            <h2 class="h5 fw-bold mb-1">{{ $file->title ?: $file->original_name }}</h2>
                        </div>
                        <span class="status-pill status-active">public</span>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mb-3">
                        @forelse($file->tags->take(3) as $tag)
                            <span class="tag-chip">{{ $tag->name }}</span>
                        @empty
                            <span class="text-secondary small">Tanpa tag</span>
                        @endforelse
                    </div>

                    <div class="text-secondary small mb-3">
                        {{ $file->uploaded_at?->translatedFormat('d M Y H:i') ?? '-' }} | {{ $file->final_file_type ?? $file->detected_file_type ?? $file->mime_type ?? $file->extension ?? '-' }}
                    </div>

                    <div class="mt-auto d-flex gap-2">
                        <a href="{{ route('tenant.catalog.show', ['tenant_slug' => $tenant->slug, 'file' => $file->id]) }}" class="btn btn-light border fw-semibold">Detail</a>
                        <a
                            href="{{ route('tenant.catalog.download', ['tenant_slug' => $tenant->slug, 'file' => $file->id]) }}"
                            class="btn btn-brand"
                            @if(strtolower((string) $file->extension) === 'pdf' || strtolower((string) $file->mime_type) === 'application/pdf') target="_blank" rel="noopener" @endif
                        >
                            Unduh
                        </a>
                    </div>
                </article>
            </div>
        @empty
            <div class="col-12">
                <section class="panel-box p-5 text-center text-secondary">
                    Tidak ada arsip publik yang cocok dengan filter saat ini.
                </section>
            </div>
        @endforelse
    </section>

    @if($files->hasPages())
        <div class="mt-4">
            {{ $files->links() }}
        </div>
    @endif
@endsection
