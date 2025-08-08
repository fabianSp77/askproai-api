/**
 * NUCLEAR NAVIGATION FIX - Complete takeover of all navigation
 */

console.error('☢️☢️☢️ NUCLEAR NAVIGATION FIX ACTIVATED ☢️☢️☢️');

(function() {
    'use strict';
    
    // 1. Disable Livewire navigation completely
    if (window.Livewire) {
        console.log('Disabling Livewire navigation...');
        window.Livewire.stopListeningToNavigate = true;
        window.Livewire.navigate = function(url) {
            console.log('☢️ Livewire navigate intercepted, using direct navigation:', url);
            window.location.href = url;
        };
    }
    
    // 2. Disable Alpine.js on links
    if (window.Alpine) {
        console.log('Disabling Alpine on navigation elements...');
        const originalData = window.Alpine.data;
        window.Alpine.data = function(name, callback) {
            if (name.includes('navigation') || name.includes('link')) {
                console.log('☢️ Blocked Alpine component:', name);
                return originalData.call(this, name, () => ({}));
            }
            return originalData.call(this, name, callback);
        };
    }
    
    // 3. Global click interceptor with highest priority
    document.addEventListener('click', function(e) {
        const target = e.target;
        const link = target.closest('a, [wire\\:navigate], [href]');
        
        if (link) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            const url = link.href || link.getAttribute('href') || link.getAttribute('wire:navigate');
            
            if (url && url !== '#' && url !== 'javascript:void(0)') {
                console.log('☢️ NUCLEAR INTERCEPT - Navigating to:', url);
                window.location.href = url;
                return false;
            }
        }
    }, true); // Use capture phase
    
    // 4. Replace all link click handlers
    function nukeAllLinks() {
        document.querySelectorAll('a, [wire\\:navigate]').forEach(element => {
            // Remove all attributes that might interfere
            ['wire:click', 'x-on:click', '@click', 'onclick', 'wire:loading'].forEach(attr => {
                if (element.hasAttribute(attr)) {
                    console.log('☢️ Removing attribute:', attr, 'from', element);
                    element.removeAttribute(attr);
                }
            });
            
            // Get URL
            const url = element.href || element.getAttribute('href') || element.getAttribute('wire:navigate');
            
            if (url && url !== '#') {
                // Create overlay div that captures ALL clicks
                if (!element.querySelector('.nuclear-click-overlay')) {
                    const overlay = document.createElement('div');
                    overlay.className = 'nuclear-click-overlay';
                    overlay.style.cssText = `
                        position: absolute;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        z-index: 9999;
                        cursor: pointer;
                        background: transparent;
                    `;
                    
                    // Make parent relative if needed
                    if (getComputedStyle(element).position === 'static') {
                        element.style.position = 'relative';
                    }
                    
                    overlay.onclick = function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();
                        console.log('☢️ NUCLEAR OVERLAY CLICK - Navigating to:', url);
                        window.location.href = url;
                        return false;
                    };
                    
                    element.appendChild(overlay);
                }
            }
        });
    }
    
    // 5. Override native methods
    const originalPreventDefault = Event.prototype.preventDefault;
    Event.prototype.preventDefault = function() {
        if (this.type === 'click' && this.target && this.target.closest('a')) {
            console.log('☢️ Blocked preventDefault on link click');
            return;
        }
        return originalPreventDefault.call(this);
    };
    
    // 6. CSS Nuclear Option
    const style = document.createElement('style');
    style.textContent = `
        /* NUCLEAR CSS */
        * {
            pointer-events: auto !important;
        }
        
        a, button, [role="button"], [role="link"] {
            pointer-events: auto !important;
            cursor: pointer !important;
            user-select: auto !important;
            position: relative !important;
        }
        
        /* Kill ALL overlays except emergency menus */
        .fixed.inset-0:not(#emergency-nav-panel):not(#inline-emergency-menu):not(.nuclear-click-overlay) {
            pointer-events: none !important;
            display: none !important;
        }
        
        /* Make overlays visible for debugging */
        .nuclear-click-overlay {
            background: rgba(255, 0, 0, 0.1) !important;
        }
        
        /* Ensure sidebar links work */
        .fi-sidebar-nav a {
            display: block !important;
            pointer-events: auto !important;
        }
    `;
    document.head.appendChild(style);
    
    // 7. Run the nuclear option
    nukeAllLinks();
    
    // Keep running it
    setInterval(nukeAllLinks, 500);
    
    // Monitor for new elements
    const observer = new MutationObserver(() => {
        nukeAllLinks();
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    console.error('☢️☢️☢️ NUCLEAR NAVIGATION ACTIVE - ALL LINKS SHOULD HAVE RED OVERLAY ☢️☢️☢️');
    console.error('☢️ Click ANYWHERE on a link to navigate directly');
})();