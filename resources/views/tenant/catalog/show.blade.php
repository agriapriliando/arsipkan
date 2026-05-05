@extends('layouts.platform')

@php
    $title = ($file->title ?: $file->original_name).' - '.$tenant->name;
    $metaOgTitle = $title;
    $metaOgDescription = 'Lihat detail berkas publik '.$tenant->name.' di Arsipkan.';
    $metaOgImage = asset('android-chrome-512x512.png');
    $isPdf = strtolower((string) $file->extension) === 'pdf' || strtolower((string) $file->mime_type) === 'application/pdf';
    $fileTypeLabel = strtoupper((string) ($file->extension ?: 'FILE'));
    $fileSizeInKb = ($file->file_size ?? 0) / 1024;
    $fileSizeLabel = $fileSizeInKb >= 1024
        ? number_format($fileSizeInKb / 1024, 2).' MB'
        : number_format($fileSizeInKb, 1).' KB';
@endphp

@section('content')
    <style>
        :root {
            --public-detail-primary: #6d28d9;
            --public-detail-primary-hover: #5b21b6;
            --public-detail-light: #f5f3ff;
            --public-detail-bg: #f8fafc;
        }

        .public-detail-shell {
            margin: -2rem -2rem 0;
            background-color: var(--public-detail-bg);
            min-height: 100vh;
        }

        .public-detail-nav {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem 0;
        }

        .public-detail-nav .navbar-brand {
            color: #0f172a;
            text-decoration: none;
        }

        .public-detail-logo {
            width: 32px;
            height: 32px;
            background: var(--public-detail-primary);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }

        .public-detail-card {
            background: #fff;
            border-radius: 24px;
            border: 1px solid #e2e8f0;
            padding: 2.5rem;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.03);
        }

        .public-detail-file-icon {
            width: 80px;
            height: 80px;
            background: var(--public-detail-light);
            color: var(--public-detail-primary);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            font-weight: 800;
        }

        .public-detail-meta-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .public-detail-meta-icon {
            width: 32px;
            height: 32px;
            background: #f1f5f9;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            flex-shrink: 0;
        }

        .public-detail-tag-pill {
            background: #f1f5f9;
            color: #475569;
            padding: 0.4rem 1rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-decoration: none;
        }

        .public-detail-download {
            background: var(--public-detail-primary);
            color: #fff;
            border: 0;
            border-radius: 14px;
            padding: 1rem 2rem;
            font-weight: 700;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            transition: all 0.2s;
            text-decoration: none;
        }

        .public-detail-download:hover {
            background: var(--public-detail-primary-hover);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(109, 40, 217, 0.2);
        }

        @media (max-width: 991.98px) {
            .public-detail-shell {
                margin: -1rem -1rem 0;
            }
        }

        @media (max-width: 767.98px) {
            .public-detail-card {
                padding: 1.5rem;
            }
        }
    </style>

    <div class="public-detail-shell">
        <x-tenant.public-header
            :tenant="$tenant"
            subtitle="Detail berkas publik"
            active="catalog"
            :show-back="true"
            :back-url="route('tenant.home', ['tenant_slug' => $tenant->slug])"
            back-label="Kembali"
            nav-class="public-detail-nav"
            logo-class="public-detail-logo"
        />

        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">
                    <div class="public-detail-card">
                        <div class="row g-5">
                            <div class="col-12 col-md-4">
                                <div class="public-detail-file-icon mx-auto mx-md-0">
                                    {{ $fileTypeLabel }}
                                </div>
                                <h1 class="h4 fw-bold mb-4 text-center text-md-start">{{ $file->title ?: $file->original_name }}</h1>

                                <div class="public-detail-meta-item">
                                    <div class="public-detail-meta-icon"><i data-lucide="user" style="width: 16px;"></i></div>
                                    <div>
                                        <p class="mb-0 text-muted small">Uploader</p>
                                        <p class="mb-0 fw-bold small">{{ $file->guestUploader?->name ?? '-' }}</p>
                                    </div>
                                </div>
                                <div class="public-detail-meta-item">
                                    <div class="public-detail-meta-icon"><i data-lucide="calendar" style="width: 16px;"></i></div>
                                    <div>
                                        <p class="mb-0 text-muted small">Tanggal Upload</p>
                                        <p class="mb-0 fw-bold small">{{ $file->uploaded_at?->translatedFormat('d M Y H:i') ?? '-' }}</p>
                                    </div>
                                </div>
                                <div class="public-detail-meta-item">
                                    <div class="public-detail-meta-icon"><i data-lucide="database" style="width: 16px;"></i></div>
                                    <div>
                                        <p class="mb-0 text-muted small">Ukuran File</p>
                                        <p class="mb-0 fw-bold small">{{ $fileSizeLabel }}</p>
                                    </div>
                                </div>
                                <div class="public-detail-meta-item">
                                    <div class="public-detail-meta-icon"><i data-lucide="file-text" style="width: 16px;"></i></div>
                                    <div>
                                        <p class="mb-0 text-muted small">Nama Asli File</p>
                                        <p class="mb-0 fw-bold small">{{ $file->original_name }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-8">
                                <h2 class="h5 fw-bold mb-3">Deskripsi Berkas</h2>
                                <p class="text-muted mb-4" style="line-height: 1.8;">
                                    {{ $file->description ?: 'Belum ada deskripsi untuk berkas publik ini.' }}
                                </p>

                                <h2 class="h5 fw-bold mb-3">Kategori & Tag</h2>
                                <div class="d-flex flex-wrap gap-2 mb-5">
                                    <span class="badge bg-primary rounded-pill px-3 py-2">{{ $file->category?->name ?? 'Tanpa kategori' }}</span>
                                    @forelse($file->tags as $tag)
                                        <span class="public-detail-tag-pill">#{{ $tag->name }}</span>
                                    @empty
                                        <span class="public-detail-tag-pill">#tanpa-tag</span>
                                    @endforelse
                                </div>

                                <div class="bg-light p-4 rounded-4 mb-4">
                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        <i data-lucide="shield-check" class="text-success"></i>
                                        <span class="fw-bold small">Berkas Publik Tervalidasi</span>
                                    </div>
                                    <p class="text-muted small mb-0">
                                        Berkas ini telah lolos proses validasi Admin Organisasi {{ $tenant->name }} dan tersedia untuk diakses publik.
                                    </p>
                                </div>

                                <a
                                    href="{{ route('tenant.catalog.download', ['tenant_slug' => $tenant->slug, 'file' => $file->id]) }}"
                                    class="public-detail-download"
                                    @if($isPdf) target="_blank" rel="noopener" @endif
                                >
                                    <i data-lucide="download"></i>
                                    Unduh Berkas Sekarang
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>
@endsection
