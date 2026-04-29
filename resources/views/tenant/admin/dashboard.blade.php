@extends('layouts.platform')

@section('content')
    <section class="hero-card p-4 p-lg-5">
        <span class="eyebrow mb-3">Admin Tenant</span>
        <h1 class="display-6 fw-bold mb-3">Dashboard Admin {{ $currentTenant->name ?? 'Tenant' }}</h1>
        <p class="text-secondary fs-5 mb-0">Akun admin tenant sudah masuk dalam konteks tenant aktif.</p>

        <div class="d-flex flex-wrap gap-2 mt-4">
            <a href="{{ route('tenant.admin.upload-links.index', ['tenant_slug' => request()->route('tenant_slug')]) }}" class="btn btn-brand">Link Upload</a>
            <a href="{{ route('tenant.admin.master-data.index', ['tenant_slug' => request()->route('tenant_slug')]) }}#kategori" class="btn btn-brand">CRUD Kategori</a>
            <a href="{{ route('tenant.admin.master-data.index', ['tenant_slug' => request()->route('tenant_slug')]) }}#tag" class="btn btn-outline-brand">CRUD Tag</a>
        </div>
    </section>
@endsection
