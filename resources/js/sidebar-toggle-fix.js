/**
 * Sidebar Toggle Fix
 * Ensures the burger menu works properly
 */

(function() {
    'use strict';
    
    // Function to ensure sidebar toggle works
    function fixSidebarToggle() {
        // Find all possible burger menu buttons
        const toggleButtons = document.querySelectorAll(
            '.fi-topbar-open-sidebar-btn, ' +
            '[onclick*="classList.toggle(\'fi-sidebar-open\')"], ' +
            '[x-on\\:click*="sidebar"]'
        );
        
        toggleButtons.forEach(button => {
            // Remove any existing onclick handlers
            button.removeAttribute('onclick');
            
            // Add clean click handler
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Toggle the sidebar open class
                document.body.classList.toggle('fi-sidebar-open');
                
                // Also check for Alpine.js store
                if (window.Alpine && window.Alpine.store && window.Alpine.store('sidebar')) {
                    window.Alpine.store('sidebar').toggle();
                }
                
                console.log('Sidebar toggled, open:', document.body.classList.contains('fi-sidebar-open'));
            });
        });
        
        // Ensure sidebar overlay works
        const overlay = document.querySelector('.fi-sidebar-overlay');
        if (overlay) {
            overlay.addEventListener('click', function() {
                document.body.classList.remove('fi-sidebar-open');
                if (window.Alpine && window.Alpine.store && window.Alpine.store('sidebar')) {
                    window.Alpine.store('sidebar').close();
                }
            });
        }
    }
    
    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fixSidebarToggle);
    } else {
        fixSidebarToggle();
    }
    
    // Re-run after Livewire updates
    if (window.Livewire) {
        window.Livewire.hook('message.processed', () => {
            setTimeout(fixSidebarToggle, 100);
        });
    }
    
    // Also run after Alpine initializes
    document.addEventListener('alpine:init', () => {
        setTimeout(fixSidebarToggle, 100);
    });
})();