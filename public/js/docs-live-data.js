/**
 * AskProAI Documentation Live Data Loader
 * 
 * This script fetches and displays real-time data in documentation pages
 */

class DocsLiveData {
    constructor(apiBaseUrl = 'https://api.askproai.de/api/docs-data') {
        this.apiBaseUrl = apiBaseUrl;
        this.refreshInterval = 60000; // 1 minute
        this.intervals = [];
    }

    /**
     * Initialize live data loading
     */
    init() {
        // Load metrics
        this.loadMetrics();
        
        // Load performance data
        this.loadPerformance();
        
        // Load workflow status
        this.loadWorkflows();
        
        // Load system health
        this.loadHealth();
        
        // Set up auto-refresh
        this.setupAutoRefresh();
    }

    /**
     * Load system metrics
     */
    async loadMetrics() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/metrics`);
            const data = await response.json();
            
            // Update appointment metrics
            this.updateElement('total-appointments', data.appointments.total);
            this.updateElement('today-appointments', data.appointments.today);
            this.updateElement('week-appointments', data.appointments.week);
            this.updateElement('month-appointments', data.appointments.month);
            
            // Update customer metrics
            this.updateElement('total-customers', data.customers.total);
            this.updateElement('active-customers', data.customers.active);
            this.updateElement('new-customers-today', data.customers.new_today);
            
            // Update call metrics
            this.updateElement('total-calls', data.calls.total);
            this.updateElement('today-calls', data.calls.today);
            this.updateElement('active-calls', data.calls.active);
            this.updateElement('avg-call-duration', Math.round(data.calls.avg_duration) + 's');
            
            // Update system metrics
            this.updateElement('system-uptime', data.system.uptime);
            this.updateElement('queue-size', data.system.queue_size);
            this.updateElement('failed-jobs', data.system.failed_jobs);
            
        } catch (error) {
            console.error('Failed to load metrics:', error);
        }
    }

    /**
     * Load performance data
     */
    async loadPerformance() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/performance`);
            const data = await response.json();
            
            // Update performance table
            const perfTable = document.getElementById('performance-table');
            if (perfTable) {
                let html = '<table class="performance-table">';
                html += '<thead><tr><th>Endpoint</th><th>Avg Response</th><th>P95</th><th>Req/Min</th><th>Error Rate</th></tr></thead>';
                html += '<tbody>';
                
                for (const [endpoint, metrics] of Object.entries(data)) {
                    html += `
                        <tr>
                            <td><code>${endpoint}</code></td>
                            <td>${metrics.avg_response_ms}ms</td>
                            <td>${metrics.p95_response_ms}ms</td>
                            <td>${metrics.requests_per_min}</td>
                            <td>${(metrics.error_rate * 100).toFixed(2)}%</td>
                        </tr>
                    `;
                }
                
                html += '</tbody></table>';
                perfTable.innerHTML = html;
            }
            
        } catch (error) {
            console.error('Failed to load performance data:', error);
        }
    }

    /**
     * Load workflow status
     */
    async loadWorkflows() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/workflows`);
            const data = await response.json();
            
            // Update booking flow stats
            const bookingFlow = data.booking_flow;
            this.updateElement('booking-success-rate', (bookingFlow.success_rate * 100).toFixed(1) + '%');
            this.updateElement('booking-avg-time', bookingFlow.avg_time_seconds + 's');
            this.updateElement('bookings-today', bookingFlow.total_today);
            
            // Update workflow step visualization
            const workflowSteps = document.getElementById('workflow-steps');
            if (workflowSteps && bookingFlow.steps) {
                let html = '<div class="workflow-steps">';
                
                for (const [step, stats] of Object.entries(bookingFlow.steps)) {
                    const successRate = (stats.success / (stats.success + stats.failed) * 100).toFixed(1);
                    const stepName = step.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    
                    html += `
                        <div class="workflow-step">
                            <h4>${stepName}</h4>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: ${successRate}%"></div>
                            </div>
                            <p>${successRate}% Success (${stats.success}/${stats.success + stats.failed})</p>
                        </div>
                    `;
                }
                
                html += '</div>';
                workflowSteps.innerHTML = html;
            }
            
        } catch (error) {
            console.error('Failed to load workflow data:', error);
        }
    }

    /**
     * Load system health
     */
    async loadHealth() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/health`);
            const data = await response.json();
            
            // Update overall health status
            const healthStatus = document.getElementById('system-health-status');
            if (healthStatus) {
                healthStatus.className = `health-status ${data.status}`;
                healthStatus.textContent = data.status.toUpperCase();
            }
            
            // Update individual service health
            const healthChecks = document.getElementById('health-checks');
            if (healthChecks) {
                let html = '<div class="health-checks">';
                
                for (const [service, check] of Object.entries(data.checks)) {
                    if (typeof check === 'object' && check.status) {
                        html += this.renderHealthCheck(service, check);
                    } else if (typeof check === 'object') {
                        // Nested services
                        for (const [subService, subCheck] of Object.entries(check)) {
                            html += this.renderHealthCheck(subService, subCheck);
                        }
                    }
                }
                
                html += '</div>';
                healthChecks.innerHTML = html;
            }
            
        } catch (error) {
            console.error('Failed to load health data:', error);
        }
    }

    /**
     * Render a single health check
     */
    renderHealthCheck(service, check) {
        const icon = check.status === 'healthy' ? '✅' : '❌';
        const latency = check.latency_ms ? `${check.latency_ms}ms` : 'N/A';
        
        return `
            <div class="health-check ${check.status}">
                <span class="icon">${icon}</span>
                <span class="service">${service.replace(/_/g, ' ').toUpperCase()}</span>
                <span class="latency">${latency}</span>
            </div>
        `;
    }

    /**
     * Update an element's content
     */
    updateElement(id, value) {
        const element = document.getElementById(id);
        if (element) {
            // Add animation class
            element.classList.add('updating');
            
            // Update value
            element.textContent = typeof value === 'number' ? value.toLocaleString() : value;
            
            // Remove animation class after animation completes
            setTimeout(() => {
                element.classList.remove('updating');
            }, 300);
        }
    }

    /**
     * Set up auto-refresh for all data
     */
    setupAutoRefresh() {
        // Refresh metrics every minute
        this.intervals.push(setInterval(() => this.loadMetrics(), this.refreshInterval));
        
        // Refresh performance every 5 minutes
        this.intervals.push(setInterval(() => this.loadPerformance(), this.refreshInterval * 5));
        
        // Refresh workflows every 2 minutes
        this.intervals.push(setInterval(() => this.loadWorkflows(), this.refreshInterval * 2));
        
        // Refresh health every 30 seconds
        this.intervals.push(setInterval(() => this.loadHealth(), this.refreshInterval / 2));
    }

    /**
     * Stop all refresh intervals
     */
    destroy() {
        this.intervals.forEach(interval => clearInterval(interval));
        this.intervals = [];
    }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    // Only initialize if we're on a documentation page with live data
    if (document.querySelector('[data-live-docs]')) {
        window.docsLiveData = new DocsLiveData();
        window.docsLiveData.init();
    }
});

// Add CSS for animations
const style = document.createElement('style');
style.textContent = `
    .updating {
        animation: pulse 0.3s ease-in-out;
    }
    
    @keyframes pulse {
        0% { opacity: 0.6; transform: scale(0.95); }
        50% { opacity: 1; transform: scale(1.05); }
        100% { opacity: 1; transform: scale(1); }
    }
    
    .health-status {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 0.25rem;
        font-weight: bold;
        text-transform: uppercase;
    }
    
    .health-status.healthy {
        background-color: #10b981;
        color: white;
    }
    
    .health-status.degraded {
        background-color: #f59e0b;
        color: white;
    }
    
    .health-status.unhealthy {
        background-color: #ef4444;
        color: white;
    }
    
    .health-check {
        display: flex;
        align-items: center;
        padding: 0.5rem;
        margin-bottom: 0.5rem;
        background: #f3f4f6;
        border-radius: 0.25rem;
    }
    
    .health-check.healthy {
        border-left: 4px solid #10b981;
    }
    
    .health-check.unhealthy {
        border-left: 4px solid #ef4444;
    }
    
    .health-check .icon {
        margin-right: 0.5rem;
    }
    
    .health-check .service {
        flex: 1;
        font-weight: 500;
    }
    
    .health-check .latency {
        color: #6b7280;
        font-size: 0.875rem;
    }
    
    .workflow-step {
        margin-bottom: 1rem;
    }
    
    .progress-bar {
        width: 100%;
        height: 1.5rem;
        background: #e5e7eb;
        border-radius: 0.75rem;
        overflow: hidden;
    }
    
    .progress-fill {
        height: 100%;
        background: #10b981;
        transition: width 0.3s ease;
    }
    
    .performance-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .performance-table th,
    .performance-table td {
        padding: 0.5rem;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .performance-table th {
        background: #f3f4f6;
        font-weight: 600;
    }
`;
document.head.appendChild(style);