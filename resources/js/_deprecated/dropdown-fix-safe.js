// Safe Dropdown Fix - Works with Filament's Alpine.js implementation

document.addEventListener('DOMContentLoaded', function() {
    // Wait a bit for Alpine to initialize
    setTimeout(function() {
        // Only add click outside listener, don't mess with initial state
        document.addEventListener('click', function(e) {
            // Check if we clicked outside any dropdown
            if (!e.target.closest('.fi-dropdown-panel') && 
                !e.target.closest('[x-data]') && 
                !e.target.closest('[aria-expanded="true"]')) {
                
                // Tell Alpine to close dropdowns
                document.querySelectorAll('[x-data*="open"]').forEach(component => {
                    if (component.__x && component.__x.$data && component.__x.$data.open) {
                        component.__x.$data.open = false;
                    }
                });
            }
        }, true);
        
        // ESC key support
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('[x-data*="open"]').forEach(component => {
                    if (component.__x && component.__x.$data && component.__x.$data.open) {
                        component.__x.$data.open = false;
                    }
                });
            }
        });
    }, 100);
});