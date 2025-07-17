/**
 * Service Worker Registration and Management
 */

export const ServiceWorkerManager = {
    registration: null,
    updateAvailable: false,
    
    /**
     * Register the service worker
     */
    async register() {
        if (!('serviceWorker' in navigator)) {
            console.log('[SW Manager] Service Worker not supported');
            return;
        }
        
        try {
            const registration = await navigator.serviceWorker.register('/business-service-worker.js', {
                scope: '/business/'
            });
            
            this.registration = registration;
            console.log('[SW Manager] Service Worker registered:', registration);
            
            // Check for updates
            this.checkForUpdates();
            
            // Listen for updates
            registration.addEventListener('updatefound', () => {
                this.handleUpdateFound(registration);
            });
            
            // Handle controller change
            navigator.serviceWorker.addEventListener('controllerchange', () => {
                this.handleControllerChange();
            });
            
            return registration;
        } catch (error) {
            console.error('[SW Manager] Service Worker registration failed:', error);
            throw error;
        }
    },
    
    /**
     * Unregister the service worker
     */
    async unregister() {
        if (!this.registration) return;
        
        try {
            const success = await this.registration.unregister();
            if (success) {
                console.log('[SW Manager] Service Worker unregistered');
                this.registration = null;
            }
            return success;
        } catch (error) {
            console.error('[SW Manager] Service Worker unregistration failed:', error);
            throw error;
        }
    },
    
    /**
     * Check for service worker updates
     */
    async checkForUpdates() {
        if (!this.registration) return;
        
        try {
            await this.registration.update();
        } catch (error) {
            console.error('[SW Manager] Update check failed:', error);
        }
    },
    
    /**
     * Handle when a new service worker is found
     */
    handleUpdateFound(registration) {
        const newWorker = registration.installing;
        if (!newWorker) return;
        
        newWorker.addEventListener('statechange', () => {
            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                // New service worker available
                this.updateAvailable = true;
                this.notifyUpdateAvailable();
            }
        });
    },
    
    /**
     * Handle controller change (new SW activated)
     */
    handleControllerChange() {
        // Reload the page when a new service worker takes control
        window.location.reload();
    },
    
    /**
     * Notify user about available update
     */
    notifyUpdateAvailable() {
        if (window.showUpdateNotification) {
            window.showUpdateNotification();
        }
        
        // Dispatch custom event
        window.dispatchEvent(new CustomEvent('sw-update-available'));
    },
    
    /**
     * Skip waiting and activate new service worker
     */
    async skipWaiting() {
        if (!this.registration?.waiting) return;
        
        // Send skip waiting message to service worker
        this.registration.waiting.postMessage({ type: 'SKIP_WAITING' });
    },
    
    /**
     * Clear all caches
     */
    async clearCache() {
        if (!navigator.serviceWorker.controller) return;
        
        return new Promise((resolve, reject) => {
            const messageChannel = new MessageChannel();
            
            messageChannel.port1.onmessage = (event) => {
                if (event.data.type === 'CACHE_CLEARED') {
                    resolve();
                } else {
                    reject(new Error('Failed to clear cache'));
                }
            };
            
            navigator.serviceWorker.controller.postMessage(
                { type: 'CLEAR_CACHE' },
                [messageChannel.port2]
            );
        });
    },
    
    /**
     * Request notification permission
     */
    async requestNotificationPermission() {
        if (!('Notification' in window)) {
            console.log('[SW Manager] Notifications not supported');
            return 'unsupported';
        }
        
        if (Notification.permission === 'granted') {
            return 'granted';
        }
        
        if (Notification.permission !== 'denied') {
            const permission = await Notification.requestPermission();
            return permission;
        }
        
        return 'denied';
    },
    
    /**
     * Subscribe to push notifications
     */
    async subscribeToPush() {
        if (!this.registration) {
            throw new Error('Service Worker not registered');
        }
        
        const permission = await this.requestNotificationPermission();
        if (permission !== 'granted') {
            throw new Error('Notification permission denied');
        }
        
        try {
            const subscription = await this.registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.urlBase64ToUint8Array(process.env.REACT_APP_VAPID_PUBLIC_KEY)
            });
            
            // Send subscription to server
            await this.sendSubscriptionToServer(subscription);
            
            return subscription;
        } catch (error) {
            console.error('[SW Manager] Push subscription failed:', error);
            throw error;
        }
    },
    
    /**
     * Unsubscribe from push notifications
     */
    async unsubscribeFromPush() {
        if (!this.registration) return;
        
        try {
            const subscription = await this.registration.pushManager.getSubscription();
            if (subscription) {
                await subscription.unsubscribe();
                await this.removeSubscriptionFromServer(subscription);
            }
        } catch (error) {
            console.error('[SW Manager] Push unsubscription failed:', error);
            throw error;
        }
    },
    
    /**
     * Convert VAPID key
     */
    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');
        
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        
        return outputArray;
    },
    
    /**
     * Send subscription to server
     */
    async sendSubscriptionToServer(subscription) {
        // TODO: Implement API call to save subscription
        console.log('[SW Manager] Sending subscription to server:', subscription);
    },
    
    /**
     * Remove subscription from server
     */
    async removeSubscriptionFromServer(subscription) {
        // TODO: Implement API call to remove subscription
        console.log('[SW Manager] Removing subscription from server:', subscription);
    },
    
    /**
     * Check if service worker is supported and active
     */
    isSupported() {
        return 'serviceWorker' in navigator;
    },
    
    /**
     * Check if push notifications are supported
     */
    isPushSupported() {
        return 'PushManager' in window && this.isSupported();
    },
    
    /**
     * Get current registration state
     */
    getState() {
        if (!this.registration) return 'unregistered';
        
        if (this.registration.installing) return 'installing';
        if (this.registration.waiting) return 'waiting';
        if (this.registration.active) return 'active';
        
        return 'unknown';
    }
};

// Auto-register on load in production
if (process.env.NODE_ENV === 'production' && typeof window !== 'undefined') {
    window.addEventListener('load', () => {
        ServiceWorkerManager.register().catch(console.error);
    });
}

export default ServiceWorkerManager;