// Yuga Assistant — Service Worker v2
const CACHE = "yuga-v2";
const STATIC = ["./","./index.php","../widget/icon-192.svg","../widget/icon-512.svg"];

// Install: pre-cache static assets
self.addEventListener("install", e => {
  e.waitUntil(caches.open(CACHE).then(c => c.addAll(STATIC).catch(()=>{})));
  self.skipWaiting();
});

// Activate: clean old caches
self.addEventListener("activate", e => {
  e.waitUntil(caches.keys().then(keys =>
    Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))
  ));
  self.clients.claim();
});

// Fetch: network first, cache fallback, offline queue for API calls
self.addEventListener("fetch", e => {
  const url = new URL(e.request.url);

  // API calls: network only (no caching sensitive data), queue if offline
  if (url.pathname.includes("/api/")) {
    e.respondWith(
      fetch(e.request.clone()).catch(() =>
        new Response(JSON.stringify({ok:false,error:"offline",offline:true}),
          {headers:{"Content-Type":"application/json"}})
      )
    );
    return;
  }

  // Static assets: cache first
  if (e.request.method === "GET") {
    e.respondWith(
      caches.match(e.request).then(cached => {
        const network = fetch(e.request).then(res => {
          if (res.ok) caches.open(CACHE).then(c => c.put(e.request, res.clone()));
          return res;
        }).catch(() => cached || new Response("Offline",{status:503}));
        return cached || network;
      })
    );
  }
});

// Background sync for queued messages
self.addEventListener("sync", e => {
  if (e.tag === "yuga-sync") {
    e.waitUntil(
      self.clients.matchAll().then(clients =>
        clients.forEach(c => c.postMessage({type:"sync"}))
      )
    );
  }
});
