/**
 * Login Overlay Remover
 * Removes any blocking overlays on the login page
 */

(function() {
    'use strict';
    
    function removeOverlays() {
        // Find all modal overlays
        const overlays = document.querySelectorAll('.fi-modal-close-overlay, [class*="overlay"], .fi-modal[x-data]');
        
        overlays.forEach(overlay => {
            console.log('[OverlayRemover] Found overlay:', overlay.className);
            
            // Check if it's blocking the page
            const styles = window.getComputedStyle(overlay);
            if (styles.position === 'fixed' || styles.position === 'absolute') {
                if (styles.zIndex > 1) {
                    console.log('[OverlayRemover] Removing blocking overlay:', overlay);
                    overlay.style.display = 'none';
                    overlay.style.pointerEvents = 'none';
                }
            }
        });
        
        // Find any Alpine.js modals that might be open
        const alpineModals = document.querySelectorAll('[x-data*="isOpen"][x-show="isOpen"]');
        alpineModals.forEach(modal => {
            console.log('[OverlayRemover] Found Alpine modal, attempting to close');
            // Try to close it through Alpine
            if (modal.__x) {
                modal.__x.$data.isOpen = false;
            }
        });
        
        // Ensure form is clickable
        const form = document.querySelector('form[wire\\:submit="authenticate"]');
        if (form) {
            form.style.position = 'relative';
            form.style.zIndex = '100';
            console.log('[OverlayRemover] Made form clickable');
        }
        
        // Make all inputs clickable
        const inputs = document.querySelectorAll('input, button, a');
        inputs.forEach(input => {
            input.style.pointerEvents = 'auto';
        });
    }
    
    // Run immediately
    removeOverlays();
    
    // Run after DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', removeOverlays);
    }
    
    // Run after Alpine initializes
    document.addEventListener('alpine:init', removeOverlays);
    document.addEventListener('alpine:initialized', removeOverlays);
    
    // Run after Livewire loads
    if (window.Livewire) {
        Livewire.hook('message.processed', removeOverlays);
    }
    
    // Run periodically for dynamic content
    let attempts = 0;
    const interval = setInterval(() => {
        removeOverlays();
        attempts++;
        if (attempts > 10) {
            clearInterval(interval);
        }
    }, 500);
    
})();