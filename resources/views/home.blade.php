@extends('layouts.platform')

@php
    $title = 'Arsipkan - Aplikasi Arsip Digital Multi-Tenant untuk Organisasi';
    $metaDescription = 'Arsipkan adalah aplikasi arsip digital multi-tenant untuk instansi dan organisasi. Kelola dokumen, validasi berkas publik, pantau kontribusi uploader, dan buka katalog publik tenant dalam satu platform.';
    $metaKeywords = 'arsipkan, aplikasi arsip digital, manajemen dokumen instansi, katalog publik dokumen, aplikasi arsip organisasi, multi tenant arsip';
    $metaOgTitle = $title;
    $metaOgDescription = $metaDescription;
@endphp

@section('content')
    <style>
        .landing-shell {
            margin: -2rem -2rem 0;
            background:
                radial-gradient(circle at top left, rgba(109, 40, 217, 0.12), transparent 28%),
                radial-gradient(circle at top right, rgba(14, 165, 233, 0.1), transparent 24%),
                linear-gradient(180deg, #f8fafc 0%, #ffffff 38%, #f8fafc 100%);
        }

        .landing-nav {
            padding: 1.25rem 0;
            background: rgba(255, 255, 255, 0.82);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.9);
        }

        .landing-nav .brand-mark {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: linear-gradient(135deg, #6d28d9 0%, #4c1d95 100%);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 16px 30px rgba(109, 40, 217, 0.24);
        }

        .landing-hero {
            padding: 5rem 0 4rem;
        }

        .landing-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.9rem;
            border-radius: 999px;
            border: 1px solid rgba(109, 40, 217, 0.16);
            background: rgba(237, 233, 254, 0.9);
            color: #5b21b6;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .landing-hero-title {
            font-size: clamp(2.4rem, 5vw, 4.4rem);
            line-height: 1.02;
            letter-spacing: -0.04em;
        }

        .landing-hero-copy {
            max-width: 760px;
            color: #475569;
            font-size: 1.08rem;
        }

        .landing-visual {
            position: relative;
            overflow: hidden;
            border-radius: 28px;
            border: 1px solid #e2e8f0;
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            box-shadow: 0 30px 60px rgba(15, 23, 42, 0.08);
        }

        .landing-grid-note {
            position: absolute;
            inset: auto 1.5rem 1.5rem auto;
            width: min(280px, 100%);
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
        }

        .landing-section {
            padding: 1rem 0 4rem;
        }

        .landing-metric,
        .landing-feature,
        .landing-step,
        .landing-cta-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
            height: 100%;
        }

        .landing-metric {
            padding: 1.5rem;
        }

        .landing-metric-value {
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -0.03em;
        }

        .landing-feature,
        .landing-step {
            padding: 1.5rem;
        }

        .landing-feature-icon,
        .landing-step-icon {
            width: 52px;
            height: 52px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            background: #f5f3ff;
            color: #6d28d9;
            margin-bottom: 1rem;
        }

        .landing-step-number {
            width: 34px;
            height: 34px;
            border-radius: 999px;
            background: #ede9fe;
            color: #6d28d9;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }

        .landing-cta-card {
            padding: 2rem;
            background: linear-gradient(135deg, #ffffff 0%, #f5f3ff 100%);
        }

        .landing-footer {
            padding: 2rem 0 3rem;
            color: #64748b;
        }

        @media (max-width: 991.98px) {
            .landing-shell {
                margin: -1rem -1rem 0;
            }

            .landing-hero {
                padding-top: 3.5rem;
            }

            .landing-grid-note {
                position: static;
                width: 100%;
                margin-top: 1rem;
            }
        }
    </style>

    <div class="landing-shell">
        <nav class="landing-nav sticky-top">
            <div class="container">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="brand-mark">
                            <i data-lucide="archive" style="width: 20px;"></i>
                        </div>
                        <div>
                            <div class="fw-bold fs-5">Arsipkan</div>
                            <div class="small text-secondary">Platform arsip digital organisasi</div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="https://wa.me/6285249441182?text=Hai%20Agri%20Apriliando%2C%20mau%20tanya%20tentang%20Arsipkan." class="btn btn-outline-brand" target="_blank" rel="noopener">Hubungi Kami</a>
                        <a href="{{ url('/demo-dinas') }}" class="btn btn-brand">Lihat Tenant Demo</a>
                    </div>
                </div>
            </div>
        </nav>

        <section class="landing-hero">
            <div class="container">
                <div class="row g-4 align-items-center">
                    <div class="col-12 col-lg-7">
                        <span class="landing-chip mb-3">
                            <i data-lucide="shield-check" style="width: 16px;"></i>
                            Arsip digital untuk instansi dan organisasi
                        </span>
                        <h1 class="landing-hero-title fw-bold mb-4">Kelola file digital, katalog publik, arsip internal, dan skor kontribusi uploader dalam satu aplikasi.</h1>
                        <p class="landing-hero-copy mb-4">
                            Arsipkan membantu organisasi menyimpan dokumen secara terstruktur, memvalidasi berkas, mengatur visibilitas file, dan
                            menyediakan katalog publik organisasi yang mudah diakses masyarakat dengan kontrol penuh oleh organisasi.
                        </p>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ url('/demo-dinas') }}" class="btn btn-brand btn-lg">Buka Katalog Demo</a>
                            <a href="{{ url('/demo-dinas/leaderboard') }}" class="btn btn-outline-brand btn-lg">Lihat Leaderboard Publik</a>
                        </div>
                    </div>

                    <div class="col-12 col-lg-5">
                        <div class="landing-visual p-4 p-lg-5">
                            <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
                                <div>
                                    <div class="muted-label mb-2">Sorotan Platform</div>
                                    <div class="h4 fw-bold mb-1">Arsip terstruktur, aman, dan siap dipublikasikan</div>
                                    <div class="text-secondary">Cocok untuk dinas, sekolah, yayasan, koperasi, atau organisasi.</div>
                                </div>
                                <div class="brand-mark" style="width: 56px; height: 56px;">
                                    <i data-lucide="folders" style="width: 24px;"></i>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="panel-box p-3">
                                        <div class="d-flex justify-content-between align-items-center gap-3">
                                            <div>
                                                <div class="muted-label mb-1">Visibilitas Arsip</div>
                                                <div class="fw-semibold">Private, Internal, dan Public</div>
                                            </div>
                                            <span class="tenant-chip">Terkelola</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="panel-box p-3 h-100">
                                        <div class="muted-label mb-1">Review Admin</div>
                                        <div class="fw-semibold mb-1">Validasi berkas publik</div>
                                        <div class="small text-secondary">Hanya file yang lolos review yang tampil ke publik.</div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="panel-box p-3 h-100">
                                        <div class="muted-label mb-1">Berkas Internal</div>
                                        <div class="fw-semibold mb-2">Akses terbatas dengan akun internal</div>
                                        <div class="small text-secondary">Landing page ini dapat mengarahkan pengunjung ke tenant demo atau tenant produksi tertentu sesuai kebutuhan organisasi.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="landing-grid-note p-3">
                                <div class="muted-label mb-1">Skor Unggahan</div>
                                <div class="fw-semibold mb-1">Leaderboard uploader</div>
                                <div class="small text-secondary">Pantau kontribusi unggahan dan download file publik by User.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="landing-section">
            <div class="container">
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-4">
                        <div class="landing-metric">
                            <div class="landing-metric-value">3 Level</div>
                            <div class="fw-semibold mb-1">Visibilitas dokumen</div>
                            <div class="text-secondary small">Atur arsip sebagai private (pribadi), internal (organisasi), atau publik (masyarakat) sesuai kebutuhan organisasi.</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="landing-metric">
                            <div class="landing-metric-value">1 Organisasi</div>
                            <div class="fw-semibold mb-1">1 katalog publik</div>
                            <div class="text-secondary small">Setiap organisasi memiliki katalog file publik dan leaderboard uploader.</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="landing-metric">
                            <div class="landing-metric-value">1 Audit</div>
                            <div class="fw-semibold mb-1">Jejak aktivitas penting</div>
                            <div class="text-secondary small">Unduh publik, skor kontribusi, dan pengelolaan file dipantau dalam alur aplikasi.</div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-12 col-lg-4">
                        <div class="landing-feature">
                            <div class="landing-feature-icon">
                                <i data-lucide="link-2" style="width: 22px;"></i>
                            </div>
                            <h2 class="h5 fw-bold mb-2">Unggah Berkas Tanpa Login</h2>
                            <p class="text-secondary mb-0">
                                Pengguna dapat mengunggah berkas hanya melalui link private (sesuai periode) yang dibuat admin organisasi, tanpa harus login ke aplikasi.
                            </p>
                        </div>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="landing-feature">
                            <div class="landing-feature-icon">
                                <i data-lucide="database" style="width: 22px;"></i>
                            </div>
                            <h2 class="h5 fw-bold mb-2">Arsip Internal Organisasi</h2>
                            <p class="text-secondary mb-0">
                                Seluruh akun pengguna yang login dapat mengakses seluruh berkas internal organisasi, sehingga aplikasi menjadi basis data file digital organisasi.
                            </p>
                        </div>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="landing-feature">
                            <div class="landing-feature-icon">
                                <i data-lucide="trophy" style="width: 22px;"></i>
                            </div>
                            <h2 class="h5 fw-bold mb-2">Kontribusi Uploader Terukur</h2>
                            <p class="text-secondary mb-0">
                                Sistem skor dan leaderboard membantu organisasi melihat siapa uploader yang aktif dan paling berkontribusi.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="landing-section pt-0">
            <div class="container">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
                    <div>
                        <span class="eyebrow mb-3">Alur Penggunaan</span>
                        <h2 class="h2 fw-bold mb-2">Bagaimana Arsipkan bekerja</h2>
                        <p class="text-secondary mb-0">Dirancang untuk alur kerja unggah praktis, review, publikasi, dan pemantauan arsip yang sistematis.</p>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="landing-step">
                            <div class="landing-step-number">1</div>
                            <h3 class="h6 fw-bold mb-2">Uploader kirim berkas</h3>
                            <p class="text-secondary small mb-0">Berkas dikirim (tanpa login) melalui link token unik milik organisasi yang dikelola admin organisasi</p>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="landing-step">
                            <div class="landing-step-number">2</div>
                            <h3 class="h6 fw-bold mb-2">Admin melakukan review</h3>
                            <p class="text-secondary small mb-0">Khusus untuk berkas Publik, Admin dapat mengubah metadata, kategori, tag, visibilitas, dan status validasi file.</p>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="landing-step">
                            <div class="landing-step-number">3</div>
                            <h3 class="h6 fw-bold mb-2">File tampil sesuai visibilitas</h3>
                            <p class="text-secondary small mb-0">Untuk berkas publik ditampilkan tanpa login, berkas internal diakses oleh pengguna terdaftar organisasi dengan cara login, berkas private hanya dapat diakses oleh uploader dan admin organisasi.</p>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="landing-step">
                            <div class="landing-step-number">4</div>
                            <h3 class="h6 fw-bold mb-2">Kontribusi tercatat otomatis</h3>
                            <p class="text-secondary small mb-0">Download publik, upload valid, dan penyesuaian skor manual dipakai untuk membentuk leaderboard tenant.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="landing-section pt-0">
            <div class="container">
                <div class="landing-cta-card">
                    <div class="row g-4 align-items-center">
                        <div class="col-12 col-lg-8">
                            <span class="eyebrow mb-3">Siap Dijelajahi</span>
                            <h2 class="h2 fw-bold mb-2">Mulai dari akun demo atau masuk ke area superadmin.</h2>
                            <p class="text-secondary mb-0">
                                Hadirkan pengelolaan arsip yang lebih cepat, lebih rapi, dan lebih profesional untuk seluruh dokumen organisasi Anda.
                            </p>
                        </div>
                        <div class="col-12 col-lg-4">
                            <div class="d-grid gap-2">
                                <a href="{{ url('/demo-dinas/login') }}" class="btn btn-brand">Masuk Sebagai Pengguna Demo</a>
                                <a href="{{ url('/demo-dinas/admin/login') }}" class="btn btn-outline-brand">Masuk Admin Demo</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <footer class="landing-footer">
            <div class="container d-flex flex-column flex-md-row justify-content-between gap-2">
                <div>&copy; {{ now()->year }} Arsipkan. Platform arsip digital organisasi.</div>
                <div class="small">Solusi arsip digital untuk publikasi dokumen, pengelolaan internal, dan kolaborasi organisasi.</div>
            </div>
        </footer>
    </div>

    <script>
        lucide.createIcons();
    </script>
@endsection
