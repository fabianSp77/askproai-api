// Enhanced Dropdown Debugging
window.debugDropdowns = function() {
    //console.log('=== Dropdown Debug Info ===');
    
    // Check Alpine
    //console.log('Alpine.js:', !!window.Alpine, window.Alpine?.version);
    
    // Check Livewire
    //console.log('Livewire:', !!window.Livewire);
    if (window.Livewire) {
        //console.log('Livewire components:', Object.keys(window.Livewire.components.componentsById).length);
    }
    
    // Find all dropdowns
    const selectors = [
        '.fi-fo-select',
        '.fi-select-container',
        '[x-data*="select"]',
        '[x-data*="dropdown"]',
        '.choices',
        '.fi-dropdown-panel'
    ];
    
    selectors.forEach(selector => {
        const elements = document.querySelectorAll(selector);
        if (elements.length > 0) {
            //console.log(`Found ${elements.length} elements matching "${selector}"`);
            
            // Check first element
            const first = elements[0];
            //console.log('First element:', first);
            //console.log('Alpine data:', first._x_dataStack);
            //console.log('Has x-data:', first.hasAttribute('x-data'));
            //console.log('x-data value:', first.getAttribute('x-data'));
            
            // Check for search inputs
            const searchInput = first.querySelector('input[type="search"], input[x-ref="searchInput"]');
            if (searchInput) {
                //console.log('Search input found:', searchInput);
                //console.log('Autocomplete:', searchInput.getAttribute('autocomplete'));
                //console.log('Disabled:', searchInput.disabled);
                //console.log('Readonly:', searchInput.readOnly);
            }
        }
    });
    
    // Check for specific issues
    //console.log('\n=== Checking for Issues ===');
    
    // Multiple Alpine instances
    if (window.Alpine && window.FilamentAlpine && window.Alpine !== window.FilamentAlpine) {
        console.error('⚠️ Multiple Alpine instances detected!');
    }
    
    // Check for event listeners
    const buttons = document.querySelectorAll('.fi-select-trigger button');
    //console.log(`Found ${buttons.length} dropdown trigger buttons`);
    
    // Test click on first button
    if (buttons.length > 0) {
        //console.log('Testing click on first dropdown...');
        const testButton = buttons[0];
        //console.log('Button:', testButton);
        //console.log('onclick:', testButton.onclick);
        //console.log('Alpine click handler:', testButton.getAttribute('x-on:click'));
        //console.log('Wire click handler:', testButton.getAttribute('wire:click'));
    }
    
    return 'Debug complete - check console output';
};

// Auto-run on load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(() => {
            //console.log('Auto-running dropdown debug...');
            window.debugDropdowns();
        }, 1000);
    });
}