/**
 * Mobile Sidebar Text Fix
 * Forces text visibility in mobile sidebar
 */

document.addEventListener('DOMContentLoaded', function() {
    // Watch for sidebar open state changes
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                const body = mutation.target;
                const hasSidebarOpen = body.classList.contains('fi-sidebar-open');
                const isMobile = window.innerWidth < 1024;
                
                if (hasSidebarOpen && isMobile) {
                    // Force Alpine store to correct state
                    if (window.Alpine && window.Alpine.store('sidebar')) {
                        window.Alpine.store('sidebar').isOpen = true;
                    }
                    
                    // Force all x-show elements to display
                    const xShowElements = document.querySelectorAll('.fi-sidebar [x-show]');
                    xShowElements.forEach(el => {
                        el.style.display = '';
                        el.style.opacity = '1';
                        el.style.visibility = 'visible';
                    });
                    
                    // Ensure all text labels are visible
                    const textLabels = document.querySelectorAll('.fi-sidebar-item-label, .fi-sidebar-group-label');
                    textLabels.forEach(label => {
                        label.style.display = 'inline-block';
                        label.style.opacity = '1';
                        label.style.visibility = 'visible';
                    });
                    
                    console.log('Mobile sidebar text fix applied');
                }
            }
        });
    });
    
    // Start observing body class changes
    observer.observe(document.body, {
        attributes: true,
        attributeFilter: ['class']
    });
    
    // Also fix on Alpine init
    document.addEventListener('alpine:initialized', function() {
        const body = document.body;
        const hasSidebarOpen = body.classList.contains('fi-sidebar-open');
        const isMobile = window.innerWidth < 1024;
        
        if (hasSidebarOpen && isMobile && window.Alpine.store('sidebar')) {
            window.Alpine.store('sidebar').isOpen = true;
        }
    });
});