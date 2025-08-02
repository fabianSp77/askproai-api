/**
 * Filament Mobile Fix Final
 * Works with the existing Alpine.js sidebar store from Filament
 */

(function() {
    'use strict';
    
    console.log('[Filament Mobile Fix] Starting...');
    
    // Wait for Alpine to be ready
    function waitForAlpine(callback) {
        if (window.Alpine && Alpine.store) {
            callback();
        } else {
            setTimeout(() => waitForAlpine(callback), 100);
        }
    }
    
    // Fix mobile menu functionality
    function fixMobileMenu() {
        console.log('[Filament Mobile Fix] Fixing mobile menu...');
        
        // Get the sidebar store
        const sidebarStore = Alpine.store('sidebar');
        if (!sidebarStore) {
            console.error('[Filament Mobile Fix] Sidebar store not found!');
            return;
        }
        
        // Find the mobile menu button
        const menuButtons = document.querySelectorAll(`
            button[x-on\\:click*="sidebar"],
            button[x-on\\:click*="$store.sidebar"],
            .fi-topbar button:first-child,
            .fi-icon-btn
        `);
        
        console.log('[Filament Mobile Fix] Found buttons:', menuButtons.length);
        
        menuButtons.forEach((button, index) => {
            // Make sure button is visible and clickable
            button.style.display = 'flex';
            button.style.opacity = '1';
            button.style.visibility = 'visible';
            button.style.pointerEvents = 'auto';
            button.style.cursor = 'pointer';
            button.style.zIndex = '9999';
            
            // Remove any blocking styles
            button.style.position = 'relative';
            
            // Visual feedback for debugging
            if (window.location.search.includes('debug')) {
                button.style.border = '2px solid red';
            }
            
            // Re-bind the click event properly
            button.removeAttribute('x-on:click');
            button.addEventListener('click', function(e) {
                e.stopPropagation();
                console.log('[Filament Mobile Fix] Menu button clicked!');
                
                // Use Filament's sidebar store methods
                if (sidebarStore.isOpen) {
                    sidebarStore.close();
                } else {
                    sidebarStore.open();
                }
                
                // Also toggle the body class
                document.body.classList.toggle('fi-sidebar-open', sidebarStore.isOpen);
            });
        });
        
        // Fix click outside to close
        document.addEventListener('click', function(e) {
            if (window.innerWidth < 1024) {
                const sidebar = document.querySelector('.fi-sidebar');
                const menuButton = e.target.closest('button');
                
                if (sidebarStore.isOpen && 
                    !sidebar?.contains(e.target) && 
                    !menuButton) {
                    console.log('[Filament Mobile Fix] Closing sidebar (click outside)');
                    sidebarStore.close();
                    document.body.classList.remove('fi-sidebar-open');
                }
            }
        });
        
        // Escape key to close
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebarStore.isOpen) {
                sidebarStore.close();
                document.body.classList.remove('fi-sidebar-open');
            }
        });
    }
    
    // Fix pointer-events issues
    function fixPointerEvents() {
        console.log('[Filament Mobile Fix] Fixing pointer-events...');
        
        // Remove all pointer-events: none
        const elementsWithPointerEventsNone = document.querySelectorAll('[style*="pointer-events: none"]');
        elementsWithPointerEventsNone.forEach(el => {
            el.style.pointerEvents = 'auto';
        });
        
        // Ensure important elements are clickable
        const importantSelectors = [
            'button',
            'a',
            'input',
            'select',
            'textarea',
            '[role="button"]',
            '[x-on\\:click]',
            '[wire\\:click]'
        ];
        
        importantSelectors.forEach(selector => {
            document.querySelectorAll(selector).forEach(el => {
                el.style.pointerEvents = 'auto';
                el.style.cursor = 'pointer';
            });
        });
    }
    
    // Monitor for dynamic content
    function setupMutationObserver() {
        const observer = new MutationObserver(() => {
            fixPointerEvents();
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['style']
        });
    }
    
    // Initialize when Alpine is ready
    waitForAlpine(() => {
        console.log('[Filament Mobile Fix] Alpine is ready!');
        
        // Initial fixes
        fixMobileMenu();
        fixPointerEvents();
        setupMutationObserver();
        
        // Re-run after Livewire updates
        if (window.Livewire) {
            Livewire.hook('message.processed', () => {
                setTimeout(() => {
                    fixMobileMenu();
                    fixPointerEvents();
                }, 100);
            });
        }
        
        // Also listen for Alpine init event
        document.addEventListener('alpine:initialized', () => {
            fixMobileMenu();
        });
    });
    
    // Debug utilities
    window.filamentMobileDebug = {
        checkSidebar: function() {
            const store = Alpine.store('sidebar');
            console.log('Sidebar store:', store);
            console.log('isOpen:', store?.isOpen);
        },
        
        toggleSidebar: function() {
            const store = Alpine.store('sidebar');
            if (store.isOpen) {
                store.close();
            } else {
                store.open();
            }
            document.body.classList.toggle('fi-sidebar-open', store.isOpen);
        },
        
        findButtons: function() {
            const buttons = document.querySelectorAll('button');
            buttons.forEach((btn, i) => {
                console.log(`Button ${i}:`, btn, 'Computed styles:', {
                    display: getComputedStyle(btn).display,
                    visibility: getComputedStyle(btn).visibility,
                    pointerEvents: getComputedStyle(btn).pointerEvents,
                    opacity: getComputedStyle(btn).opacity
                });
            });
        }
    };
    
    console.log('[Filament Mobile Fix] Ready! Debug with: filamentMobileDebug.checkSidebar()');
})();