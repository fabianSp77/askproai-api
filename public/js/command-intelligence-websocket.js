/**
 * Command Intelligence WebSocket Integration
 * Provides real-time updates using Laravel Echo
 */

// Pusher is already loaded from CDN
// Echo is already loaded from CDN

class CommandIntelligenceWebSocket {
    constructor(api) {
        this.api = api;
        this.echo = null;
        this.subscriptions = new Map();
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.isConnected = false;
    }

    /**
     * Initialize WebSocket connection
     */
    initialize() {
        if (!this.api.isAuthenticated()) {
            console.warn('Cannot initialize WebSocket without authentication');
            return;
        }

        // Initialize Laravel Echo
        this.echo = new Echo({
            broadcaster: 'pusher',
            key: window.PUSHER_APP_KEY || 'local',
            cluster: window.PUSHER_APP_CLUSTER || 'mt1',
            forceTLS: true,
            auth: {
                headers: {
                    Authorization: `Bearer ${this.api.token}`,
                    Accept: 'application/json',
                },
            },
            authEndpoint: '/broadcasting/auth',
            enabledTransports: ['ws', 'wss'],
        });

        // Monitor connection status
        this.echo.connector.pusher.connection.bind('connected', () => {
            this.isConnected = true;
            this.reconnectAttempts = 0;
            this.onConnected();
        });

        this.echo.connector.pusher.connection.bind('disconnected', () => {
            this.isConnected = false;
            this.onDisconnected();
        });

        this.echo.connector.pusher.connection.bind('error', (error) => {
            this.onError(error);
        });

        // Subscribe to user channel for general updates
        this.subscribeToUserChannel();
    }

    /**
     * Subscribe to user's private channel
     */
    subscribeToUserChannel() {
        const userId = this.getUserId();
        if (!userId) return;

        const channel = this.echo.private(`user.${userId}`);
        
        // Listen for command execution updates
        channel.listen('.command.execution.status', (data) => {
            this.handleExecutionUpdate(data);
        });

        // Listen for workflow execution updates
        channel.listen('.workflow.execution.status', (data) => {
            this.handleWorkflowUpdate(data);
        });

        // Listen for system notifications
        channel.listen('.system.notification', (data) => {
            this.handleSystemNotification(data);
        });
    }

    /**
     * Subscribe to specific execution channel
     */
    subscribeToExecution(executionId, callback) {
        const channel = this.echo.private(`execution.${executionId}`);
        
        const subscription = channel.listen('.command.execution.status', (data) => {
            callback(data);
            
            // Auto-unsubscribe when execution completes
            if (data.status === 'success' || data.status === 'failed') {
                setTimeout(() => {
                    this.unsubscribeFromExecution(executionId);
                }, 5000);
            }
        });

        this.subscriptions.set(`execution.${executionId}`, subscription);
        return subscription;
    }

    /**
     * Subscribe to workflow execution channel
     */
    subscribeToWorkflowExecution(executionId, callback) {
        const channel = this.echo.private(`workflow-execution.${executionId}`);
        
        const subscription = channel.listen('.workflow.execution.status', (data) => {
            callback(data);
            
            // Auto-unsubscribe when workflow completes
            if (data.status === 'success' || data.status === 'failed') {
                setTimeout(() => {
                    this.unsubscribeFromWorkflowExecution(executionId);
                }, 5000);
            }
        });

        this.subscriptions.set(`workflow-execution.${executionId}`, subscription);
        return subscription;
    }

    /**
     * Unsubscribe from execution updates
     */
    unsubscribeFromExecution(executionId) {
        const key = `execution.${executionId}`;
        if (this.subscriptions.has(key)) {
            this.echo.leave(`execution.${executionId}`);
            this.subscriptions.delete(key);
        }
    }

    /**
     * Unsubscribe from workflow execution updates
     */
    unsubscribeFromWorkflowExecution(executionId) {
        const key = `workflow-execution.${executionId}`;
        if (this.subscriptions.has(key)) {
            this.echo.leave(`workflow-execution.${executionId}`);
            this.subscriptions.delete(key);
        }
    }

    /**
     * Handle execution status update
     */
    handleExecutionUpdate(data) {
        // Dispatch custom event for UI updates
        window.dispatchEvent(new CustomEvent('execution:updated', {
            detail: data
        }));
    }

    /**
     * Handle workflow status update
     */
    handleWorkflowUpdate(data) {
        // Dispatch custom event for UI updates
        window.dispatchEvent(new CustomEvent('workflow:updated', {
            detail: data
        }));
    }

    /**
     * Handle system notifications
     */
    handleSystemNotification(data) {
        // Dispatch custom event for notifications
        window.dispatchEvent(new CustomEvent('system:notification', {
            detail: data
        }));
    }

    /**
     * Connection event handlers
     */
    onConnected() {
        console.log('WebSocket connected');
        window.dispatchEvent(new CustomEvent('websocket:connected'));
    }

    onDisconnected() {
        console.log('WebSocket disconnected');
        window.dispatchEvent(new CustomEvent('websocket:disconnected'));
        
        // Attempt reconnection
        this.attemptReconnect();
    }

    onError(error) {
        console.error('WebSocket error:', error);
        window.dispatchEvent(new CustomEvent('websocket:error', {
            detail: error
        }));
    }

    /**
     * Attempt to reconnect
     */
    attemptReconnect() {
        if (this.reconnectAttempts >= this.maxReconnectAttempts) {
            console.error('Max reconnection attempts reached');
            return;
        }

        this.reconnectAttempts++;
        const delay = Math.min(1000 * Math.pow(2, this.reconnectAttempts), 30000);
        
        console.log(`Attempting reconnection in ${delay}ms...`);
        
        setTimeout(() => {
            if (!this.isConnected) {
                this.echo.connector.pusher.connect();
            }
        }, delay);
    }

    /**
     * Get current user ID from API or storage
     */
    getUserId() {
        // This should be set when user logs in
        return localStorage.getItem('user_id');
    }

    /**
     * Clean up and disconnect
     */
    disconnect() {
        if (this.echo) {
            // Unsubscribe from all channels
            this.subscriptions.forEach((_, key) => {
                this.echo.leave(key);
            });
            this.subscriptions.clear();
            
            // Disconnect
            this.echo.disconnect();
            this.echo = null;
        }
        this.isConnected = false;
    }

    /**
     * Check connection status
     */
    isWebSocketConnected() {
        return this.isConnected;
    }
}

// Export for use
window.CommandIntelligenceWebSocket = CommandIntelligenceWebSocket;