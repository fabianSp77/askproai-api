// MCP Real-time Integration with WebSocket/SSE
class MCPRealtimeClient {
    constructor(options = {}) {
        this.endpoint = options.endpoint || '/api/mcp/stream';
        this.reconnectInterval = options.reconnectInterval || 5000;
        this.maxReconnectAttempts = options.maxReconnectAttempts || 10;
        this.reconnectAttempts = 0;
        this.eventHandlers = new Map();
        this.isConnected = false;
        
        // Performance metrics
        this.metrics = {
            messagesReceived: 0,
            bytesReceived: 0,
            latency: [],
            errors: 0
        };
        
        this.initializeConnection();
    }
    
    initializeConnection() {
        // Use Server-Sent Events for real-time updates
        this.eventSource = new EventSource(this.endpoint, {
            withCredentials: true
        });
        
        this.eventSource.onopen = () => {
            this.isConnected = true;
            this.reconnectAttempts = 0;
            console.log('MCP Real-time connection established');
            this.emit('connected');
        };
        
        this.eventSource.onerror = (error) => {
            this.isConnected = false;
            this.metrics.errors++;
            console.error('MCP Real-time connection error:', error);
            this.handleReconnect();
        };
        
        // Handle different event types
        this.setupEventHandlers();
    }
    
    setupEventHandlers() {
        // System health updates
        this.eventSource.addEventListener('health', (event) => {
            const data = JSON.parse(event.data);
            this.updateMetrics(event.data.length);
            this.emit('health:update', data);
        });
        
        // Performance metrics
        this.eventSource.addEventListener('metrics', (event) => {
            const data = JSON.parse(event.data);
            this.updateMetrics(event.data.length);
            this.emit('metrics:update', data);
        });
        
        // Service status changes
        this.eventSource.addEventListener('service:status', (event) => {
            const data = JSON.parse(event.data);
            this.updateMetrics(event.data.length);
            this.emit('service:status', data);
        });
        
        // Error events
        this.eventSource.addEventListener('error:detected', (event) => {
            const data = JSON.parse(event.data);
            this.updateMetrics(event.data.length);
            this.emit('error:detected', data);
        });
        
        // Queue updates
        this.eventSource.addEventListener('queue:update', (event) => {
            const data = JSON.parse(event.data);
            this.updateMetrics(event.data.length);
            this.emit('queue:update', data);
        });
    }
    
    updateMetrics(bytesReceived) {
        this.metrics.messagesReceived++;
        this.metrics.bytesReceived += bytesReceived;
        
        // Calculate latency
        const timestamp = Date.now();
        if (this.lastMessageTime) {
            const latency = timestamp - this.lastMessageTime;
            this.metrics.latency.push(latency);
            
            // Keep only last 100 latency measurements
            if (this.metrics.latency.length > 100) {
                this.metrics.latency.shift();
            }
        }
        this.lastMessageTime = timestamp;
    }
    
    handleReconnect() {
        if (this.reconnectAttempts >= this.maxReconnectAttempts) {
            console.error('Max reconnection attempts reached');
            this.emit('connection:failed');
            return;
        }
        
        this.reconnectAttempts++;
        console.log(`Reconnecting... Attempt ${this.reconnectAttempts}`);
        
        setTimeout(() => {
            this.initializeConnection();
        }, this.reconnectInterval);
    }
    
    on(event, handler) {
        if (!this.eventHandlers.has(event)) {
            this.eventHandlers.set(event, []);
        }
        this.eventHandlers.get(event).push(handler);
    }
    
    off(event, handler) {
        if (this.eventHandlers.has(event)) {
            const handlers = this.eventHandlers.get(event);
            const index = handlers.indexOf(handler);
            if (index > -1) {
                handlers.splice(index, 1);
            }
        }
    }
    
    emit(event, data) {
        if (this.eventHandlers.has(event)) {
            this.eventHandlers.get(event).forEach(handler => {
                try {
                    handler(data);
                } catch (error) {
                    console.error(`Error in event handler for ${event}:`, error);
                }
            });
        }
    }
    
    getMetrics() {
        const avgLatency = this.metrics.latency.length > 0
            ? this.metrics.latency.reduce((a, b) => a + b, 0) / this.metrics.latency.length
            : 0;
            
        return {
            ...this.metrics,
            averageLatency: avgLatency,
            connectionStatus: this.isConnected ? 'connected' : 'disconnected'
        };
    }
    
    close() {
        if (this.eventSource) {
            this.eventSource.close();
            this.isConnected = false;
            this.emit('disconnected');
        }
    }
}

// Initialize on page load
window.MCPRealtimeClient = MCPRealtimeClient;

// Auto-initialize for MCP dashboard
document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('[data-mcp-dashboard]')) {
        window.mcpClient = new MCPRealtimeClient();
        
        // Update UI components
        window.mcpClient.on('health:update', (data) => {
            window.dispatchEvent(new CustomEvent('mcp:health:update', { detail: data }));
        });
        
        window.mcpClient.on('metrics:update', (data) => {
            window.dispatchEvent(new CustomEvent('mcp:metrics:update', { detail: data }));
        });
        
        window.mcpClient.on('error:detected', (data) => {
            window.dispatchEvent(new CustomEvent('mcp:error:detected', { detail: data }));
        });
    }
});