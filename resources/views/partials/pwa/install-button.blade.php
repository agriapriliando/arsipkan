<button
    id="pwa-install-button"
    type="button"
    hidden
    aria-label="Install aplikasi Arsipkan"
    style="
        position: fixed;
        right: 16px;
        bottom: 16px;
        z-index: 1090;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        border: 1px solid rgba(148, 163, 184, 0.22);
        border-radius: 999px;
        padding: 12px 16px;
        background: linear-gradient(135deg, rgba(15, 23, 42, 0.96), rgba(30, 41, 59, 0.96));
        color: #e2e8f0;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.38);
        backdrop-filter: blur(14px);
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
    "
>
    <img
        src="{{ asset('android-chrome-192x192.png') }}"
        alt=""
        aria-hidden="true"
        style="width: 20px; height: 20px; object-fit: contain;"
    >
    <span>Install App</span>
</button>

<script>
    (() => {
        if (window.__arsipkanPwaInstallBound) {
            return;
        }

        window.__arsipkanPwaInstallBound = true;

        let deferredPrompt = null;
        const installButton = document.getElementById('pwa-install-button');

        if (!installButton) {
            return;
        }

        const isStandalone = () =>
            window.matchMedia('(display-mode: standalone)').matches ||
            window.navigator.standalone === true;

        const updateButtonVisibility = () => {
            installButton.hidden = !deferredPrompt || isStandalone();
        };

        window.addEventListener('beforeinstallprompt', (event) => {
            // Tahan prompt bawaan browser agar kita pakai tombol custom.
            event.preventDefault();
            deferredPrompt = event;
            updateButtonVisibility();
        });

        window.addEventListener('appinstalled', () => {
            deferredPrompt = null;
            updateButtonVisibility();
        });

        installButton.addEventListener('click', async () => {
            if (!deferredPrompt) {
                return;
            }

            deferredPrompt.prompt();
            await deferredPrompt.userChoice;
            deferredPrompt = null;
            updateButtonVisibility();
        });

        updateButtonVisibility();
    })();
</script>
