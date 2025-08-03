// Menu Click Fix - Ensures all menu items are clickable
// console.log('🔧 Menu Click Fix Loading...');

document.addEventListener('DOMContentLoaded', () => {
    // console.log('📋 Checking menu clickability...');
    
    // Function to ensure menu items are clickable
    function fixMenuClickability() {
        // Target all sidebar navigation links
        const menuItems = document.querySelectorAll(`
            .fi-sidebar-nav a,
            .fi-sidebar-item,
            .fi-sidebar-nav-link,
            .fi-sidebar-nav button,
            .fi-sidebar-header a,
            [class*="fi-sidebar"] a,
            [class*="fi-sidebar"] button
        `);
        
        // console.log(`Found ${menuItems.length} menu items to fix`);
        
        menuItems.forEach((item, index) => {
            // Remove any blocking styles
            const computedStyle = window.getComputedStyle(item);
            
            if (computedStyle.pointerEvents === 'none') {
                item.style.pointerEvents = 'auto';
                // console.log(`Fixed pointer-events for menu item ${index}`);
            }
            
            // Ensure proper cursor
            if (!item.style.cursor || item.style.cursor === 'default') {
                item.style.cursor = 'pointer';
            }
            
            // Remove any transform that might block clicks
            if (computedStyle.transform && computedStyle.transform !== 'none') {
                item.style.transform = 'none';
            }
            
            // Ensure proper z-index
            if (parseInt(computedStyle.zIndex) < 0) {
                item.style.zIndex = '1';
            }
            
            // Add click event listener if missing
            if (!item.onclick && !item.hasAttribute('wire:click') && !item.hasAttribute('x-on:click')) {
                item.addEventListener('click', function(e) {
                    // console.log('Menu item clicked:', this);
                    // Don't prevent default for links
                    if (this.tagName !== 'A') {
                        e.stopPropagation();
                    }
                });
            }
        });
        
        // Fix mobile menu toggle button
        const mobileMenuButtons = document.querySelectorAll(`
            .fi-topbar-open-sidebar-btn,
            .fi-sidebar-close-btn,
            .fi-sidebar-nav-toggle,
            button[x-on\\:click*="sidebar"]
        `);
        
        mobileMenuButtons.forEach(btn => {
            btn.style.pointerEvents = 'auto';
            btn.style.cursor = 'pointer';
            btn.style.zIndex = '50';
            // console.log('Fixed mobile menu button:', btn);
        });
        
        // Ensure sidebar itself is interactive
        const sidebar = document.querySelector('.fi-sidebar');
        if (sidebar) {
            sidebar.style.pointerEvents = 'auto';
            // console.log('Fixed sidebar container');
        }
    }
    
    // Run immediately
    fixMenuClickability();
    
    // Run again after a short delay (for dynamic content)
    setTimeout(fixMenuClickability, 500);
    
    // Run on Livewire navigations
    if (window.Livewire) {
        Livewire.hook('message.processed', () => {
            setTimeout(fixMenuClickability, 100);
        });
    }
    
    // Run on Alpine.js updates
    if (window.Alpine) {
        document.addEventListener('alpine:initialized', () => {
            setTimeout(fixMenuClickability, 100);
        });
    }
    
    // Monitor for dynamic changes
    const observer = new MutationObserver((mutations) => {
        const hasMenuChanges = mutations.some(mutation => {
            return Array.from(mutation.addedNodes).some(node => {
                return node.nodeType === 1 && (
                    node.classList?.contains('fi-sidebar') ||
                    node.querySelector?.('.fi-sidebar')
                );
            });
        });
        
        if (hasMenuChanges) {
            // console.log('Menu structure changed, reapplying fixes...');
            setTimeout(fixMenuClickability, 100);
        }
    });
    
    // Start observing
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // console.log('✅ Menu Click Fix initialized');
});