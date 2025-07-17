// Emergency Business Portal Fix
// This script provides immediate fixes for dropdown and button issues
(function() {
    'use strict';
    
    //console.log('[Emergency Fix] Loading emergency fixes for Business Portal...');
    
    let fixAttempts = 0;
    const maxAttempts = 10;
    
    function applyEmergencyFixes() {
        fixAttempts++;
        //console.log(`[Emergency Fix] Attempt ${fixAttempts}/${maxAttempts}`);
        
        // 1. Fix Company Selector Dropdown
        fixCompanySelector();
        
        // 2. Fix Portal Button
        fixPortalButton();
        
        // 3. Fix Branch Selector
        fixBranchSelector();
        
        // 4. General Dropdown Fix
        fixAllDropdowns();
        
        // 5. Ensure Livewire Events Work
        fixLivewireEvents();
        
        if (fixAttempts < maxAttempts) {
            setTimeout(applyEmergencyFixes, 2000);
        }
    }
    
    function fixCompanySelector() {
        const selects = document.querySelectorAll('select[wire\\:model="selectedCompanyId"]');
        
        selects.forEach(select => {
            if (select.dataset.emergencyFixed) return;
            
            //console.log('[Emergency Fix] Fixing company selector...');
            select.dataset.emergencyFixed = 'true';
            
            // Remove any disabled state
            select.disabled = false;
            select.removeAttribute('disabled');
            
            // Add change listener
            select.addEventListener('change', function(e) {
                //console.log('[Emergency Fix] Company selected:', e.target.value);
                
                // Force Livewire update
                if (window.Livewire) {
                    const component = Livewire.find(
                        select.closest('[wire\\:id]')?.getAttribute('wire:id')
                    );
                    
                    if (component) {
                        component.set('selectedCompanyId', e.target.value);
                        //console.log('[Emergency Fix] Livewire updated');
                        
                        // Force component refresh
                        component.call('loadCompanyData');
                    }
                }
            });
        });
    }
    
    function fixPortalButton() {
        const buttons = Array.from(document.querySelectorAll('button'));
        const portalButtons = buttons.filter(btn => 
            btn.textContent.includes('Portal öffnen') || 
            btn.textContent.includes('Kundenportal öffnen')
        );
        
        portalButtons.forEach(button => {
            if (button.dataset.emergencyFixed) return;
            
            //console.log('[Emergency Fix] Fixing portal button...');
            button.dataset.emergencyFixed = 'true';
            
            // Remove disabled state
            button.disabled = false;
            button.classList.remove('opacity-50', 'cursor-not-allowed');
            
            // Add click handler
            button.addEventListener('click', function(e) {
                //console.log('[Emergency Fix] Portal button clicked');
                
                // Get wire:click action
                const wireClick = button.getAttribute('wire:click');
                
                if (wireClick && window.Livewire) {
                    const component = Livewire.find(
                        button.closest('[wire\\:id]')?.getAttribute('wire:id')
                    );
                    
                    if (component) {
                        // Extract method name
                        const methodMatch = wireClick.match(/^(\w+)(?:\((.*)\))?$/);
                        if (methodMatch) {
                            const method = methodMatch[1];
                            const params = methodMatch[2];
                            
                            //console.log('[Emergency Fix] Calling Livewire method:', method, params);
                            
                            if (params) {
                                component.call(method, ...params.split(',').map(p => p.trim()));
                            } else {
                                component.call(method);
                            }
                        }
                    }
                }
            });
        });
    }
    
    function fixBranchSelector() {
        // Fix branch selector in navigation
        const branchSelectors = document.querySelectorAll(
            '[x-data*="branchSelector"], .branch-selector, [wire\\:model*="currentBranch"]'
        );
        
        branchSelectors.forEach(selector => {
            if (selector.dataset.emergencyFixed) return;
            
            //console.log('[Emergency Fix] Fixing branch selector...');
            selector.dataset.emergencyFixed = 'true';
            
            // Ensure Alpine is initialized
            if (window.Alpine && !selector.__x) {
                try {
                    Alpine.initTree(selector);
                    //console.log('[Emergency Fix] Alpine initialized on branch selector');
                } catch (e) {
                    console.error('[Emergency Fix] Alpine init failed:', e);
                }
            }
        });
    }
    
    function fixAllDropdowns() {
        // Fix all Filament dropdowns
        document.querySelectorAll('.fi-dropdown').forEach(dropdown => {
            if (dropdown.dataset.emergencyFixed) return;
            
            dropdown.dataset.emergencyFixed = 'true';
            
            const trigger = dropdown.querySelector('button[type="button"]');
            const panel = dropdown.querySelector('.fi-dropdown-panel, [x-ref="panel"]');
            
            if (trigger && panel) {
                //console.log('[Emergency Fix] Fixing dropdown...');
                
                // Ensure proper event handling
                trigger.addEventListener('click', function(e) {
                    e.stopPropagation();
                    
                    // Toggle panel visibility
                    const isOpen = panel.style.display !== 'none';
                    panel.style.display = isOpen ? 'none' : 'block';
                    
                    // Update Alpine state if available
                    if (dropdown.__x && dropdown.__x.$data) {
                        dropdown.__x.$data.open = !isOpen;
                    }
                });
            }
        });
    }
    
    function fixLivewireEvents() {
        if (!window.Livewire) return;
        
        //console.log('[Emergency Fix] Setting up Livewire event handlers...');
        
        // Handle redirect events
        window.addEventListener('redirect-to-portal', function(event) {
            //console.log('[Emergency Fix] Redirect event received:', event.detail);
            if (event.detail && event.detail.url) {
                window.location.href = event.detail.url;
            }
        });
        
        // Livewire v3 syntax
        Livewire.on('redirect-to-portal', (data) => {
            //console.log('[Emergency Fix] Livewire redirect:', data);
            if (data && data.url) {
                window.location.href = data.url;
            }
        });
        
        // Fix for Livewire navigation
        document.addEventListener('livewire:navigated', () => {
            //console.log('[Emergency Fix] Livewire navigated, reapplying fixes...');
            setTimeout(applyEmergencyFixes, 500);
        });
    }
    
    // Start emergency fixes
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyEmergencyFixes);
    } else {
        applyEmergencyFixes();
    }
    
    // Also run on Livewire init
    document.addEventListener('livewire:init', applyEmergencyFixes);
    
    // Expose debug function
    window.emergencyFix = {
        status: function() {
            //console.log('=== Emergency Fix Status ===');
            //console.log('Fix Attempts:', fixAttempts);
            //console.log('Company Selectors Fixed:', document.querySelectorAll('[data-emergency-fixed]').length);
            //console.log('Alpine:', typeof Alpine !== 'undefined' ? 'Loaded' : 'Not Loaded');
            //console.log('Livewire:', typeof Livewire !== 'undefined' ? 'Loaded' : 'Not Loaded');
        },
        reapply: applyEmergencyFixes,
        testPortalButton: function() {
            const button = document.querySelector('button[wire\\:click*="openCustomerPortal"]');
            if (button) {
                button.click();
                //console.log('Portal button clicked programmatically');
            } else {
                console.error('Portal button not found');
            }
        },
        testCompanySelect: function() {
            const select = document.querySelector('select[wire\\:model="selectedCompanyId"]');
            if (select && select.options.length > 1) {
                select.selectedIndex = 1;
                select.dispatchEvent(new Event('change'));
                //console.log('Company select changed programmatically');
            } else {
                console.error('Company select not found or has no options');
            }
        }
    };
    
    //console.log('[Emergency Fix] Emergency fixes loaded. Use window.emergencyFix.status() for debug info.');
})();