/**
 * Command Intelligence API Client
 * Connects the PWA frontend to the Laravel backend
 */

class CommandIntelligenceAPI {
    constructor(baseUrl = '/api/v2', token = null) {
        this.baseUrl = baseUrl;
        this.token = token || localStorage.getItem('command_api_token');
        this.headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        };
        
        if (this.token) {
            this.headers['Authorization'] = `Bearer ${this.token}`;
        }
    }

    /**
     * Set authentication token
     */
    setToken(token) {
        this.token = token;
        this.headers['Authorization'] = `Bearer ${token}`;
        localStorage.setItem('command_api_token', token);
    }

    /**
     * Clear authentication
     */
    clearAuth() {
        delete this.headers['Authorization'];
        localStorage.removeItem('command_api_token');
        this.token = null;
    }

    /**
     * Make API request
     */
    async request(method, endpoint, data = null) {
        const url = `${this.baseUrl}${endpoint}`;
        const options = {
            method,
            headers: this.headers,
            credentials: 'same-origin'
        };

        if (data && method !== 'GET') {
            options.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(url, options);
            
            if (response.status === 401) {
                this.clearAuth();
                window.dispatchEvent(new CustomEvent('auth:required'));
                throw new Error('Authentication required');
            }

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || `HTTP ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('API Request failed:', error);
            throw error;
        }
    }

    /**
     * Commands API
     */
    async getCommands(params = {}) {
        const query = new URLSearchParams(params).toString();
        return this.request('GET', `/commands${query ? '?' + query : ''}`);
    }

    async getCommand(id) {
        return this.request('GET', `/commands/${id}`);
    }

    async createCommand(data) {
        return this.request('POST', '/commands', data);
    }

    async updateCommand(id, data) {
        return this.request('PUT', `/commands/${id}`, data);
    }

    async deleteCommand(id) {
        return this.request('DELETE', `/commands/${id}`);
    }

    async executeCommand(id, parameters = {}) {
        return this.request('POST', `/commands/${id}/execute`, { parameters });
    }

    async toggleCommandFavorite(id) {
        return this.request('POST', `/commands/${id}/favorite`);
    }

    async searchCommands(query) {
        return this.request('POST', '/commands/search', { query });
    }

    async getCommandCategories() {
        return this.request('GET', '/commands/categories');
    }

    /**
     * Workflows API
     */
    async getWorkflows(params = {}) {
        const query = new URLSearchParams(params).toString();
        return this.request('GET', `/workflows${query ? '?' + query : ''}`);
    }

    async getWorkflow(id) {
        return this.request('GET', `/workflows/${id}`);
    }

    async createWorkflow(data) {
        return this.request('POST', '/workflows', data);
    }

    async updateWorkflow(id, data) {
        return this.request('PUT', `/workflows/${id}`, data);
    }

    async deleteWorkflow(id) {
        return this.request('DELETE', `/workflows/${id}`);
    }

    async executeWorkflow(id, parameters = {}) {
        return this.request('POST', `/workflows/${id}/execute`, { parameters });
    }

    async toggleWorkflowFavorite(id) {
        return this.request('POST', `/workflows/${id}/favorite`);
    }

    /**
     * Executions API
     */
    async getCommandExecutions() {
        return this.request('GET', '/executions/commands');
    }

    async getWorkflowExecutions() {
        return this.request('GET', '/executions/workflows');
    }

    async getCommandExecution(id) {
        return this.request('GET', `/executions/commands/${id}`);
    }

    async getWorkflowExecution(id) {
        return this.request('GET', `/executions/workflows/${id}`);
    }

    /**
     * WebSocket connection for real-time updates
     */
    connectWebSocket() {
        if (!this.token) {
            console.warn('No auth token for WebSocket connection');
            return;
        }

        // WebSocket implementation will be added when Laravel Echo is configured
        // For now, use polling for execution status
        this.pollingIntervals = new Map();
    }

    /**
     * Poll execution status
     */
    pollExecutionStatus(executionId, callback, interval = 2000) {
        const poll = async () => {
            try {
                const execution = await this.getCommandExecution(executionId);
                callback(execution);
                
                if (execution.status === 'success' || execution.status === 'failed') {
                    this.stopPolling(executionId);
                }
            } catch (error) {
                console.error('Polling error:', error);
                this.stopPolling(executionId);
            }
        };

        // Initial poll
        poll();
        
        // Set up interval
        const intervalId = setInterval(poll, interval);
        this.pollingIntervals.set(executionId, intervalId);
    }

    stopPolling(executionId) {
        const intervalId = this.pollingIntervals.get(executionId);
        if (intervalId) {
            clearInterval(intervalId);
            this.pollingIntervals.delete(executionId);
        }
    }

    /**
     * Helper to check if authenticated
     */
    isAuthenticated() {
        return !!this.token;
    }
}

// Export for use in other scripts
window.CommandIntelligenceAPI = CommandIntelligenceAPI;