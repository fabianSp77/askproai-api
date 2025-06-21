// Optimized MCP API Client with request batching and circuit breaker
class MCPApiClient {
    constructor(options = {}) {
        this.baseUrl = options.baseUrl || '/api/mcp';
        this.timeout = options.timeout || 30000;
        this.maxRetries = options.maxRetries || 3;
        this.batchDelay = options.batchDelay || 50; // ms
        
        // Circuit breaker config
        this.circuitBreaker = {
            failureThreshold: 5,
            resetTimeout: 60000, // 1 minute
            halfOpenRequests: 3,
            state: 'closed', // closed, open, half-open
            failures: 0,
            lastFailTime: null,
            successCount: 0
        };
        
        // Request batching
        this.batchQueue = [];
        this.batchTimer = null;
        
        // Request deduplication
        this.pendingRequests = new Map();
        
        // Performance tracking
        this.metrics = {
            totalRequests: 0,
            cachedResponses: 0,
            failedRequests: 0,
            averageLatency: 0,
            latencies: []
        };
    }
    
    async request(endpoint, options = {}) {
        // Check circuit breaker
        if (!this.canMakeRequest()) {
            throw new Error('Circuit breaker is open - service unavailable');
        }
        
        const url = `${this.baseUrl}${endpoint}`;
        const requestKey = `${options.method || 'GET'}:${url}:${JSON.stringify(options.body || {})}`;
        
        // Check for pending duplicate request
        if (this.pendingRequests.has(requestKey)) {
            return this.pendingRequests.get(requestKey);
        }
        
        // Create request promise
        const requestPromise = this.executeRequest(url, options);
        this.pendingRequests.set(requestKey, requestPromise);
        
        // Clean up after completion
        requestPromise.finally(() => {
            this.pendingRequests.delete(requestKey);
        });
        
        return requestPromise;
    }
    
    async executeRequest(url, options = {}) {
        const startTime = performance.now();
        this.metrics.totalRequests++;
        
        // Add default headers
        const headers = {
            'Content-Type': 'application/json',
            'X-MCP-Client': 'frontend-v1',
            ...options.headers
        };
        
        // Add tenant context if available
        const tenantId = this.getTenantId();
        if (tenantId) {
            headers['X-Tenant-ID'] = tenantId;
        }
        
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), this.timeout);
        
        try {
            const response = await fetch(url, {
                ...options,
                headers,
                signal: controller.signal
            });
            
            clearTimeout(timeoutId);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            
            // Track success
            this.recordSuccess(performance.now() - startTime);
            
            return data;
        } catch (error) {
            // Track failure
            this.recordFailure();
            
            // Retry logic
            if (options.retry !== false && (options.retryCount || 0) < this.maxRetries) {
                const retryDelay = Math.pow(2, options.retryCount || 0) * 1000;
                await this.delay(retryDelay);
                
                return this.executeRequest(url, {
                    ...options,
                    retryCount: (options.retryCount || 0) + 1
                });
            }
            
            throw error;
        } finally {
            clearTimeout(timeoutId);
        }
    }
    
    // Batch multiple operations
    async batch(operations) {
        return new Promise((resolve) => {
            this.batchQueue.push({ operations, resolve });
            
            // Clear existing timer
            if (this.batchTimer) {
                clearTimeout(this.batchTimer);
            }
            
            // Set new timer
            this.batchTimer = setTimeout(() => {
                this.executeBatch();
            }, this.batchDelay);
        });
    }
    
    async executeBatch() {
        const batch = this.batchQueue.splice(0);
        if (batch.length === 0) return;
        
        try {
            const response = await this.request('/batch', {
                method: 'POST',
                body: JSON.stringify({
                    operations: batch.flatMap(b => b.operations)
                })
            });
            
            // Distribute results
            let resultIndex = 0;
            batch.forEach(({ operations, resolve }) => {
                const results = response.results.slice(
                    resultIndex,
                    resultIndex + operations.length
                );
                resultIndex += operations.length;
                resolve(results);
            });
        } catch (error) {
            // Reject all batched operations
            batch.forEach(({ resolve }) => resolve(Promise.reject(error)));
        }
    }
    
    // Circuit breaker methods
    canMakeRequest() {
        const now = Date.now();
        
        switch (this.circuitBreaker.state) {
            case 'closed':
                return true;
                
            case 'open':
                // Check if we should transition to half-open
                if (now - this.circuitBreaker.lastFailTime > this.circuitBreaker.resetTimeout) {
                    this.circuitBreaker.state = 'half-open';
                    this.circuitBreaker.successCount = 0;
                    return true;
                }
                return false;
                
            case 'half-open':
                // Allow limited requests in half-open state
                return this.circuitBreaker.successCount < this.circuitBreaker.halfOpenRequests;
                
            default:
                return true;
        }
    }
    
    recordSuccess(latency) {
        this.metrics.latencies.push(latency);
        if (this.metrics.latencies.length > 100) {
            this.metrics.latencies.shift();
        }
        
        this.metrics.averageLatency = this.metrics.latencies.reduce((a, b) => a + b, 0) / this.metrics.latencies.length;
        
        switch (this.circuitBreaker.state) {
            case 'half-open':
                this.circuitBreaker.successCount++;
                if (this.circuitBreaker.successCount >= this.circuitBreaker.halfOpenRequests) {
                    // Transition back to closed
                    this.circuitBreaker.state = 'closed';
                    this.circuitBreaker.failures = 0;
                }
                break;
                
            case 'closed':
                // Reset failure count on success
                this.circuitBreaker.failures = 0;
                break;
        }
    }
    
    recordFailure() {
        this.metrics.failedRequests++;
        this.circuitBreaker.failures++;
        this.circuitBreaker.lastFailTime = Date.now();
        
        if (this.circuitBreaker.failures >= this.circuitBreaker.failureThreshold) {
            this.circuitBreaker.state = 'open';
            console.error('Circuit breaker opened due to repeated failures');
        }
    }
    
    // Helper methods
    getTenantId() {
        // Try to get tenant ID from various sources
        return window.tenantId || 
               document.querySelector('meta[name="tenant-id"]')?.content ||
               null;
    }
    
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
    
    // Convenience methods for common operations
    async getEventTypes(companyId) {
        return this.request(`/calcom/event-types`, {
            params: { company_id: companyId }
        });
    }
    
    async getAvailability(eventTypeId, startDate, endDate) {
        return this.request('/calcom/availability', {
            method: 'POST',
            body: JSON.stringify({
                event_type_id: eventTypeId,
                start_date: startDate,
                end_date: endDate
            })
        });
    }
    
    async getCallStats(companyId, dateRange) {
        return this.request('/retell/call-stats', {
            method: 'POST',
            body: JSON.stringify({
                company_id: companyId,
                date_range: dateRange
            })
        });
    }
    
    async getSystemHealth() {
        return this.request('/health');
    }
    
    async getMetrics() {
        return {
            ...this.metrics,
            circuitBreakerState: this.circuitBreaker.state
        };
    }
}

// Initialize global MCP client
window.mcpClient = new MCPApiClient();

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = MCPApiClient;
}