@extends('layouts.platform')

@section('content')
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
        <div>
            <span class="eyebrow mb-3">Profil</span>
            <h1 class="h2 fw-bold mb-1">Profil User Uploader</h1>
            <p class="text-secondary mb-0">Informasi akun login uploader pada tenant aktif.</p>
        </div>

        <a href="{{ route('tenant.password.edit', ['tenant_slug' => request()->route('tenant_slug')]) }}" class="btn btn-brand">Ubah Password</a>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <section class="panel-box p-4">
                <h2 class="h5 fw-bold mb-3">Informasi Akun</h2>

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="muted-label mb-1">Nama</div>
                        <div class="fw-semibold">{{ $account->guestUploader?->name ?? '-' }}</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="muted-label mb-1">Nomor HP</div>
                        <div class="fw-semibold">{{ $account->guestUploader?->phone_number ?? '-' }}</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="muted-label mb-1">Tenant</div>
                        <div class="fw-semibold">{{ $tenant->name }}</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="muted-label mb-1">Status Akun</div>
                        <div class="fw-semibold">{{ $account->is_active ? 'Aktif' : 'Nonaktif' }}</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="muted-label mb-1">Login Terakhir</div>
                        <div class="fw-semibold">{{ $account->last_login_at?->format('d M Y H:i') ?? '-' }}</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="muted-label mb-1">Password Diganti</div>
                        <div class="fw-semibold">{{ $account->password_changed_at?->format('d M Y H:i') ?? 'Belum pernah' }}</div>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
