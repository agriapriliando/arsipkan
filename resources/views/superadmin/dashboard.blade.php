@extends('layouts.platform')

@section('content')
    <section class="hero-card p-4 p-lg-5">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-4">
            <div>
                <span class="eyebrow mb-3">Area Platform</span>
                <h1 class="display-6 fw-bold mb-3">Dashboard Superadmin berada di luar konteks tenant.</h1>
                <p class="text-secondary fs-5 mb-0">
                    Semua route dengan awalan <code>/superadmin</code> tidak melewati resolver tenant, sehingga tidak bentrok dengan slug tenant yang valid.
                </p>
                <a href="{{ route('superadmin.tenants.index') }}" class="btn btn-brand mt-4">Kelola Tenant</a>
            </div>

            <div class="info-card p-4" style="min-width: min(100%, 320px);">
                <div class="muted-label mb-2">Path aktif</div>
                <div class="fs-4 fw-bold mb-3">{{ request()->path() }}</div>
                <div class="text-secondary">Gunakan area ini untuk manajemen tenant, kuota storage, dan admin tenant pada tahap berikutnya.</div>
            </div>
        </div>
    </section>
@endsection
