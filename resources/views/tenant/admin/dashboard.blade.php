@extends('layouts.platform')

@section('content')
    <section class="hero-card p-4 p-lg-5 mb-4">
        <span class="eyebrow mb-3">Admin Organisasi</span>
        <h1 class="display-6 fw-bold mb-3">Dashboard Admin {{ $tenant->name }}</h1>
        <p class="text-secondary fs-5 mb-0">Pantau antrean review, kondisi arsip, dan kontribusi uploader dari satu dashboard operasional.</p>

        <div class="d-flex flex-wrap gap-2 mt-4">
            <a href="{{ route('tenant.admin.files.pending', ['tenant_slug' => request()->route('tenant_slug')]) }}" class="btn btn-brand">Pending Review</a>
            <a href="{{ route('tenant.admin.files.index', ['tenant_slug' => request()->route('tenant_slug')]) }}" class="btn btn-outline-brand">Semua Berkas</a>
            <a href="{{ route('tenant.admin.files.deleted', ['tenant_slug' => request()->route('tenant_slug')]) }}" class="btn btn-outline-brand">Berkas Terhapus</a>
            <a href="{{ route('tenant.admin.upload-links.index', ['tenant_slug' => request()->route('tenant_slug')]) }}" class="btn btn-brand">Link Upload</a>
            <a href="{{ route('tenant.admin.user-accounts.index', ['tenant_slug' => request()->route('tenant_slug')]) }}" class="btn btn-outline-brand">Akun Uploader</a>
        </div>
    </section>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-md-6 col-xl-3">
            <section class="panel-box p-4 h-100">
                <div class="text-secondary small mb-2">Pending Review</div>
                <div class="d-flex align-items-center justify-content-between gap-3">
                    <div class="display-6 fw-bold mb-0">{{ number_format($pendingReviewCount, 0, ',', '.') }}</div>
                    <span class="badge rounded-pill text-bg-warning-subtle text-warning-emphasis px-3 py-2">Perlu ditinjau</span>
                </div>
                <p class="text-secondary small mb-0 mt-3">Jumlah berkas yang masih menunggu validasi admin.</p>
            </section>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <section class="panel-box p-4 h-100">
                <div class="text-secondary small mb-2">Berkas Aktif</div>
                <div class="d-flex align-items-center justify-content-between gap-3">
                    <div class="display-6 fw-bold mb-0">{{ number_format($activeFileCount, 0, ',', '.') }}</div>
                    <span class="badge rounded-pill text-bg-success-subtle text-success-emphasis px-3 py-2">Arsip tersedia</span>
                </div>
                <p class="text-secondary small mb-0 mt-3">Total berkas Organisasi yang masih aktif.</p>
            </section>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <section class="panel-box p-4 h-100">
                <div class="text-secondary small mb-2">Berkas Terhapus</div>
                <div class="d-flex align-items-center justify-content-between gap-3">
                    <div class="display-6 fw-bold mb-0">{{ number_format($deletedFileCount, 0, ',', '.') }}</div>
                    <span class="badge rounded-pill text-bg-secondary px-3 py-2">Arsip sampah</span>
                </div>
                <p class="text-secondary small mb-0 mt-3">Berkas yang sudah dihapus dan masih bisa ditinjau admin.</p>
            </section>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <section class="panel-box p-4 h-100">
                <div class="text-secondary small mb-2">Akun Uploader Aktif</div>
                <div class="d-flex align-items-center justify-content-between gap-3">
                    <div class="display-6 fw-bold mb-0">{{ number_format($activeUploaderAccountCount, 0, ',', '.') }}</div>
                    <span class="badge rounded-pill text-bg-primary-subtle text-primary-emphasis px-3 py-2">Bisa login</span>
                </div>
                <p class="text-secondary small mb-0 mt-3">Jumlah akun uploader aktif yang dapat mengakses portal user.</p>
            </section>
        </div>

        <div class="col-12 col-xl-6">
            <section class="panel-box p-4 h-100">
                <h2 class="h5 fw-bold mb-3">Leaderboard Mingguan</h2>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Peringkat</th>
                                <th>Uploader</th>
                                <th>Upload valid</th>
                                <th>Download sah</th>
                                <th>Skor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($weeklyLeaderboard as $index => $uploader)
                                <tr>
                                    <td class="fw-bold">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $uploader->name }}</div>
                                        <div class="text-secondary small">{{ $uploader->phone_number }}</div>
                                    </td>
                                    <td>{{ $uploader->valid_upload_count }}</td>
                                    <td>{{ $uploader->counted_download_count }}</td>
                                    <td class="fw-bold">{{ number_format((float) $uploader->period_score, 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-secondary py-4">Belum ada data skor minggu ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-6">
            <section class="panel-box p-4 h-100">
                <h2 class="h5 fw-bold mb-3">Leaderboard Bulanan</h2>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Peringkat</th>
                                <th>Uploader</th>
                                <th>Upload valid</th>
                                <th>Download sah</th>
                                <th>Skor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($monthlyLeaderboard as $index => $uploader)
                                <tr>
                                    <td class="fw-bold">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $uploader->name }}</div>
                                        <div class="text-secondary small">{{ $uploader->phone_number }}</div>
                                    </td>
                                    <td>{{ $uploader->valid_upload_count }}</td>
                                    <td>{{ $uploader->counted_download_count }}</td>
                                    <td class="fw-bold">{{ number_format((float) $uploader->period_score, 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-secondary py-4">Belum ada data skor bulan ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
@endsection
