// Emergency Button Fix - Works immediately without dependencies
(function() {
    'use strict';
    
    console.log('[Emergency Button Fix] Activating...');
    
    // Fix mobile menu immediately
    function fixMobileMenuNow() {
        // Find open button
        const openBtns = document.querySelectorAll('.fi-topbar-open-sidebar-btn, button[aria-label*="Open sidebar"], button svg[class*="heroicon-o-bars-3"]');
        console.log(`[Emergency Button Fix] Found ${openBtns.length} open buttons`);
        
        openBtns.forEach((btn, i) => {
            // Find the actual button element if we found an SVG
            const actualBtn = btn.tagName === 'BUTTON' ? btn : btn.closest('button');
            if (!actualBtn) return;
            
            // Remove all existing event listeners by cloning
            const newBtn = actualBtn.cloneNode(true);
            actualBtn.parentNode.replaceChild(newBtn, actualBtn);
            
            // Add our handler
            newBtn.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log(`[Emergency Button Fix] Open button ${i} clicked!`);
                
                const sidebar = document.querySelector('.fi-sidebar');
                if (sidebar) {
                    sidebar.classList.add('fi-sidebar-open', 'translate-x-0');
                    sidebar.classList.remove('-translate-x-full');
                    sidebar.style.transform = 'translateX(0)';
                    sidebar.style.display = 'block';
                    sidebar.style.visibility = 'visible';
                    
                    // Also try Alpine store if available
                    if (window.Alpine && window.Alpine.store && window.Alpine.store('sidebar')) {
                        window.Alpine.store('sidebar').isOpen = true;
                    }
                }
                return false;
            };
            
            // Make sure button is enabled
            newBtn.disabled = false;
            newBtn.style.pointerEvents = 'auto';
            newBtn.style.cursor = 'pointer';
            newBtn.style.zIndex = '9999';
            newBtn.style.position = 'relative';
        });
        
        // Find close button
        const closeBtns = document.querySelectorAll('.fi-topbar-close-sidebar-btn, .fi-sidebar-close-btn, button[x-on\\:click*="close"]');
        console.log(`[Emergency Button Fix] Found ${closeBtns.length} close buttons`);
        
        closeBtns.forEach((btn, i) => {
            const newBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(newBtn, btn);
            
            newBtn.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log(`[Emergency Button Fix] Close button ${i} clicked!`);
                
                const sidebar = document.querySelector('.fi-sidebar');
                if (sidebar) {
                    sidebar.classList.remove('fi-sidebar-open', 'translate-x-0');
                    sidebar.classList.add('-translate-x-full');
                    sidebar.style.transform = '';
                    
                    // Also try Alpine store if available
                    if (window.Alpine && window.Alpine.store && window.Alpine.store('sidebar')) {
                        window.Alpine.store('sidebar').isOpen = false;
                    }
                }
                return false;
            };
            
            newBtn.disabled = false;
            newBtn.style.pointerEvents = 'auto';
            newBtn.style.cursor = 'pointer';
        });
    }
    
    // Fix company selector
    function fixCompanySelectorNow() {
        const selects = document.querySelectorAll('select');
        console.log(`[Emergency Button Fix] Found ${selects.length} select elements`);
        
        selects.forEach(select => {
            // Check if it has wire:model="selectedCompanyId"
            if (select.hasAttribute('wire:model') && select.getAttribute('wire:model') === 'selectedCompanyId') {
                console.log('[Emergency Button Fix] Found company selector');
                
                select.disabled = false;
                select.style.pointerEvents = 'auto';
                select.style.cursor = 'pointer';
                select.classList.remove('opacity-50', 'cursor-not-allowed');
                
                // Add change handler
                select.onchange = function(e) {
                    console.log('[Emergency Button Fix] Company changed to:', e.target.value);
                    
                    // Try to trigger Livewire update
                    if (window.Livewire) {
                        // Find component
                        let component = null;
                        let el = select;
                        while (el && !component) {
                            if (el.hasAttribute('wire:id')) {
                                const wireId = el.getAttribute('wire:id');
                                component = window.Livewire.find(wireId);
                                break;
                            }
                            el = el.parentElement;
                        }
                        
                        if (component) {
                            console.log('[Emergency Button Fix] Found Livewire component');
                            if (component.set) {
                                component.set('selectedCompanyId', e.target.value);
                            }
                            if (component.call) {
                                component.call('loadCompanyData');
                            }
                        }
                    }
                };
            }
        });
    }
    
    // Fix portal buttons
    function fixPortalButtonsNow() {
        const buttons = document.querySelectorAll('button');
        let portalBtns = [];
        
        buttons.forEach(btn => {
            const text = btn.textContent.toLowerCase();
            if (text.includes('portal') || text.includes('öffnen') || btn.hasAttribute('wire:click')) {
                portalBtns.push(btn);
            }
        });
        
        console.log(`[Emergency Button Fix] Found ${portalBtns.length} potential portal buttons`);
        
        portalBtns.forEach((btn, i) => {
            btn.disabled = false;
            btn.style.pointerEvents = 'auto';
            btn.style.cursor = 'pointer';
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
            
            const wireClick = btn.getAttribute('wire:click');
            if (wireClick) {
                console.log(`[Emergency Button Fix] Button ${i} has wire:click:`, wireClick);
                
                // Clone to remove existing handlers
                const newBtn = btn.cloneNode(true);
                btn.parentNode.replaceChild(newBtn, btn);
                
                newBtn.onclick = function(e) {
                    e.preventDefault();
                    console.log(`[Emergency Button Fix] Portal button ${i} clicked!`);
                    
                    // Try Livewire
                    if (window.Livewire) {
                        let component = null;
                        let el = newBtn;
                        while (el && !component) {
                            if (el.hasAttribute('wire:id')) {
                                const wireId = el.getAttribute('wire:id');
                                component = window.Livewire.find(wireId);
                                break;
                            }
                            el = el.parentElement;
                        }
                        
                        if (component && component.call) {
                            const match = wireClick.match(/^(\w+)(?:\((.*)\))?$/);
                            if (match) {
                                const method = match[1];
                                console.log(`[Emergency Button Fix] Calling method: ${method}`);
                                component.call(method);
                            }
                        }
                    }
                };
            }
        });
    }
    
    // Fix all dropdowns
    function fixDropdownsNow() {
        const dropdowns = document.querySelectorAll('.fi-dropdown');
        console.log(`[Emergency Button Fix] Found ${dropdowns.length} dropdowns`);
        
        dropdowns.forEach((dropdown, i) => {
            const trigger = dropdown.querySelector('button');
            const panel = dropdown.querySelector('.fi-dropdown-panel, [x-ref="panel"]');
            
            if (trigger && panel) {
                // Hide panel initially
                panel.style.display = 'none';
                panel.style.zIndex = '9999';
                
                // Clone trigger to remove handlers
                const newTrigger = trigger.cloneNode(true);
                trigger.parentNode.replaceChild(newTrigger, trigger);
                
                newTrigger.onclick = function(e) {
                    e.stopPropagation();
                    console.log(`[Emergency Button Fix] Dropdown ${i} clicked`);
                    
                    const isVisible = panel.style.display === 'block';
                    
                    // Close all other dropdowns first
                    document.querySelectorAll('.fi-dropdown-panel').forEach(p => {
                        if (p !== panel) p.style.display = 'none';
                    });
                    
                    panel.style.display = isVisible ? 'none' : 'block';
                };
                
                newTrigger.disabled = false;
                newTrigger.style.pointerEvents = 'auto';
                newTrigger.style.cursor = 'pointer';
            }
        });
        
        // Close dropdowns on outside click
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.fi-dropdown')) {
                document.querySelectorAll('.fi-dropdown-panel').forEach(panel => {
                    panel.style.display = 'none';
                });
            }
        });
    }
    
    // Apply all fixes
    function applyAllFixes() {
        console.log('[Emergency Button Fix] Applying all fixes...');
        fixMobileMenuNow();
        fixCompanySelectorNow();
        fixPortalButtonsNow();
        fixDropdownsNow();
    }
    
    // Run immediately
    applyAllFixes();
    
    // Run again after a short delay
    setTimeout(applyAllFixes, 500);
    setTimeout(applyAllFixes, 1000);
    setTimeout(applyAllFixes, 2000);
    
    // Run on DOM changes
    const observer = new MutationObserver(() => {
        applyAllFixes();
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // Export for manual testing
    window.emergencyButtonFix = {
        applyAll: applyAllFixes,
        testMobileMenu: function() {
            const btn = document.querySelector('.fi-topbar-open-sidebar-btn');
            if (btn) {
                console.log('Clicking mobile menu...');
                btn.click();
            } else {
                console.log('Mobile menu button not found');
            }
        },
        testCompanySelect: function() {
            const select = document.querySelector('select[wire\\:model="selectedCompanyId"]');
            if (select) {
                console.log('Testing company select...');
                if (select.options.length > 1) {
                    select.selectedIndex = 1;
                    select.dispatchEvent(new Event('change'));
                }
            } else {
                console.log('Company select not found');
            }
        },
        showClickableElements: function() {
            document.body.classList.add('debug-clickable');
        }
    };
    
    console.log('[Emergency Button Fix] Ready! Use window.emergencyButtonFix for testing');
    
})();