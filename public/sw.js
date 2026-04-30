/*
|--------------------------------------------------------------------------
| Arsipkan Service Worker
|--------------------------------------------------------------------------
| Cache hanya untuk aset statis. Navigasi HTML selalu mencoba network dulu
| agar halaman dinamis, Livewire, dan request perubahan data tidak tersimpan.
|
| Saat deploy, ubah CACHE_VERSION agar cache lama dibersihkan.
|--------------------------------------------------------------------------
*/

const CACHE_VERSION = "arsipkan-pwa-v2026-04-30-01";
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const OFFLINE_FALLBACK_URL = "/offline.html";

const PRECACHE_URLS = [
  OFFLINE_FALLBACK_URL,
  "/manifest.json",
  "/android-chrome-192x192.png",
  "/android-chrome-512x512.png",
  "/apple-touch-icon.png",
  "/favicon-32x32.png",
  "/favicon-16x16.png",
  "/favicon.ico",
  "/favicon.svg"
];

const STATIC_PATH_PREFIXES = ["/assets/"];
const STATIC_FILE_PATTERN = /\.(?:css|js|mjs|png|jpg|jpeg|gif|webp|svg|ico|woff2?|ttf|eot)$/i;

self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(STATIC_CACHE).then((cache) => cache.addAll(PRECACHE_URLS)),
  );

  self.skipWaiting();
});

self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(
        keys
          .filter((key) => key !== STATIC_CACHE)
          .map((key) => caches.delete(key)),
      ),
    ),
  );

  self.clients.claim();
});

const isNavigationRequest = (request) => request.mode === "navigate";

const isCacheableStaticRequest = (requestUrl, request) => {
  if (request.method !== "GET") {
    return false;
  }

  if (requestUrl.origin !== self.location.origin) {
    return false;
  }

  if (
    requestUrl.pathname.startsWith("/livewire") ||
    requestUrl.pathname.startsWith("/api") ||
    requestUrl.pathname.startsWith("/broadcasting") ||
    requestUrl.pathname.startsWith("/logout") ||
    requestUrl.pathname.startsWith("/sanctum") ||
    requestUrl.pathname.startsWith("/superadmin") ||
    request.headers.has("X-Livewire")
  ) {
    return false;
  }

  return (
    STATIC_PATH_PREFIXES.some((prefix) => requestUrl.pathname.startsWith(prefix)) ||
    STATIC_FILE_PATTERN.test(requestUrl.pathname)
  );
};

self.addEventListener("fetch", (event) => {
  const request = event.request;
  const requestUrl = new URL(request.url);

  if (isNavigationRequest(request)) {
    event.respondWith(
      fetch(request).catch(async () => {
        const cache = await caches.open(STATIC_CACHE);

        return cache.match(OFFLINE_FALLBACK_URL);
      }),
    );

    return;
  }

  if (!isCacheableStaticRequest(requestUrl, request)) {
    return;
  }

  event.respondWith(
    caches.match(request).then(async (cachedResponse) => {
      if (cachedResponse) {
        return cachedResponse;
      }

      const networkResponse = await fetch(request);

      if (networkResponse.ok) {
        const cache = await caches.open(STATIC_CACHE);
        cache.put(request, networkResponse.clone());
      }

      return networkResponse;
    }),
  );
});
