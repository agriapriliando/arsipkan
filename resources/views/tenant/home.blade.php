@extends('layouts.platform')

@section('content')
    <section class="hero-card p-4 p-lg-5 mb-4">
        <div class="row g-4 align-items-center">
            <div class="col-12 col-lg-8">
                <span class="eyebrow mb-3">Konteks Tenant Aktif</span>
                <h1 class="display-6 fw-bold mb-3">{{ $tenant->name }}</h1>
                <p class="text-secondary fs-5 mb-0">
                    Resolver tenant membaca slug <code>{{ $tenant->slug }}</code> dari URL, memverifikasi status aktif, lalu menyimpan tenant ini ke request dan service container.
                </p>
            </div>

            <div class="col-12 col-lg-4">
                <div class="info-card p-4">
                    <div class="muted-label mb-2">Path Prefix</div>
                    <div class="fs-4 fw-bold mb-2">{{ $tenant->path_prefix }}</div>
                    <div class="text-secondary">Semua route tenant berikutnya akan dibangun di bawah prefix ini.</div>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-4">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="kpi-card">
                <div class="muted-label mb-2">Kode Tenant</div>
                <div class="fs-4 fw-bold">{{ $tenant->code }}</div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="kpi-card">
                <div class="muted-label mb-2">Status</div>
                <div class="fs-4 fw-bold">{{ $tenant->is_active ? 'Aktif' : 'Nonaktif' }}</div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="kpi-card">
                <div class="muted-label mb-2">Kuota Storage</div>
                <div class="fs-4 fw-bold">{{ number_format($tenant->storage_quota_bytes) }} B</div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="kpi-card">
                <div class="muted-label mb-2">Ambang Peringatan</div>
                <div class="fs-4 fw-bold">{{ $tenant->storage_warning_threshold_percent }}%</div>
            </div>
        </div>
    </section>
@endsection
