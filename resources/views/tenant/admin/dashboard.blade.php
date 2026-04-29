@extends('layouts.platform')

@section('content')
    <section class="hero-card p-4 p-lg-5">
        <span class="eyebrow mb-3">Admin Tenant</span>
        <h1 class="display-6 fw-bold mb-3">Dashboard Admin {{ $currentTenant->name ?? 'Tenant' }}</h1>
        <p class="text-secondary fs-5 mb-0">Akun admin tenant sudah masuk dalam konteks tenant aktif.</p>
    </section>
@endsection
