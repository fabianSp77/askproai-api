// MCP Frontend Cache Manager with IndexedDB
class MCPCacheManager {
    constructor() {
        this.dbName = 'MCPCache';
        this.dbVersion = 1;
        this.stores = {
            apiResponses: 'api_responses',
            metrics: 'metrics',
            eventTypes: 'event_types',
            serviceHealth: 'service_health'
        };
        
        this.cacheConfig = {
            apiResponses: { ttl: 300000 }, // 5 minutes
            metrics: { ttl: 60000 }, // 1 minute
            eventTypes: { ttl: 3600000 }, // 1 hour
            serviceHealth: { ttl: 30000 } // 30 seconds
        };
        
        this.initializeDB();
    }
    
    async initializeDB() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.dbVersion);
            
            request.onerror = () => reject(request.error);
            request.onsuccess = () => {
                this.db = request.result;
                resolve();
            };
            
            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                
                // Create object stores
                Object.entries(this.stores).forEach(([key, storeName]) => {
                    if (!db.objectStoreNames.contains(storeName)) {
                        const store = db.createObjectStore(storeName, { keyPath: 'key' });
                        store.createIndex('timestamp', 'timestamp', { unique: false });
                        store.createIndex('tenantId', 'tenantId', { unique: false });
                    }
                });
            };
        });
    }
    
    async get(storeName, key, tenantId = null) {
        const transaction = this.db.transaction([storeName], 'readonly');
        const store = transaction.objectStore(storeName);
        
        return new Promise((resolve, reject) => {
            const request = store.get(key);
            
            request.onsuccess = () => {
                const result = request.result;
                
                if (!result) {
                    resolve(null);
                    return;
                }
                
                // Check tenant isolation
                if (tenantId && result.tenantId !== tenantId) {
                    resolve(null);
                    return;
                }
                
                // Check TTL
                const ttl = this.cacheConfig[storeName]?.ttl || 300000;
                const age = Date.now() - result.timestamp;
                
                if (age > ttl) {
                    // Delete expired entry
                    this.delete(storeName, key);
                    resolve(null);
                    return;
                }
                
                resolve(result.data);
            };
            
            request.onerror = () => reject(request.error);
        });
    }
    
    async set(storeName, key, data, tenantId = null) {
        const transaction = this.db.transaction([storeName], 'readwrite');
        const store = transaction.objectStore(storeName);
        
        const entry = {
            key,
            data,
            timestamp: Date.now(),
            tenantId
        };
        
        return new Promise((resolve, reject) => {
            const request = store.put(entry);
            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }
    
    async delete(storeName, key) {
        const transaction = this.db.transaction([storeName], 'readwrite');
        const store = transaction.objectStore(storeName);
        
        return new Promise((resolve, reject) => {
            const request = store.delete(key);
            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }
    
    async clear(storeName) {
        const transaction = this.db.transaction([storeName], 'readwrite');
        const store = transaction.objectStore(storeName);
        
        return new Promise((resolve, reject) => {
            const request = store.clear();
            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }
    
    async cleanExpired() {
        for (const [key, storeName] of Object.entries(this.stores)) {
            const transaction = this.db.transaction([storeName], 'readwrite');
            const store = transaction.objectStore(storeName);
            const ttl = this.cacheConfig[key]?.ttl || 300000;
            const cutoff = Date.now() - ttl;
            
            const request = store.index('timestamp').openCursor(IDBKeyRange.upperBound(cutoff));
            
            request.onsuccess = (event) => {
                const cursor = event.target.result;
                if (cursor) {
                    store.delete(cursor.primaryKey);
                    cursor.continue();
                }
            };
        }
    }
    
    // Cache-aware fetch wrapper
    async cachedFetch(url, options = {}, cacheKey = null, storeName = 'apiResponses') {
        const key = cacheKey || `${url}:${JSON.stringify(options)}`;
        const tenantId = options.headers?.['X-Tenant-ID'] || null;
        
        // Check cache first
        const cached = await this.get(storeName, key, tenantId);
        if (cached) {
            console.log(`Cache hit for ${url}`);
            return cached;
        }
        
        // Fetch from network
        try {
            const response = await fetch(url, options);
            const data = await response.json();
            
            // Cache successful responses
            if (response.ok) {
                await this.set(storeName, key, data, tenantId);
            }
            
            return data;
        } catch (error) {
            console.error('Fetch error:', error);
            
            // Return stale cache if available
            const staleCache = await this.get(storeName, key, tenantId);
            if (staleCache) {
                console.warn('Returning stale cache due to network error');
                return staleCache;
            }
            
            throw error;
        }
    }
    
    // Batch fetch with deduplication
    async batchFetch(requests) {
        const pending = new Map();
        const results = [];
        
        for (const request of requests) {
            const key = `${request.url}:${JSON.stringify(request.options || {})}`;
            
            if (pending.has(key)) {
                // Deduplicate identical requests
                results.push(pending.get(key));
            } else {
                const promise = this.cachedFetch(
                    request.url,
                    request.options,
                    request.cacheKey,
                    request.storeName
                );
                pending.set(key, promise);
                results.push(promise);
            }
        }
        
        return Promise.all(results);
    }
}

// Initialize global cache manager
window.mcpCache = new MCPCacheManager();

// Periodic cleanup
setInterval(() => {
    window.mcpCache.cleanExpired();
}, 60000); // Clean every minute