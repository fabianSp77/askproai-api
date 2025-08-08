// NUCLEAR UNBLOCK EVERYTHING - Issue #509
// Radikale Lösung um ALLE Click-Blocker zu entfernen

console.error('🔥🔥🔥 NUCLEAR UNBLOCK STARTING - REMOVING ALL BLOCKERS 🔥🔥🔥');

(function() {
    'use strict';
    
    // 1. OVERRIDE ALL EVENT METHODS
    const originalPreventDefault = Event.prototype.preventDefault;
    const originalStopPropagation = Event.prototype.stopPropagation;
    const originalStopImmediatePropagation = Event.prototype.stopImmediatePropagation;
    
    Event.prototype.preventDefault = function() {
        console.warn('[NUCLEAR] preventDefault BLOCKED on', this.type, 'event');
        // Don't call original - let everything through
    };
    
    Event.prototype.stopPropagation = function() {
        console.warn('[NUCLEAR] stopPropagation BLOCKED on', this.type, 'event');
        // Don't call original - let events bubble
    };
    
    Event.prototype.stopImmediatePropagation = function() {
        console.warn('[NUCLEAR] stopImmediatePropagation BLOCKED on', this.type, 'event');
        // Don't call original
    };
    
    // 2. REMOVE ALL EXISTING EVENT LISTENERS
    function removeAllEventListeners() {
        // Get all elements
        const allElements = document.querySelectorAll('*');
        
        allElements.forEach(element => {
            // Clone to remove listeners
            const clone = element.cloneNode(true);
            if (element.parentNode) {
                element.parentNode.replaceChild(clone, element);
            }
        });
        
        console.error('[NUCLEAR] Removed all event listeners from', allElements.length, 'elements');
    }
    
    // 3. FORCE ALL ELEMENTS TO BE CLICKABLE
    function forceClickable() {
        const style = document.createElement('style');
        style.id = 'nuclear-unblock-style';
        style.innerHTML = `
            * {
                pointer-events: auto !important;
                cursor: auto !important;
                user-select: auto !important;
                -webkit-user-select: auto !important;
                -moz-user-select: auto !important;
                -ms-user-select: auto !important;
                opacity: 1 !important;
                visibility: visible !important;
                z-index: auto !important;
            }
            
            a, button, input, select, textarea,
            [role="button"], [role="link"],
            [wire\\:click], [wire\\:navigate],
            [x-on\\:click], [onclick] {
                pointer-events: auto !important;
                cursor: pointer !important;
                display: block !important;
                position: relative !important;
                z-index: 999 !important;
            }
            
            /* Remove ALL overlays */
            .fixed.inset-0,
            [class*="overlay"],
            [class*="backdrop"],
            [class*="modal-"] {
                display: none !important;
                pointer-events: none !important;
            }
            
            /* Ensure forms work */
            form, form * {
                pointer-events: auto !important;
            }
            
            /* Fix specific Filament elements */
            .fi-sidebar,
            .fi-sidebar *,
            .fi-simple-page,
            .fi-simple-page * {
                pointer-events: auto !important;
                position: relative !important;
                z-index: 1 !important;
            }
            
            /* Nuclear warning */
            body::before {
                content: "🔥 NUCLEAR MODE ACTIVE - ALL BLOCKERS REMOVED 🔥" !important;
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                background: #ff0000 !important;
                color: white !important;
                text-align: center !important;
                padding: 10px !important;
                z-index: 999999 !important;
                font-weight: bold !important;
                font-size: 16px !important;
            }
        `;
        
        // Remove old style if exists
        const oldStyle = document.getElementById('nuclear-unblock-style');
        if (oldStyle) oldStyle.remove();
        
        document.head.appendChild(style);
    }
    
    // 4. INTERCEPT ALL CLICKS AND FORCE THEM THROUGH
    function interceptClicks() {
        document.addEventListener('click', function(e) {
            const target = e.target;
            console.log('[NUCLEAR] Click on:', target.tagName, target.className);
            
            // Force navigation for links
            if (target.tagName === 'A' && target.href) {
                console.error('[NUCLEAR] Forcing navigation to:', target.href);
                window.location.href = target.href;
                return;
            }
            
            // Force form submission
            if (target.type === 'submit' || target.tagName === 'BUTTON') {
                const form = target.closest('form');
                if (form) {
                    console.error('[NUCLEAR] Forcing form submission');
                    form.submit();
                }
            }
        }, true); // Use capture phase
    }
    
    // 5. FIX SPECIFIC FRAMEWORKS
    function fixFrameworks() {
        // Fix Alpine.js
        if (window.Alpine) {
            console.error('[NUCLEAR] Patching Alpine.js');
            const originalAlpineData = window.Alpine.data;
            window.Alpine.data = function(name, callback) {
                return originalAlpineData.call(this, name, function() {
                    const component = callback.apply(this, arguments);
                    // Override any click handlers
                    Object.keys(component).forEach(key => {
                        if (typeof component[key] === 'function' && key.includes('click')) {
                            const original = component[key];
                            component[key] = function(e) {
                                console.warn('[NUCLEAR] Alpine click handler intercepted');
                                return original.call(this, e);
                            };
                        }
                    });
                    return component;
                });
            };
        }
        
        // Fix Livewire
        if (window.Livewire) {
            console.error('[NUCLEAR] Patching Livewire');
            window.Livewire.hook('message.sent', (message, component) => {
                console.log('[NUCLEAR] Livewire message sent:', message);
            });
        }
    }
    
    // 6. CONTINUOUS MONITORING
    function startMonitoring() {
        // Re-apply fixes every second
        setInterval(() => {
            // Check if anything is blocking
            const blockedElements = document.querySelectorAll('[style*="pointer-events: none"]');
            if (blockedElements.length > 0) {
                console.error('[NUCLEAR] Found', blockedElements.length, 'blocked elements, fixing...');
                blockedElements.forEach(el => {
                    el.style.pointerEvents = 'auto';
                });
            }
        }, 1000);
        
        // Monitor for new elements
        const observer = new MutationObserver((mutations) => {
            mutations.forEach(mutation => {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === 1) { // Element node
                        node.style.pointerEvents = 'auto';
                    }
                });
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['style', 'disabled']
        });
    }
    
    // 7. EMERGENCY OVERRIDES
    window.NUCLEAR_UNBLOCK = {
        forceClick: (selector) => {
            const element = document.querySelector(selector);
            if (element) {
                console.error('[NUCLEAR] Force clicking:', element);
                element.click();
            }
        },
        
        removeAllBlockers: () => {
            removeAllEventListeners();
            forceClickable();
        },
        
        status: () => {
            const blocked = document.querySelectorAll('[style*="pointer-events: none"]').length;
            const disabled = document.querySelectorAll('[disabled]').length;
            console.table({
                'Blocked elements': blocked,
                'Disabled elements': disabled,
                'Total elements': document.querySelectorAll('*').length
            });
        }
    };
    
    // EXECUTE ALL FIXES
    console.error('[NUCLEAR] Executing all fixes...');
    
    // Wait for DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            removeAllEventListeners();
            forceClickable();
            interceptClicks();
            fixFrameworks();
            startMonitoring();
        });
    } else {
        removeAllEventListeners();
        forceClickable();
        interceptClicks();
        fixFrameworks();
        startMonitoring();
    }
    
    console.error('🔥🔥🔥 NUCLEAR UNBLOCK LOADED - USE window.NUCLEAR_UNBLOCK FOR MANUAL CONTROL 🔥🔥🔥');
})();