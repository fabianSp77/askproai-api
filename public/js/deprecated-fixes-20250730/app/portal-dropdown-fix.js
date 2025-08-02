// Portal Dropdown Fix - Prevents auto-opening of dropdowns
document.addEventListener('DOMContentLoaded', () => {
    console.log('Portal Dropdown Fix: Initializing...');
    
    // Override any auto-open behavior
    const preventAutoOpen = () => {
        // Find all elements with dropdown-like behavior
        const dropdowns = document.querySelectorAll('[x-data*="dropdown"], [x-data*="open"], [x-data*="show"]');
        
        dropdowns.forEach(dropdown => {
            // Get Alpine data
            if (dropdown.__x && dropdown.__x.$data) {
                const data = dropdown.__x.$data;
                
                // Force close any open states
                if ('open' in data) data.open = false;
                if ('isOpen' in data) data.isOpen = false;
                if ('dropdownOpen' in data) data.dropdownOpen = false;
                if ('showTooltip' in data) data.showTooltip = false;
                if ('showDropdown' in data) data.showDropdown = false;
                
                console.log('Portal Dropdown Fix: Closed dropdown', dropdown);
            }
        });
    };
    
    // Run immediately
    preventAutoOpen();
    
    // Run after Alpine initializes
    if (window.Alpine) {
        document.addEventListener('alpine:initialized', preventAutoOpen);
    }
    
    // Run after a short delay to catch late initializations
    setTimeout(preventAutoOpen, 100);
    setTimeout(preventAutoOpen, 500);
});

// Intercept dropdown manager if it exists
if (window.dropdownManager) {
    const originalInit = window.dropdownManager.init;
    window.dropdownManager.init = function() {
        console.log('Portal Dropdown Fix: Intercepting dropdown manager init');
        originalInit.call(this);
        
        // Force close all dropdowns after init
        setTimeout(() => {
            if (this.closeAll) {
                this.closeAll();
            }
        }, 50);
    };
}