// AskProAI Command Intelligence - Service Worker v1.0
const CACHE_NAME = 'askproai-cmd-v1';
const urlsToCache = [
  '/claude-command-intelligence.html',
  '/manifest.json'
];

// Install Service Worker
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Opened cache');
        return cache.addAll(urlsToCache);
      })
  );
  self.skipWaiting();
});

// Cache and return requests
self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        if (response) {
          return response;
        }
        return fetch(event.request);
      }
    )
  );
});

// Update service worker
self.addEventListener('activate', event => {
  const cacheWhitelist = [CACHE_NAME];
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheWhitelist.indexOf(cacheName) === -1) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// Background sync
self.addEventListener('sync', event => {
  if (event.tag === 'sync-commands') {
    event.waitUntil(syncPendingCommands());
  }
});

// Push notifications
self.addEventListener('push', event => {
  const options = {
    body: event.data ? event.data.text() : 'Neue Benachrichtigung',
    icon: 'data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 512 512%27%3E%3Crect width=%27512%27 height=%27512%27 fill=%27%233B82F6%27/%3E%3Ctext x=%27256%27 y=%27320%27 font-family=%27Arial%27 font-size=%27256%27 fill=%27white%27 text-anchor=%27middle%27%3E🚀%3C/text%3E%3C/svg%3E',
    badge: 'data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 72 72%27%3E%3Ccircle cx=%2736%27 cy=%2736%27 r=%2736%27 fill=%27%233B82F6%27/%3E%3C/svg%3E',
    vibrate: [100, 50, 100],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1
    }
  };

  event.waitUntil(
    self.registration.showNotification('AskProAI Command Intelligence', options)
  );
});

// Notification click
self.addEventListener('notificationclick', event => {
  event.notification.close();
  event.waitUntil(
    clients.openWindow('/claude-command-intelligence.html')
  );
});

// Helper function for syncing
async function syncPendingCommands() {
  // This would sync pending commands with the server
  console.log('Syncing pending commands...');
}