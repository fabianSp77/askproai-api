// Minimal UI Fix - Safe version without loops or heavy operations
(function() {
    'use strict';
    
    console.log('[Minimal UI Fix] Loading...');
    
    // Only run once
    if (window.minimalUIFixApplied) {
        console.log('[Minimal UI Fix] Already applied, skipping...');
        return;
    }
    window.minimalUIFixApplied = true;
    
    // Wait for DOM ready
    function onReady(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }
    
    // Simple mobile menu fix
    function fixMobileMenu() {
        const openBtn = document.querySelector('.fi-topbar-open-sidebar-btn');
        const closeBtn = document.querySelector('.fi-topbar-close-sidebar-btn');
        const sidebar = document.querySelector('.fi-sidebar');
        
        if (openBtn && sidebar) {
            openBtn.onclick = function(e) {
                e.preventDefault();
                sidebar.classList.add('fi-sidebar-open', 'translate-x-0');
                sidebar.classList.remove('-translate-x-full');
                console.log('[Minimal UI Fix] Sidebar opened');
            };
        }
        
        if (closeBtn && sidebar) {
            closeBtn.onclick = function(e) {
                e.preventDefault();
                sidebar.classList.remove('fi-sidebar-open', 'translate-x-0');
                sidebar.classList.add('-translate-x-full');
                console.log('[Minimal UI Fix] Sidebar closed');
            };
        }
    }
    
    // Fix company selector
    function fixCompanySelector() {
        const selects = document.querySelectorAll('select');
        selects.forEach(select => {
            if (select.hasAttribute('wire:model') && select.getAttribute('wire:model') === 'selectedCompanyId') {
                console.log('[Minimal UI Fix] Found company selector');
                select.disabled = false;
                select.classList.remove('opacity-50', 'cursor-not-allowed');
                
                // Simple change handler
                if (!select.dataset.minimalFixed) {
                    select.dataset.minimalFixed = 'true';
                    select.addEventListener('change', function(e) {
                        console.log('[Minimal UI Fix] Company selected:', e.target.value);
                        // Let Livewire handle the rest naturally
                    });
                }
            }
        });
    }
    
    // Fix portal buttons
    function fixPortalButtons() {
        const buttons = document.querySelectorAll('button');
        buttons.forEach(button => {
            if (button.hasAttribute('wire:click') && 
                (button.getAttribute('wire:click').includes('openCustomerPortal') || 
                 button.getAttribute('wire:click').includes('openPortal'))) {
                
                console.log('[Minimal UI Fix] Found portal button');
                button.disabled = false;
                button.classList.remove('opacity-50', 'cursor-not-allowed');
                button.style.cursor = 'pointer';
            }
        });
    }
    
    // Fix all dropdowns - Enhanced version
    function fixDropdowns() {
        console.log('[Minimal UI Fix] Fixing dropdowns...');
        
        // Fix Filament dropdowns
        const dropdowns = document.querySelectorAll('.fi-dropdown');
        dropdowns.forEach((dropdown, index) => {
            const trigger = dropdown.querySelector('button[type="button"], [x-on\\:click]');
            const panel = dropdown.querySelector('.fi-dropdown-panel, [x-ref="panel"]');
            
            if (trigger && panel && !trigger.dataset.minimalFixed) {
                trigger.dataset.minimalFixed = 'true';
                console.log(`[Minimal UI Fix] Fixing dropdown ${index + 1}`);
                
                // Ensure panel is hidden initially
                panel.style.display = 'none';
                
                // Add click handler
                trigger.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const isVisible = panel.style.display === 'block';
                    
                    // Close all other dropdowns
                    document.querySelectorAll('.fi-dropdown-panel').forEach(p => {
                        if (p !== panel) p.style.display = 'none';
                    });
                    
                    // Toggle this dropdown
                    panel.style.display = isVisible ? 'none' : 'block';
                    console.log(`[Minimal UI Fix] Dropdown ${index + 1} ${isVisible ? 'closed' : 'opened'}`);
                    
                    // Update Alpine state if available
                    if (dropdown.__x && dropdown.__x.$data) {
                        dropdown.__x.$data.open = !isVisible;
                    }
                });
                
                // Make dropdown items clickable
                panel.querySelectorAll('button, a, [role="button"]').forEach(item => {
                    item.style.cursor = 'pointer';
                    item.style.pointerEvents = 'auto';
                });
            }
        });
        
        // Fix date range pickers and other select-style dropdowns
        const selects = document.querySelectorAll('.fi-input-wrp select, [x-data*="select"]');
        selects.forEach(select => {
            if (!select.dataset.minimalFixed) {
                select.dataset.minimalFixed = 'true';
                select.disabled = false;
                select.style.cursor = 'pointer';
                console.log('[Minimal UI Fix] Fixed select dropdown');
            }
        });
        
        // Close dropdowns when clicking outside
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
        fixMobileMenu();
        fixCompanySelector();
        fixPortalButtons();
        fixDropdowns();
    }
    
    // Apply fixes once DOM is ready
    onReady(function() {
        console.log('[Minimal UI Fix] Applying fixes...');
        applyAllFixes();
        console.log('[Minimal UI Fix] Done');
        
        // Watch for new content (for dynamically loaded dropdowns)
        const observer = new MutationObserver(function(mutations) {
            let hasNewDropdowns = false;
            mutations.forEach(mutation => {
                if (mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeType === 1 && node.querySelector && 
                            (node.querySelector('.fi-dropdown') || node.classList?.contains('fi-dropdown'))) {
                            hasNewDropdowns = true;
                        }
                    });
                }
            });
            
            if (hasNewDropdowns) {
                console.log('[Minimal UI Fix] New dropdowns detected, reapplying fixes...');
                setTimeout(fixDropdowns, 100);
            }
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    });
    
    // Also apply fixes after Livewire navigations
    document.addEventListener('livewire:navigated', function() {
        console.log('[Minimal UI Fix] Livewire navigation detected, reapplying fixes...');
        setTimeout(applyAllFixes, 100);
    });
    
    // Export for manual testing
    window.minimalUIFix = {
        applyAll: applyAllFixes,
        fixDropdowns: fixDropdowns,
        status: function() {
            const dropdowns = document.querySelectorAll('.fi-dropdown');
            const fixedDropdowns = document.querySelectorAll('[data-minimal-fixed="true"]');
            console.log(`Found ${dropdowns.length} dropdowns, ${fixedDropdowns.length} are fixed`);
        }
    };
    
})();