const CACHE_NAME = 'ygxone-v1';
const ASSETS = [
    '/',
    '/dashboard',
    '/css/app.css',
    '/js/app.js'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(ASSETS);
        })
    );
});

self.addEventListener('fetch', (event) => {
    event.respondWith(
        caches.match(event.request).then((response) => {
            return response || fetch(event.request);
        })
    );
});

// Handle Push Notifications
self.addEventListener('push', (event) => {
    const data = event.data ? event.data.json() : { title: 'New Empire Update', body: 'Check your YGXONE notifications.' };
    
    event.waitUntil(
        self.registration.showNotification(data.title, {
            body: data.body,
            icon: 'https://pay.ygxone.com/assets/images/logo-icon.png',
            badge: 'https://pay.ygxone.com/assets/images/logo-icon.png',
            data: { url: data.action_url || '/' }
        })
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    event.waitUntil(
        clients.openWindow(event.notification.data.url)
    );
});
