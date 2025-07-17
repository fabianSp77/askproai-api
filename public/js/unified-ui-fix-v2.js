// Unified UI Fix V2 - Works without waiting for Livewire
(function() {
    'use strict';
    
    //console.log('[Unified UI Fix V2] Starting...');
    
    let initialized = false;
    let initAttempts = 0;
    const maxInitAttempts = 10;
    
    // Helper to find elements with wire:model attribute
    function findWireModelElements(modelName) {
        const allElements = document.querySelectorAll('select, input, textarea');
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
        const allElements = document.querySelectorAll('button, a, div[role="button"]');
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
    
    // Helper to find Livewire component (with better compatibility)
    function findLivewireComponent(element) {
        if (!element) return null;
        
        // Try different methods to find Livewire component
        let current = element;
        while (current && current !== document.body) {
            // Method 1: wire:id attribute
            if (current.hasAttribute('wire:id')) {
                const wireId = current.getAttribute('wire:id');
                // Try both Livewire v2 and v3 methods
                if (window.Livewire && window.Livewire.find) {
                    return window.Livewire.find(wireId);
                }
                if (window.livewire && window.livewire.find) {
                    return window.livewire.find(wireId);
                }
            }
            
            // Method 2: __livewire property
            if (current.__livewire) {
                return current.__livewire;
            }
            
            // Method 3: Alpine component with Livewire
            if (current._x_dataStack && window.Alpine) {
                const alpineData = Alpine.$data(current);
                if (alpineData && alpineData.$wire) {
                    return alpineData.$wire;
                }
            }
            
            current = current.parentElement;
        }
        return null;
    }
    
    // Initialize Alpine Store (with better checks)
    function initAlpineStore() {
        if (!window.Alpine) {
            //console.log('[Unified UI Fix V2] Alpine not available for store initialization');
            return;
        }
        
        try {
            if (!window.Alpine.store('sidebar')) {
                //console.log('[Unified UI Fix V2] Creating Alpine sidebar store');
                window.Alpine.store('sidebar', {
                    isOpen: false,
                    open() {
                        this.isOpen = true;
                        //console.log('[Unified UI Fix V2] Sidebar opened via store');
                        updateSidebarDOM(true);
                    },
                    close() {
                        this.isOpen = false;
                        //console.log('[Unified UI Fix V2] Sidebar closed via store');
                        updateSidebarDOM(false);
                    },
                    toggle() {
                        this.isOpen = !this.isOpen;
                        //console.log('[Unified UI Fix V2] Sidebar toggled via store:', this.isOpen);
                        updateSidebarDOM(this.isOpen);
                    }
                });
            }
        } catch (e) {
            console.error('[Unified UI Fix V2] Error creating Alpine store:', e);
        }
    }
    
    // Update sidebar DOM directly
    function updateSidebarDOM(isOpen) {
        const sidebar = document.querySelector('.fi-sidebar');
        if (sidebar) {
            if (isOpen) {
                sidebar.classList.add('fi-sidebar-open', 'translate-x-0');
                sidebar.classList.remove('-translate-x-full');
                sidebar.style.transform = 'translateX(0)';
            } else {
                sidebar.classList.remove('fi-sidebar-open', 'translate-x-0');
                sidebar.classList.add('-translate-x-full');
                sidebar.style.transform = '';
            }
        }
    }
    
    // Fix mobile menu with multiple approaches
    function fixMobileMenu() {
        //console.log('[Unified UI Fix V2] Fixing mobile menu...');
        
        // Fix open buttons
        const openButtons = document.querySelectorAll('.fi-topbar-open-sidebar-btn, [x-on\\:click*="$store.sidebar.open"], button[aria-label*="Open sidebar"]');
        //console.log(`[Unified UI Fix V2] Found ${openButtons.length} open buttons`);
        
        openButtons.forEach((btn, index) => {
            if (btn.dataset.unifiedFixed) return;
            btn.dataset.unifiedFixed = 'true';
            
            // Clone and replace to remove all existing listeners
            const newBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(newBtn, btn);
            
            newBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                //console.log(`[Unified UI Fix V2] Mobile menu open button ${index} clicked`);
                
                // Try multiple methods
                if (window.Alpine && window.Alpine.store('sidebar')) {
                    window.Alpine.store('sidebar').open();
                } else {
                    updateSidebarDOM(true);
                }
                
                return false;
            }, true);
        });
        
        // Fix close buttons
        const closeButtons = document.querySelectorAll('.fi-topbar-close-sidebar-btn, [x-on\\:click*="$store.sidebar.close"], .fi-sidebar-close-btn');
        //console.log(`[Unified UI Fix V2] Found ${closeButtons.length} close buttons`);
        
        closeButtons.forEach((btn, index) => {
            if (btn.dataset.unifiedFixed) return;
            btn.dataset.unifiedFixed = 'true';
            
            // Clone and replace to remove all existing listeners
            const newBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(newBtn, btn);
            
            newBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                //console.log(`[Unified UI Fix V2] Mobile menu close button ${index} clicked`);
                
                // Try multiple methods
                if (window.Alpine && window.Alpine.store('sidebar')) {
                    window.Alpine.store('sidebar').close();
                } else {
                    updateSidebarDOM(false);
                }
                
                return false;
            }, true);
        });
    }
    
    // Fix company selector with better error handling
    function fixCompanySelector() {
        //console.log('[Unified UI Fix V2] Fixing company selector...');
        
        const selects = findWireModelElements('selectedCompanyId');
        //console.log(`[Unified UI Fix V2] Found ${selects.length} company selectors`);
        
        selects.forEach(select => {
            if (select.dataset.unifiedFixed) return;
            select.dataset.unifiedFixed = 'true';
            
            // Enable select
            select.disabled = false;
            select.removeAttribute('disabled');
            select.classList.remove('opacity-50', 'cursor-not-allowed');
            
            // Add change handler
            select.addEventListener('change', function(e) {
                //console.log('[Unified UI Fix V2] Company selected:', e.target.value);
                
                const component = findLivewireComponent(select);
                if (component) {
                    try {
                        if (component.set) {
                            component.set('selectedCompanyId', e.target.value);
                        } else if (component.$set) {
                            component.$set('selectedCompanyId', e.target.value);
                        }
                        
                        if (component.call) {
                            component.call('loadCompanyData');
                        } else if (component.$call) {
                            component.$call('loadCompanyData');
                        }
                    } catch (error) {
                        console.error('[Unified UI Fix V2] Error updating Livewire:', error);
                    }
                } else {
                    //console.log('[Unified UI Fix V2] No Livewire component found, form will submit normally');
                }
            });
        });
    }
    
    // Fix portal buttons
    function fixPortalButtons() {
        //console.log('[Unified UI Fix V2] Fixing portal buttons...');
        
        // Find all potential portal buttons
        const buttons = Array.from(document.querySelectorAll('button, a[role="button"]')).filter(btn => {
            const text = btn.textContent.toLowerCase();
            const hasPortalText = text.includes('portal') || text.includes('öffnen');
            const hasWireClick = btn.hasAttribute('wire:click');
            return hasPortalText || hasWireClick;
        });
        
        //console.log(`[Unified UI Fix V2] Found ${buttons.length} potential portal buttons`);
        
        buttons.forEach((button, index) => {
            if (button.dataset.unifiedFixed) return;
            button.dataset.unifiedFixed = 'true';
            
            // Enable button
            button.disabled = false;
            button.classList.remove('opacity-50', 'cursor-not-allowed', 'pointer-events-none');
            button.style.pointerEvents = 'auto';
            button.style.cursor = 'pointer';
            
            // Add click handler
            button.addEventListener('click', function(e) {
                //console.log(`[Unified UI Fix V2] Portal button ${index} clicked`);
                
                const wireClick = button.getAttribute('wire:click');
                if (wireClick) {
                    e.preventDefault();
                    //console.log('[Unified UI Fix V2] Wire click action:', wireClick);
                    
                    const component = findLivewireComponent(button);
                    if (component) {
                        try {
                            // Parse method and params
                            const match = wireClick.match(/^(\w+)(?:\((.*)\))?$/);
                            if (match) {
                                const method = match[1];
                                const params = match[2];
                                
                                //console.log('[Unified UI Fix V2] Calling method:', method);
                                
                                if (params) {
                                    const paramValues = params.split(',').map(p => {
                                        const trimmed = p.trim();
                                        if (trimmed === 'true') return true;
                                        if (trimmed === 'false') return false;
                                        if (trimmed === 'null') return null;
                                        if (!isNaN(trimmed)) return Number(trimmed);
                                        return trimmed.replace(/['"]/g, '');
                                    });
                                    
                                    if (component.call) {
                                        component.call(method, ...paramValues);
                                    } else if (component.$call) {
                                        component.$call(method, ...paramValues);
                                    }
                                } else {
                                    if (component.call) {
                                        component.call(method);
                                    } else if (component.$call) {
                                        component.$call(method);
                                    }
                                }
                            }
                        } catch (error) {
                            console.error('[Unified UI Fix V2] Error calling Livewire method:', error);
                        }
                    } else {
                        //console.log('[Unified UI Fix V2] No Livewire component found');
                    }
                }
            });
        });
    }
    
    // Fix all dropdowns
    function fixDropdowns() {
        //console.log('[Unified UI Fix V2] Fixing dropdowns...');
        
        // Fix Filament dropdowns
        const dropdowns = document.querySelectorAll('.fi-dropdown, [x-data*="dropdown"]');
        //console.log(`[Unified UI Fix V2] Found ${dropdowns.length} dropdowns`);
        
        dropdowns.forEach((dropdown, index) => {
            if (dropdown.dataset.unifiedFixed) return;
            dropdown.dataset.unifiedFixed = 'true';
            
            const trigger = dropdown.querySelector('button[type="button"], [x-ref="trigger"]');
            const panel = dropdown.querySelector('.fi-dropdown-panel, [x-ref="panel"]');
            
            if (trigger && panel) {
                // Make sure panel is hidden initially
                panel.style.display = 'none';
                
                trigger.addEventListener('click', function(e) {
                    e.stopPropagation();
                    //console.log(`[Unified UI Fix V2] Dropdown ${index} clicked`);
                    
                    const isVisible = panel.style.display === 'block';
                    panel.style.display = isVisible ? 'none' : 'block';
                    
                    // Try to update Alpine state if available
                    if (dropdown.__x && dropdown.__x.$data) {
                        dropdown.__x.$data.open = !isVisible;
                    }
                });
                
                // Close on outside click
                document.addEventListener('click', function(e) {
                    if (!dropdown.contains(e.target)) {
                        panel.style.display = 'none';
                        if (dropdown.__x && dropdown.__x.$data) {
                            dropdown.__x.$data.open = false;
                        }
                    }
                });
            }
        });
    }
    
    // Main initialization
    function init() {
        if (initialized) {
            //console.log('[Unified UI Fix V2] Already initialized, skipping...');
            return;
        }
        
        initAttempts++;
        //console.log(`[Unified UI Fix V2] Initializing... (attempt ${initAttempts}/${maxInitAttempts})`);
        
        // Initialize Alpine store if available
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
        
        //console.log('[Unified UI Fix V2] Initialization complete!');
    }
    
    // Mutation observer for dynamic content
    function setupMutationObserver() {
        const observer = new MutationObserver((mutations) => {
            let shouldReapply = false;
            
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === 1) {
                            if (node.querySelector && (
                                node.querySelector('select') ||
                                node.querySelector('button') ||
                                node.querySelector('.fi-dropdown') ||
                                node.classList?.contains('fi-dropdown')
                            )) {
                                shouldReapply = true;
                            }
                        }
                    });
                }
            });
            
            if (shouldReapply) {
                //console.log('[Unified UI Fix V2] New content detected, reapplying fixes...');
                setTimeout(() => {
                    fixCompanySelector();
                    fixPortalButtons();
                    fixDropdowns();
                    fixMobileMenu();
                }, 100);
            }
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    // Initialize immediately on DOM ready
    function startInit() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            // Small delay to let other scripts initialize
            setTimeout(init, 100);
        }
    }
    
    // Start initialization
    startInit();
    
    // Also listen for Alpine init
    document.addEventListener('alpine:init', () => {
        //console.log('[Unified UI Fix V2] Alpine initialized');
        setTimeout(init, 100);
    });
    
    // Listen for Livewire events
    document.addEventListener('livewire:init', () => {
        //console.log('[Unified UI Fix V2] Livewire initialized');
        setTimeout(init, 100);
    });
    
    document.addEventListener('livewire:navigated', () => {
        //console.log('[Unified UI Fix V2] Livewire navigated');
        initialized = false; // Reset to reapply fixes
        setTimeout(init, 100);
    });
    
    // Retry initialization periodically if not successful
    const retryInterval = setInterval(() => {
        if (initAttempts < maxInitAttempts && !initialized) {
            //console.log('[Unified UI Fix V2] Retrying initialization...');
            init();
        } else {
            clearInterval(retryInterval);
        }
    }, 1000);
    
    // Debug interface
    window.unifiedUIFixV2 = {
        status: function() {
            //console.log('=== Unified UI Fix V2 Status ===');
            //console.log('Initialized:', initialized);
            //console.log('Init Attempts:', initAttempts);
            //console.log('Alpine:', typeof window.Alpine !== 'undefined');
            //console.log('Livewire:', typeof window.Livewire !== 'undefined');
            //console.log('Fixed elements:', document.querySelectorAll('[data-unified-fixed]').length);
            
            const wireModels = findWireModelElements();
            //console.log('Elements with wire:model:', wireModels.length);
            
            const wireClicks = findWireClickElements();
            //console.log('Elements with wire:click:', wireClicks.length);
            
            //console.log('Mobile menu buttons:', {
                open: document.querySelectorAll('.fi-topbar-open-sidebar-btn').length,
                close: document.querySelectorAll('.fi-topbar-close-sidebar-btn').length
            });
            
            //console.log('Company selectors:', findWireModelElements('selectedCompanyId').length);
            //console.log('Dropdowns:', document.querySelectorAll('.fi-dropdown').length);
        },
        reapply: function() {
            //console.log('[Unified UI Fix V2] Manually reapplying...');
            initialized = false;
            init();
        },
        test: {
            mobileMenu: function() {
                //console.log('[Unified UI Fix V2] Testing mobile menu...');
                const btn = document.querySelector('.fi-topbar-open-sidebar-btn');
                if (btn) {
                    //console.log('Clicking open button...');
                    btn.click();
                } else {
                    //console.log('No open button found');
                }
            },
            companySelect: function() {
                //console.log('[Unified UI Fix V2] Testing company select...');
                const select = findWireModelElements('selectedCompanyId')[0];
                if (select && select.options.length > 1) {
                    //console.log('Changing selection...');
                    select.selectedIndex = 1;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                } else {
                    //console.log('No company select found or no options');
                }
            },
            portalButton: function() {
                //console.log('[Unified UI Fix V2] Testing portal button...');
                const btn = findWireClickElements('openCustomerPortal')[0] ||
                          findWireClickElements('openPortal')[0];
                if (btn) {
                    //console.log('Clicking portal button...');
                    btn.click();
                } else {
                    //console.log('No portal button found');
                }
            },
            sidebar: {
                open: () => updateSidebarDOM(true),
                close: () => updateSidebarDOM(false),
                toggle: () => {
                    const sidebar = document.querySelector('.fi-sidebar');
                    const isOpen = sidebar && sidebar.classList.contains('fi-sidebar-open');
                    updateSidebarDOM(!isOpen);
                }
            }
        }
    };
    
    //console.log('[Unified UI Fix V2] Script loaded. Use window.unifiedUIFixV2.status() for debug.');
    
})();