// YGXONE Browser — Service Worker
// Cache strategy: Cache-first for static assets, Network-first for dynamic pages

const CACHE_NAME = 'ygxone-browser-v1';
const STATIC_ASSETS = [
    '/css/app.css',
    '/js/app.js',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
];

// ── Install: Pre-cache static assets ──
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS).catch(() => {
                // Some assets may not exist yet — that's fine
            });
        }).then(() => self.skipWaiting())
    );
});

// ── Activate: Clean old caches ──
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((names) => {
            return Promise.all(
                names.filter((name) => name !== CACHE_NAME)
                    .map((name) => caches.delete(name))
            );
        }).then(() => self.clients.claim())
    );
});

// ── Fetch: Cache-first for static, Network-first for dynamic ──
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // Skip non-GET and non-http(s)
    if (event.request.method !== 'GET' || !url.protocol.startsWith('http')) {
        return;
    }

    // Skip API calls, agent runs, browse proxy — always network
    if (
        url.pathname.startsWith('/api/') ||
        url.pathname.startsWith('/agent/') ||
        url.pathname.startsWith('/browse') ||
        url.pathname.startsWith('/search')
    ) {
        return; // Let browser handle normally
    }

    // Static assets: Cache-first
    if (
        url.pathname.match(/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff2?|ttf|eot)$/i) ||
        url.pathname.startsWith('/icons/')
    ) {
        event.respondWith(
            caches.match(event.request).then((cached) => {
                const fetched = fetch(event.request).then((response) => {
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(event.request, clone);
                        });
                    }
                    return response;
                });
                return cached || fetched;
            })
        );
        return;
    }

    // HTML pages: Network-first, fallback to cache
    event.respondWith(
        fetch(event.request).then((response) => {
            if (response.ok) {
                const clone = response.clone();
                caches.open(CACHE_NAME).then((cache) => {
                    cache.put(event.request, clone);
                });
            }
            return response;
        }).catch(() => {
            return caches.match(event.request).then((cached) => {
                return cached || caches.match('/browser');
            });
        })
    );
});

// ── Message handler: Skip waiting on user request ──
self.addEventListener('message', (event) => {
    if (event.data === 'skipWaiting') {
        self.skipWaiting();
    }
});
