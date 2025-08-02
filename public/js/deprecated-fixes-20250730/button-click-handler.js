/**
 * Global Button Click Handler Fix
 * Fixes double-click requirement issue in Admin Panel
 * 
 * Problem: Multiple event handlers are preventing normal button clicks
 * Solution: Ensures proper event propagation and prevents duplicate handlers
 */

(function() {
    'use strict';
    
    console.log('[ButtonClickFix] Initializing...');
    
    // Skip on login page to prevent form submission issues
    if (window.location.pathname.includes('/admin/login')) {
        console.log('[ButtonClickFix] Skipping on login page');
        return;
    }
    
    // Track initialized elements to prevent duplicate handlers
    const initializedElements = new WeakSet();
    
    // Fix function to ensure buttons work on single click
    function fixButtonClickHandlers() {
        // Fix all submit buttons
        document.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(button => {
            if (!initializedElements.has(button)) {
                initializedElements.add(button);
                
                // Add loading state on click
                button.addEventListener('click', function(e) {
                    // Don't interfere with wire:click
                    if (this.hasAttribute('wire:click')) {
                        return;
                    }
                    
                    // If it's a form submit button, add loading state
                    const form = this.closest('form');
                    if (form && !this.disabled) {
                        // Add loading state
                        this.disabled = true;
                        const originalText = this.textContent;
                        this.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Verarbeitung...';
                        
                        // Re-enable after a timeout (in case of validation errors)
                        setTimeout(() => {
                            this.disabled = false;
                            this.textContent = originalText;
                        }, 5000);
                    }
                }, { capture: false });
            }
        });
        
        // Fix wire:click buttons
        document.querySelectorAll('[wire\\:click]').forEach(button => {
            if (!initializedElements.has(button)) {
                initializedElements.add(button);
                
                // Ensure the click event reaches Livewire
                button.addEventListener('click', function(e) {
                    console.log('[ButtonClickFix] Wire:click triggered:', this.getAttribute('wire:click'));
                    // Let the event bubble up naturally
                }, { capture: false });
            }
        });
        
        // Fix Alpine.js @click buttons
        document.querySelectorAll('[\\@click], [x-on\\:click]').forEach(button => {
            if (!initializedElements.has(button)) {
                initializedElements.add(button);
                
                button.addEventListener('click', function(e) {
                    console.log('[ButtonClickFix] Alpine click triggered:', this.getAttribute('@click') || this.getAttribute('x-on:click'));
                    // Let Alpine handle it
                }, { capture: false });
            }
        });
    }
    
    // Remove problematic global click handlers that prevent propagation
    function removeProblematicHandlers() {
        // Override the problematic page transition handler
        const originalAddEventListener = EventTarget.prototype.addEventListener;
        EventTarget.prototype.addEventListener = function(type, listener, options) {
            // Skip page transition handlers that preventDefault on all links
            if (type === 'click' && listener.toString().includes('page-transition-exit-active')) {
                console.warn('[ButtonClickFix] Blocked problematic page transition handler');
                return;
            }
            return originalAddEventListener.call(this, type, listener, options);
        };
    }
    
    // Initialize fixes
    function initialize() {
        removeProblematicHandlers();
        fixButtonClickHandlers();
        
        // Re-run after Livewire updates
        if (window.Livewire) {
            Livewire.hook('message.processed', () => {
                setTimeout(fixButtonClickHandlers, 100);
            });
        }
        
        // Re-run after Alpine initializes
        document.addEventListener('alpine:initialized', () => {
            setTimeout(fixButtonClickHandlers, 100);
        });
        
        // Observe DOM changes
        const observer = new MutationObserver((mutations) => {
            let shouldFix = false;
            mutations.forEach(mutation => {
                if (mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeType === 1 && (
                            node.matches?.('button, [wire\\:click], [\\@click], [x-on\\:click]') ||
                            node.querySelector?.('button, [wire\\:click], [\\@click], [x-on\\:click]')
                        )) {
                            shouldFix = true;
                        }
                    });
                }
            });
            
            if (shouldFix) {
                setTimeout(fixButtonClickHandlers, 100);
            }
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    // Start initialization
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }
    
    console.log('[ButtonClickFix] Initialized successfully');
})();