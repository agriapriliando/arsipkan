<!doctype html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? config('app.name', 'Arsipkan') }}</title>
        <meta name="description" content="{{ $metaDescription ?? 'Arsipkan adalah aplikasi arsip digital multi-tenant untuk pengelolaan dokumen, katalog publik, validasi file, dan kontribusi uploader per organisasi.' }}">
        <meta name="keywords" content="{{ $metaKeywords ?? 'aplikasi arsip digital, manajemen dokumen, katalog publik, arsip instansi, arsip tenant, unggah dokumen, leaderboard uploader' }}">
        <meta name="robots" content="{{ $metaRobots ?? 'index,follow' }}">
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ $metaOgTitle ?? ($title ?? config('app.name', 'Arsipkan')) }}">
        <meta property="og:description" content="{{ $metaOgDescription ?? ($metaDescription ?? 'Arsipkan membantu organisasi mengelola arsip digital secara terstruktur, aman, dan mudah diakses.') }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:site_name" content="{{ config('app.name', 'Arsipkan') }}">
        @if (! empty($metaOgImage ?? null))
            <meta property="og:image" content="{{ $metaOgImage }}">
            <meta name="twitter:card" content="summary">
            <meta name="twitter:image" content="{{ $metaOgImage }}">
        @endif
        @include('partials.pwa.head')
        <x-layouts.assets />
        @livewireStyles
    </head>
    <body>
        @php
            $layoutVariant = $layoutVariant ?? match (true) {
                request()->is('superadmin*') && ! request()->is('superadmin/login') => 'superadmin',
                request()->is('*/admin*') && ! request()->is('*/admin/login') => 'tenant-admin',
                request()->is('*/dashboard')
                    || request()->is('*/password/*')
                    || request()->is('*/my-files*')
                    || request()->is('*/tenant-files*')
                    || request()->is('*/files/*')
                    || request()->is('*/profile') => 'user',
                default => 'guest',
            };
            $withSidebar = $layoutVariant !== 'guest';
            $disableAppScripts = $disableAppScripts ?? request()->is('*/admin/master-data*');
        @endphp

        <div class="app-shell">
            @if($withSidebar)
                <x-layouts.sidebar :variant="$layoutVariant" />
            @endif

            <main class="main-content {{ $withSidebar ? '' : 'no-sidebar' }}">
                @if($withSidebar)
                    <x-layouts.header :variant="$layoutVariant" />
                @endif

                @yield('content')
            </main>
        </div>

        @include('partials.pwa.install-button')
        @livewireScripts
        @include('partials.pwa.register-sw')
        @unless($disableAppScripts)
            <x-layouts.scripts />
        @endunless
    </body>
</html>
