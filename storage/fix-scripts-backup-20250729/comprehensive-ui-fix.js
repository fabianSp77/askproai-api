// Comprehensive UI Fix - Fixes all UI interaction issues
// Includes correct selector syntax and proper event handling
(function() {
    'use strict';
    
    //console.log('[Comprehensive UI Fix] Loading...');
    
    let initialized = false;
    let initAttempts = 0;
    const maxAttempts = 20;
    
    // Wait for dependencies
    function waitForDependencies() {
        return new Promise((resolve) => {
            const checkInterval = setInterval(() => {
                if (
                    typeof window.Alpine !== 'undefined' && 
                    typeof window.Livewire !== 'undefined' &&
                    document.readyState === 'complete'
                ) {
                    clearInterval(checkInterval);
                    //console.log('[Comprehensive UI Fix] Dependencies ready');
                    resolve();
                }
            }, 100);
        });
    }
    
    // Initialize Alpine Store for sidebar if not exists
    function initializeAlpineStore() {
        if (!window.Alpine.store('sidebar')) {
            //console.log('[Comprehensive UI Fix] Initializing Alpine sidebar store');
            window.Alpine.store('sidebar', {
                isOpen: false,
                open() {
                    this.isOpen = true;
                    //console.log('[Comprehensive UI Fix] Sidebar opened');
                },
                close() {
                    this.isOpen = false;
                    //console.log('[Comprehensive UI Fix] Sidebar closed');
                }
            });
        }
    }
    
    // Fix mobile menu (burger button)
    function fixMobileMenu() {
        //console.log('[Comprehensive UI Fix] Fixing mobile menu...');
        
        // Open button
        const openButtons = document.querySelectorAll('.fi-topbar-open-sidebar-btn');
        openButtons.forEach(btn => {
            if (btn.dataset.comprehensiveFixed) return;
            btn.dataset.comprehensiveFixed = 'true';
            
            // Remove any existing handlers
            const newBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(newBtn, btn);
            
            // Add new handler
            newBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                //console.log('[Comprehensive UI Fix] Mobile menu open clicked');
                
                if (window.Alpine && window.Alpine.store('sidebar')) {
                    window.Alpine.store('sidebar').open();
                } else {
                    // Fallback: manually toggle sidebar
                    const sidebar = document.querySelector('.fi-sidebar');
                    if (sidebar) {
                        sidebar.classList.add('fi-sidebar-open');
                        sidebar.classList.remove('-translate-x-full');
                    }
                }
            });
        });
        
        // Close button
        const closeButtons = document.querySelectorAll('.fi-topbar-close-sidebar-btn');
        closeButtons.forEach(btn => {
            if (btn.dataset.comprehensiveFixed) return;
            btn.dataset.comprehensiveFixed = 'true';
            
            // Remove any existing handlers
            const newBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(newBtn, btn);
            
            // Add new handler
            newBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                //console.log('[Comprehensive UI Fix] Mobile menu close clicked');
                
                if (window.Alpine && window.Alpine.store('sidebar')) {
                    window.Alpine.store('sidebar').close();
                } else {
                    // Fallback: manually toggle sidebar
                    const sidebar = document.querySelector('.fi-sidebar');
                    if (sidebar) {
                        sidebar.classList.remove('fi-sidebar-open');
                        sidebar.classList.add('-translate-x-full');
                    }
                }
            });
        });
    }
    
    // Fix company selector with CORRECT selector syntax
    function fixCompanySelector() {
        //console.log('[Comprehensive UI Fix] Fixing company selector...');
        
        // Use correct double backslash for attribute selectors
        const selects = document.querySelectorAll('select[wire\\\\:model="selectedCompanyId"]');
        //console.log(`[Comprehensive UI Fix] Found ${selects.length} company selectors`);
        
        selects.forEach(select => {
            if (select.dataset.comprehensiveFixed) return;
            select.dataset.comprehensiveFixed = 'true';
            
            // Enable the select
            select.disabled = false;
            select.removeAttribute('disabled');
            
            // Add change handler
            select.addEventListener('change', function(e) {
                //console.log('[Comprehensive UI Fix] Company selected:', e.target.value);
                
                // Find Livewire component
                const wireId = findLivewireId(select);
                if (wireId && window.Livewire) {
                    const component = window.Livewire.find(wireId);
                    if (component) {
                        component.set('selectedCompanyId', e.target.value);
                        //console.log('[Comprehensive UI Fix] Livewire updated successfully');
                    }
                }
            });
        });
    }
    
    // Fix portal buttons
    function fixPortalButtons() {
        //console.log('[Comprehensive UI Fix] Fixing portal buttons...');
        
        const buttons = Array.from(document.querySelectorAll('button'));
        const portalButtons = buttons.filter(btn => 
            btn.textContent.includes('Portal öffnen') || 
            btn.textContent.includes('Kundenportal öffnen')
        );
        
        //console.log(`[Comprehensive UI Fix] Found ${portalButtons.length} portal buttons`);
        
        portalButtons.forEach(button => {
            if (button.dataset.comprehensiveFixed) return;
            button.dataset.comprehensiveFixed = 'true';
            
            // Enable button
            button.disabled = false;
            button.classList.remove('opacity-50', 'cursor-not-allowed');
            
            // Clone to remove existing handlers
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
            
            // Add click handler
            newButton.addEventListener('click', function(e) {
                //console.log('[Comprehensive UI Fix] Portal button clicked');
                
                // Get wire:click attribute
                const wireClick = newButton.getAttribute('wire:click');
                
                if (wireClick && window.Livewire) {
                    const wireId = findLivewireId(newButton);
                    if (wireId) {
                        const component = window.Livewire.find(wireId);
                        if (component) {
                            // Parse method call
                            const match = wireClick.match(/^(\w+)(?:\((.*)\))?$/);
                            if (match) {
                                const method = match[1];
                                const params = match[2];
                                
                                //console.log('[Comprehensive UI Fix] Calling:', method);
                                
                                if (params) {
                                    // Parse parameters
                                    const paramValues = params.split(',').map(p => {
                                        const trimmed = p.trim();
                                        // Handle numeric parameters
                                        if (!isNaN(trimmed)) {
                                            return parseInt(trimmed);
                                        }
                                        // Remove quotes for strings
                                        return trimmed.replace(/['"]/g, '');
                                    });
                                    component.call(method, ...paramValues);
                                } else {
                                    component.call(method);
                                }
                            }
                        }
                    }
                }
            });
        });
    }
    
    // Fix all Filament dropdowns
    function fixFilamentDropdowns() {
        //console.log('[Comprehensive UI Fix] Fixing Filament dropdowns...');
        
        const dropdowns = document.querySelectorAll('.fi-dropdown');
        //console.log(`[Comprehensive UI Fix] Found ${dropdowns.length} dropdowns`);
        
        dropdowns.forEach(dropdown => {
            if (dropdown.dataset.comprehensiveFixed) return;
            dropdown.dataset.comprehensiveFixed = 'true';
            
            const trigger = dropdown.querySelector('button[type="button"]');
            if (!trigger) return;
            
            // Ensure Alpine component is initialized
            if (window.Alpine && dropdown.hasAttribute('x-data')) {
                if (!dropdown.__x) {
                    try {
                        window.Alpine.initTree(dropdown);
                        //console.log('[Comprehensive UI Fix] Initialized Alpine on dropdown');
                    } catch (e) {
                        console.error('[Comprehensive UI Fix] Alpine init failed:', e);
                    }
                }
            }
            
            // Add fallback click handler
            const newTrigger = trigger.cloneNode(true);
            trigger.parentNode.replaceChild(newTrigger, trigger);
            
            newTrigger.addEventListener('click', function(e) {
                e.stopPropagation();
                //console.log('[Comprehensive UI Fix] Dropdown clicked');
                
                // Try Alpine first
                if (dropdown.__x && dropdown.__x.$data) {
                    dropdown.__x.$data.toggle = !dropdown.__x.$data.toggle;
                } else {
                    // Fallback: toggle panel visibility
                    const panel = dropdown.querySelector('.fi-dropdown-panel, [x-ref="panel"]');
                    if (panel) {
                        const isHidden = panel.style.display === 'none' || !panel.style.display;
                        panel.style.display = isHidden ? 'block' : 'none';
                    }
                }
            });
        });
    }
    
    // Fix branch selector
    function fixBranchSelector() {
        //console.log('[Comprehensive UI Fix] Fixing branch selector...');
        
        // Look for branch selector with multiple possible selectors
        const selectors = [
            '[x-data*="branchSelector"]',
            '.branch-selector',
            '[wire\\\\:model*="currentBranch"]',
            'select[name*="branch"]'
        ];
        
        selectors.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(element => {
                if (element.dataset.comprehensiveFixed) return;
                element.dataset.comprehensiveFixed = 'true';
                
                // Initialize Alpine if needed
                if (window.Alpine && element.hasAttribute('x-data') && !element.__x) {
                    try {
                        window.Alpine.initTree(element);
                        //console.log('[Comprehensive UI Fix] Initialized Alpine on branch selector');
                    } catch (e) {
                        console.error('[Comprehensive UI Fix] Alpine init failed:', e);
                    }
                }
                
                // For select elements
                if (element.tagName === 'SELECT') {
                    element.disabled = false;
                    element.addEventListener('change', function(e) {
                        //console.log('[Comprehensive UI Fix] Branch selected:', e.target.value);
                    });
                }
            });
        });
    }
    
    // Helper function to find Livewire component ID
    function findLivewireId(element) {
        let current = element;
        while (current && current !== document.body) {
            // Livewire v3 uses wire:id
            if (current.hasAttribute('wire:id')) {
                return current.getAttribute('wire:id');
            }
            // Also check for wire:snapshot (Livewire v3)
            if (current.hasAttribute('wire:snapshot')) {
                try {
                    const snapshot = JSON.parse(current.getAttribute('wire:snapshot'));
                    if (snapshot && snapshot.memo && snapshot.memo.id) {
                        return snapshot.memo.id;
                    }
                } catch (e) {
                    // Invalid JSON, continue searching
                }
            }
            current = current.parentElement;
        }
        return null;
    }
    
    // Apply all fixes
    async function applyAllFixes() {
        initAttempts++;
        //console.log(`[Comprehensive UI Fix] Apply attempt ${initAttempts}/${maxAttempts}`);
        
        try {
            // Initialize Alpine store
            initializeAlpineStore();
            
            // Apply all fixes
            fixMobileMenu();
            fixCompanySelector();
            fixPortalButtons();
            fixFilamentDropdowns();
            fixBranchSelector();
            
            // Set up mutation observer for dynamic content
            if (!initialized) {
                setupMutationObserver();
                initialized = true;
            }
            
        } catch (error) {
            console.error('[Comprehensive UI Fix] Error applying fixes:', error);
        }
        
        // Retry if needed
        if (initAttempts < maxAttempts) {
            setTimeout(applyAllFixes, 1000);
        }
    }
    
    // Set up mutation observer for dynamic content
    function setupMutationObserver() {
        const observer = new MutationObserver((mutations) => {
            let shouldReapply = false;
            
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === 1) { // Element node
                            // Check if new elements need fixing
                            if (
                                node.querySelector && (
                                    node.querySelector('select[wire\\\\:model]') ||
                                    node.querySelector('button') ||
                                    node.querySelector('.fi-dropdown') ||
                                    node.classList?.contains('fi-dropdown')
                                )
                            ) {
                                shouldReapply = true;
                            }
                        }
                    });
                }
            });
            
            if (shouldReapply) {
                //console.log('[Comprehensive UI Fix] New content detected, reapplying fixes...');
                setTimeout(() => {
                    fixCompanySelector();
                    fixPortalButtons();
                    fixFilamentDropdowns();
                    fixBranchSelector();
                }, 100);
            }
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
        
        //console.log('[Comprehensive UI Fix] Mutation observer active');
    }
    
    // Start when dependencies are ready
    waitForDependencies().then(() => {
        applyAllFixes();
        
        // Also listen for Livewire events
        if (window.Livewire) {
            document.addEventListener('livewire:navigated', () => {
                //console.log('[Comprehensive UI Fix] Livewire navigated, reapplying fixes...');
                setTimeout(applyAllFixes, 100);
            });
            
            document.addEventListener('livewire:load', () => {
                //console.log('[Comprehensive UI Fix] Livewire loaded, reapplying fixes...');
                setTimeout(applyAllFixes, 100);
            });
        }
    });
    
    // Expose debug functions
    window.comprehensiveUIFix = {
        status: function() {
            //console.log('=== Comprehensive UI Fix Status ===');
            //console.log('Initialized:', initialized);
            //console.log('Alpine loaded:', typeof window.Alpine !== 'undefined');
            //console.log('Livewire loaded:', typeof window.Livewire !== 'undefined');
            //console.log('Sidebar store:', window.Alpine?.store('sidebar'));
            //console.log('Fixed elements:', document.querySelectorAll('[data-comprehensive-fixed]').length);
            
            // Test selectors
            //console.log('\n=== Selector Test ===');
            //console.log('Company selects:', document.querySelectorAll('select[wire\\\\:model="selectedCompanyId"]').length);
            //console.log('Portal buttons:', Array.from(document.querySelectorAll('button')).filter(b => b.textContent.includes('Portal')).length);
            //console.log('Dropdowns:', document.querySelectorAll('.fi-dropdown').length);
            //console.log('Mobile menu buttons:', document.querySelectorAll('.fi-topbar-open-sidebar-btn, .fi-topbar-close-sidebar-btn').length);
        },
        reapply: function() {
            initialized = false;
            applyAllFixes();
        },
        testMobileMenu: function() {
            const openBtn = document.querySelector('.fi-topbar-open-sidebar-btn');
            if (openBtn) {
                openBtn.click();
                //console.log('Mobile menu open button clicked');
            } else {
                console.error('Mobile menu open button not found');
            }
        }
    };
    
    //console.log('[Comprehensive UI Fix] Loaded. Use window.comprehensiveUIFix.status() for debug info.');
    
})();