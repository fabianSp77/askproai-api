// AskProAI Service Worker
const CACHE_VERSION = 'v1.0.0';
const CACHE_NAME = `askproai-${CACHE_VERSION}`;
const urlsToCache = [
  '/',
  '/mobile/dashboard',
  '/mobile/appointments',
  '/mobile/customers',
  '/mobile/profile',
  '/css/app.css',
  '/js/app.js',
  '/manifest.json',
  '/offline.html'
];

// Install Service Worker
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Opened cache');
        return cache.addAll(urlsToCache);
      })
      .then(() => self.skipWaiting())
  );
});

// Activate Service Worker
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames
          .filter(cacheName => cacheName.startsWith('askproai-') && cacheName !== CACHE_NAME)
          .map(cacheName => caches.delete(cacheName))
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch Strategy: Network First, Cache Fallback
self.addEventListener('fetch', event => {
  // Skip non-GET requests
  if (event.request.method !== 'GET') return;

  // Skip cross-origin requests
  if (!event.request.url.startsWith(self.location.origin)) return;

  event.respondWith(
    fetch(event.request)
      .then(response => {
        // Clone the response
        const responseToCache = response.clone();

        // Cache successful responses
        if (response.status === 200) {
          caches.open(CACHE_NAME).then(cache => {
            cache.put(event.request, responseToCache);
          });
        }

        return response;
      })
      .catch(() => {
        // Network failed, try cache
        return caches.match(event.request).then(response => {
          if (response) {
            return response;
          }

          // If it's a navigation request, return offline page
          if (event.request.mode === 'navigate') {
            return caches.match('/offline.html');
          }

          // Return a generic offline response
          return new Response('Offline', {
            status: 503,
            statusText: 'Service Unavailable',
            headers: new Headers({
              'Content-Type': 'text/plain'
            })
          });
        });
      })
  );
});

// Background Sync for Offline Actions
self.addEventListener('sync', event => {
  if (event.tag === 'sync-appointments') {
    event.waitUntil(syncAppointments());
  }
});

async function syncAppointments() {
  try {
    const cache = await caches.open(CACHE_NAME);
    const requests = await cache.keys();
    
    // Filter for appointment-related requests
    const appointmentRequests = requests.filter(req => 
      req.url.includes('/api/appointments') && req.method === 'POST'
    );

    // Retry failed appointment creations
    for (const request of appointmentRequests) {
      try {
        const response = await fetch(request);
        if (response.ok) {
          await cache.delete(request);
        }
      } catch (error) {
        console.error('Failed to sync appointment:', error);
      }
    }
  } catch (error) {
    console.error('Sync failed:', error);
  }
}

// Push Notifications
self.addEventListener('push', event => {
  const options = {
    body: event.data ? event.data.text() : 'New notification from AskProAI',
    icon: '/images/icons/icon-192x192.png',
    badge: '/images/icons/badge-72x72.png',
    vibrate: [200, 100, 200],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1
    },
    actions: [
      {
        action: 'view',
        title: 'View',
        icon: '/images/icons/view.png'
      },
      {
        action: 'close',
        title: 'Close',
        icon: '/images/icons/close.png'
      }
    ]
  };

  event.waitUntil(
    self.registration.showNotification('AskProAI', options)
  );
});

// Notification Click Handler
self.addEventListener('notificationclick', event => {
  event.notification.close();

  if (event.action === 'view') {
    event.waitUntil(
      clients.openWindow('/mobile/appointments')
    );
  }
});

// Periodic Background Sync (if supported)
self.addEventListener('periodicsync', event => {
  if (event.tag === 'update-appointments') {
    event.waitUntil(updateAppointments());
  }
});

async function updateAppointments() {
  try {
    const response = await fetch('/api/appointments/upcoming');
    const data = await response.json();
    
    // Update cache with fresh data
    const cache = await caches.open(CACHE_NAME);
    await cache.put('/api/appointments/upcoming', new Response(JSON.stringify(data)));
  } catch (error) {
    console.error('Failed to update appointments:', error);
  }
}