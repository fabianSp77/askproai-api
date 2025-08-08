import axios from 'axios';

class MCPService {
    constructor() {
        this.baseUrl = '/api/mcp';
        this.adminUrl = '/admin/api/mcp';
        
        // Create axios instance with default config
        this.client = axios.create({
            timeout: 30000,
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        // Add request interceptor for auth
        this.client.interceptors.request.use((config) => {
            // Add CSRF token
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (token) {
                config.headers['X-CSRF-TOKEN'] = token;
            }

            // Add tenant context
            const tenantId = this.getTenantId();
            if (tenantId) {
                config.headers['X-Tenant-ID'] = tenantId;
            }

            // Add user context
            const userId = this.getUserId();
            if (userId) {
                config.headers['X-User-ID'] = userId;
            }

            return config;
        });

        // Add response interceptor for error handling
        this.client.interceptors.response.use(
            (response) => response,
            (error) => {
                console.error('MCP Service Error:', error.response?.data || error.message);
                
                // Handle specific error cases
                if (error.response?.status === 401) {
                    window.location.reload(); // Refresh to re-authenticate
                } else if (error.response?.status === 429) {
                    throw new Error('Rate limit exceeded. Please try again later.');
                } else if (error.response?.status >= 500) {
                    throw new Error('Server error occurred. Please contact support.');
                }
                
                throw error;
            }
        );
    }

    // Helper methods
    getTenantId() {
        return window.tenantId || 
               document.querySelector('meta[name="tenant-id"]')?.content ||
               null;
    }

    getUserId() {
        return window.userId || 
               document.querySelector('meta[name="user-id"]')?.content ||
               null;
    }

    // Configuration Management
    async getConfiguration() {
        const response = await this.client.get(`${this.adminUrl}/configuration`);
        return response.data;
    }

    async updateConfiguration(config) {
        const response = await this.client.put(`${this.adminUrl}/configuration`, config);
        return response.data;
    }

    async resetConfiguration() {
        const response = await this.client.post(`${this.adminUrl}/configuration/reset`);
        return response.data;
    }

    // Metrics and Monitoring
    async getMetrics(timeRange = '1h') {
        const response = await this.client.get(`${this.adminUrl}/metrics`, {
            params: { timeRange }
        });
        return response.data;
    }

    async getDetailedMetrics(timeRange = '24h') {
        const response = await this.client.get(`${this.adminUrl}/metrics/detailed`, {
            params: { timeRange }
        });
        return response.data;
    }

    async getRecentCalls(limit = 50) {
        const response = await this.client.get(`${this.adminUrl}/calls/recent`, {
            params: { limit }
        });
        return response.data;
    }

    async resetMetrics() {
        const response = await this.client.post(`${this.adminUrl}/metrics/reset`);
        return response.data;
    }

    // Circuit Breaker Management
    async getCircuitBreakerStatus() {
        const response = await this.client.get(`${this.adminUrl}/circuit-breaker/status`);
        return response.data;
    }

    async toggleCircuitBreaker() {
        const response = await this.client.post(`${this.adminUrl}/circuit-breaker/toggle`);
        return response.data;
    }

    async resetCircuitBreaker() {
        const response = await this.client.post(`${this.adminUrl}/circuit-breaker/reset`);
        return response.data;
    }

    // Tool Testing
    async testTool(toolName, params = {}) {
        const startTime = performance.now();
        
        try {
            const response = await this.client.post(`${this.adminUrl}/tools/${toolName}/test`, params);
            const responseTime = Math.round(performance.now() - startTime);
            
            return {
                success: true,
                responseTime,
                data: response.data,
                timestamp: new Date().toISOString()
            };
        } catch (error) {
            const responseTime = Math.round(performance.now() - startTime);
            
            return {
                success: false,
                responseTime,
                error: error.response?.data?.message || error.message,
                timestamp: new Date().toISOString()
            };
        }
    }

    async testAllTools() {
        const tools = ['calcom', 'database', 'retell', 'webhook', 'queue'];
        const results = {};

        const promises = tools.map(async (tool) => {
            try {
                const result = await this.testTool(tool);
                results[tool] = result;
            } catch (error) {
                results[tool] = {
                    success: false,
                    error: error.message,
                    timestamp: new Date().toISOString()
                };
            }
        });

        await Promise.allSettled(promises);
        return results;
    }

    // Performance Analysis
    async getPerformanceReport(timeRange = '24h') {
        const response = await this.client.get(`${this.adminUrl}/performance/report`, {
            params: { timeRange }
        });
        return response.data;
    }

    async getComparisonReport(startDate, endDate) {
        const response = await this.client.get(`${this.adminUrl}/performance/comparison`, {
            params: { startDate, endDate }
        });
        return response.data;
    }

    // Health Checks
    async healthCheck() {
        const response = await this.client.get(`${this.adminUrl}/health`);
        return response.data;
    }

    async systemStatus() {
        const response = await this.client.get(`${this.adminUrl}/system/status`);
        return response.data;
    }

    // Real-time Operations
    async subscribeToMetrics(callback) {
        if (window.Echo) {
            const channel = window.Echo.channel('mcp-metrics');
            
            channel.listen('MCPMetricsUpdated', (data) => {
                callback('metrics', data);
            });

            channel.listen('MCPCallCompleted', (data) => {
                callback('call', data);
            });

            channel.listen('CircuitBreakerStateChanged', (data) => {
                callback('circuitBreaker', data);
            });

            return () => {
                channel.stopListening('MCPMetricsUpdated');
                channel.stopListening('MCPCallCompleted');
                channel.stopListening('CircuitBreakerStateChanged');
            };
        }
        return null;
    }

    // Batch Operations
    async batchRequest(requests) {
        const response = await this.client.post(`${this.adminUrl}/batch`, {
            requests
        });
        return response.data;
    }

    // Configuration Validation
    async validateConfiguration(config) {
        const response = await this.client.post(`${this.adminUrl}/configuration/validate`, config);
        return response.data;
    }

    // Tool Management
    async getAvailableTools() {
        const response = await this.client.get(`${this.adminUrl}/tools`);
        return response.data;
    }

    async getToolInfo(toolName) {
        const response = await this.client.get(`${this.adminUrl}/tools/${toolName}/info`);
        return response.data;
    }

    async updateToolConfiguration(toolName, config) {
        const response = await this.client.put(`${this.adminUrl}/tools/${toolName}/config`, config);
        return response.data;
    }

    // Error Tracking
    async getErrors(timeRange = '24h', severity = 'all') {
        const response = await this.client.get(`${this.adminUrl}/errors`, {
            params: { timeRange, severity }
        });
        return response.data;
    }

    async getErrorDetails(errorId) {
        const response = await this.client.get(`${this.adminUrl}/errors/${errorId}`);
        return response.data;
    }

    async markErrorResolved(errorId) {
        const response = await this.client.post(`${this.adminUrl}/errors/${errorId}/resolve`);
        return response.data;
    }

    // Analytics and Reporting
    async getUsageAnalytics(timeRange = '7d') {
        const response = await this.client.get(`${this.adminUrl}/analytics/usage`, {
            params: { timeRange }
        });
        return response.data;
    }

    async exportMetrics(format = 'json', timeRange = '24h') {
        const response = await this.client.get(`${this.adminUrl}/metrics/export`, {
            params: { format, timeRange }
        });
        return response.data;
    }

    // Cache Management
    async getCacheStatus() {
        const response = await this.client.get(`${this.adminUrl}/cache/status`);
        return response.data;
    }

    async clearCache(cacheType = 'all') {
        const response = await this.client.post(`${this.adminUrl}/cache/clear`, {
            type: cacheType
        });
        return response.data;
    }

    // Feature Flags
    async getFeatureFlags() {
        const response = await this.client.get(`${this.adminUrl}/features`);
        return response.data;
    }

    async updateFeatureFlag(flagName, enabled) {
        const response = await this.client.put(`${this.adminUrl}/features/${flagName}`, {
            enabled
        });
        return response.data;
    }

    // Webhook Management
    async getWebhookComparison(timeRange = '24h') {
        const response = await this.client.get(`${this.adminUrl}/webhooks/comparison`, {
            params: { timeRange }
        });
        return response.data;
    }

    async switchToWebhookMode() {
        const response = await this.client.post(`${this.adminUrl}/mode/webhook`);
        return response.data;
    }

    async switchToMCPMode() {
        const response = await this.client.post(`${this.adminUrl}/mode/mcp`);
        return response.data;
    }

    // Utility Methods
    formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    formatDuration(ms) {
        if (ms < 1000) return `${ms}ms`;
        if (ms < 60000) return `${(ms / 1000).toFixed(1)}s`;
        return `${(ms / 60000).toFixed(1)}m`;
    }

    calculateSuccessRate(successful, total) {
        return total > 0 ? (successful / total * 100) : 0;
    }
}

// Create singleton instance
const mcpService = new MCPService();

export { mcpService };
export default MCPService;