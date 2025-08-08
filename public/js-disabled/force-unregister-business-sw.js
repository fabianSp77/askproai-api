/**
 * Force Unregister Business Portal Service Worker
 * This script removes any registered service workers that might be causing cache issues
 */

(function() {
    'use strict';
    
    console.log('[SW Cleanup] Starting service worker cleanup...');
    
    // Check if service workers are supported
    if (!('serviceWorker' in navigator)) {
        console.log('[SW Cleanup] Service workers not supported in this browser');
        return;
    }
    
    // Get all service worker registrations
    navigator.serviceWorker.getRegistrations()
        .then(function(registrations) {
            if (registrations.length === 0) {
                console.log('[SW Cleanup] No service workers found');
                return;
            }
            
            console.log('[SW Cleanup] Found ' + registrations.length + ' service worker(s)');
            
            // Unregister each service worker
            var unregisterPromises = registrations.map(function(registration) {
                console.log('[SW Cleanup] Unregistering:', registration.scope);
                return registration.unregister();
            });
            
            // Wait for all unregistrations to complete
            return Promise.all(unregisterPromises);
        })
        .then(function() {
            console.log('[SW Cleanup] All service workers unregistered successfully');
            
            // Clear all caches
            if ('caches' in window) {
                return caches.keys();
            }
            return [];
        })
        .then(function(cacheNames) {
            if (cacheNames.length === 0) {
                console.log('[SW Cleanup] No caches found');
                return;
            }
            
            console.log('[SW Cleanup] Found ' + cacheNames.length + ' cache(s)');
            
            // Delete each cache
            var deletePromises = cacheNames.map(function(cacheName) {
                console.log('[SW Cleanup] Deleting cache:', cacheName);
                return caches.delete(cacheName);
            });
            
            return Promise.all(deletePromises);
        })
        .then(function() {
            console.log('[SW Cleanup] All caches cleared successfully');
            
            // FIXED: Don't clear localStorage/sessionStorage - this breaks authentication!
            // Only clear service worker related data
            try {
                // Only remove service worker specific keys if needed
                const swKeys = Object.keys(localStorage).filter(key => 
                    key.startsWith('sw-') || key.startsWith('workbox-')
                );
                swKeys.forEach(key => {
                    localStorage.removeItem(key);
                    console.log('[SW Cleanup] Removed SW key:', key);
                });
                
                if (swKeys.length === 0) {
                    console.log('[SW Cleanup] No SW-specific localStorage keys found');
                }
            } catch (e) {
                console.error('[SW Cleanup] Failed to clean SW data:', e);
            }
            
            // Show success message to user
            if (window.showNotification) {
                window.showNotification('Service Worker bereinigt! Bitte die Seite neu laden.', 'success');
            } else {
                console.log('[SW Cleanup] ✅ Cleanup complete! Please refresh the page.');
            }
        })
        .catch(function(error) {
            console.error('[SW Cleanup] Error during cleanup:', error);
            
            if (window.showNotification) {
                window.showNotification('Fehler beim Bereinigen des Service Workers', 'error');
            }
        });
        
    // FIXED: Don't block new service worker registrations
    // This was causing issues with legitimate functionality
    console.log('[SW Cleanup] Service worker cleanup complete - registrations are allowed');
})();