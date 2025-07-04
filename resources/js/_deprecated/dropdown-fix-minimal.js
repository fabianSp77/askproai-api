// Minimal Dropdown Fix - Only handles dropdown closing, doesn't interfere with other elements

document.addEventListener('DOMContentLoaded', function() {
    // Ensure all dropdowns are closed on page load
    document.querySelectorAll('.fi-dropdown-panel').forEach(dropdown => {
        // Skip if it's supposed to be open (has specific attributes)
        if (!dropdown.hasAttribute('data-keep-open') && 
            !dropdown.closest('[data-dropdown-open="true"]')) {
            dropdown.classList.add('hidden');
            dropdown.style.display = 'none';
        }
    });
    // Only handle clicks for closing dropdowns
    document.addEventListener('click', function(e) {
        // Don't interfere with dropdown triggers
        if (e.target.closest('[aria-expanded]') || e.target.closest('.fi-dropdown-trigger')) {
            return;
        }
        
        // Close open dropdowns when clicking outside
        if (!e.target.closest('.fi-dropdown-panel')) {
            document.querySelectorAll('.fi-dropdown-panel:not(.hidden)').forEach(dropdown => {
                // Only hide if it's actually visible
                if (dropdown.offsetParent !== null) {
                    dropdown.classList.add('hidden');
                }
            });
        }
    });
    
    // ESC key closes dropdowns
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.fi-dropdown-panel:not(.hidden)').forEach(dropdown => {
                dropdown.classList.add('hidden');
            });
        }
    });
});