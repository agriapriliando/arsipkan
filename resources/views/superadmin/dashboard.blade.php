@extends('layouts.platform')

@section('content')
    <section class="hero-card p-4 p-lg-5">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-4">
            <div>
                <span class="eyebrow mb-3">Area Platform</span>
                <h1 class="display-6 fw-bold mb-3">Dashboard Superadmin berada di luar konteks organisasi.</h1>
                <p class="text-secondary fs-5 mb-0">
                    Semua route dengan awalan <code>/superadmin</code> tidak melewati resolver organisasi, sehingga tidak bentrok dengan slug organisasi yang valid.
                </p>
                <div class="d-flex flex-wrap gap-2 mt-4">
                    <a href="{{ route('superadmin.tenants.index') }}" class="btn btn-brand">Kelola Organisasi</a>
                    <a href="{{ route('superadmin.admins.index') }}" class="btn btn-outline-brand">Kelola Admin Organisasi</a>
                    <a href="{{ route('superadmin.master-data.index') }}" class="btn btn-outline-brand">Master Data Organisasi</a>
                    <a href="{{ route('superadmin.upload-links.index') }}" class="btn btn-outline-brand">Link Upload Organisasi</a>
                </div>
            </div>

            <div class="info-card p-4" style="min-width: min(100%, 320px);">
                <div class="muted-label mb-2">Path aktif</div>
                <div class="fs-4 fw-bold mb-3">{{ request()->path() }}</div>
                <div class="text-secondary">Gunakan area ini untuk manajemen organisasi, kuota storage, dan admin organisasi.</div>
            </div>
        </div>
    </section>
@endsection
