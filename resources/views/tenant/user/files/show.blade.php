@extends('layouts.platform')

@section('content')
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
        <div>
            <span class="eyebrow mb-3">Detail Berkas</span>
            <h1 class="h2 fw-bold mb-1">{{ $file->title ?: $file->original_name }}</h1>
            <p class="text-secondary mb-0">Informasi lengkap berkas di tenant aktif.</p>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('tenant.user.files.download', ['tenant_slug' => request()->route('tenant_slug'), 'file' => $file->id]) }}" class="btn btn-brand">Unduh</a>
            <a href="{{ url()->previous() }}" class="btn btn-outline-brand">Kembali</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <section class="panel-box p-4">
                <h2 class="h5 fw-bold mb-3">Informasi Berkas</h2>

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="muted-label mb-1">Nama Asli</div>
                        <div class="fw-semibold">{{ $file->original_name }}</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="muted-label mb-1">Judul</div>
                        <div class="fw-semibold">{{ $file->title ?? '-' }}</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="muted-label mb-1">Visibilitas</div>
                        <div class="fw-semibold text-capitalize">{{ $file->visibility }}</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="muted-label mb-1">Status</div>
                        <div class="fw-semibold text-capitalize">{{ str_replace('_', ' ', $file->status) }}</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="muted-label mb-1">Kategori</div>
                        <div class="fw-semibold">{{ $file->category?->name ?? '-' }}</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="muted-label mb-1">Tipe File</div>
                        <div class="fw-semibold">{{ $file->final_file_type ?? $file->mime_type ?? '-' }}</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="muted-label mb-1">Ukuran</div>
                        <div class="fw-semibold">{{ number_format(($file->file_size ?? 0) / 1024, 1) }} KB</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="muted-label mb-1">Diunggah</div>
                        <div class="fw-semibold">{{ $file->uploaded_at?->format('d M Y H:i') ?? '-' }}</div>
                    </div>
                    <div class="col-12">
                        <div class="muted-label mb-1">Deskripsi</div>
                        <div class="fw-semibold">{{ $file->description ?: '-' }}</div>
                    </div>
                    <div class="col-12">
                        <div class="muted-label mb-1">Tag</div>
                        <div class="d-flex flex-wrap gap-2">
                            @forelse($file->tags as $tag)
                                <span class="tag-chip">{{ $tag->name }}</span>
                            @empty
                                <span class="text-secondary">Belum ada tag.</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-12 col-lg-4">
            <section class="info-card p-4">
                <div class="muted-label mb-1">Pengunggah</div>
                <div class="fw-semibold">{{ $file->guestUploader?->name ?? '-' }}</div>
                <div class="text-secondary small">{{ $file->guestUploader?->phone_number ?? '-' }}</div>
            </section>
        </div>
    </div>
@endsection
