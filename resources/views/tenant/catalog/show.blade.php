@extends('layouts.platform')

@php
    $title = ($file->title ?: $file->original_name).' - '.$tenant->name;
@endphp

@section('content')
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
        <div>
            <span class="eyebrow mb-3">Detail Arsip Publik</span>
            <h1 class="h2 fw-bold mb-1">{{ $file->title ?: $file->original_name }}</h1>
            <p class="text-secondary mb-0">Berkas publik valid dari tenant {{ $tenant->name }}.</p>
        </div>

        <div class="d-flex gap-2">
            <a
                href="{{ route('tenant.catalog.download', ['tenant_slug' => $tenant->slug, 'file' => $file->id]) }}"
                class="btn btn-brand"
                @if(strtolower((string) $file->extension) === 'pdf' || strtolower((string) $file->mime_type) === 'application/pdf') target="_blank" rel="noopener" @endif
            >
                Unduh
            </a>
            <a href="{{ route('tenant.home', ['tenant_slug' => $tenant->slug]) }}" class="btn btn-outline-brand">Kembali</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <section class="panel-box p-4">
                <h2 class="h5 fw-bold mb-3">Informasi Berkas</h2>

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="muted-label mb-1">Nama asli</div>
                        <div class="fw-semibold">{{ $file->original_name }}</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="muted-label mb-1">Kategori</div>
                        <div class="fw-semibold">{{ $file->category?->name ?? '-' }}</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="muted-label mb-1">Tipe file</div>
                        <div class="fw-semibold">{{ $file->final_file_type ?? $file->detected_file_type ?? $file->mime_type ?? $file->extension ?? '-' }}</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="muted-label mb-1">Ukuran</div>
                        <div class="fw-semibold">{{ number_format(($file->file_size ?? 0) / 1024, 1) }} KB</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="muted-label mb-1">Diunggah</div>
                        <div class="fw-semibold">{{ $file->uploaded_at?->translatedFormat('d M Y H:i') ?? '-' }}</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="muted-label mb-1">Pengunggah</div>
                        <div class="fw-semibold">{{ $file->guestUploader?->name ?? '-' }}</div>
                    </div>
                    <div class="col-12">
                        <div class="muted-label mb-1">Deskripsi</div>
                        <div class="fw-semibold">{{ $file->description ?: '-' }}</div>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-12 col-lg-4">
            <section class="info-card p-4">
                <div class="muted-label mb-2">Tag</div>
                <div class="d-flex flex-wrap gap-2">
                    @forelse($file->tags as $tag)
                        <span class="tag-chip">{{ $tag->name }}</span>
                    @empty
                        <span class="text-secondary">Belum ada tag.</span>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection
