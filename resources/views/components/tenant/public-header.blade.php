@props([
    'tenant',
    'subtitle' => 'Katalog berkas publik',
    'active' => 'catalog',
    'showBack' => false,
    'backUrl' => null,
    'backLabel' => 'Kembali',
    'navClass' => '',
    'logoClass' => '',
])

<style>
    .public-header-mobile-toggle {
        display: none;
        width: 44px;
        height: 44px;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        border: 1px solid #dbe4ee;
        background: #fff;
        color: #0f172a;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
    }

    .public-header-nav-group {
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
        justify-content: flex-end;
        gap: 0.75rem;
    }

    .public-header-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 42px;
        padding: 0.7rem 1rem;
        border-radius: 999px;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 700;
        line-height: 1;
        transition: all 0.2s ease;
        border: 1px solid transparent;
        color: #334155;
        background: transparent;
    }

    .public-header-link:hover {
        color: #4c1d95;
        background: #f8fafc;
        border-color: #e2e8f0;
        transform: translateY(-1px);
    }

    .public-header-link.is-active {
        color: #5b21b6;
        background: linear-gradient(180deg, #ffffff 0%, #f5f3ff 100%);
        border-color: #d8b4fe;
        box-shadow: 0 8px 18px rgba(109, 40, 217, 0.12);
    }

    .public-header-link.is-primary {
        color: #fff;
        background: linear-gradient(135deg, #6d28d9 0%, #5b21b6 100%);
        border-color: #6d28d9;
        box-shadow: 0 10px 20px rgba(109, 40, 217, 0.2);
    }

    .public-header-link.is-primary:hover {
        color: #fff;
        background: linear-gradient(135deg, #5b21b6 0%, #4c1d95 100%);
        border-color: #5b21b6;
    }

    .public-header-link.is-secondary {
        color: #0f172a;
        background: #fff;
        border-color: #dbe4ee;
    }

    .public-header-link.is-secondary:hover {
        color: #4c1d95;
        background: #fff;
        border-color: #cbd5e1;
    }

    .public-header-mobile-only {
        display: none;
    }

    @media (max-width: 767.98px) {
        .public-header-mobile-toggle {
            display: inline-flex;
        }

        .public-header-nav-group {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: min(82vw, 320px);
            z-index: 1045;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            justify-content: flex-start;
            gap: 0.85rem;
            padding: 5.5rem 1rem 1rem;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border-right: 1px solid #e2e8f0;
            box-shadow: 20px 0 40px rgba(15, 23, 42, 0.16);
            transform: translateX(-100%);
            transition: transform 0.25s ease;
        }

        .public-header-nav-group.is-open {
            transform: translateX(0);
        }

        .public-header-link {
            width: 100%;
            min-height: 46px;
            padding-inline: 0.9rem;
            font-size: 0.85rem;
            justify-content: flex-start;
        }

        .public-header-link.is-back-mobile-full {
            margin-top: auto;
        }

        .public-header-mobile-only {
            display: flex;
        }

        .public-header-backdrop {
            position: fixed;
            inset: 0;
            z-index: 1040;
            background: rgba(15, 23, 42, 0.42);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s ease;
        }

        .public-header-backdrop.is-open {
            opacity: 1;
            pointer-events: auto;
        }
    }
</style>

@php($drawerId = 'public-nav-'.md5($tenant->slug.'-'.$subtitle.'-'.$active))

<nav class="{{ trim($navClass) }} sticky-top">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center justify-content-between gap-3 flex-grow-1">
                <a class="navbar-brand d-flex align-items-center gap-2 mb-0" href="{{ route('tenant.home', ['tenant_slug' => $tenant->slug]) }}">
                    <div class="{{ trim($logoClass) }}">
                        <i data-lucide="file-up" style="width: 18px;"></i>
                    </div>
                    <div>
                        <div class="fw-bold">{{ $tenant->name }}</div>
                        <div class="small text-secondary">{{ $subtitle }}</div>
                    </div>
                </a>

                <button
                    type="button"
                    class="public-header-mobile-toggle"
                    data-public-nav-toggle="{{ $drawerId }}"
                    aria-controls="{{ $drawerId }}"
                    aria-expanded="false"
                    aria-label="Buka menu navigasi"
                >
                    <i data-lucide="menu" style="width: 20px;"></i>
                </button>
            </div>

            <div class="public-header-backdrop" data-public-nav-backdrop="{{ $drawerId }}"></div>

            <div class="public-header-nav-group" id="{{ $drawerId }}" data-public-nav-drawer>
                <div class="public-header-mobile-only align-items-center justify-content-between mb-2">
                    <div class="fw-bold text-dark">Menu</div>
                    <button
                        type="button"
                        class="public-header-mobile-toggle"
                        data-public-nav-close="{{ $drawerId }}"
                        aria-label="Tutup menu navigasi"
                    >
                        <i data-lucide="x" style="width: 20px;"></i>
                    </button>
                </div>
                <a
                    href="{{ route('tenant.home', ['tenant_slug' => $tenant->slug]) }}"
                    class="public-header-link {{ $active === 'catalog' ? 'is-active' : '' }}"
                >
                    Katalog
                </a>
                <a
                    href="{{ route('tenant.leaderboard', ['tenant_slug' => $tenant->slug]) }}"
                    class="public-header-link {{ $active === 'leaderboard' ? 'is-primary' : '' }}"
                >
                    Leaderboard
                </a>

                @if($showBack && $backUrl)
                    <a href="{{ $backUrl }}" class="public-header-link is-secondary is-back-mobile-full">{{ $backLabel }}</a>
                @endif
            </div>
        </div>
    </div>
</nav>

<script>
    (() => {
        const drawerId = @json($drawerId);
        const drawer = document.getElementById(drawerId);
        const openButton = document.querySelector(`[data-public-nav-toggle="${drawerId}"]`);
        const closeButton = document.querySelector(`[data-public-nav-close="${drawerId}"]`);
        const backdrop = document.querySelector(`[data-public-nav-backdrop="${drawerId}"]`);

        if (!drawer || !openButton || !backdrop) {
            return;
        }

        const setOpen = (isOpen) => {
            drawer.classList.toggle('is-open', isOpen);
            backdrop.classList.toggle('is-open', isOpen);
            openButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            document.body.style.overflow = isOpen ? 'hidden' : '';
        };

        openButton.addEventListener('click', () => setOpen(true));
        closeButton?.addEventListener('click', () => setOpen(false));
        backdrop.addEventListener('click', () => setOpen(false));

        drawer.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => setOpen(false));
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) {
                setOpen(false);
            }
        });
    })();
</script>
