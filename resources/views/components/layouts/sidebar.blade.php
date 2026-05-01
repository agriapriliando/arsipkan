@props(['variant' => 'public'])

@php
    $tenantSlug = request()->route('tenant_slug');
    $tenantParams = $tenantSlug ? ['tenant_slug' => $tenantSlug] : [];

    $menus = match ($variant) {
        'superadmin' => [
            ['label' => 'Dashboard', 'icon' => 'bi bi-grid-1x2-fill', 'route' => 'superadmin.dashboard'],
            ['label' => 'Tenant', 'icon' => 'bi bi-buildings', 'route' => 'superadmin.tenants.index'],
            ['label' => 'Admin Tenant', 'icon' => 'bi bi-shield-lock', 'route' => 'superadmin.admins.index'],
            ['label' => 'Master Data', 'icon' => 'bi bi-tags-fill', 'route' => 'superadmin.master-data.index'],
            ['label' => 'Link Upload', 'icon' => 'bi bi-link-45deg', 'route' => 'superadmin.upload-links.index'],
        ],
        'tenant-admin' => [
            ['label' => 'Dashboard', 'icon' => 'bi bi-grid-1x2-fill', 'route' => 'tenant.admin.dashboard', 'params' => $tenantParams],
            ['label' => 'Pending Review', 'icon' => 'bi bi-hourglass-split', 'route' => 'tenant.admin.files.pending', 'params' => $tenantParams],
            ['label' => 'Semua Berkas', 'icon' => 'bi bi-folder-check', 'route' => 'tenant.admin.files.index', 'params' => $tenantParams],
            ['label' => 'Berkas Terhapus', 'icon' => 'bi bi-trash3', 'route' => 'tenant.admin.files.deleted', 'params' => $tenantParams],
        ],
        'user' => [
            ['label' => 'Dashboard', 'icon' => 'bi bi-grid-1x2-fill', 'route' => 'tenant.user.dashboard', 'params' => $tenantParams],
            ['label' => 'Berkas Saya', 'icon' => 'bi bi-folder2-open', 'route' => 'tenant.user.files.mine', 'params' => $tenantParams],
            ['label' => 'Arsip Tenant', 'icon' => 'bi bi-archive-fill', 'route' => 'tenant.user.files.tenant', 'params' => $tenantParams],
            ['label' => 'Profil', 'icon' => 'bi bi-person-circle', 'route' => 'tenant.user.profile', 'params' => $tenantParams],
        ],
        default => [
            ['label' => 'Beranda', 'icon' => 'bi bi-house-door-fill', 'route' => 'home'],
        ],
    };
@endphp

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand d-flex align-items-center gap-2">
            <div class="logo-box">
                <i class="bi bi-cloud-arrow-up-fill"></i>
            </div>
            <h4 class="sidebar-label mb-0 fw-bold">ArsipKu</h4>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="sidebar-collapse-toggle d-none d-lg-inline-flex" id="sidebarCollapseToggle" title="Sempitkan sidebar" aria-label="Sempitkan sidebar" aria-expanded="true">
                <i data-sidebar-collapse-icon class="bi bi-layout-sidebar-inset"></i>
            </button>
            <button type="button" class="btn d-lg-none p-0 text-muted" id="closeSidebar" aria-label="Tutup sidebar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>

    <nav class="sidebar-nav">
        @foreach($menus as $item)
            @php
                $params = $item['params'] ?? [];
                $active = request()->routeIs($item['route']);
            @endphp
            <a href="{{ route($item['route'], $params) }}" class="nav-link {{ $active ? 'active' : '' }}" title="{{ $item['label'] }}">
                <i class="{{ $item['icon'] }}"></i>
                <span class="sidebar-label">{{ $item['label'] }}</span>
            </a>
        @endforeach

        @if($variant === 'tenant-admin')
            <div class="nav-section-title">Sistem</div>
            <a href="{{ route('tenant.admin.upload-links.index', $tenantParams) }}" class="nav-link {{ request()->routeIs('tenant.admin.upload-links.*') ? 'active' : '' }}" title="Link Upload">
                <i class="bi bi-link-45deg"></i>
                <span class="sidebar-label">Link Upload</span>
            </a>
            <a href="{{ route('tenant.admin.user-accounts.index', $tenantParams) }}" class="nav-link {{ request()->routeIs('tenant.admin.user-accounts.*') ? 'active' : '' }}" title="Akun Uploader">
                <i class="bi bi-person-gear"></i>
                <span class="sidebar-label">Akun Uploader</span>
            </a>
            <a href="{{ route('tenant.admin.master-data.categories', $tenantParams) }}" class="nav-link {{ request()->routeIs('tenant.admin.master-data.categories') ? 'active' : '' }}" title="CRUD Kategori">
                <i class="bi bi-folder2-open"></i>
                <span class="sidebar-label">CRUD Kategori</span>
            </a>
            <a href="{{ route('tenant.admin.master-data.tags', $tenantParams) }}" class="nav-link {{ request()->routeIs('tenant.admin.master-data.tags') ? 'active' : '' }}" title="CRUD Tag">
                <i class="bi bi-tags"></i>
                <span class="sidebar-label">CRUD Tag</span>
            </a>
            <a href="{{ route('tenant.admin.settings', $tenantParams) }}" class="nav-link {{ request()->routeIs('tenant.admin.settings') ? 'active' : '' }}" title="Pengaturan">
                <i class="bi bi-gear"></i>
                <span class="sidebar-label">Pengaturan</span>
            </a>
        @endif

        @auth('superadmin')
            @if($variant === 'superadmin')
                <form method="POST" action="{{ route('superadmin.logout') }}" class="mt-4">
                    @csrf
                    <button type="submit" class="nav-link text-danger border-0 bg-transparent w-100" title="Keluar">
                        <i class="bi bi-box-arrow-right"></i>
                        <span class="sidebar-label">Keluar</span>
                    </button>
                </form>
            @endif
        @endauth

        @auth('tenant_admin')
            @if($variant === 'tenant-admin')
                <form method="POST" action="{{ route('tenant.admin.logout', $tenantParams) }}" class="mt-4">
                    @csrf
                    <button type="submit" class="nav-link text-danger border-0 bg-transparent w-100" title="Keluar">
                        <i class="bi bi-box-arrow-right"></i>
                        <span class="sidebar-label">Keluar</span>
                    </button>
                </form>
            @endif
        @endauth

        @auth('user_account')
            @if($variant === 'user')
                <form method="POST" action="{{ route('tenant.logout', $tenantParams) }}" class="mt-4">
                    @csrf
                    <button type="submit" class="nav-link text-danger border-0 bg-transparent w-100" title="Keluar">
                        <i class="bi bi-box-arrow-right"></i>
                        <span class="sidebar-label">Keluar</span>
                    </button>
                </form>
            @endif
        @endauth
    </nav>
</aside>
