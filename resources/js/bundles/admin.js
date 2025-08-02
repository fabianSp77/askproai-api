// Admin Panel JavaScript Bundle
// Consolidated admin-specific JavaScript functionality

console.log('[Admin Bundle] Loading admin panel enhancements...');

// Wait for frameworks to be ready
document.addEventListener('DOMContentLoaded', () => {
    initializeAdminEnhancements();
});

function initializeAdminEnhancements() {
    console.log('[Admin Bundle] Initializing admin enhancements...');
    
    // Fix dropdown interactions
    fixDropdownInteractions();
    
    // Fix table interactions
    fixTableInteractions();
    
    // Initialize mobile navigation
    initializeMobileNavigation();
    
    // Fix form interactions
    fixFormInteractions();
    
    console.log('[Admin Bundle] Admin enhancements initialized');
}

function fixDropdownInteractions() {
    console.log('[Admin Bundle] Fixing dropdown interactions...');
    
    // Use event delegation for dropdown triggers
    document.addEventListener('click', (e) => {
        const dropdownTrigger = e.target.closest('.fi-dropdown-trigger, [x-on\\:click]');
        if (!dropdownTrigger) return;
        
        // Don't interfere with form elements
        if (e.target.matches('input, select, textarea, label')) return;
        
        e.preventDefault();
        e.stopPropagation();
        
        // Find the dropdown container
        const dropdown = dropdownTrigger.closest('[x-data]');
        if (!dropdown) return;
        
        // Try to access Alpine component data
        if (window.Alpine && dropdown._x_dataStack) {
            const alpineData = dropdown._x_dataStack[0];
            if (alpineData && 'open' in alpineData) {
                alpineData.open = !alpineData.open;
                console.log('[Admin Bundle] Toggled dropdown via Alpine:', alpineData.open);
            }
        }
    }, true);
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('[x-data*="open"]')) {
            document.querySelectorAll('[x-data*="open"]').forEach(dropdown => {
                if (window.Alpine && dropdown._x_dataStack) {
                    const alpineData = dropdown._x_dataStack[0];
                    if (alpineData && alpineData.open === true) {
                        alpineData.open = false;
                    }
                }
            });
        }
    });
}

function fixTableInteractions() {
    console.log('[Admin Bundle] Fixing table interactions...');
    
    // Enable horizontal scrolling for tables
    document.querySelectorAll('.fi-ta-table-container').forEach(container => {
        container.style.overflowX = 'auto';
        container.style.maxWidth = '100%';
        
        // Add scroll indicators
        const table = container.querySelector('table');
        if (table) {
            table.style.minWidth = 'max-content';
        }
    });
    
    // Fix table action buttons
    document.querySelectorAll('.fi-ta-actions-item').forEach(item => {
        item.style.pointerEvents = 'auto';
        item.style.cursor = 'pointer';
    });
    
    // Fix pagination links
    document.querySelectorAll('.fi-pagination-item').forEach(item => {
        item.style.pointerEvents = 'auto';
        item.style.cursor = 'pointer';
    });
}

function initializeMobileNavigation() {
    console.log('[Admin Bundle] Initializing mobile navigation...');
    
    // Fix mobile sidebar toggle
    const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', (e) => {
            e.preventDefault();
            document.body.classList.toggle('fi-sidebar-open');
        });
    }
    
    // Fix mobile navigation menu items
    document.querySelectorAll('.fi-sidebar-nav-item').forEach(item => {
        item.style.pointerEvents = 'auto';
        item.style.cursor = 'pointer';
    });
}

function fixFormInteractions() {
    console.log('[Admin Bundle] Fixing form interactions...');
    
    // Fix form buttons
    document.querySelectorAll('.fi-btn').forEach(btn => {
        btn.style.pointerEvents = 'auto';
        btn.style.cursor = 'pointer';
    });
    
    // Fix form field interactions
    document.querySelectorAll('.fi-input, .fi-select, .fi-textarea').forEach(field => {
        field.style.pointerEvents = 'auto';
    });
    
    // Fix checkbox and radio interactions
    document.querySelectorAll('input[type="checkbox"], input[type="radio"]').forEach(input => {
        input.style.pointerEvents = 'auto';
        input.style.cursor = 'pointer';
    });
}

// Global utility functions
window.adminUtils = {
    fixClickability: function() {
        document.querySelectorAll('a, button, [role="button"], .fi-btn, .fi-link').forEach(el => {
            if (getComputedStyle(el).pointerEvents === 'none') {
                el.style.pointerEvents = 'auto';
                el.style.cursor = 'pointer';
            }
        });
    },
    
    refreshComponents: function() {
        setTimeout(() => {
            initializeAdminEnhancements();
        }, 100);
    },
    
    debug: function() {
        return {
            clickableElements: document.querySelectorAll('a, button, [role="button"]').length,
            dropdowns: document.querySelectorAll('[x-data*="open"]').length,
            blockedElements: Array.from(document.querySelectorAll('a, button, [role="button"]')).filter(el => 
                getComputedStyle(el).pointerEvents === 'none'
            ).length
        };
    }
};

// Listen for Livewire component updates
if (typeof Livewire !== 'undefined') {
    Livewire.hook('component.initialized', () => {
        adminUtils.refreshComponents();
    });
    
    Livewire.hook('message.processed', () => {
        adminUtils.refreshComponents();
    });
}

// Re-initialize after Alpine updates
if (typeof Alpine !== 'undefined') {
    Alpine.nextTick(() => {
        adminUtils.refreshComponents();
    });
}

console.log('[Admin Bundle] Admin bundle loaded successfully');