import io from 'socket.io-client';
import { message } from 'antd';

class NotificationService {
    constructor() {
        this.socket = null;
        this.listeners = new Map();
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 1000;
        this.isConnected = false;
        this.notificationSound = new Audio('/sounds/notification.mp3');
        this.hasPermission = false;
        this.unreadCount = 0;
        this.callbacks = {
            onNotification: null,
            onUnreadCountChange: null,
            onConnectionChange: null
        };
    }

    async initialize(token, callbacks = {}) {
        this.callbacks = { ...this.callbacks, ...callbacks };
        
        // Request notification permission
        await this.requestPermission();
        
        // Initialize WebSocket connection
        this.connect(token);
        
        // Load initial unread count
        await this.fetchUnreadCount();
    }

    connect(token) {
        // WebSocket URL - in production this would be your actual WebSocket server
        const wsUrl = process.env.NODE_ENV === 'production' 
            ? 'wss://api.askproai.de' 
            : 'ws://localhost:6001';

        try {
            this.socket = io(wsUrl, {
                auth: { token },
                transports: ['websocket', 'polling'],
                reconnection: true,
                reconnectionAttempts: this.maxReconnectAttempts,
                reconnectionDelay: this.reconnectDelay,
                autoConnect: false // Don't connect automatically
            });

            // Only connect if WebSocket endpoint is configured
            // For now, disable WebSocket connection since server is not running
            // WebSocket notifications are currently disabled
            return;

            // When WebSocket server is available, remove the return above and uncomment:
            // this.socket.connect();
            // this.setupSocketListeners();
        } catch (error) {
            // WebSocket connection not available
        }
    }

    setupSocketListeners() {
        this.socket.on('connect', () => {
            // WebSocket connected
            this.isConnected = true;
            this.reconnectAttempts = 0;
            this.callbacks.onConnectionChange?.(true);
            
            // Subscribe to user's notification channel
            this.socket.emit('subscribe', 'notifications');
        });

        this.socket.on('disconnect', () => {
            // WebSocket disconnected
            this.isConnected = false;
            this.callbacks.onConnectionChange?.(false);
        });

        this.socket.on('error', (error) => {
            // WebSocket error - connection will be retried
        });

        this.socket.on('notification', (notification) => {
            this.handleNotification(notification);
        });

        this.socket.on('notifications.read', (data) => {
            if (data.all) {
                this.unreadCount = 0;
            } else {
                this.unreadCount = Math.max(0, this.unreadCount - 1);
            }
            this.callbacks.onUnreadCountChange?.(this.unreadCount);
        });

        this.socket.on('notifications.deleted', (data) => {
            if (data.wasUnread) {
                this.unreadCount = Math.max(0, this.unreadCount - 1);
                this.callbacks.onUnreadCountChange?.(this.unreadCount);
            }
        });
    }

    async handleNotification(notification) {
        // Update unread count
        this.unreadCount++;
        this.callbacks.onUnreadCountChange?.(this.unreadCount);

        // Call notification callback
        this.callbacks.onNotification?.(notification);

        // Show browser notification if permitted
        if (this.hasPermission && notification.priority !== 'low') {
            this.showBrowserNotification(notification);
        }

        // Play sound if enabled
        const preferences = await this.getPreferences();
        if (preferences?.sound) {
            this.playSound();
        }

        // Show in-app notification
        this.showInAppNotification(notification);
    }

    showBrowserNotification(notification) {
        if (!('Notification' in window) || Notification.permission !== 'granted') {
            return;
        }

        const { title, message, action_url } = notification.data;
        
        const browserNotification = new Notification(title || 'Neue Benachrichtigung', {
            body: message,
            icon: '/favicon.ico',
            badge: '/favicon.ico',
            tag: notification.id,
            requireInteraction: notification.priority === 'urgent',
            silent: false
        });

        browserNotification.onclick = () => {
            window.focus();
            if (action_url) {
                window.location.href = action_url;
            }
            browserNotification.close();
        };

        // Auto close after 10 seconds for non-urgent
        if (notification.priority !== 'urgent') {
            setTimeout(() => browserNotification.close(), 10000);
        }
    }

    showInAppNotification(notification) {
        const { title, message } = notification.data;
        const type = this.getNotificationType(notification.category);
        
        message[type]({
            content: title || message,
            duration: notification.priority === 'urgent' ? 0 : 5,
            onClick: () => {
                if (notification.action_url) {
                    window.location.href = notification.action_url;
                }
            }
        });
    }

    getNotificationType(category) {
        const typeMap = {
            'appointment': 'info',
            'call': 'success',
            'invoice': 'warning',
            'system': 'warning',
            'error': 'error'
        };
        return typeMap[category] || 'info';
    }

    async requestPermission() {
        if (!('Notification' in window)) {
            // Browser does not support notifications
            return false;
        }

        if (Notification.permission === 'granted') {
            this.hasPermission = true;
            return true;
        }

        if (Notification.permission !== 'denied') {
            const permission = await Notification.requestPermission();
            this.hasPermission = permission === 'granted';
            return this.hasPermission;
        }

        return false;
    }

    playSound() {
        try {
            this.notificationSound.play().catch(e => {
                // Could not play notification sound
            });
        } catch (e) {
            // Error playing notification sound
        }
    }

    async fetchUnreadCount() {
        try {
            const response = await fetch('/business/api/notifications?unread=true', {
                headers: {
                    'Accept': 'application/json'
                },
                credentials: 'include'
            });

            if (response.ok) {
                const data = await response.json();
                this.unreadCount = data.unread_count || 0;
                this.callbacks.onUnreadCountChange?.(this.unreadCount);
            }
        } catch (error) {
            // Error fetching unread count - will use cached value
        }
    }

    async markAsRead(notificationId) {
        try {
            const response = await fetch(`/business/api/notifications/${notificationId}/read`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                credentials: 'include'
            });

            if (response.ok) {
                this.socket?.emit('notification.read', { id: notificationId });
                return true;
            }
        } catch (error) {
            // Error marking notification as read
        }
        return false;
    }

    async markAllAsRead(category = null) {
        try {
            const url = '/business/api/notifications/read-all';
            const body = category ? { category } : {};
            
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                credentials: 'include',
                body: JSON.stringify(body)
            });

            if (response.ok) {
                this.socket?.emit('notifications.read', { all: true, category });
                return true;
            }
        } catch (error) {
            // Error marking all as read
        }
        return false;
    }

    async getPreferences() {
        try {
            const response = await fetch('/business/api/notifications/preferences', {
                headers: {
                    'Accept': 'application/json'
                },
                credentials: 'include'
            });

            if (response.ok) {
                const data = await response.json();
                return data.preferences;
            }
        } catch (error) {
            // Error fetching preferences - will use defaults
        }
        return null;
    }

    async updatePreferences(preferences) {
        try {
            const response = await fetch('/business/api/notifications/preferences', {
                method: 'PUT',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                credentials: 'include',
                body: JSON.stringify({ preferences })
            });

            return response.ok;
        } catch (error) {
            // Error updating preferences
            return false;
        }
    }

    disconnect() {
        if (this.socket) {
            this.socket.disconnect();
            this.socket = null;
        }
        this.isConnected = false;
        this.callbacks.onConnectionChange?.(false);
    }

    on(event, callback) {
        if (!this.listeners.has(event)) {
            this.listeners.set(event, new Set());
        }
        this.listeners.get(event).add(callback);
        
        // Return unsubscribe function
        return () => {
            const callbacks = this.listeners.get(event);
            if (callbacks) {
                callbacks.delete(callback);
            }
        };
    }

    emit(event, data) {
        const callbacks = this.listeners.get(event);
        if (callbacks) {
            callbacks.forEach(callback => callback(data));
        }
    }
}

// Create singleton instance
const notificationService = new NotificationService();

export default notificationService;