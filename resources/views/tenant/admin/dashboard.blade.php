@extends('layouts.platform')

@section('content')
    <section class="hero-card p-4 p-lg-5 mb-4">
        <span class="eyebrow mb-3">Admin Organisasi</span>
        <h1 class="display-6 fw-bold mb-3">Dashboard Admin {{ $tenant->name }}</h1>
        <p class="text-secondary fs-5 mb-0">Pantau kontribusi uploader, skor aktif, dan lakukan penyesuaian manual jika diperlukan.</p>

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
        <div class="col-12 col-xl-4">
            <section class="panel-box p-4 h-100">
                <h2 class="h5 fw-bold mb-3">Kuota Storage Organisasi</h2>
                <div class="muted-label mb-1">Total kuota</div>
                <div class="fs-4 fw-bold mb-3">{{ number_format($tenant->storage_quota_bytes / 1024 / 1024 / 1024, 2, ',', '.') }} GB</div>
                <div class="muted-label mb-1">Terpakai</div>
                <div class="fs-5 fw-bold mb-3">{{ number_format($tenant->storage_used_bytes / 1024 / 1024 / 1024, 2, ',', '.') }} GB</div>
                <div class="muted-label mb-1">Sisa</div>
                <div class="fs-5 fw-bold mb-3">{{ number_format($storageRemainingBytes / 1024 / 1024 / 1024, 2, ',', '.') }} GB</div>
                <div class="progress mb-2" role="progressbar" aria-label="Pemakaian storage" aria-valuenow="{{ $storageUsagePercent }}" aria-valuemin="0" aria-valuemax="100" style="height: 0.75rem">
                    <div class="progress-bar {{ $storageNearLimit ? 'bg-warning' : 'bg-success' }}" style="width: {{ $storageUsagePercent }}%"></div>
                </div>
                <div class="d-flex justify-content-between text-secondary small">
                    <span>{{ number_format($storageUsagePercent, 2, ',', '.') }}% terpakai</span>
                    <span>Ambang {{ $tenant->storage_warning_threshold_percent }}%</span>
                </div>
                <div class="small mt-3 {{ $storageNearLimit ? 'text-warning' : 'text-secondary' }}">
                    {{ $storageNearLimit ? 'Pemakaian storage mendekati atau melewati batas peringatan.' : 'Pemakaian storage masih dalam batas aman.' }}
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-8">
            <section class="panel-box p-4 h-100">
                <div class="row g-4">
                    <div class="col-12 col-lg-5">
                        <h2 class="h5 fw-bold mb-3">Aturan Skor Aktif</h2>
                        <div class="muted-label mb-1">Upload valid</div>
                        <div class="fs-4 fw-bold mb-3">+{{ $scoreRule->upload_valid_point }}</div>
                        <div class="text-secondary small mb-3">Diberikan saat file uploader berstatus <code>valid</code>.</div>
                        <div class="muted-label mb-1">Download sah</div>
                        <div class="fs-4 fw-bold mb-3">+{{ $scoreRule->download_point }}</div>
                        <div class="text-secondary small mb-3">Dihitung dari download file publik yang tercatat dan layak dinilai.</div>
                        <div class="text-secondary small">Rule default platform otomatis dibuat jika organisasi belum memiliki data skor sebelumnya.</div>
                    </div>

                    <div class="col-12 col-lg-7">
                        <h3 class="h6 fw-bold mb-3">Penyesuaian Skor Manual</h3>
                        <p class="text-secondary small mb-3">Gunakan nilai positif untuk menambah skor dan nilai negatif untuk mengurangi skor uploader.</p>
                        <form method="POST" action="{{ route('tenant.admin.score-adjustments.store', ['tenant_slug' => request()->route('tenant_slug')]) }}" class="row g-3 align-items-end">
                            @csrf
                            <div class="col-md-12">
                                <label for="guest_uploader_id" class="form-label fw-semibold">Uploader</label>
                                <select id="guest_uploader_id" name="guest_uploader_id" class="form-select @error('guest_uploader_id') is-invalid @enderror">
                                    <option value="">Pilih uploader</option>
                                    @foreach($guestUploaders as $uploader)
                                        <option value="{{ $uploader->id }}" @selected((string) old('guest_uploader_id') === (string) $uploader->id)>
                                            {{ $uploader->name }} (skor saat ini: {{ number_format((float) $uploader->last_score, 2, ',', '.') }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('guest_uploader_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-12">
                                <label for="delta" class="form-label fw-semibold">Nilai</label>
                                <input id="delta" type="number" step="0.01" name="delta" value="{{ old('delta') }}" class="form-control @error('delta') is-invalid @enderror" placeholder="contoh: 5 atau -2">
                                <div class="form-text">Contoh: <code>5</code> menambah, <code>-2</code> mengurangi.</div>
                                @error('delta')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-brand w-100">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="row g-4 mt-1">
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
