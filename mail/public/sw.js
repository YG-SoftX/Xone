// YG Mail Service Worker - PWA Offline Support & Background Sync
const CACHE_NAME = 'yg-mail-v1';
const OFFLINE_PAGE = '/offline.html';

// Assets to cache on install
const STATIC_ASSETS = [
  '/',
  '/css/app.css',
  '/js/app.js',
  '/manifest.json',
  '/icons/icon-192x192.png',
  '/icons/icon-512x512.png',
  '/offline.html',
];

// API endpoints to cache with network-first strategy
const API_ENDPOINTS = [
  '/api/mailbox/inbox',
  '/api/mailbox/sent',
  '/api/mailbox/drafts',
];

// Install event - cache static assets
self.addEventListener('install', (event) => {
  console.log('[Service Worker] Installing...');
  
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('[Service Worker] Caching static assets');
        return cache.addAll(STATIC_ASSETS);
      })
      .then(() => {
        console.log('[Service Worker] Installation complete');
        return self.skipWaiting();
      })
  );
});

// Activate event - clean old caches
self.addEventListener('activate', (event) => {
  console.log('[Service Worker] Activating...');
  
  event.waitUntil(
    caches.keys()
      .then((cacheNames) => {
        return Promise.all(
          cacheNames
            .filter((name) => name !== CACHE_NAME)
            .map((name) => {
              console.log('[Service Worker] Deleting old cache:', name);
              return caches.delete(name);
            })
        );
      })
      .then(() => {
        console.log('[Service Worker] Activation complete');
        return self.clients.claim();
      })
  );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Skip non-GET requests
  if (request.method !== 'GET') {
    return;
  }

  // Skip Chrome extension requests
  if (url.protocol === 'chrome-extension:') {
    return;
  }

  // API requests - Network first, fallback to cache
  if (url.pathname.startsWith('/api/')) {
    event.respondWith(networkFirstStrategy(request));
    return;
  }

  // Static assets - Cache first, fallback to network
  if (isStaticAsset(url.pathname)) {
    event.respondWith(cacheFirstStrategy(request));
    return;
  }

  // HTML pages - Stale-while-revalidate
  if (request.headers.get('accept').includes('text/html')) {
    event.respondWith(staleWhileRevalidateStrategy(request));
    return;
  }

  // Default - Network first
  event.respondWith(networkFirstStrategy(request));
});

// Push notification handler
self.addEventListener('push', (event) => {
  console.log('[Service Worker] Push received');

  if (!event.data) {
    return;
  }

  const data = event.data.json();
  
  const options = {
    body: data.body || 'You have a new email',
    icon: data.icon || '/icons/icon-192x192.png',
    badge: '/icons/badge-72x72.png',
    vibrate: [200, 100, 200],
    tag: data.tag || 'email-notification',
    requireInteraction: true,
    actions: [
      {
        action: 'view',
        title: 'View Email',
        icon: '/icons/view-icon.png'
      },
      {
        action: 'mark-read',
        title: 'Mark as Read',
        icon: '/icons/read-icon.png'
      },
      {
        action: 'archive',
        title: 'Archive',
        icon: '/icons/archive-icon.png'
      }
    ],
    data: {
      url: data.url || '/inbox',
      messageId: data.messageId,
      mailboxId: data.mailboxId
    }
  };

  event.waitUntil(
    self.registration.showNotification(data.title || 'YG Mail', options)
  );
});

// Notification click handler
self.addEventListener('notificationclick', (event) => {
  console.log('[Service Worker] Notification clicked');

  event.notification.close();

  const action = event.action;
  const data = event.notification.data;

  if (action === 'mark-read') {
    // Mark email as read via API
    event.waitUntil(markEmailAsRead(data.messageId));
  } else if (action === 'archive') {
    // Archive email via API
    event.waitUntil(archiveEmail(data.messageId));
  } else {
    // Open the app
    event.waitUntil(
      clients.matchAll({ type: 'window' })
        .then((clientList) => {
          // Check if window is already open
          for (const client of clientList) {
            if (client.url.includes(data.url) && 'focus' in client) {
              return client.focus();
            }
          }
          
          // Open new window
          if (clients.openWindow) {
            return clients.openWindow(data.url);
          }
        })
    );
  }
});

// Background sync for offline email sending
self.addEventListener('sync', (event) => {
  console.log('[Service Worker] Background sync triggered:', event.tag);

  if (event.tag === 'send-email') {
    event.waitUntil(processOutboxQueue());
  } else if (event.tag === 'sync-mailbox') {
    event.waitUntil(syncMailboxUpdates());
  }
});

// Periodic background sync (if supported)
self.addEventListener('periodicsync', (event) => {
  console.log('[Service Worker] Periodic sync:', event.tag);

  if (event.tag === 'check-new-emails') {
    event.waitUntil(checkForNewEmails());
  }
});

// Helper functions

/**
 * Network-first strategy for API calls
 */
async function networkFirstStrategy(request) {
  try {
    const networkResponse = await fetch(request);
    
    // Cache successful responses
    if (networkResponse.ok) {
      const cache = await caches.open(CACHE_NAME);
      cache.put(request, networkResponse.clone());
    }
    
    return networkResponse;
  } catch (error) {
    console.log('[Service Worker] Network failed, trying cache:', error);
    
    // Try cache
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }
    
    // Return offline page for navigation requests
    if (request.headers.get('accept').includes('text/html')) {
      return caches.match(OFFLINE_PAGE);
    }
    
    throw error;
  }
}

/**
 * Cache-first strategy for static assets
 */
async function cacheFirstStrategy(request) {
  const cachedResponse = await caches.match(request);
  
  if (cachedResponse) {
    // Update cache in background
    fetch(request).then((response) => {
      if (response.ok) {
        caches.open(CACHE_NAME).then((cache) => {
          cache.put(request, response);
        });
      }
    });
    
    return cachedResponse;
  }
  
  // Fallback to network
  return fetch(request);
}

/**
 * Stale-while-revalidate for HTML pages
 */
async function staleWhileRevalidateStrategy(request) {
  const cache = await caches.open(CACHE_NAME);
  const cachedResponse = await cache.match(request);
  
  const fetchPromise = fetch(request).then((response) => {
    if (response.ok) {
      cache.put(request, response.clone());
    }
    return response;
  });
  
  return cachedResponse || fetchPromise;
}

/**
 * Check if URL is a static asset
 */
function isStaticAsset(pathname) {
  const extensions = ['.js', '.css', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.woff', '.woff2'];
  return extensions.some(ext => pathname.endsWith(ext));
}

/**
 * Mark email as read
 */
async function markEmailAsRead(messageId) {
  try {
    await fetch('/api/email/mark-read', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ messageId }),
    });
  } catch (error) {
    console.error('[Service Worker] Failed to mark email as read:', error);
  }
}

/**
 * Archive email
 */
async function archiveEmail(messageId) {
  try {
    await fetch('/api/email/archive', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ messageId }),
    });
  } catch (error) {
    console.error('[Service Worker] Failed to archive email:', error);
  }
}

/**
 * Process outbox queue for offline emails
 */
async function processOutboxQueue() {
  try {
    const outbox = await getOutboxQueue();
    
    for (const email of outbox) {
      try {
        const response = await fetch('/api/email/send', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(email),
        });
        
        if (response.ok) {
          await removeFromOutbox(email.id);
          showSuccessNotification('Email sent successfully!');
        }
      } catch (error) {
        console.error('[Service Worker] Failed to send email:', error);
      }
    }
  } catch (error) {
    console.error('[Service Worker] Outbox processing failed:', error);
  }
}

/**
 * Sync mailbox updates
 */
async function syncMailboxUpdates() {
  try {
    const response = await fetch('/api/mailbox/sync');
    
    if (response.ok) {
      const data = await response.json();
      
      // Show notification for new emails
      if (data.newEmailCount > 0) {
        self.registration.showNotification('YG Mail', {
          body: `You have ${data.newEmailCount} new email(s)`,
          icon: '/icons/icon-192x192.png',
          badge: '/icons/badge-72x72.png',
        });
      }
    }
  } catch (error) {
    console.error('[Service Worker] Mailbox sync failed:', error);
  }
}

/**
 * Check for new emails
 */
async function checkForNewEmails() {
  try {
    const response = await fetch('/api/mailbox/check-new');
    
    if (response.ok) {
      const data = await response.json();
      
      if (data.hasNewEmails) {
        self.registration.showNotification('YG Mail', {
          body: `${data.count} new email(s) received`,
          icon: '/icons/icon-192x192.png',
        });
      }
    }
  } catch (error) {
    console.error('[Service Worker] New email check failed:', error);
  }
}

/**
 * Get outbox queue from IndexedDB
 */
async function getOutboxQueue() {
  // Implementation depends on IndexedDB structure
  return [];
}

/**
 * Remove email from outbox
 */
async function removeFromOutbox(id) {
  // Implementation depends on IndexedDB structure
}

/**
 * Show success notification
 */
async function showSuccessNotification(message) {
  self.registration.showNotification('YG Mail', {
    body: message,
    icon: '/icons/icon-192x192.png',
  });
}
