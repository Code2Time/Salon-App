const PREFIX = 'salon-pwa-';
const CACHE = PREFIX + '82ffc50df8562c6d';
const OFFLINE = '/pwa/offline.html';

self.addEventListener('install', event => {
    event.waitUntil(caches.open(CACHE).then(cache => cache.add(OFFLINE)));
    // New versions wait until the customer chooses to update or closes old tabs.
});
self.addEventListener('activate', event => {
    event.waitUntil(Promise.all([
        caches.keys().then(keys => Promise.all(keys.filter(key => key.startsWith(PREFIX) && key !== CACHE).map(key => caches.delete(key)))),
        self.clients.claim(),
    ]));
});
self.addEventListener('message', event => {
    if (event.data?.type === 'SKIP_WAITING') self.skipWaiting();
});
self.addEventListener('fetch', event => {
    const request = event.request;
    const url = new URL(request.url);
    if (request.method !== 'GET' || url.origin !== self.location.origin) return;
    // Never intercept API, auth requests, uploads, or any panel navigation.
    if (url.pathname.startsWith('/api/') || url.pathname.startsWith('/sanctum/')) return;
    if (request.mode === 'navigate' && (url.pathname === '/app' || url.pathname.startsWith('/app/') || ['/login', '/register'].includes(url.pathname))) {
        event.respondWith(fetch(request).catch(async () => (await (await caches.open(CACHE)).match(OFFLINE)) || Response.error()));
        return;
    }
    if (url.pathname.startsWith('/build/assets/') && /-[\w-]+\.(js|css|woff2?|png|svg|webp)$/.test(url.pathname)) {
        event.respondWith((async () => {
            const cache = await caches.open(CACHE);
            const cached = await cache.match(request);
            if (cached) return cached;
            const response = await fetch(request);
            if (response.ok && response.type === 'basic') {
                try {
                    await cache.put(request, response.clone());
                    const keys = await cache.keys();
                    if (keys.length > 80) {
                        const removable = keys.filter(key => new URL(key.url).pathname !== OFFLINE);
                        await Promise.all(removable.slice(0, keys.length - 80).map(key => cache.delete(key)));
                    }
                } catch { /* Cache quota failures must not break loading the application. */ }
            }
            return response;
        })());
    }
});
