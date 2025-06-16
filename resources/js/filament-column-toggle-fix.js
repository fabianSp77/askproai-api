/**
 * Fix for Filament column toggle dropdown overflow
 * This script dynamically adjusts the dropdown height based on viewport
 */

document.addEventListener('DOMContentLoaded', function() {
    // Use MutationObserver to watch for dropdown creation
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1) { // Element node
                    // Check if it's a column toggle dropdown
                    const columnToggle = node.querySelector?.('.fi-ta-col-toggle .fi-dropdown-panel');
                    if (columnToggle || (node.classList?.contains('fi-dropdown-panel') && node.closest('.fi-ta-col-toggle'))) {
                        adjustColumnToggleHeight(columnToggle || node);
                    }
                }
            });
        });
    });

    // Start observing the document body for changes
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

    function adjustColumnToggleHeight(dropdown) {
        const viewportHeight = window.innerHeight;
        const dropdownRect = dropdown.getBoundingClientRect();
        const topOffset = dropdownRect.top;
        const bottomPadding = 20; // Leave some space at the bottom
        
        // Calculate maximum available height
        const maxAvailableHeight = viewportHeight - topOffset - bottomPadding;
        
        // Set responsive max heights
        let maxHeight;
        if (window.innerWidth >= 768) {
            // Desktop: Use 70% of viewport or 600px, whichever is smaller
            maxHeight = Math.min(maxAvailableHeight, viewportHeight * 0.7, 600);
        } else {
            // Mobile: Use 60% of viewport or available height
            maxHeight = Math.min(maxAvailableHeight, viewportHeight * 0.6);
        }
        
        // Apply the calculated height
        dropdown.style.maxHeight = `${maxHeight}px`;
        dropdown.style.overflowY = 'auto';
        
        // Add smooth scrolling
        dropdown.style.scrollBehavior = 'smooth';
        
        // Ensure the content div also has proper scrolling
        const contentDiv = dropdown.querySelector(':scope > div');
        if (contentDiv) {
            contentDiv.style.maxHeight = '100%';
            contentDiv.style.overflowY = 'auto';
        }
    }
    
    // Also adjust on window resize
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            const dropdowns = document.querySelectorAll('.fi-ta-col-toggle .fi-dropdown-panel');
            dropdowns.forEach(adjustColumnToggleHeight);
        }, 250);
    });
    
    // Listen for Livewire updates
    if (window.Livewire) {
        Livewire.hook('message.processed', () => {
            setTimeout(() => {
                const dropdowns = document.querySelectorAll('.fi-ta-col-toggle .fi-dropdown-panel');
                dropdowns.forEach(adjustColumnToggleHeight);
            }, 100);
        });
    }
});

// Alpine.js integration for immediate response
document.addEventListener('alpine:init', () => {
    Alpine.directive('column-toggle-fix', (el) => {
        // Apply fix when Alpine initializes the element
        if (el.classList.contains('fi-dropdown-panel') && el.closest('.fi-ta-col-toggle')) {
            setTimeout(() => {
                const viewportHeight = window.innerHeight;
                const rect = el.getBoundingClientRect();
                const maxHeight = Math.min(viewportHeight - rect.top - 20, 600);
                el.style.maxHeight = `${maxHeight}px`;
                el.style.overflowY = 'auto';
            }, 50);
        }
    });
});