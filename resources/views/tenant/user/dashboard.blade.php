@extends('layouts.platform')

@section('content')
    <section class="hero-card p-4 p-lg-5 mb-4">
        <span class="eyebrow mb-3">User Uploader</span>
        <h1 class="display-6 fw-bold mb-3">Portal Uploader {{ $currentTenant->name ?? 'Organisasi' }}</h1>
        <p class="text-secondary fs-5 mb-0">Kelola berkas Anda dan lihat arsip internal organisasi. Upload hanya dilakukan
            melalui link upload organisasi.</p>

    </section>

    <div class="row g-4">
        <div class="col-12 col-md-3">
            <section class="stat-card">
                <div class="stat-icon icon-purple">
                    <i data-lucide="folder"></i>
                </div>
                <a href="{{ route('tenant.user.files.mine', ['tenant_slug' => request()->route('tenant_slug')]) }}"
                    class="btn btn-brand btn-sm mt-1">Berkas Saya
                </a>
                <div class="stat-value">{{ $myFileCount }}</div>
                <div class="stat-label">Total berkas saya
                </div>
            </section>
        </div>

        <div class="col-12 col-md-3">
            <section class="stat-card">
                <div class="stat-icon icon-amber">
                    <i data-lucide="file-clock"></i>
                </div>
                <div class="stat-value">{{ $pendingReviewCount }}</div>
                <div class="stat-label">Menunggu review</div>
            </section>
        </div>

        <div class="col-12 col-md-3">
            <section class="stat-card">
                <div class="stat-icon icon-emerald">
                    <i data-lucide="database"></i>
                </div>
                <a href="{{ route('tenant.user.files.tenant', ['tenant_slug' => request()->route('tenant_slug')]) }}"
                    class="btn btn-outline-brand btn-sm mt-1">Arsip Organisasi</a>
                <div class="stat-value">{{ $tenantFileCount }}</div>
                <div class="stat-label">Total berkas organisasi</div>
            </section>
        </div>
        <div class="col-12 col-md-3">
            <section class="stat-card">
                <div class="stat-icon icon-emerald">
                    <i data-lucide="badge-plus"></i>
                </div>
                <div class="stat-value">{{ number_format((float) $currentScore, 2, ',', '.') }}</div>
                <div class="stat-label">Skor kontribusi saya</div>
            </section>
        </div>
    </div>

    <section class="panel-box p-4 mt-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
            <div>
                <h2 class="h5 fw-bold mb-1">Daftar Link Upload Aktif</h2>
                <p class="text-secondary mb-0">Gunakan salah satu link aktif berikut untuk mengunggah berkas tanpa login
                    tambahan.</p>
            </div>
            <span class="tenant-chip">{{ $uploadLinkCount }} link aktif</span>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Judul</th>
                        <th>Kode</th>
                        <th>Masa Berlaku</th>
                        <th>Batas Pakai</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activeUploadLinks as $uploadLink)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $uploadLink->title ?: 'Link Upload' }}</div>
                                <div class="text-secondary small">Unggah tamu untuk
                                    {{ $currentTenant->name ?? 'organisasi' }}</div>
                            </td>
                            <td><code>{{ $uploadLink->code }}</code></td>
                            <td>
                                @if ($uploadLink->expires_at)
                                    <div class="fw-semibold">{{ $uploadLink->expires_at->translatedFormat('d M Y H:i') }}
                                    </div>
                                @else
                                    <div class="fw-semibold">Tanpa batas waktu</div>
                                @endif
                            </td>
                            <td>
                                @if ($uploadLink->max_usage)
                                    <div class="fw-semibold">{{ $uploadLink->usage_count }}/{{ $uploadLink->max_usage }}
                                    </div>
                                @else
                                    <div class="fw-semibold">Tak terbatas</div>
                                @endif
                            </td>
                            <td class="text-end">
                                <a target="_blank"
                                    href="{{ route('tenant.upload.show', ['tenant_slug' => request()->route('tenant_slug'), 'code' => $uploadLink->code]) }}"
                                    class="btn btn-outline-brand btn-sm">Buka Link</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-4">Belum ada link upload aktif yang bisa
                                digunakan saat ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="row g-4 mt-1">
        <div class="col-12 col-xl-6">
            <section class="panel-box p-4 h-100">
                <h2 class="h5 fw-bold mb-3">Leaderboard Mingguan</h2>
                <p class="text-secondary small mb-3">Skor dihitung dari upload valid dan download sah pada minggu berjalan.
                </p>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Peringkat</th>
                                <th>Uploader</th>
                                <th>Skor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($weeklyLeaderboard as $index => $uploader)
                                <tr>
                                    <td class="fw-bold">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $uploader->name }}</div>
                                        <div class="text-secondary small">{{ $uploader->valid_upload_count }} upload valid
                                            | {{ $uploader->counted_download_count }} download sah</div>
                                    </td>
                                    <td class="fw-bold">{{ number_format((float) $uploader->period_score, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-secondary py-4">Belum ada data minggu ini.
                                    </td>
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
                <p class="text-secondary small mb-3">Skor dihitung dari upload valid dan download sah pada bulan berjalan.
                </p>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Peringkat</th>
                                <th>Uploader</th>
                                <th>Skor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($monthlyLeaderboard as $index => $uploader)
                                <tr>
                                    <td class="fw-bold">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $uploader->name }}</div>
                                        <div class="text-secondary small">{{ $uploader->valid_upload_count }} upload valid
                                            | {{ $uploader->counted_download_count }} download sah</div>
                                    </td>
                                    <td class="fw-bold">{{ number_format((float) $uploader->period_score, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-secondary py-4">Belum ada data bulan ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
@endsection
