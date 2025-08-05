/**
 * Dashboard Lazy Loading System
 * Progressively loads dashboard widgets to improve initial page load
 */

class DashboardLazyLoader {
    constructor() {
        this.loadedWidgets = new Set();
        this.loadingWidgets = new Set();
        this.widgetObserver = null;
        this.errorRetries = new Map();
        this.maxRetries = 3;
        
        // Performance metrics
        this.metrics = {
            startTime: performance.now(),
            widgetLoadTimes: new Map()
        };
    }

    /**
     * Initialize lazy loading
     */
    init() {
        // Check for IntersectionObserver support
        if (!('IntersectionObserver' in window)) {
            console.warn('IntersectionObserver not supported, loading all widgets');
            this.loadAllWidgets();
            return;
        }

        // Set up intersection observer
        this.setupObserver();
        
        // Find and observe all lazy-loadable widgets
        this.observeWidgets();
        
        // Load critical widgets immediately
        this.loadCriticalWidgets();
        
        // Set up performance monitoring
        this.setupPerformanceMonitoring();
    }

    /**
     * Setup Intersection Observer
     */
    setupObserver() {
        const options = {
            root: null,
            rootMargin: '50px', // Start loading 50px before widget enters viewport
            threshold: 0.01
        };

        this.widgetObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const widget = entry.target;
                    const widgetId = widget.dataset.widgetId || widget.id;
                    
                    if (!this.loadedWidgets.has(widgetId) && !this.loadingWidgets.has(widgetId)) {
                        this.loadWidget(widget);
                    }
                }
            });
        }, options);
    }

    /**
     * Find and observe widgets
     */
    observeWidgets() {
        // Find all widgets marked for lazy loading
        const lazyWidgets = document.querySelectorAll('[data-lazy-load="true"], .lazy-widget');
        
        lazyWidgets.forEach(widget => {
            // Add loading placeholder
            this.addLoadingPlaceholder(widget);
            
            // Observe widget
            this.widgetObserver.observe(widget);
        });

        console.log(`Observing ${lazyWidgets.length} widgets for lazy loading`);
    }

    /**
     * Load critical widgets immediately
     */
    loadCriticalWidgets() {
        const criticalWidgets = document.querySelectorAll('[data-priority="critical"], [data-load-immediate="true"]');
        
        criticalWidgets.forEach(widget => {
            this.loadWidget(widget);
        });
    }

    /**
     * Load a widget
     */
    async loadWidget(widget) {
        const widgetId = widget.dataset.widgetId || widget.id;
        const widgetType = widget.dataset.widgetType || 'default';
        const widgetUrl = widget.dataset.widgetUrl || this.getWidgetUrl(widgetType, widgetId);

        if (!widgetUrl) {
            console.error(`No URL for widget ${widgetId}`);
            return;
        }

        // Mark as loading
        this.loadingWidgets.add(widgetId);
        const startTime = performance.now();

        try {
            // Add loading animation
            this.showLoadingAnimation(widget);

            // Fetch widget content
            const response = await fetch(widgetUrl, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            // Render widget content
            this.renderWidget(widget, data);
            
            // Mark as loaded
            this.loadedWidgets.add(widgetId);
            this.loadingWidgets.delete(widgetId);
            
            // Stop observing
            this.widgetObserver.unobserve(widget);
            
            // Record performance metrics
            const loadTime = performance.now() - startTime;
            this.metrics.widgetLoadTimes.set(widgetId, loadTime);
            
            console.log(`Widget ${widgetId} loaded in ${loadTime.toFixed(2)}ms`);

        } catch (error) {
            console.error(`Error loading widget ${widgetId}:`, error);
            this.handleLoadError(widget, error);
        }
    }

    /**
     * Get widget URL based on type
     */
    getWidgetUrl(type, id) {
        const baseUrl = '/api/v2/widgets';
        
        const urlMap = {
            'stats': `${baseUrl}/stats`,
            'recent-calls': `${baseUrl}/recent-calls`,
            'appointments': `${baseUrl}/appointments`,
            'revenue': `${baseUrl}/revenue`,
            'chart': `${baseUrl}/chart/${id}`,
            'default': `${baseUrl}/${id}`
        };

        return urlMap[type] || urlMap.default;
    }

    /**
     * Render widget content
     */
    renderWidget(widget, data) {
        // Remove loading placeholder
        const placeholder = widget.querySelector('.loading-placeholder');
        if (placeholder) {
            placeholder.remove();
        }

        // Render based on widget type
        const widgetType = widget.dataset.widgetType || 'default';

        switch (widgetType) {
            case 'stats':
                this.renderStatsWidget(widget, data);
                break;
            case 'chart':
                this.renderChartWidget(widget, data);
                break;
            case 'table':
                this.renderTableWidget(widget, data);
                break;
            default:
                this.renderDefaultWidget(widget, data);
        }

        // Trigger rendered event
        widget.dispatchEvent(new CustomEvent('widget:rendered', { detail: data }));
    }

    /**
     * Render stats widget
     */
    renderStatsWidget(widget, data) {
        const html = `
            <div class="stat-value">${data.value || '-'}</div>
            <div class="stat-label">${data.label || ''}</div>
            ${data.trend ? `<div class="stat-trend ${data.trend > 0 ? 'positive' : 'negative'}">${data.trend}%</div>` : ''}
        `;
        widget.innerHTML = html;
    }

    /**
     * Render chart widget
     */
    renderChartWidget(widget, data) {
        // This would integrate with your charting library
        widget.innerHTML = `<canvas id="chart-${widget.id}"></canvas>`;
        
        // Initialize chart after DOM update
        setTimeout(() => {
            if (window.Chart && data.chartData) {
                new Chart(widget.querySelector('canvas'), data.chartData);
            }
        }, 0);
    }

    /**
     * Render table widget
     */
    renderTableWidget(widget, data) {
        if (!data.rows || !Array.isArray(data.rows)) {
            widget.innerHTML = '<p class="no-data">Keine Daten verfügbar</p>';
            return;
        }

        const html = `
            <table class="widget-table">
                ${data.headers ? `
                    <thead>
                        <tr>${data.headers.map(h => `<th>${h}</th>`).join('')}</tr>
                    </thead>
                ` : ''}
                <tbody>
                    ${data.rows.map(row => `
                        <tr>${row.map(cell => `<td>${cell}</td>`).join('')}</tr>
                    `).join('')}
                </tbody>
            </table>
        `;
        widget.innerHTML = html;
    }

    /**
     * Render default widget
     */
    renderDefaultWidget(widget, data) {
        if (data.html) {
            widget.innerHTML = data.html;
        } else if (data.content) {
            widget.textContent = data.content;
        } else {
            widget.innerHTML = '<p class="no-data">Keine Daten verfügbar</p>';
        }
    }

    /**
     * Add loading placeholder
     */
    addLoadingPlaceholder(widget) {
        const placeholder = document.createElement('div');
        placeholder.className = 'loading-placeholder';
        placeholder.innerHTML = `
            <div class="loading-skeleton">
                <div class="skeleton-line"></div>
                <div class="skeleton-line short"></div>
            </div>
        `;
        widget.appendChild(placeholder);
    }

    /**
     * Show loading animation
     */
    showLoadingAnimation(widget) {
        widget.classList.add('widget-loading');
    }

    /**
     * Handle load error
     */
    handleLoadError(widget, error) {
        const widgetId = widget.dataset.widgetId || widget.id;
        const retries = this.errorRetries.get(widgetId) || 0;

        if (retries < this.maxRetries) {
            // Retry with exponential backoff
            const delay = Math.pow(2, retries) * 1000;
            this.errorRetries.set(widgetId, retries + 1);
            
            setTimeout(() => {
                console.log(`Retrying widget ${widgetId} (attempt ${retries + 1}/${this.maxRetries})`);
                this.loadingWidgets.delete(widgetId);
                this.loadWidget(widget);
            }, delay);
        } else {
            // Show error state
            widget.innerHTML = `
                <div class="widget-error">
                    <p>Fehler beim Laden</p>
                    <button onclick="dashboardLoader.reloadWidget('${widgetId}')" class="retry-button">
                        Erneut versuchen
                    </button>
                </div>
            `;
            widget.classList.remove('widget-loading');
            this.loadingWidgets.delete(widgetId);
        }
    }

    /**
     * Reload a specific widget
     */
    reloadWidget(widgetId) {
        const widget = document.querySelector(`[data-widget-id="${widgetId}"], #${widgetId}`);
        if (widget) {
            this.errorRetries.delete(widgetId);
            this.loadedWidgets.delete(widgetId);
            this.loadWidget(widget);
        }
    }

    /**
     * Load all widgets (fallback)
     */
    loadAllWidgets() {
        const widgets = document.querySelectorAll('[data-lazy-load="true"], .lazy-widget');
        widgets.forEach(widget => this.loadWidget(widget));
    }

    /**
     * Setup performance monitoring
     */
    setupPerformanceMonitoring() {
        // Log performance metrics when all widgets are loaded
        window.addEventListener('load', () => {
            const totalTime = performance.now() - this.metrics.startTime;
            console.log(`Dashboard fully loaded in ${totalTime.toFixed(2)}ms`);
            
            // Send metrics to analytics if available
            if (window.gtag) {
                window.gtag('event', 'dashboard_load', {
                    'event_category': 'performance',
                    'value': Math.round(totalTime)
                });
            }
        });
    }

    /**
     * Destroy lazy loader
     */
    destroy() {
        if (this.widgetObserver) {
            this.widgetObserver.disconnect();
        }
        this.loadedWidgets.clear();
        this.loadingWidgets.clear();
        this.errorRetries.clear();
    }
}

// CSS for loading states
const lazyLoaderStyles = `
<style>
.loading-placeholder {
    padding: 20px;
    animation: pulse 1.5s ease-in-out infinite;
}

.loading-skeleton {
    background: #f3f4f6;
    border-radius: 4px;
    padding: 10px;
}

.skeleton-line {
    height: 20px;
    background: #e5e7eb;
    border-radius: 4px;
    margin-bottom: 10px;
}

.skeleton-line.short {
    width: 60%;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.widget-loading {
    position: relative;
    pointer-events: none;
    opacity: 0.7;
}

.widget-error {
    text-align: center;
    padding: 20px;
    color: #ef4444;
}

.retry-button {
    margin-top: 10px;
    padding: 5px 15px;
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.retry-button:hover {
    background: #2563eb;
}
</style>
`;

// Inject styles
document.head.insertAdjacentHTML('beforeend', lazyLoaderStyles);

// Initialize on DOM ready
const dashboardLoader = new DashboardLazyLoader();

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => dashboardLoader.init());
} else {
    dashboardLoader.init();
}

// Export for use in other scripts
window.DashboardLazyLoader = DashboardLazyLoader;
window.dashboardLoader = dashboardLoader;