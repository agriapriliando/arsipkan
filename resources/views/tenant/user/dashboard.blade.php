@extends('layouts.platform')

@section('content')
    <section class="hero-card p-4 p-lg-5">
        <span class="eyebrow mb-3">User Uploader</span>
        <h1 class="display-6 fw-bold mb-3">Dashboard User {{ $currentTenant->name ?? 'Tenant' }}</h1>
        <p class="text-secondary fs-5 mb-0">Akun uploader sudah terautentikasi memakai nomor HP pada tenant aktif.</p>
    </section>
@endsection
