// Service Worker for AskProAI Portal
const CACHE_NAME = 'askproai-portal-v1';
const STATIC_CACHE_NAME = 'askproai-static-v1';
const DYNAMIC_CACHE_NAME = 'askproai-dynamic-v1';

// Files to cache on install
const STATIC_FILES = [
    '/fonts/Inter-var.woff2',
    '/images/logo.svg',
    '/images/logo-dark.svg',
    '/offline.html'
];

// Cache strategies
const CACHE_STRATEGIES = {
    // Network first, fallback to cache
    networkFirst: [
        '/api/',
        '/portal/'
    ],
    // Cache first, fallback to network
    cacheFirst: [
        '/build/',
        '/fonts/',
        '/images/',
        '.css',
        '.js',
        '.woff',
        '.woff2'
    ],
    // Network only (no caching)
    networkOnly: [
        '/broadcasting/',
        '/login',
        '/logout'
    ]
};

// Install event - cache static assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE_NAME)
            .then(cache => {
                console.log('Caching static assets');
                return cache.addAll(STATIC_FILES);
            })
            .then(() => self.skipWaiting())
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then(cacheNames => {
                return Promise.all(
                    cacheNames
                        .filter(cacheName => {
                            return cacheName.startsWith('askproai-') &&
                                   cacheName !== CACHE_NAME &&
                                   cacheName !== STATIC_CACHE_NAME &&
                                   cacheName !== DYNAMIC_CACHE_NAME;
                        })
                        .map(cacheName => caches.delete(cacheName))
                );
            })
            .then(() => self.clients.claim())
    );
});

// Fetch event - implement caching strategies
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
    
    // Determine cache strategy
    const strategy = getCacheStrategy(url.pathname);
    
    switch (strategy) {
        case 'networkFirst':
            event.respondWith(networkFirst(request));
            break;
        case 'cacheFirst':
            event.respondWith(cacheFirst(request));
            break;
        case 'networkOnly':
            event.respondWith(fetch(request));
            break;
        default:
            event.respondWith(networkFirst(request));
    }
});

// Cache strategies implementation
async function networkFirst(request) {
    try {
        const networkResponse = await fetch(request);
        
        // Cache successful responses
        if (networkResponse && networkResponse.status === 200) {
            const cache = await caches.open(DYNAMIC_CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
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
        // Return from cache and update in background
        fetch(request).then(networkResponse => {
            if (networkResponse && networkResponse.status === 200) {
                caches.open(STATIC_CACHE_NAME).then(cache => {
                    cache.put(request, networkResponse);
                });
            }
        });
        
        return cachedResponse;
    }
    
    // Not in cache, fetch from network
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse && networkResponse.status === 200) {
            const cache = await caches.open(STATIC_CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        // Return offline page for navigation requests
        if (request.mode === 'navigate') {
            return caches.match('/offline.html');
        }
        
        throw error;
    }
}

// Determine cache strategy based on URL
function getCacheStrategy(pathname) {
    // Check network only patterns
    for (const pattern of CACHE_STRATEGIES.networkOnly) {
        if (pathname.includes(pattern)) {
            return 'networkOnly';
        }
    }
    
    // Check cache first patterns
    for (const pattern of CACHE_STRATEGIES.cacheFirst) {
        if (pathname.includes(pattern)) {
            return 'cacheFirst';
        }
    }
    
    // Check network first patterns
    for (const pattern of CACHE_STRATEGIES.networkFirst) {
        if (pathname.includes(pattern)) {
            return 'networkFirst';
        }
    }
    
    // Default to network first
    return 'networkFirst';
}

// Handle messages from clients
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    
    if (event.data && event.data.type === 'CLEAR_CACHE') {
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => caches.delete(cacheName))
            );
        }).then(() => {
            event.ports[0].postMessage({ cleared: true });
        });
    }
});