// Performance monitoring utility
class PerformanceMonitor {
    constructor() {
        this.metrics = {
            pageLoad: {},
            apiCalls: [],
            componentRenders: new Map(),
            memoryUsage: []
        };
        
        this.initializeMonitoring();
    }
    
    initializeMonitoring() {
        // Monitor page load performance
        if (typeof window !== 'undefined' && window.performance) {
            window.addEventListener('load', () => {
                const perfData = window.performance.timing;
                const pageLoadTime = perfData.loadEventEnd - perfData.navigationStart;
                const domReadyTime = perfData.domContentLoadedEventEnd - perfData.navigationStart;
                
                this.metrics.pageLoad = {
                    pageLoadTime,
                    domReadyTime,
                    dnsTime: perfData.domainLookupEnd - perfData.domainLookupStart,
                    tcpTime: perfData.connectEnd - perfData.connectStart,
                    requestTime: perfData.responseEnd - perfData.requestStart,
                    renderTime: perfData.domComplete - perfData.domLoading
                };
                
                this.reportMetrics('pageLoad', this.metrics.pageLoad);
            });
            
            // Monitor memory usage (if available)
            if (performance.memory) {
                setInterval(() => {
                    this.metrics.memoryUsage.push({
                        timestamp: Date.now(),
                        usedJSHeapSize: performance.memory.usedJSHeapSize,
                        totalJSHeapSize: performance.memory.totalJSHeapSize,
                        jsHeapSizeLimit: performance.memory.jsHeapSizeLimit
                    });
                    
                    // Keep only last 100 entries
                    if (this.metrics.memoryUsage.length > 100) {
                        this.metrics.memoryUsage.shift();
                    }
                }, 10000); // Check every 10 seconds
            }
        }
    }
    
    // Track API call performance
    trackApiCall(url, method, duration, status) {
        const apiMetric = {
            url,
            method,
            duration,
            status,
            timestamp: Date.now()
        };
        
        this.metrics.apiCalls.push(apiMetric);
        
        // Keep only last 100 API calls
        if (this.metrics.apiCalls.length > 100) {
            this.metrics.apiCalls.shift();
        }
        
        // Report slow API calls
        if (duration > 1000) {
            console.warn(`Slow API call detected: ${method} ${url} took ${duration}ms`);
            this.reportMetrics('slowApiCall', apiMetric);
        }
    }
    
    // Track component render performance
    trackComponentRender(componentName, renderTime) {
        if (!this.metrics.componentRenders.has(componentName)) {
            this.metrics.componentRenders.set(componentName, {
                count: 0,
                totalTime: 0,
                avgTime: 0,
                maxTime: 0
            });
        }
        
        const stats = this.metrics.componentRenders.get(componentName);
        stats.count++;
        stats.totalTime += renderTime;
        stats.avgTime = stats.totalTime / stats.count;
        stats.maxTime = Math.max(stats.maxTime, renderTime);
        
        // Report slow renders
        if (renderTime > 16) { // More than one frame (60fps)
            console.warn(`Slow render detected: ${componentName} took ${renderTime}ms`);
        }
    }
    
    // Get performance report
    getReport() {
        const apiStats = this.getApiStats();
        const componentStats = this.getComponentStats();
        const memoryStats = this.getMemoryStats();
        
        return {
            pageLoad: this.metrics.pageLoad,
            api: apiStats,
            components: componentStats,
            memory: memoryStats,
            timestamp: new Date().toISOString()
        };
    }
    
    getApiStats() {
        if (this.metrics.apiCalls.length === 0) return null;
        
        const durations = this.metrics.apiCalls.map(call => call.duration);
        const successCalls = this.metrics.apiCalls.filter(call => call.status >= 200 && call.status < 300);
        
        return {
            totalCalls: this.metrics.apiCalls.length,
            successRate: (successCalls.length / this.metrics.apiCalls.length) * 100,
            avgDuration: durations.reduce((a, b) => a + b, 0) / durations.length,
            minDuration: Math.min(...durations),
            maxDuration: Math.max(...durations),
            p95Duration: this.percentile(durations, 0.95),
            p99Duration: this.percentile(durations, 0.99)
        };
    }
    
    getComponentStats() {
        const stats = [];
        
        this.metrics.componentRenders.forEach((value, key) => {
            stats.push({
                component: key,
                ...value
            });
        });
        
        return stats.sort((a, b) => b.avgTime - a.avgTime);
    }
    
    getMemoryStats() {
        if (this.metrics.memoryUsage.length === 0) return null;
        
        const latest = this.metrics.memoryUsage[this.metrics.memoryUsage.length - 1];
        const heapUsages = this.metrics.memoryUsage.map(m => m.usedJSHeapSize);
        
        return {
            current: {
                used: this.formatBytes(latest.usedJSHeapSize),
                total: this.formatBytes(latest.totalJSHeapSize),
                limit: this.formatBytes(latest.jsHeapSizeLimit),
                percentage: (latest.usedJSHeapSize / latest.jsHeapSizeLimit) * 100
            },
            trend: {
                min: this.formatBytes(Math.min(...heapUsages)),
                max: this.formatBytes(Math.max(...heapUsages)),
                avg: this.formatBytes(heapUsages.reduce((a, b) => a + b, 0) / heapUsages.length)
            }
        };
    }
    
    // Report metrics to backend or monitoring service
    reportMetrics(type, data) {
        // In production, send to monitoring service
        if (process.env.NODE_ENV === 'production') {
            fetch('/api/portal/metrics', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                credentials: 'include',
                body: JSON.stringify({
                    type,
                    data,
                    userAgent: navigator.userAgent,
                    timestamp: new Date().toISOString()
                })
            }).catch(error => {
                console.error('Failed to report metrics:', error);
            });
        }
    }
    
    // Utility functions
    percentile(arr, p) {
        const sorted = arr.slice().sort((a, b) => a - b);
        const index = Math.ceil(sorted.length * p) - 1;
        return sorted[index];
    }
    
    formatBytes(bytes) {
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        if (bytes === 0) return '0 Bytes';
        const i = Math.floor(Math.log(bytes) / Math.log(1024));
        return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i];
    }
}

// Create singleton instance
const performanceMonitor = new PerformanceMonitor();

// Export for use in React components
export default performanceMonitor;

// Export singleton instance for use in other modules
export { performanceMonitor };