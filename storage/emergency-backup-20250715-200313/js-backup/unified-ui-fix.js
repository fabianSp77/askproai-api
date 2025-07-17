// Unified UI Fix - Alternative approach without complex escaping
(function() {
    'use strict';
    
    console.log('[Unified UI Fix] Loading...');
    
    let initialized = false;
    
    // Helper to find elements with wire:model attribute
    function findWireModelElements(modelName) {
        const allElements = document.querySelectorAll('*');
        const results = [];
        
        allElements.forEach(el => {
            if (el.hasAttribute('wire:model') && (!modelName || el.getAttribute('wire:model') === modelName)) {
                results.push(el);
            }
        });
        
        return results;
    }
    
    // Helper to find elements with wire:click attribute
    function findWireClickElements(methodName) {
        const allElements = document.querySelectorAll('*');
        const results = [];
        
        allElements.forEach(el => {
            if (el.hasAttribute('wire:click')) {
                if (!methodName || el.getAttribute('wire:click').includes(methodName)) {
                    results.push(el);
                }
            }
        });
        
        return results;
    }
    
    // Helper to find Livewire component
    function findLivewireComponent(element) {
        // Check if Livewire is available at all
        const Livewire = window.Livewire || window.livewire;
        if (!Livewire) {
            console.log('[Unified UI Fix] Livewire not available');
            return null;
        }
        
        let current = element;
        while (current && current !== document.body) {
            if (current.hasAttribute('wire:id')) {
                return Livewire.find ? Livewire.find(current.getAttribute('wire:id')) : null;
            }
            
            // Check for wire:snapshot (Livewire v3)
            if (current.hasAttribute('wire:snapshot')) {
                try {
                    const snapshot = JSON.parse(current.getAttribute('wire:snapshot'));
                    if (snapshot?.memo?.id) {
                        return Livewire.find ? Livewire.find(snapshot.memo.id) : null;
                    }
                } catch (e) {
                    // Invalid JSON
                }
            }
            
            // Also check for common Livewire root attributes
            if (current.hasAttribute('wire:initial-data')) {
                const wireId = current.getAttribute('wire:id');
                if (wireId) {
                    return Livewire.find ? Livewire.find(wireId) : null;
                }
            }
            
            current = current.parentElement;
        }
        return null;
    }
    
    // Initialize Alpine Store
    function initAlpineStore() {
        if (window.Alpine && !window.Alpine.store('sidebar')) {
            console.log('[Unified UI Fix] Creating Alpine sidebar store');
            window.Alpine.store('sidebar', {
                isOpen: false,
                open() {
                    this.isOpen = true;
                    console.log('[Unified UI Fix] Sidebar opened');
                    // Also update DOM
                    const sidebar = document.querySelector('.fi-sidebar');
                    if (sidebar) {
                        sidebar.classList.add('fi-sidebar-open');
                        sidebar.classList.remove('-translate-x-full');
                    }
                },
                close() {
                    this.isOpen = false;
                    console.log('[Unified UI Fix] Sidebar closed');
                    // Also update DOM
                    const sidebar = document.querySelector('.fi-sidebar');
                    if (sidebar) {
                        sidebar.classList.remove('fi-sidebar-open');
                        sidebar.classList.add('-translate-x-full');
                    }
                }
            });
        }
    }
    
    // Fix mobile menu
    function fixMobileMenu() {
        console.log('[Unified UI Fix] Fixing mobile menu...');
        
        // Open button
        document.querySelectorAll('.fi-topbar-open-sidebar-btn').forEach(btn => {
            if (btn.dataset.unifiedFixed) return;
            btn.dataset.unifiedFixed = 'true';
            
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('[Unified UI Fix] Mobile menu open clicked');
                
                if (window.Alpine?.store('sidebar')) {
                    window.Alpine.store('sidebar').open();
                } else {
                    // Direct DOM manipulation
                    const sidebar = document.querySelector('.fi-sidebar');
                    if (sidebar) {
                        sidebar.classList.add('fi-sidebar-open', 'translate-x-0');
                        sidebar.classList.remove('-translate-x-full');
                    }
                }
            });
        });
        
        // Close button
        document.querySelectorAll('.fi-topbar-close-sidebar-btn').forEach(btn => {
            if (btn.dataset.unifiedFixed) return;
            btn.dataset.unifiedFixed = 'true';
            
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('[Unified UI Fix] Mobile menu close clicked');
                
                if (window.Alpine?.store('sidebar')) {
                    window.Alpine.store('sidebar').close();
                } else {
                    // Direct DOM manipulation
                    const sidebar = document.querySelector('.fi-sidebar');
                    if (sidebar) {
                        sidebar.classList.remove('fi-sidebar-open', 'translate-x-0');
                        sidebar.classList.add('-translate-x-full');
                    }
                }
            });
        });
    }
    
    // Fix company selector
    function fixCompanySelector() {
        console.log('[Unified UI Fix] Fixing company selector...');
        
        const selects = findWireModelElements('selectedCompanyId');
        console.log(`[Unified UI Fix] Found ${selects.length} company selectors`);
        
        selects.forEach(select => {
            if (select.dataset.unifiedFixed) return;
            select.dataset.unifiedFixed = 'true';
            
            // Enable select
            select.disabled = false;
            select.removeAttribute('disabled');
            select.classList.remove('opacity-50');
            
            // Add change handler
            select.addEventListener('change', function(e) {
                console.log('[Unified UI Fix] Company selected:', e.target.value);
                
                const component = findLivewireComponent(select);
                if (component) {
                    component.set('selectedCompanyId', e.target.value);
                    console.log('[Unified UI Fix] Livewire component updated');
                    
                    // Try to call loadCompanyData
                    if (typeof component.loadCompanyData === 'function') {
                        component.call('loadCompanyData');
                    }
                }
            });
        });
    }
    
    // Fix portal buttons
    function fixPortalButtons() {
        console.log('[Unified UI Fix] Fixing portal buttons...');
        
        // Find buttons by text content and wire:click
        const buttons = Array.from(document.querySelectorAll('button')).filter(btn => {
            const hasPortalText = btn.textContent.includes('Portal öffnen') || 
                                 btn.textContent.includes('Kundenportal öffnen');
            const hasWireClick = btn.hasAttribute('wire:click');
            return hasPortalText || hasWireClick;
        });
        
        console.log(`[Unified UI Fix] Found ${buttons.length} portal buttons`);
        
        buttons.forEach(button => {
            if (button.dataset.unifiedFixed) return;
            button.dataset.unifiedFixed = 'true';
            
            // Enable button
            button.disabled = false;
            button.classList.remove('opacity-50', 'cursor-not-allowed');
            
            // Store original onclick
            const originalOnclick = button.onclick;
            
            button.onclick = function(e) {
                console.log('[Unified UI Fix] Portal button clicked');
                
                // Try original handler first
                if (originalOnclick) {
                    originalOnclick.call(this, e);
                }
                
                // Then try wire:click
                const wireClick = button.getAttribute('wire:click');
                if (wireClick) {
                    const component = findLivewireComponent(button);
                    if (component) {
                        // Parse method and params
                        const match = wireClick.match(/^(\w+)(?:\((.*)\))?$/);
                        if (match) {
                            const method = match[1];
                            const params = match[2];
                            
                            console.log('[Unified UI Fix] Calling Livewire method:', method);
                            
                            try {
                                if (params) {
                                    // Simple param parsing
                                    const paramValues = params.split(',').map(p => {
                                        const trimmed = p.trim();
                                        if (trimmed === 'true') return true;
                                        if (trimmed === 'false') return false;
                                        if (trimmed === 'null') return null;
                                        if (!isNaN(trimmed)) return Number(trimmed);
                                        return trimmed.replace(/['"]/g, '');
                                    });
                                    component.call(method, ...paramValues);
                                } else {
                                    component.call(method);
                                }
                            } catch (error) {
                                console.error('[Unified UI Fix] Error calling method:', error);
                            }
                        }
                    }
                }
            };
        });
    }
    
    // Fix all dropdowns
    function fixDropdowns() {
        console.log('[Unified UI Fix] Fixing dropdowns...');
        
        // Filament dropdowns
        document.querySelectorAll('.fi-dropdown').forEach(dropdown => {
            if (dropdown.dataset.unifiedFixed) return;
            dropdown.dataset.unifiedFixed = 'true';
            
            const trigger = dropdown.querySelector('button[type="button"]');
            if (!trigger) return;
            
            // Try to init Alpine if needed
            if (window.Alpine && dropdown.hasAttribute('x-data') && !dropdown.__x) {
                try {
                    window.Alpine.initTree(dropdown);
                } catch (e) {
                    console.error('[Unified UI Fix] Alpine init failed:', e);
                }
            }
            
            // Add click handler
            trigger.addEventListener('click', function(e) {
                e.stopPropagation();
                console.log('[Unified UI Fix] Dropdown clicked');
                
                // Toggle using Alpine if available
                if (dropdown.__x && dropdown.__x.$data) {
                    dropdown.__x.$data.open = !dropdown.__x.$data.open;
                } else {
                    // Manual toggle
                    const panel = dropdown.querySelector('.fi-dropdown-panel, [x-ref="panel"]');
                    if (panel) {
                        const isVisible = panel.style.display !== 'none' && panel.style.display !== '';
                        panel.style.display = isVisible ? 'none' : 'block';
                    }
                }
            });
        });
        
        // Branch selector
        const branchSelectors = document.querySelectorAll(
            '[x-data*="branchSelector"], .branch-selector, select[name*="branch"]'
        );
        
        branchSelectors.forEach(selector => {
            if (selector.dataset.unifiedFixed) return;
            selector.dataset.unifiedFixed = 'true';
            
            if (selector.tagName === 'SELECT') {
                selector.disabled = false;
                selector.addEventListener('change', function(e) {
                    console.log('[Unified UI Fix] Branch selected:', e.target.value);
                });
            }
        });
    }
    
    // Main initialization
    function init() {
        console.log('[Unified UI Fix] Initializing...');
        
        // Initialize Alpine store
        initAlpineStore();
        
        // Apply all fixes
        fixMobileMenu();
        fixCompanySelector();
        fixPortalButtons();
        fixDropdowns();
        
        // Mark as initialized
        initialized = true;
        
        // Setup mutation observer
        setupMutationObserver();
    }
    
    // Mutation observer for dynamic content
    function setupMutationObserver() {
        const observer = new MutationObserver((mutations) => {
            let shouldReapply = false;
            
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === 1 && node.querySelector) {
                            if (
                                node.querySelector('select') ||
                                node.querySelector('button') ||
                                node.querySelector('.fi-dropdown') ||
                                node.classList?.contains('fi-dropdown')
                            ) {
                                shouldReapply = true;
                            }
                        }
                    });
                }
            });
            
            if (shouldReapply) {
                console.log('[Unified UI Fix] New content detected, reapplying fixes...');
                setTimeout(() => {
                    fixCompanySelector();
                    fixPortalButtons();
                    fixDropdowns();
                }, 100);
            }
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    // Wait for dependencies and start
    function waitAndStart() {
        console.log('[Unified UI Fix] Checking dependencies...', {
            Alpine: typeof window.Alpine !== 'undefined',
            Livewire: typeof window.Livewire !== 'undefined',
            livewire: typeof window.livewire !== 'undefined',
            DOM: document.readyState
        });
        
        // Check for Livewire 3 (window.Livewire) or Livewire 2 (window.livewire)
        const livewireAvailable = typeof window.Livewire !== 'undefined' || typeof window.livewire !== 'undefined';
        
        if (
            typeof window.Alpine !== 'undefined' && 
            livewireAvailable &&
            document.readyState !== 'loading'
        ) {
            console.log('[Unified UI Fix] All dependencies ready, initializing...');
            init();
            
            // Setup Livewire hooks
            if (window.Livewire) {
                document.addEventListener('livewire:navigated', () => {
                    console.log('[Unified UI Fix] Livewire navigated');
                    setTimeout(init, 100);
                });
                
                document.addEventListener('livewire:load', () => {
                    console.log('[Unified UI Fix] Livewire loaded');
                    setTimeout(init, 100);
                });
            }
        } else {
            // Check what's missing
            if (typeof window.Alpine === 'undefined') {
                console.log('[Unified UI Fix] Waiting for Alpine...');
            }
            if (typeof window.Livewire === 'undefined' && typeof window.livewire === 'undefined') {
                console.log('[Unified UI Fix] Waiting for Livewire...');
            }
            if (document.readyState === 'loading') {
                console.log('[Unified UI Fix] Waiting for DOM...');
            }
            setTimeout(waitAndStart, 100);
        }
    }
    
    // Start
    waitAndStart();
    
    // Also try on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', waitAndStart);
    }
    
    // Fallback 1: Try to initialize after a short delay even if dependencies aren't detected
    setTimeout(() => {
        if (!initialized) {
            console.log('[Unified UI Fix] Fallback initialization after 1 second...');
            // Check if at least Alpine is available
            if (typeof window.Alpine !== 'undefined') {
                console.log('[Unified UI Fix] Alpine is available, initializing without Livewire...');
                init();
            }
        }
    }, 1000);
    
    // Fallback 2: Force initialization after 3 seconds regardless
    setTimeout(() => {
        if (!initialized) {
            console.log('[Unified UI Fix] Force initialization after 3 seconds...');
            init();
        }
    }, 3000);
    
    // Another fallback: Listen for Livewire initialization
    document.addEventListener('livewire:init', () => {
        console.log('[Unified UI Fix] Livewire init event detected');
        setTimeout(waitAndStart, 100);
    });
    
    // And for Alpine initialization
    document.addEventListener('alpine:init', () => {
        console.log('[Unified UI Fix] Alpine init event detected');
        setTimeout(waitAndStart, 100);
    });
    
    // Debug interface
    window.unifiedUIFix = {
        status: function() {
            console.log('=== Unified UI Fix Status ===');
            console.log('Initialized:', initialized);
            console.log('Alpine:', typeof window.Alpine !== 'undefined');
            console.log('Livewire (v3):', typeof window.Livewire !== 'undefined');
            console.log('livewire (v2):', typeof window.livewire !== 'undefined');
            console.log('DOM State:', document.readyState);
            console.log('Fixed elements:', document.querySelectorAll('[data-unified-fixed]').length);
            
            // Check for Alpine components
            if (window.Alpine && window.Alpine.version) {
                console.log('Alpine version:', window.Alpine.version);
            }
            
            // Test wire:model finding
            const wireModels = findWireModelElements();
            console.log('Elements with wire:model:', wireModels.length);
            
            // Test wire:click finding  
            const wireClicks = findWireClickElements();
            console.log('Elements with wire:click:', wireClicks.length);
            
            // Check for Filament components
            console.log('Filament dropdowns:', document.querySelectorAll('.fi-dropdown').length);
            console.log('Mobile menu buttons:', document.querySelectorAll('.fi-topbar-open-sidebar-btn, .fi-topbar-close-sidebar-btn').length);
        },
        reapply: function() {
            console.log('[Unified UI Fix] Manually reapplying...');
            init();
        },
        test: {
            mobileMenu: function() {
                const btn = document.querySelector('.fi-topbar-open-sidebar-btn');
                if (btn) btn.click();
            },
            companySelect: function() {
                const select = findWireModelElements('selectedCompanyId')[0];
                if (select) {
                    select.selectedIndex = 1;
                    select.dispatchEvent(new Event('change'));
                }
            },
            portalButton: function() {
                const btn = findWireClickElements('openCustomerPortal')[0] ||
                          findWireClickElements('openPortal')[0];
                if (btn) btn.click();
            }
        }
    };
    
    console.log('[Unified UI Fix] Loaded. Use window.unifiedUIFix.status() for debug.');
    
})();