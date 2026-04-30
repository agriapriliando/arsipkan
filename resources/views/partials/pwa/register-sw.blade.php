@php
    $swVersion = file_exists(public_path('sw.js')) ? filemtime(public_path('sw.js')) : time();
@endphp

<script>
    (() => {
        if (!('serviceWorker' in navigator)) {
            return;
        }

        window.addEventListener('load', async () => {
            try {
                // Query string dipakai untuk memicu update service worker saat file berubah.
                const registration = await navigator.serviceWorker.register(
                    '{{ asset("sw.js?v={$swVersion}") }}',
                    { scope: '/' }
                );

                // Minta browser cek update lagi saat tab aktif kembali.
                document.addEventListener('visibilitychange', () => {
                    if (document.visibilityState === 'visible') {
                        registration.update().catch(() => {});
                    }
                });
            } catch (error) {
                console.error('Service worker registration failed:', error);
            }
        });
    })();
</script>
