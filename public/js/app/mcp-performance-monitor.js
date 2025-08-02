// MCP Frontend Performance Monitor
class MCPPerformanceMonitor {
    constructor() {
        this.metrics = {
            navigation: {},
            resources: [],
            userTimings: {},
            errors: [],
            vitals: {}
        };
        
        this.observers = {
            performance: null,
            errors: null,
            interactions: null
        };
        
        this.initialize();
    }
    
    initialize() {
        // Capture navigation timing
        this.captureNavigationTiming();
        
        // Set up performance observer
        this.setupPerformanceObserver();
        
        // Monitor errors
        this.setupErrorMonitoring();
        
        // Track Core Web Vitals
        this.trackWebVitals();
        
        // Monitor API calls
        this.interceptFetch();
        
        // Monitor Livewire requests
        this.monitorLivewire();
    }
    
    captureNavigationTiming() {
        if (window.performance && window.performance.timing) {
            const timing = window.performance.timing;
            const navigationStart = timing.navigationStart;
            
            this.metrics.navigation = {
                domainLookup: timing.domainLookupEnd - timing.domainLookupStart,
                tcpConnection: timing.connectEnd - timing.connectStart,
                request: timing.responseStart - timing.requestStart,
                response: timing.responseEnd - timing.responseStart,
                domProcessing: timing.domComplete - timing.domLoading,
                domContentLoaded: timing.domContentLoadedEventEnd - navigationStart,
                loadComplete: timing.loadEventEnd - navigationStart
            };
        }
    }
    
    setupPerformanceObserver() {
        if ('PerformanceObserver' in window) {
            // Monitor resource timing
            const resourceObserver = new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    if (entry.name.includes('/api/mcp')) {
                        this.trackApiCall(entry);
                    }
                    
                    this.metrics.resources.push({
                        name: entry.name,
                        type: entry.initiatorType,
                        duration: entry.duration,
                        size: entry.transferSize || 0,
                        timestamp: entry.startTime
                    });
                }
            });
            
            resourceObserver.observe({ entryTypes: ['resource'] });
            
            // Monitor user timings
            const timingObserver = new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    this.metrics.userTimings[entry.name] = entry.duration;
                }
            });
            
            timingObserver.observe({ entryTypes: ['measure'] });
        }
    }
    
    trackApiCall(entry) {
        const apiMetrics = {
            endpoint: entry.name,
            duration: entry.duration,
            size: entry.transferSize || 0,
            timestamp: entry.startTime,
            cached: entry.transferSize === 0
        };
        
        // Send to analytics
        this.sendMetric('api_call', apiMetrics);
        
        // Update running statistics
        this.updateApiStats(apiMetrics);
    }
    
    updateApiStats(metrics) {
        const endpoint = new URL(metrics.endpoint).pathname;
        
        if (!this.metrics.apiStats) {
            this.metrics.apiStats = {};
        }
        
        if (!this.metrics.apiStats[endpoint]) {
            this.metrics.apiStats[endpoint] = {
                count: 0,
                totalDuration: 0,
                avgDuration: 0,
                cached: 0
            };
        }
        
        const stats = this.metrics.apiStats[endpoint];
        stats.count++;
        stats.totalDuration += metrics.duration;
        stats.avgDuration = stats.totalDuration / stats.count;
        
        if (metrics.cached) {
            stats.cached++;
        }
    }
    
    setupErrorMonitoring() {
        // JavaScript errors
        window.addEventListener('error', (event) => {
            this.metrics.errors.push({
                type: 'javascript',
                message: event.message,
                source: event.filename,
                line: event.lineno,
                column: event.colno,
                stack: event.error?.stack,
                timestamp: Date.now()
            });
            
            this.sendMetric('frontend_error', {
                type: 'javascript',
                message: event.message,
                source: event.filename
            });
        });
        
        // Promise rejections
        window.addEventListener('unhandledrejection', (event) => {
            this.metrics.errors.push({
                type: 'promise',
                reason: event.reason,
                timestamp: Date.now()
            });
            
            this.sendMetric('frontend_error', {
                type: 'promise',
                reason: String(event.reason)
            });
        });
    }
    
    trackWebVitals() {
        // Largest Contentful Paint (LCP)
        new PerformanceObserver((list) => {
            const entries = list.getEntries();
            const lastEntry = entries[entries.length - 1];
            this.metrics.vitals.lcp = lastEntry.renderTime || lastEntry.loadTime;
            this.sendMetric('web_vital', { name: 'lcp', value: this.metrics.vitals.lcp });
        }).observe({ entryTypes: ['largest-contentful-paint'] });
        
        // First Input Delay (FID)
        new PerformanceObserver((list) => {
            const firstInput = list.getEntries()[0];
            this.metrics.vitals.fid = firstInput.processingStart - firstInput.startTime;
            this.sendMetric('web_vital', { name: 'fid', value: this.metrics.vitals.fid });
        }).observe({ entryTypes: ['first-input'] });
        
        // Cumulative Layout Shift (CLS)
        let clsValue = 0;
        new PerformanceObserver((list) => {
            for (const entry of list.getEntries()) {
                if (!entry.hadRecentInput) {
                    clsValue += entry.value;
                }
            }
            this.metrics.vitals.cls = clsValue;
        }).observe({ entryTypes: ['layout-shift'] });
    }
    
    interceptFetch() {
        const originalFetch = window.fetch;
        
        window.fetch = async (...args) => {
            const startTime = performance.now();
            const [resource, config] = args;
            
            try {
                const response = await originalFetch(...args);
                const duration = performance.now() - startTime;
                
                // Track MCP API calls
                if (resource.includes('/api/mcp')) {
                    this.trackMCPRequest({
                        url: resource,
                        method: config?.method || 'GET',
                        duration,
                        status: response.status,
                        ok: response.ok
                    });
                }
                
                return response;
            } catch (error) {
                const duration = performance.now() - startTime;
                
                this.trackMCPRequest({
                    url: resource,
                    method: config?.method || 'GET',
                    duration,
                    status: 0,
                    ok: false,
                    error: error.message
                });
                
                throw error;
            }
        };
    }
    
    trackMCPRequest(data) {
        // Extract endpoint
        const url = new URL(data.url, window.location.origin);
        const endpoint = url.pathname.replace('/api/mcp', '');
        
        // Create timing mark
        performance.mark(`mcp-request-${endpoint}-end`);
        
        // Store in metrics
        if (!this.metrics.mcpRequests) {
            this.metrics.mcpRequests = [];
        }
        
        this.metrics.mcpRequests.push({
            ...data,
            endpoint,
            timestamp: Date.now()
        });
        
        // Send metric
        this.sendMetric('mcp_request', {
            endpoint,
            duration: data.duration,
            status: data.status,
            success: data.ok
        });
    }
    
    monitorLivewire() {
        if (window.Livewire) {
            // Monitor Livewire requests
            document.addEventListener('livewire:load', () => {
                Livewire.hook('message.sent', (message, component) => {
                    performance.mark(`livewire-${component.id}-start`);
                });
                
                Livewire.hook('message.received', (message, component) => {
                    performance.mark(`livewire-${component.id}-end`);
                    
                    performance.measure(
                        `livewire-${component.id}`,
                        `livewire-${component.id}-start`,
                        `livewire-${component.id}-end`
                    );
                    
                    const measure = performance.getEntriesByName(`livewire-${component.id}`)[0];
                    
                    this.sendMetric('livewire_request', {
                        component: component.name,
                        duration: measure.duration,
                        action: message.updateQueue[0]?.method || 'unknown'
                    });
                });
            });
        }
    }
    
    sendMetric(type, data) {
        // Send to backend analytics endpoint
        if (window.mcpClient) {
            window.mcpClient.request('/metrics/frontend', {
                method: 'POST',
                body: JSON.stringify({
                    type,
                    data,
                    timestamp: Date.now(),
                    session_id: this.getSessionId(),
                    page: window.location.pathname
                })
            }).catch(err => {
                console.error('Failed to send metric:', err);
            });
        }
    }
    
    getSessionId() {
        if (!this.sessionId) {
            this.sessionId = `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
        }
        return this.sessionId;
    }
    
    getMetrics() {
        return {
            ...this.metrics,
            apiCacheHitRate: this.calculateCacheHitRate(),
            avgApiLatency: this.calculateAvgApiLatency(),
            errorRate: this.calculateErrorRate()
        };
    }
    
    calculateCacheHitRate() {
        if (!this.metrics.apiStats) return 0;
        
        let totalCalls = 0;
        let cachedCalls = 0;
        
        Object.values(this.metrics.apiStats).forEach(stats => {
            totalCalls += stats.count;
            cachedCalls += stats.cached;
        });
        
        return totalCalls > 0 ? (cachedCalls / totalCalls) * 100 : 0;
    }
    
    calculateAvgApiLatency() {
        if (!this.metrics.mcpRequests || this.metrics.mcpRequests.length === 0) {
            return 0;
        }
        
        const totalDuration = this.metrics.mcpRequests.reduce((sum, req) => sum + req.duration, 0);
        return totalDuration / this.metrics.mcpRequests.length;
    }
    
    calculateErrorRate() {
        const timeWindow = 60000; // Last minute
        const now = Date.now();
        const recentErrors = this.metrics.errors.filter(err => now - err.timestamp < timeWindow);
        
        return recentErrors.length;
    }
    
    generateReport() {
        const metrics = this.getMetrics();
        
        return {
            summary: {
                pageLoadTime: metrics.navigation.loadComplete,
                domReadyTime: metrics.navigation.domContentLoaded,
                apiCacheHitRate: metrics.apiCacheHitRate.toFixed(2) + '%',
                avgApiLatency: metrics.avgApiLatency.toFixed(2) + 'ms',
                errorRate: metrics.errorRate + ' errors/min',
                webVitals: metrics.vitals
            },
            details: metrics
        };
    }
}

// Initialize performance monitor
window.mcpPerformanceMonitor = new MCPPerformanceMonitor();

// Expose for debugging
window.getMCPPerformance = () => window.mcpPerformanceMonitor.generateReport();