@extends('layouts.platform')

@php
    $title = 'Katalog Publik '.$tenant->name;
@endphp

@section('content')
    <style>
        :root {
            --public-brand-primary: #6d28d9;
            --public-brand-primary-hover: #5b21b6;
            --public-brand-light: #f5f3ff;
            --public-bg-body: #f8fafc;
        }

        .public-catalog-shell {
            margin: -2rem -2rem 0;
            background-color: var(--public-bg-body);
        }

        .public-catalog-nav {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem 0;
        }

        .public-catalog-nav .navbar-brand {
            color: #0f172a;
            text-decoration: none;
        }

        .public-logo-box {
            width: 32px;
            height: 32px;
            background: var(--public-brand-primary);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }

        .public-hero-section {
            background: linear-gradient(135deg, #6d28d9 0%, #4c1d95 100%);
            padding: 4rem 0 5rem;
            color: #fff;
            text-align: center;
        }

        .public-search-container {
            max-width: 860px;
            margin: -2rem auto 0;
            background: #fff;
            border-radius: 20px;
            padding: 0.75rem;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12);
        }

        .public-search-input,
        .public-search-select {
            border: 0;
            border-radius: 12px;
            min-height: 52px;
            box-shadow: none;
        }

        .public-search-input:focus,
        .public-search-select:focus {
            box-shadow: 0 0 0 0.2rem rgba(109, 40, 217, 0.15);
        }

        .public-search-button {
            background: var(--public-brand-primary);
            color: #fff;
            border: 0;
            border-radius: 12px;
            min-height: 44px;
            padding-inline: 0.75rem;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .public-search-button:hover {
            background: var(--public-brand-primary-hover);
            color: #fff;
        }

        .public-reset-button {
            border-radius: 12px;
            min-height: 44px;
            padding-inline: 0.75rem;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .public-filter-badge {
            padding: 0.5rem 1rem;
            border-radius: 999px;
            background: #fff;
            border: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }

        .public-filter-badge:hover,
        .public-filter-badge.active {
            background: var(--public-brand-primary);
            color: #fff;
            border-color: var(--public-brand-primary);
        }

        .public-file-card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            padding: 1.5rem;
            transition: all 0.2s;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .public-file-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.06);
            border-color: var(--public-brand-primary);
        }

        .public-file-icon {
            width: 48px;
            height: 48px;
            background: #f8fafc;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #475569;
            font-weight: 800;
            font-size: 0.75rem;
            margin-bottom: 1rem;
            text-transform: uppercase;
        }

        .public-file-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-top: auto;
        }

        .public-file-actions .btn {
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.875rem;
            padding: 0.65rem 1rem;
        }

        .public-btn-download {
            background: var(--public-brand-light);
            color: var(--public-brand-primary);
            border: 0;
        }

        .public-btn-download:hover {
            background: var(--public-brand-primary);
            color: #fff;
        }

        .public-footer {
            background: #fff;
            border-top: 1px solid #e2e8f0;
        }

        .public-pagination-box {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 1rem 1.25rem;
        }

        .public-pagination-box .pagination {
            margin-bottom: 0;
            justify-content: center;
        }

        @media (max-width: 991.98px) {
            .public-catalog-shell {
                margin: -1rem -1rem 0;
            }
        }

        @media (max-width: 767.98px) {
            .public-search-container {
                padding: 1rem;
            }

            .public-file-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="public-catalog-shell">
        <x-tenant.public-header
            :tenant="$tenant"
            subtitle="Katalog berkas publik"
            active="catalog"
            nav-class="public-catalog-nav"
            logo-class="public-logo-box"
        />

        <section class="public-hero-section">
            <div class="container">
                <h1 class="fw-bold mb-2">Katalog Berkas Publik</h1>
                <p class="opacity-75 mb-3">Temukan dan unduh arsip publik yang telah divalidasi oleh {{ $tenant->name }}.</p>
                <div class="small opacity-75">{{ number_format($files->total()) }} berkas ditemukan</div>
            </div>
        </section>

        <div class="container pb-5">
            <div class="public-search-container mb-2">
                <form method="GET" action="{{ route('tenant.home', ['tenant_slug' => $tenant->slug]) }}">
                    <div class="row g-2 justify-content-center align-items-center">
                        <div class="col-12 col-lg-5">
                            <input
                                type="search"
                                name="search"
                                value="{{ $filters['search'] }}"
                                class="form-control public-search-input"
                                placeholder="Cari judul, nama file, atau deskripsi"
                            >
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <select name="category_id" class="form-select public-search-select">
                                <option value="">Semua kategori</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) $filters['category_id'] === (string) $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-2">
                            <select name="tag_id" class="form-select public-search-select">
                                <option value="">Semua tag</option>
                                @foreach($tags as $tag)
                                    <option value="{{ $tag->id }}" @selected((string) $filters['tag_id'] === (string) $tag->id)>{{ $tag->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-2">
                            <select name="file_type" class="form-select public-search-select">
                                <option value="">Semua tipe</option>
                                @foreach($fileTypes as $fileType)
                                    <option value="{{ $fileType }}" @selected($filters['file_type'] === $fileType)>{{ $fileType }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-2">
                            <select name="per_page" class="form-select public-search-select">
                                @foreach($perPageOptions as $perPageOption)
                                    <option value="{{ $perPageOption }}" @selected((int) $filters['per_page'] === $perPageOption)>{{ $perPageOption }}/hal</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-lg-3">
                            <button type="submit" class="btn btn-sm public-search-button w-100">Cari</button>
                        </div>
                        <div class="col-6 col-lg-3">
                            <a href="{{ route('tenant.home', ['tenant_slug' => $tenant->slug]) }}" class="btn btn-sm btn-outline-secondary public-reset-button w-100 d-inline-flex align-items-center justify-content-center">Reset</a>
                        </div>
                    </div>
                </form>
            </div>

            @if($categories->isNotEmpty())
                <div class="d-flex flex-wrap gap-2 justify-content-center mt-4 mb-5">
                    <a href="{{ route('tenant.home', ['tenant_slug' => $tenant->slug, 'search' => $filters['search'], 'tag_id' => $filters['tag_id'], 'file_type' => $filters['file_type']]) }}" class="public-filter-badge {{ $filters['category_id'] === null ? 'active' : '' }}">Semua</a>
                    @foreach($categories as $category)
                        <a
                            href="{{ route('tenant.home', ['tenant_slug' => $tenant->slug, 'search' => $filters['search'], 'category_id' => $category->id, 'tag_id' => $filters['tag_id'], 'file_type' => $filters['file_type']]) }}"
                            class="public-filter-badge {{ (string) $filters['category_id'] === (string) $category->id ? 'active' : '' }}"
                        >
                            {{ $category->name }}
                        </a>
                    @endforeach
                </div>
            @endif

            <div class="row g-1 mb-5">
                @forelse($files as $file)
                    <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                        <article class="public-file-card">
                            {{-- <div class="public-file-icon">{{ strtoupper((string) ($file->extension ?: 'file')) }}</div> --}}
                            <h2 class="h6 fw-bold mb-1">{{ $file->title ?: $file->original_name }}</h2>
                            <p class="text-muted small mb-3">Kategori: {{ $file->category?->name ?? 'Tanpa kategori' }}</p>

                            <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                                <span class="small text-secondary d-inline-flex align-items-center gap-1">
                                    <i data-lucide="user" style="width: 14px;"></i>
                                    {{ $file->guestUploader?->name ?? 'Uploader' }}
                                </span>
                                <span class="small text-secondary d-inline-flex align-items-center gap-1">
                                    <i data-lucide="calendar-days" style="width: 14px;"></i>
                                    {{ $file->uploaded_at?->translatedFormat('d M Y') ?? '-' }}
                                </span>
                            </div>

                            <div class="d-flex flex-wrap gap-2 mb-4">
                                @foreach($file->tags->take(2) as $tag)
                                    <span class="badge rounded-pill text-bg-light border">{{ $tag->name }}</span>
                                @endforeach
                            </div>

                            <div class="public-file-actions">
                                <a href="{{ route('tenant.catalog.show', ['tenant_slug' => $tenant->slug, 'file' => $file->id]) }}" class="btn btn-light border">Detail</a>
                                <a
                                    href="{{ route('tenant.catalog.download', ['tenant_slug' => $tenant->slug, 'file' => $file->id]) }}"
                                    class="btn public-btn-download"
                                    @if(strtolower((string) $file->extension) === 'pdf' || strtolower((string) $file->mime_type) === 'application/pdf') target="_blank" rel="noopener" @endif
                                >
                                    Unduh
                                </a>
                            </div>
                        </article>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="bg-white border rounded-4 p-5 text-center text-secondary">
                            Tidak ada arsip publik yang cocok dengan filter saat ini.
                        </div>
                    </div>
                @endforelse
            </div>

            @if($files->total() > 0)
                <div class="public-pagination-box mb-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                        <div class="small text-secondary text-center text-md-start">
                            Menampilkan {{ $files->firstItem() }}-{{ $files->lastItem() }} dari {{ number_format($files->total()) }} berkas
                        </div>
                        <div>
                            {{ $files->links() }}
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <footer class="public-footer py-3">
            <div class="container text-center">
                <p class="text-muted small mb-0">
                    &copy; {{ now()->year }} {{ $tenant->name }}. Katalog arsip publik yang telah divalidasi.
                </p>
            </div>
        </footer>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>
@endsection
