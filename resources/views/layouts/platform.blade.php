<!doctype html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? config('app.name', 'Arsipkan') }}</title>
        <x-layouts.assets />
        @livewireStyles
    </head>
    <body>
        @php
            $layoutVariant = $layoutVariant ?? match (true) {
                request()->is('superadmin*') && ! request()->is('superadmin/login') => 'superadmin',
                request()->is('*/admin*') && ! request()->is('*/admin/login') => 'tenant-admin',
                request()->is('*/dashboard') || request()->is('*/password/*') => 'user',
                default => 'guest',
            };
            $withSidebar = $layoutVariant !== 'guest';
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
        @livewireScripts
        <x-layouts.scripts />
    </body>
</html>
