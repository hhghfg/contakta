// Service Worker для PWA
const CACHE_NAME = 'kontanta-admin-v1';
const urlsToCache = [
  '/',
  '/index.html',
  '/admin-pro.html',
  '/form-handler.js',
  '/cookies-manager.js',
  '/api/leads.php',
  '/api/admin-sessions.php',
  '/api/lead-delete.php'
];

// Установка Service Worker
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        return cache.addAll(urlsToCache).catch(() => {
          // Если не все файлы доступны, продолжаем
          return Promise.resolve();
        });
      })
      .then(() => self.skipWaiting())
  );
});

// Активация Service Worker
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Перехват запросов
self.addEventListener('fetch', event => {
  if (event.request.method !== 'GET') {
    return;
  }

  event.respondWith(
    caches.match(event.request)
      .then(response => {
        if (response) {
          return response;
        }

        return fetch(event.request).then(response => {
          if (!response || response.status !== 200 || response.type !== 'basic') {
            return response;
          }

          const responseToCache = response.clone();
          caches.open(CACHE_NAME)
            .then(cache => {
              cache.put(event.request, responseToCache);
            });

          return response;
        });
      })
      .catch(() => {
        // Возвращаем кэшированный ответ или offline страницу
        return caches.match('/admin-pro.html');
      })
  );
});

// Обработка push уведомлений
self.addEventListener('push', event => {
  const data = event.data ? event.data.json() : {
    title: 'КОНТАНТА',
    body: 'Новая заявка получена',
    icon: '/images/kontanta-logo.png'
  };

  const options = {
    body: data.body,
    icon: data.icon || '/images/kontanta-logo.png',
    badge: data.badge || '/images/kontanta-logo.png',
    tag: 'kontanta-notification',
    requireInteraction: true,
    vibrate: [200, 100, 200],
    data: data.data || {}
  };

  event.waitUntil(
    self.registration.showNotification(data.title || 'КОНТАНТА', options)
  );
});

// Обработка клика на уведомление
self.addEventListener('notificationclick', event => {
  event.notification.close();
  event.waitUntil(
    clients.matchAll({ type: 'window' }).then(clientList => {
      for (let client of clientList) {
        if (client.url === '/' && 'focus' in client) {
          return client.focus();
        }
      }
      if (clients.openWindow) {
        return clients.openWindow('/admin-pro.html');
      }
    })
  );
});
