@props(['variant' => 'public'])

@php
    $user = match ($variant) {
        'superadmin' => auth('superadmin')->user(),
        'tenant-admin' => auth('tenant_admin')->user() ?? auth('superadmin')->user(),
        'user' => auth('user_account')->user(),
        default => null,
    };

    $role = match ($variant) {
        'superadmin' => 'Superadmin',
        'tenant-admin' => auth('superadmin')->check() && ! auth('tenant_admin')->check() ? 'Superadmin' : 'Admin Tenant',
        'user' => 'User Uploader',
        default => 'Publik',
    };

    $initials = $user?->name
        ? collect(explode(' ', $user->name))->take(2)->map(fn ($part) => substr($part, 0, 1))->implode('')
        : 'AR';
@endphp

<header class="top-header">
    <div class="d-flex align-items-center gap-3">
        <button type="button" class="mobile-toggle" id="mobileToggle" aria-label="Buka sidebar" aria-controls="sidebar" aria-expanded="false">
            <i data-lucide="menu"></i>
        </button>
        <div class="search-box">
            <i data-lucide="search"></i>
            <input type="text" class="form-control shadow-none" placeholder="Cari berkas atau uploader...">
        </div>
    </div>

    <div class="header-actions d-flex align-items-center justify-content-between justify-content-lg-end gap-3">
        @if(isset($currentTenant) && $currentTenant)
            <span class="tenant-chip">Tenant aktif: {{ $currentTenant->name }}</span>
        @endif

        <button type="button" class="btn p-2 text-secondary position-relative">
            <i data-lucide="bell"></i>
            <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
        </button>
        <div class="vr mx-2 d-none d-sm-block" style="height: 30px"></div>
        <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px">
                <span class="fw-bold text-primary small">{{ \Illuminate\Support\Str::upper($initials) }}</span>
            </div>
            <div class="d-none d-sm-block">
                <p class="mb-0 fw-bold small">{{ $user?->name ?? 'Arsipkan' }}</p>
                <p class="mb-0 text-muted" style="font-size: 0.7rem">{{ $role }}</p>
            </div>
        </div>
    </div>
</header>
