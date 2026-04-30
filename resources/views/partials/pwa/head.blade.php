@php
    $manifestVersion = file_exists(public_path('manifest.json')) ? filemtime(public_path('manifest.json')) : time();
    $iconVersion = file_exists(public_path('android-chrome-192x192.png'))
        ? filemtime(public_path('android-chrome-192x192.png'))
        : time();
@endphp

<link rel="manifest" href="{{ asset("manifest.json?v={$manifestVersion}") }}">
<meta name="application-name" content="Arsipkan">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Arsipkan">
<meta name="theme-color" content="#0f172a">
<meta name="background-color" content="#020617">
<meta name="msapplication-TileColor" content="#0f172a">

<link rel="apple-touch-icon" sizes="180x180" href="{{ asset("apple-touch-icon.png?v={$iconVersion}") }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset("favicon-32x32.png?v={$iconVersion}") }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset("favicon-16x16.png?v={$iconVersion}") }}">
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset("android-chrome-192x192.png?v={$iconVersion}") }}">
<link rel="icon" type="image/png" sizes="512x512" href="{{ asset("android-chrome-512x512.png?v={$iconVersion}") }}">
<link rel="shortcut icon" href="{{ asset("favicon.ico?v={$iconVersion}") }}">
