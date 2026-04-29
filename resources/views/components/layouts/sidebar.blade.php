@props(['variant' => 'public'])

@php
    $tenantSlug = request()->route('tenant_slug');
    $tenantParams = $tenantSlug ? ['tenant_slug' => $tenantSlug] : [];

    $menus = match ($variant) {
        'superadmin' => [
            ['label' => 'Dashboard', 'icon' => 'layout-dashboard', 'route' => 'superadmin.dashboard'],
            ['label' => 'Tenant', 'icon' => 'building-2', 'route' => 'superadmin.tenants.index'],
        ],
        'tenant-admin' => [
            ['label' => 'Dashboard', 'icon' => 'layout-dashboard', 'route' => 'tenant.admin.dashboard', 'params' => $tenantParams],
            ['label' => 'Pending Review', 'icon' => 'file-clock', 'route' => 'tenant.admin.dashboard', 'params' => $tenantParams],
            ['label' => 'Semua Berkas', 'icon' => 'file-check', 'route' => 'tenant.admin.dashboard', 'params' => $tenantParams],
            ['label' => 'Uploader', 'icon' => 'users', 'route' => 'tenant.admin.dashboard', 'params' => $tenantParams],
        ],
        'user' => [
            ['label' => 'Dashboard', 'icon' => 'layout-dashboard', 'route' => 'tenant.user.dashboard', 'params' => $tenantParams],
            ['label' => 'Berkas Saya', 'icon' => 'folder', 'route' => 'tenant.user.dashboard', 'params' => $tenantParams],
            ['label' => 'Arsip Tenant', 'icon' => 'archive', 'route' => 'tenant.user.dashboard', 'params' => $tenantParams],
        ],
        default => [
            ['label' => 'Beranda', 'icon' => 'home', 'route' => 'home'],
        ],
    };
@endphp

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand d-flex align-items-center gap-2">
            <div class="logo-box">
                <i data-lucide="file-up"></i>
            </div>
            <h4 class="sidebar-label mb-0 fw-bold">ArsipKu</h4>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="sidebar-collapse-toggle d-none d-lg-inline-flex" id="sidebarCollapseToggle" title="Sempitkan sidebar" aria-label="Sempitkan sidebar" aria-expanded="true">
                <i data-sidebar-collapse-icon data-lucide="panel-left-close"></i>
            </button>
            <button type="button" class="btn d-lg-none p-0 text-muted" id="closeSidebar" aria-label="Tutup sidebar">
                <i data-lucide="menu"></i>
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
                <i data-lucide="{{ $item['icon'] }}"></i>
                <span class="sidebar-label">{{ $item['label'] }}</span>
            </a>
        @endforeach

        @if($variant === 'tenant-admin')
            <div class="nav-section-title">Sistem</div>
            <a href="{{ route('tenant.admin.dashboard', $tenantParams) }}" class="nav-link" title="Link Upload">
                <i data-lucide="link"></i>
                <span class="sidebar-label">Link Upload</span>
            </a>
            <a href="{{ route('tenant.admin.dashboard', $tenantParams) }}" class="nav-link" title="Pengaturan">
                <i data-lucide="settings"></i>
                <span class="sidebar-label">Pengaturan</span>
            </a>
        @endif

        @auth('superadmin')
            @if($variant === 'superadmin')
                <form method="POST" action="{{ route('superadmin.logout') }}" class="mt-4">
                    @csrf
                    <button type="submit" class="nav-link text-danger border-0 bg-transparent w-100" title="Keluar">
                        <i data-lucide="log-out"></i>
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
                        <i data-lucide="log-out"></i>
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
                        <i data-lucide="log-out"></i>
                        <span class="sidebar-label">Keluar</span>
                    </button>
                </form>
            @endif
        @endauth
    </nav>
</aside>
