// Widget Display Fix - Ensure widgets are properly rendered
(function() {
    'use strict';
    
    console.log('[Widget Fix] Starting widget display fix...');
    
    // Function to wait for Alpine
    function waitForAlpine(callback) {
        if (window.Alpine && window.Alpine.version) {
            callback();
        } else {
            setTimeout(() => waitForAlpine(callback), 100);
        }
    }
    
    // Function to force widget visibility
    function forceWidgetVisibility() {
        console.log('[Widget Fix] Forcing widget visibility...');
        
        // Target all widget containers
        const selectors = [
            '.fi-page-header-widgets',
            '.fi-widgets-container',
            '.fi-widgets-grid',
            '.fi-wi',
            '.fi-widget',
            '[class*="fi-wi-"]',
            '[wire\\:id]',
            '[x-data]'
        ];
        
        selectors.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(el => {
                // Remove any hiding classes
                el.classList.remove('hidden', 'invisible', 'opacity-0');
                
                // Force display
                el.style.display = '';
                el.style.visibility = 'visible';
                el.style.opacity = '1';
                
                // Remove x-cloak
                el.removeAttribute('x-cloak');
                
                // If it's a Livewire component, ensure it's initialized
                if (el.hasAttribute('wire:id')) {
                    el.style.minHeight = '1px';
                }
            });
        });
        
        // Special handling for empty containers
        document.querySelectorAll('.fi-page > div:empty').forEach(el => {
            el.style.minHeight = '100px';
            el.style.border = '2px dashed red';
            el.innerHTML = '<div style="text-align: center; padding: 20px; color: red;">Widget container detected (empty)</div>';
        });
    }
    
    // Function to reinitialize Alpine components if needed
    function reinitializeAlpineComponents() {
        console.log('[Widget Fix] Checking Alpine components...');
        
        document.querySelectorAll('[x-data]:not([x-ignore])').forEach(el => {
            if (!el._x_dataStack) {
                console.log('[Widget Fix] Reinitializing Alpine component:', el);
                
                try {
                    // Get the x-data attribute
                    const xDataAttr = el.getAttribute('x-data');
                    if (xDataAttr) {
                        // Force Alpine to process this element
                        el.setAttribute('x-ignore', 'temp');
                        el.removeAttribute('x-ignore');
                        
                        // Trigger Alpine to reprocess
                        if (window.Alpine && window.Alpine.initializeComponent) {
                            window.Alpine.initializeComponent(el);
                        }
                    }
                } catch (e) {
                    console.error('[Widget Fix] Error reinitializing component:', e);
                }
            }
        });
    }
    
    // Main initialization
    function init() {
        console.log('[Widget Fix] Initializing...');
        
        // Force visibility immediately
        forceWidgetVisibility();
        
        // Wait for Alpine to be ready
        waitForAlpine(() => {
            console.log('[Widget Fix] Alpine ready, applying fixes...');
            
            // Force visibility again after Alpine init
            forceWidgetVisibility();
            
            // Reinitialize components if needed
            reinitializeAlpineComponents();
            
            // Monitor for new elements
            const observer = new MutationObserver((mutations) => {
                let shouldUpdate = false;
                
                mutations.forEach(mutation => {
                    if (mutation.type === 'childList') {
                        mutation.addedNodes.forEach(node => {
                            if (node.nodeType === 1 && (
                                node.classList?.contains('fi-wi') ||
                                node.hasAttribute('wire:id') ||
                                node.hasAttribute('x-data')
                            )) {
                                shouldUpdate = true;
                            }
                        });
                    }
                });
                
                if (shouldUpdate) {
                    console.log('[Widget Fix] New widgets detected, updating...');
                    forceWidgetVisibility();
                    reinitializeAlpineComponents();
                }
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
            
            console.log('[Widget Fix] Monitoring for changes...');
        });
    }
    
    // Initialize on multiple events to ensure it runs
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Also run on Livewire events
    document.addEventListener('livewire:navigated', () => {
        console.log('[Widget Fix] Livewire navigated, reapplying fixes...');
        setTimeout(forceWidgetVisibility, 100);
    });
    
    document.addEventListener('livewire:initialized', () => {
        console.log('[Widget Fix] Livewire initialized, reapplying fixes...');
        setTimeout(forceWidgetVisibility, 100);
    });
    
    // Expose function globally for debugging
    window.forceWidgetVisibility = forceWidgetVisibility;
    window.debugWidgets = function() {
        console.log('=== Widget Debug Info ===');
        console.log('Alpine:', window.Alpine ? 'Loaded' : 'Not loaded');
        console.log('Livewire:', window.Livewire ? 'Loaded' : 'Not loaded');
        
        const widgetContainers = document.querySelectorAll('.fi-page-header-widgets, .fi-widgets-container, .fi-widgets-grid');
        console.log('Widget containers found:', widgetContainers.length);
        
        const widgets = document.querySelectorAll('.fi-wi, .fi-widget, [class*="fi-wi-"]');
        console.log('Widgets found:', widgets.length);
        
        widgets.forEach((widget, index) => {
            console.log(`Widget ${index + 1}:`, {
                classes: widget.className,
                display: window.getComputedStyle(widget).display,
                visibility: window.getComputedStyle(widget).visibility,
                opacity: window.getComputedStyle(widget).opacity,
                hasWireId: widget.hasAttribute('wire:id'),
                hasXData: widget.hasAttribute('x-data')
            });
        });
    };
})();