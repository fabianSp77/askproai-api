/**
 * FORCE DIRECT NAVIGATION - Make ALL links work like emergency menu
 */

console.error('🔴🔴🔴 FORCING DIRECT NAVIGATION ON ALL LINKS 🔴🔴🔴');

(function() {
    'use strict';
    
    // Function to convert any element to direct navigation
    function forceDirectNavigation(element) {
        // Get the URL
        let url = null;
        
        // Try different ways to get URL
        if (element.href) {
            url = element.href;
        } else if (element.getAttribute('href')) {
            url = element.getAttribute('href');
        } else if (element.getAttribute('wire:navigate')) {
            url = element.getAttribute('wire:navigate');
        } else if (element.dataset.url) {
            url = element.dataset.url;
        }
        
        if (!url || url === '#' || url === 'javascript:void(0)') {
            return;
        }
        
        // Remove ALL existing event listeners by cloning
        const newElement = element.cloneNode(true);
        
        // Remove all event attributes
        ['onclick', 'wire:click', 'x-on:click', '@click'].forEach(attr => {
            newElement.removeAttribute(attr);
        });
        
        // Force styles
        newElement.style.cursor = 'pointer !important';
        newElement.style.pointerEvents = 'auto !important';
        newElement.style.userSelect = 'auto !important';
        newElement.style.position = 'relative';
        newElement.style.zIndex = '999';
        
        // Add direct navigation like emergency menu
        newElement.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            console.log('🔴 FORCED NAVIGATION TO:', url);
            
            // Use same method as emergency menu
            window.location.href = url;
            
            return false;
        }, true);
        
        // Also add mousedown as backup
        newElement.addEventListener('mousedown', function(e) {
            if (e.button === 0) { // Left click only
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                console.log('🔴 FORCED NAVIGATION (mousedown) TO:', url);
                window.location.href = url;
                
                return false;
            }
        }, true);
        
        // Replace original element
        if (element.parentNode) {
            element.parentNode.replaceChild(newElement, element);
        }
    }
    
    // Fix all links and buttons
    function fixAllNavigation() {
        console.log('Fixing all navigation elements...');
        
        // Fix all <a> tags
        document.querySelectorAll('a').forEach(link => {
            if (!link.dataset.forcedNav) {
                link.dataset.forcedNav = 'true';
                forceDirectNavigation(link);
            }
        });
        
        // Fix all buttons that look like navigation
        document.querySelectorAll('button').forEach(button => {
            const text = button.textContent.toLowerCase();
            const hasNavigationAttribute = button.hasAttribute('wire:navigate') || 
                                         button.hasAttribute('href') ||
                                         button.closest('a');
            
            if ((hasNavigationAttribute || text.includes('view') || text.includes('edit') || 
                 text.includes('show') || text.includes('open')) && !button.dataset.forcedNav) {
                button.dataset.forcedNav = 'true';
                
                // Try to find associated URL
                const parentLink = button.closest('a');
                if (parentLink && parentLink.href) {
                    button.dataset.url = parentLink.href;
                    forceDirectNavigation(button);
                }
            }
        });
        
        // Fix Filament specific navigation
        document.querySelectorAll('.fi-sidebar-nav-item a, .fi-breadcrumb-item a, .fi-ta-text-item a').forEach(link => {
            if (!link.dataset.forcedNav) {
                link.dataset.forcedNav = 'true';
                forceDirectNavigation(link);
            }
        });
    }
    
    // Kill all blocking event handlers
    function killBlockingHandlers() {
        // Override addEventListener for click events
        const originalAddEventListener = EventTarget.prototype.addEventListener;
        EventTarget.prototype.addEventListener = function(type, listener, options) {
            if (type === 'click' && this.tagName === 'A') {
                console.warn('🔴 Blocked click listener on link:', this.href);
                return;
            }
            return originalAddEventListener.call(this, type, listener, options);
        };
        
        // Remove pointer-events: none from everything
        const style = document.createElement('style');
        style.textContent = `
            * {
                pointer-events: auto !important;
                cursor: auto !important;
            }
            a, button, [role="button"], [role="link"] {
                pointer-events: auto !important;
                cursor: pointer !important;
            }
            /* Kill any overlays */
            .fixed.inset-0:not(#emergency-nav-panel):not(#inline-emergency-menu) {
                display: none !important;
            }
        `;
        document.head.appendChild(style);
    }
    
    // Run immediately
    killBlockingHandlers();
    fixAllNavigation();
    
    // Run repeatedly to catch dynamic content
    setInterval(fixAllNavigation, 1000);
    
    // Run on any DOM changes
    const observer = new MutationObserver(() => {
        fixAllNavigation();
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['href', 'wire:navigate']
    });
    
    // Global helper function
    window.forceNavigate = function(url) {
        console.log('🔴 Manual force navigate to:', url);
        window.location.href = url;
    };
    
    console.error('🔴 ALL LINKS SHOULD NOW USE DIRECT NAVIGATION LIKE EMERGENCY MENU');
})();