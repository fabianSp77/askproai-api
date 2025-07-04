// Fix for user dropdown not closing issue
document.addEventListener('DOMContentLoaded', function() {
    // Debug logging
    console.log('Dropdown fix loaded');
    
    // Fix for Filament dropdowns
    document.addEventListener('click', function(event) {
        // Check if click is outside any open dropdown
        const openDropdowns = document.querySelectorAll('[data-state="open"]');
        
        openDropdowns.forEach(dropdown => {
            // If click is outside the dropdown and its trigger
            const trigger = document.querySelector(`[aria-controls="${dropdown.id}"]`);
            
            if (trigger && !dropdown.contains(event.target) && !trigger.contains(event.target)) {
                // Close the dropdown
                dropdown.setAttribute('data-state', 'closed');
                dropdown.classList.add('hidden');
                
                // Update trigger state
                trigger.setAttribute('aria-expanded', 'false');
                
                console.log('Closed dropdown:', dropdown.id);
            }
        });
    });
    
    // Additional fix for Alpine.js dropdowns
    if (window.Alpine) {
        document.addEventListener('alpine:init', () => {
            Alpine.data('dropdownFix', () => ({
                open: false,
                toggle() {
                    this.open = !this.open;
                },
                close() {
                    this.open = false;
                }
            }));
        });
    }
    
    // Force close dropdowns on Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const openDropdowns = document.querySelectorAll('[data-state="open"]');
            openDropdowns.forEach(dropdown => {
                dropdown.setAttribute('data-state', 'closed');
                dropdown.classList.add('hidden');
                
                const trigger = document.querySelector(`[aria-controls="${dropdown.id}"]`);
                if (trigger) {
                    trigger.setAttribute('aria-expanded', 'false');
                }
            });
        }
    });
});

// Export for use in other modules
export function closeAllDropdowns() {
    const openDropdowns = document.querySelectorAll('[data-state="open"]');
    openDropdowns.forEach(dropdown => {
        dropdown.setAttribute('data-state', 'closed');
        dropdown.classList.add('hidden');
        
        const trigger = document.querySelector(`[aria-controls="${dropdown.id}"]`);
        if (trigger) {
            trigger.setAttribute('aria-expanded', 'false');
        }
    });
}