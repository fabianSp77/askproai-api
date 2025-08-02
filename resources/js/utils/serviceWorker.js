/**
 * Service Worker Registration and Management
 * DISABLED - Service worker functionality is currently disabled to avoid overhead
 */

export const ServiceWorkerManager = {
    registration: null,
    updateAvailable: false,
    
    /**
     * Register the service worker
     * DISABLED - Returns immediately without registering
     */
    async register() {
        console.log('[SW Manager] Service Worker registration disabled');
        
        // Check if there's an existing registration and unregister it
        if ('serviceWorker' in navigator) {
            const registrations = await navigator.serviceWorker.getRegistrations();
            for (const registration of registrations) {
                await registration.unregister();
                console.log('[SW Manager] Unregistered existing service worker');
            }
        }
        
        return null;
    },
    
    /**
     * Unregister the service worker
     */
    async unregister() {
        if (!('serviceWorker' in navigator)) return;
        
        try {
            const registrations = await navigator.serviceWorker.getRegistrations();
            for (const registration of registrations) {
                await registration.unregister();
            }
            console.log('[SW Manager] All service workers unregistered');
            this.registration = null;
            return true;
        } catch (error) {
            console.error('[SW Manager] Service Worker unregistration failed:', error);
            return false;
        }
    },
    
    /**
     * Check for service worker updates
     * DISABLED - No-op
     */
    async checkForUpdates() {
        // No-op
    },
    
    /**
     * Skip waiting and activate new service worker
     * DISABLED - No-op
     */
    async skipWaiting() {
        // No-op
    },
    
    /**
     * Clear all caches
     * DISABLED - No-op
     */
    async clearCache() {
        // No-op
    },
    
    /**
     * Request notification permission
     * DISABLED - Returns denied
     */
    async requestNotificationPermission() {
        return 'denied';
    },
    
    /**
     * Subscribe to push notifications
     * DISABLED - Throws error
     */
    async subscribeToPush() {
        throw new Error('Push notifications are disabled');
    },
    
    /**
     * Unsubscribe from push notifications
     * DISABLED - No-op
     */
    async unsubscribeFromPush() {
        // No-op
    },
    
    /**
     * Check if service worker is supported and active
     * DISABLED - Always returns false
     */
    isSupported() {
        return false;
    },
    
    /**
     * Check if push notifications are supported
     * DISABLED - Always returns false
     */
    isPushSupported() {
        return false;
    },
    
    /**
     * Get current registration state
     * DISABLED - Always returns 'disabled'
     */
    getState() {
        return 'disabled';
    }
};

// Auto-unregister any existing service workers on load
if (typeof window !== 'undefined') {
    window.addEventListener('load', () => {
        ServiceWorkerManager.unregister().catch(console.error);
    });
}

// Listen for unregister message from service worker
if (typeof window !== 'undefined' && 'serviceWorker' in navigator) {
    navigator.serviceWorker.addEventListener('message', (event) => {
        if (event.data && event.data.type === 'UNREGISTER_SW') {
            ServiceWorkerManager.unregister();
        }
    });
}

export default ServiceWorkerManager;