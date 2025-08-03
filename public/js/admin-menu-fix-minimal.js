/**
 * Minimal Admin Menu Fix
 * Fixes menu click issues without excessive console output
 */

(function() {
    'use strict';
    
    // Wait for DOM to be ready
    function ready(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }
    
    // Main fix function
    function fixMenuInteractions() {
        // Remove all blocking overlays
        const blockingElements = document.querySelectorAll(`
            [style*="pointer-events: none"],
            .pointer-events-none,
            .select-none
        `);
        
        blockingElements.forEach(el => {
            // Skip if it's supposed to be non-interactive
            if (!el.closest('.fi-modal-close-overlay') && 
                !el.closest('[aria-hidden="true"]')) {
                el.style.pointerEvents = '';
                el.classList.remove('pointer-events-none', 'select-none');
            }
        });
        
        // Fix all menu links and buttons
        const interactiveElements = document.querySelectorAll(`
            .fi-sidebar a,
            .fi-sidebar button,
            .fi-topbar a,
            .fi-topbar button,
            .fi-breadcrumbs a,
            [wire\\:navigate],
            [x-on\\:click],
            [onclick]
        `);
        
        interactiveElements.forEach(el => {
            // Ensure element is clickable
            el.style.pointerEvents = 'auto';
            el.style.cursor = 'pointer';
            
            // Remove any transform that might interfere
            if (el.style.transform && el.style.transform.includes('translate')) {
                el.style.transform = '';
            }
            
            // Ensure proper z-index
            const zIndex = window.getComputedStyle(el).zIndex;
            if (zIndex && parseInt(zIndex) < 0) {
                el.style.zIndex = '1';
            }
        });
        
        // Special handling for dropdown triggers
        document.querySelectorAll('[x-data*="dropdown"], [aria-haspopup="true"]').forEach(el => {
            el.style.pointerEvents = 'auto';
            el.style.cursor = 'pointer';
        });
        
        // Fix mobile menu toggle
        const mobileToggle = document.querySelector('.fi-topbar-open-sidebar-btn');
        if (mobileToggle) {
            mobileToggle.style.pointerEvents = 'auto';
            mobileToggle.style.cursor = 'pointer';
            mobileToggle.style.zIndex = '50';
        }
    }
    
    // Apply fixes
    ready(() => {
        // Initial fix
        fixMenuInteractions();
        
        // Reapply after dynamic updates
        if (window.Livewire) {
            Livewire.hook('message.processed', () => {
                requestAnimationFrame(fixMenuInteractions);
            });
        }
        
        // Monitor for Alpine.js updates
        document.addEventListener('alpine:initialized', () => {
            requestAnimationFrame(fixMenuInteractions);
        });
        
        // Minimal mutation observer for critical changes
        const observer = new MutationObserver(() => {
            requestAnimationFrame(fixMenuInteractions);
        });
        
        // Only observe the body with minimal config
        observer.observe(document.body, {
            childList: true,
            subtree: false,
            attributes: false
        });
    });
})();