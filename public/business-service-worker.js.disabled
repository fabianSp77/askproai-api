// Service Worker for AskProAI Business Portal
// Version: 1.0.0

const CACHE_NAME = 'askproai-business-v1';
const STATIC_CACHE_NAME = 'askproai-business-static-v1';
const DYNAMIC_CACHE_NAME = 'askproai-business-dynamic-v1';
const API_CACHE_NAME = 'askproai-business-api-v1';

// Files to cache immediately
const STATIC_ASSETS = [
    '/business',
    '/business/login',
    '/offline.html',
    '/manifest.json',
    // // ''/build/assets/app.css'' // Removed - removed,
    // // ''/build/assets/app.js'' // Removed - removed,
    // Add more static assets as needed
];

// API routes to cache
const API_CACHE_ROUTES = [
    '/business/api/dashboard',
    '/business/api/calls',
    '/business/api/appointments',
    '/business/api/customers',
    '/business/api/team',
    '/business/api/settings',
];

// Cache strategies
const CACHE_STRATEGIES = {
    networkFirst: [
        '/business/api/',
        '/api/',
    ],
    cacheFirst: [
        '/build/',
        '/assets/',
        '/images/',
        '/fonts/',
    ],
    staleWhileRevalidate: [
        '/business/api/dashboard',
        '/business/api/appointments/filters',
        '/business/api/team/filters',
    ]
};

// Install event - cache static assets
self.addEventListener('install', (event) => {
    console.log('[Business SW] Installing...');
    
    event.waitUntil(
        caches.open(STATIC_CACHE_NAME)
            .then(cache => {
                console.log('[Business SW] Caching static assets');
                return cache.addAll(STATIC_ASSETS);
            })
            .catch(err => {
                console.error('[Business SW] Failed to cache static assets:', err);
            })
    );
    
    // Force the waiting service worker to become the active service worker
    self.skipWaiting();
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    console.log('[Business SW] Activating...');
    
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames
                    .filter(cacheName => {
                        return cacheName.startsWith('askproai-business-') && 
                               cacheName !== CACHE_NAME &&
                               cacheName !== STATIC_CACHE_NAME &&
                               cacheName !== DYNAMIC_CACHE_NAME &&
                               cacheName !== API_CACHE_NAME;
                    })
                    .map(cacheName => {
                        console.log('[Business SW] Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    })
            );
        })
    );
    
    // Claim all clients
    self.clients.claim();
});

// Fetch event - implement caching strategies
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }
    
    // Skip cross-origin requests
    if (url.origin !== location.origin) {
        return;
    }
    
    // Determine caching strategy
    if (isNetworkFirst(url.pathname)) {
        event.respondWith(networkFirst(request));
    } else if (isCacheFirst(url.pathname)) {
        event.respondWith(cacheFirst(request));
    } else if (isStaleWhileRevalidate(url.pathname)) {
        event.respondWith(staleWhileRevalidate(request));
    } else {
        // Default strategy: network first
        event.respondWith(networkFirst(request));
    }
});

// Cache strategies implementation
async function networkFirst(request) {
    try {
        const response = await fetch(request);
        
        // Cache successful responses
        if (response.ok) {
            const cache = await caches.open(DYNAMIC_CACHE_NAME);
            cache.put(request, response.clone());
        }
        
        return response;
    } catch (error) {
        // Try cache on network failure
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Return offline page for navigation requests
        if (request.mode === 'navigate') {
            return caches.match('/offline.html');
        }
        
        throw error;
    }
}

async function cacheFirst(request) {
    const cachedResponse = await caches.match(request);
    
    if (cachedResponse) {
        return cachedResponse;
    }
    
    try {
        const response = await fetch(request);
        
        if (response.ok) {
            const cache = await caches.open(STATIC_CACHE_NAME);
            cache.put(request, response.clone());
        }
        
        return response;
    } catch (error) {
        // Return offline page for navigation requests
        if (request.mode === 'navigate') {
            return caches.match('/offline.html');
        }
        
        throw error;
    }
}

async function staleWhileRevalidate(request) {
    const cache = await caches.open(API_CACHE_NAME);
    const cachedResponse = await cache.match(request);
    
    // Fetch fresh data in background
    const fetchPromise = fetch(request).then(response => {
        if (response.ok) {
            cache.put(request, response.clone());
        }
        return response;
    }).catch(error => {
        console.error('[Business SW] Fetch failed:', error);
        return cachedResponse;
    });
    
    // Return cached response immediately if available
    return cachedResponse || fetchPromise;
}

// Helper functions
function isNetworkFirst(pathname) {
    return CACHE_STRATEGIES.networkFirst.some(pattern => pathname.includes(pattern));
}

function isCacheFirst(pathname) {
    return CACHE_STRATEGIES.cacheFirst.some(pattern => pathname.includes(pattern));
}

function isStaleWhileRevalidate(pathname) {
    return CACHE_STRATEGIES.staleWhileRevalidate.some(pattern => pathname.includes(pattern));
}

// Background sync for failed requests
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-calls') {
        event.waitUntil(syncCalls());
    } else if (event.tag === 'sync-appointments') {
        event.waitUntil(syncAppointments());
    }
});

async function syncCalls() {
    // Implement sync logic for calls
    console.log('[Business SW] Syncing calls...');
}

async function syncAppointments() {
    // Implement sync logic for appointments
    console.log('[Business SW] Syncing appointments...');
}

// Push notifications
self.addEventListener('push', (event) => {
    if (!event.data) return;
    
    const data = event.data.json();
    const options = {
        body: data.body,
        icon: '/images/icon-192.png',
        badge: '/images/badge-72.png',
        vibrate: [200, 100, 200],
        data: {
            url: data.url || '/business',
        },
        actions: data.actions || []
    };
    
    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// Notification click
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    
    const url = event.notification.data.url || '/business';
    
    event.waitUntil(
        clients.matchAll({ type: 'window' }).then(clientList => {
            // Check if there's already a window/tab open
            for (const client of clientList) {
                if (client.url === url && 'focus' in client) {
                    return client.focus();
                }
            }
            
            // Open new window if not found
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});

// Message handling
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    
    if (event.data && event.data.type === 'CLEAR_CACHE') {
        caches.keys().then(cacheNames => {
            cacheNames.forEach(cacheName => {
                caches.delete(cacheName);
            });
        });
        
        event.ports[0].postMessage({ type: 'CACHE_CLEARED' });
    }
});