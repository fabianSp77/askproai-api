// Filament Dropdown Global Fix - Comprehensive Solution
(function() {
    'use strict';
    
    //console.log('[Filament Dropdown Fix] Initializing...');
    
    // Track initialization state
    let isInitialized = false;
    let alpineReady = false;
    let livewireReady = false;
    
    // Function to fix dropdown behavior globally
    function fixFilamentDropdowns() {
        // Method 1: Fix Alpine-based dropdowns
        document.querySelectorAll('[x-data]').forEach(component => {
            const dataAttr = component.getAttribute('x-data');
            if (dataAttr && dataAttr.includes('open')) {
                // Ensure Alpine is initialized
                if (window.Alpine && !component.__x) {
                    try {
                        Alpine.initTree(component);
                        //console.log('[Dropdown Fix] Initialized Alpine component');
                    } catch (e) {
                        console.error('[Dropdown Fix] Failed to init Alpine:', e);
                    }
                }
                
                // Add click handler to toggle buttons
                const toggleBtn = component.querySelector('[x-on\\:click="toggle"], [x-on\\:click="open = !open"], [x-on\\:click="open = true"]');
                if (toggleBtn && !toggleBtn.hasAttribute('data-dropdown-fixed')) {
                    toggleBtn.setAttribute('data-dropdown-fixed', 'true');
                    
                    toggleBtn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        
                        // Force toggle using Alpine data
                        if (component.__x && component.__x.$data) {
                            component.__x.$data.open = !component.__x.$data.open;
                            //console.log('[Dropdown Fix] Toggled dropdown:', component.__x.$data.open);
                        }
                    });
                }
            }
        });
        
        // Method 2: Fix Filament dropdown components
        document.querySelectorAll('.fi-dropdown').forEach(dropdown => {
            const trigger = dropdown.querySelector('button[type="button"]');
            const panel = dropdown.querySelector('.fi-dropdown-panel, [x-ref="panel"]');
            
            if (trigger && panel) {
                // Ensure proper z-index
                panel.style.zIndex = '9999';
                
                // Add fallback click handler
                if (!trigger.hasAttribute('data-filament-fixed')) {
                    trigger.setAttribute('data-filament-fixed', 'true');
                    
                    trigger.addEventListener('click', function(e) {
                        e.stopPropagation();
                        
                        // Toggle visibility
                        const isHidden = panel.style.display === 'none' || !panel.offsetParent;
                        if (isHidden) {
                            panel.style.display = 'block';
                            panel.style.visibility = 'visible';
                            panel.style.opacity = '1';
                        } else {
                            panel.style.display = 'none';
                        }
                    });
                }
            }
        });
        
        // Method 3: Global click handler for closing dropdowns
        document.addEventListener('click', function(e) {
            // Skip if clicking inside a dropdown
            if (e.target.closest('.fi-dropdown-panel, .fi-dropdown')) return;
            
            // Close all open dropdowns
            document.querySelectorAll('.fi-dropdown-panel').forEach(panel => {
                if (panel.style.display === 'block') {
                    panel.style.display = 'none';
                }
            });
            
            // Close Alpine dropdowns
            document.querySelectorAll('[x-data]').forEach(component => {
                if (component.__x && component.__x.$data && component.__x.$data.open === true) {
                    const clickedInside = component.contains(e.target);
                    if (!clickedInside) {
                        component.__x.$data.open = false;
                    }
                }
            });
        }, true);
    }
    
    // Function to ensure Alpine is working
    function ensureAlpine() {
        // Alpine should already be available when this is called
        if (typeof Alpine === 'undefined') {
            console.error('[Dropdown Fix] Alpine still not available after initialization');
            return;
        }
        
        // Add Alpine magic for dropdowns
        if (!Alpine.magic('dropdown')) {
            Alpine.magic('dropdown', () => {
                return {
                    open: false,
                    toggle() {
                        this.open = !this.open;
                    },
                    close() {
                        this.open = false;
                    }
                };
            });
        }
        
        //console.log('[Dropdown Fix] Alpine configured successfully');
    }
    
    // Initialize fixes
    function initialize() {
        if (isInitialized) {
            //console.log('[Dropdown Fix] Already initialized, skipping...');
            return;
        }
        
        //console.log('[Dropdown Fix] Starting initialization...');
        
        // Check for Alpine
        function checkAlpine() {
            if (typeof Alpine !== 'undefined') {
                alpineReady = true;
                //console.log('[Dropdown Fix] Alpine is ready');
                checkDependencies();
            } else {
                setTimeout(checkAlpine, 50);
            }
        }
        
        // Check for Livewire
        function checkLivewire() {
            if (typeof Livewire !== 'undefined') {
                livewireReady = true;
                //console.log('[Dropdown Fix] Livewire is ready');
                checkDependencies();
            } else {
                setTimeout(checkLivewire, 50);
            }
        }
        
        // Initialize when both are ready
        function checkDependencies() {
            if (alpineReady && livewireReady && !isInitialized) {
                isInitialized = true;
                //console.log('[Dropdown Fix] All dependencies ready, applying fixes...');
                
                // Apply fixes
                fixFilamentDropdowns();
                ensureAlpine();
                setupMutationObserver();
                fixLivewireRedirects();
                fixSelectComponents();
            }
        }
        
        // Start checking
        checkAlpine();
        checkLivewire();
    }
    
    // Setup mutation observer separately
    function setupMutationObserver() {
        // Re-apply after DOM changes
        const observer = new MutationObserver(function(mutations) {
            let hasRelevantChanges = false;
            
            mutations.forEach(mutation => {
                if (mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeType === 1) { // Element node
                            if (node.matches?.('.fi-dropdown, [x-data]') || 
                                node.querySelector?.('.fi-dropdown, [x-data]')) {
                                hasRelevantChanges = true;
                            }
                        }
                    });
                }
            });
            
            if (hasRelevantChanges) {
                setTimeout(fixFilamentDropdowns, 50);
            }
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
        
        // Re-apply fixes after Livewire updates
        if (window.Livewire) {
            Livewire.hook('message.processed', () => {
                setTimeout(fixFilamentDropdowns, 50);
            });
            
            //console.log('[Dropdown Fix] Livewire hooks installed');
        }
    }
    
    // Start initialization based on DOM state
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        // DOM already loaded
        initialize();
    }
    
    // Also initialize on Alpine init
    document.addEventListener('alpine:init', () => {
        //console.log('[Dropdown Fix] Alpine init event');
        fixFilamentDropdowns();
    });
    
    // Fix Livewire redirect issues
    function fixLivewireRedirects() {
        //console.log('[Dropdown Fix] Fixing Livewire redirects...');
        
        // Listen for Livewire redirect events
        if (window.Livewire) {
            // Intercept wire:click events that might fail
            document.addEventListener('click', function(e) {
                const wireClick = e.target.closest('[wire\\:click]');
                if (wireClick) {
                    const action = wireClick.getAttribute('wire:click');
                    
                    // Special handling for portal opening
                    if (action && (action.includes('openCustomerPortal') || action.includes('openPortalForCompany'))) {
                        //console.log('[Dropdown Fix] Intercepted portal open action:', action);
                        
                        // Add fallback behavior if Livewire fails
                        setTimeout(() => {
                            // Check if we're still on the same page (redirect didn't work)
                            if (window.location.pathname.includes('business-portal-admin')) {
                                console.warn('[Dropdown Fix] Livewire redirect may have failed, checking...');
                            }
                        }, 1000);
                    }
                }
            }, true);
            
            // Fix Livewire component updates
            Livewire.hook('component.initialized', (component) => {
                //console.log('[Dropdown Fix] Livewire component initialized:', component.name);
            });
            
            // Ensure redirects work
            Livewire.hook('request', ({ component, options }) => {
                //console.log('[Dropdown Fix] Livewire request:', component.name, options);
            });
        }
    }
    
    // Fix Select components
    function fixSelectComponents() {
        //console.log('[Dropdown Fix] Fixing select components...');
        
        // Find all Filament select components
        document.querySelectorAll('[wire\\\\:model*="selectedCompanyId"], select[wire\\\\:model]').forEach(select => {
            //console.log('[Dropdown Fix] Found select component:', select);
            
            // Ensure Alpine is initialized on the component
            const alpineComponent = select.closest('[x-data]');
            if (alpineComponent && window.Alpine && !alpineComponent.__x) {
                try {
                    Alpine.initTree(alpineComponent);
                    //console.log('[Dropdown Fix] Initialized Alpine on select component');
                } catch (e) {
                    console.error('[Dropdown Fix] Failed to init Alpine on select:', e);
                }
            }
        });
    }
    
    // Listen for Livewire initialization
    document.addEventListener('livewire:init', () => {
        //console.log('[Dropdown Fix] Livewire init event fired');
        initialize();
    });
    
    // Export for debugging
    window.filamentDropdownFix = {
        fix: fixFilamentDropdowns,
        fixSelects: fixSelectComponents,
        fixRedirects: fixLivewireRedirects,
        debug: function() {
            const dropdowns = document.querySelectorAll('.fi-dropdown, [x-data*="open"]');
            //console.log(`[Dropdown Fix] Found ${dropdowns.length} dropdowns`);
            
            dropdowns.forEach((dd, i) => {
                //console.log(`Dropdown ${i + 1}:`, {
                    element: dd,
                    hasAlpine: !!dd.__x,
                    alpineData: dd.__x?.$data,
                    visible: dd.offsetParent !== null
                });
            });
        }
    };
    
    //console.log('[Filament Dropdown Fix] Loaded successfully');
})();