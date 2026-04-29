@extends('layouts.platform')

@section('content')
    <section class="hero-card p-4 p-lg-5 mb-4">
        <span class="eyebrow mb-3">User Uploader</span>
        <h1 class="display-6 fw-bold mb-3">Portal Uploader {{ $currentTenant->name ?? 'Tenant' }}</h1>
        <p class="text-secondary fs-5 mb-0">Kelola berkas Anda dan lihat arsip internal tenant. Upload hanya dilakukan melalui link upload tenant.</p>

        <div class="d-flex flex-wrap gap-2 mt-4">
            <a href="{{ route('tenant.user.files.mine', ['tenant_slug' => request()->route('tenant_slug')]) }}" class="btn btn-brand">Berkas Saya</a>
            <a href="{{ route('tenant.user.files.tenant', ['tenant_slug' => request()->route('tenant_slug')]) }}" class="btn btn-outline-brand">Arsip Tenant</a>
        </div>
    </section>

    <div class="row g-4">
        <div class="col-12 col-md-4">
            <section class="stat-card">
                <div class="stat-icon icon-purple">
                    <i data-lucide="folder"></i>
                </div>
                <div class="stat-value">{{ $myFileCount }}</div>
                <div class="stat-label">Total berkas saya</div>
            </section>
        </div>

        <div class="col-12 col-md-4">
            <section class="stat-card">
                <div class="stat-icon icon-amber">
                    <i data-lucide="file-clock"></i>
                </div>
                <div class="stat-value">{{ $pendingReviewCount }}</div>
                <div class="stat-label">Menunggu review</div>
            </section>
        </div>

        <div class="col-12 col-md-4">
            <section class="stat-card">
                <div class="stat-icon icon-emerald">
                    <i data-lucide="link"></i>
                </div>
                <div class="stat-value">{{ $uploadLinkCount }}</div>
                <div class="stat-label">Link upload aktif</div>
            </section>
        </div>
    </div>

    <section class="panel-box p-4 mt-4">
        <h2 class="h5 fw-bold mb-2">Cara Upload</h2>
        <p class="text-secondary mb-0">Sebagai user uploader, Anda tidak mengunggah file dari portal ini. Minta atau gunakan link upload tenant yang dibagikan admin, lalu unggah file melalui halaman link tersebut.</p>
    </section>
@endsection
