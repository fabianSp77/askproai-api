/**
 * Branch Dropdown Fix
 * Ensures the branch selector dropdown works correctly
 */

// Wait for DOM and Alpine to be ready
const initBranchDropdownFix = () => {
    console.log('Branch Dropdown Fix initializing...');
    
    // Find all branch selector dropdowns
    const branchSelectors = document.querySelectorAll('.branch-selector-dropdown');
    
    branchSelectors.forEach(selector => {
        // Ensure Alpine is initialized on this element
        if (selector.__x) {
            console.log('Alpine already initialized on branch selector');
            return;
        }
        
        // If Alpine is available but not initialized on this element
        if (window.Alpine) {
            console.log('Manually initializing Alpine on branch selector');
            Alpine.initTree(selector);
        }
    });
    
    // Global click handler to close dropdowns
    document.addEventListener('click', (event) => {
        // Check all branch selectors
        document.querySelectorAll('.branch-selector-dropdown').forEach(selector => {
            if (!selector.contains(event.target) && selector.__x && selector.__x.$data.open) {
                selector.__x.$data.close();
            }
        });
    }, true);
    
    // Fix for branch selection
    document.addEventListener('click', (event) => {
        const menuItem = event.target.closest('button[role="menuitem"]');
        if (menuItem && menuItem.closest('.branch-selector-dropdown')) {
            console.log('Branch menu item clicked');
            // The selectBranch function should handle navigation
        }
    });
};

// Try multiple initialization strategies
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBranchDropdownFix);
} else {
    initBranchDropdownFix();
}

// Also try when Alpine initializes
document.addEventListener('alpine:init', initBranchDropdownFix);
document.addEventListener('alpine:initialized', initBranchDropdownFix);

// Livewire hook for when components are updated
if (window.Livewire) {
    Livewire.hook('message.processed', (message, component) => {
        setTimeout(initBranchDropdownFix, 100);
    });
}

// Export for debugging
window.debugBranchDropdown = () => {
    const selectors = document.querySelectorAll('.branch-selector-dropdown');
    selectors.forEach((selector, index) => {
        console.log(`Branch Selector ${index}:`, {
            element: selector,
            alpineData: selector.__x?.$data,
            isOpen: selector.__x?.$data.open,
            branches: selector.__x?.$data.branches
        });
    });
};