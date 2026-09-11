// Legacy public service worker retirement.
// The public site no longer uses offline tracking or cached API responses.
self.addEventListener('install', event => event.waitUntil(self.skipWaiting()));
self.addEventListener('activate', event => event.waitUntil((async () => {
  const keys = await caches.keys();
  await Promise.all(keys.map(key => caches.delete(key)));
  await self.clients.claim();
  await self.registration.unregister();
})()));
self.addEventListener('fetch', () => {});
