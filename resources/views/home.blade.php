@extends('layouts.platform')

@section('content')
    <section class="hero-card p-4 p-lg-5">
        <div class="row g-4 align-items-center">
            <div class="col-12 col-lg-7">
                <span class="eyebrow mb-3">Tahap 3.2 aktif</span>
                <h1 class="display-6 fw-bold mb-3">Route tenant dan superadmin sudah dipisahkan dari level URL paling depan.</h1>
                <p class="text-secondary fs-5 mb-4">
                    Tenant dibaca dari segmen pertama path seperti <code>/demo-kabupaten</code>, sedangkan area platform tetap berada di <code>/superadmin</code>.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('superadmin.dashboard') }}" class="btn btn-brand">Buka Area Superadmin</a>
                    <span class="btn btn-light border rounded-3 px-3 py-2 fw-semibold disabled">Contoh tenant: buat data tenant lalu buka <code>/slug-tenant</code></span>
                </div>
            </div>

            <div class="col-12 col-lg-5">
                <div class="info-card p-4">
                    <div class="muted-label mb-2">Fondasi yang tersedia</div>
                    <div class="fw-semibold mb-2">Tenant resolver, tenant context, reserved slug, dan migration `tenants`.</div>
                    <p class="text-secondary mb-0">Tahap berikutnya bisa langsung melanjutkan ke migration tabel inti lain dan autentikasi per area tanpa mengubah pola URL lagi.</p>
                </div>
            </div>
        </div>
    </section>
@endsection
