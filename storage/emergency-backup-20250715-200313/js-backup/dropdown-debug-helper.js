// Dropdown Debug Helper
window.dropdownDebug = {
    analyze: function() {
        console.log('=== DROPDOWN ANALYSIS ===');
        
        // Find all potential dropdowns
        const dropdowns = {
            filament: document.querySelectorAll('.fi-dropdown'),
            alpine: document.querySelectorAll('[x-data*="dropdown"], [x-data*="open"]'),
            select: document.querySelectorAll('.fi-fo-select'),
            filters: document.querySelectorAll('.fi-ta-filters'),
            dateRange: document.querySelectorAll('[wire\\:model*="date"], .fi-fo-date-time-picker'),
            search: document.querySelectorAll('[wire\\:model*="search"], input[role="combobox"]'),
            buttons: document.querySelectorAll('button[aria-haspopup="true"], button[aria-expanded]')
        };
        
        Object.entries(dropdowns).forEach(([type, elements]) => {
            console.log(`${type}: ${elements.length} found`);
            if (elements.length > 0 && elements.length <= 5) {
                elements.forEach((el, i) => {
                    console.log(`  ${type}[${i}]:`, {
                        classes: el.className,
                        wireModel: el.getAttribute('wire:model'),
                        xData: el.getAttribute('x-data'),
                        hasButton: !!el.querySelector('button'),
                        hasPanel: !!el.querySelector('.absolute, .fi-dropdown-panel')
                    });
                });
            }
        });
        
        console.log('\n=== INTERACTIVE TEST ===');
        console.log('Run dropdownDebug.test(index) to test a specific dropdown');
        console.log('Run dropdownDebug.fixAll() to try fixing all dropdowns');
    },
    
    test: function(index = 0) {
        const dropdown = document.querySelectorAll('.fi-dropdown')[index];
        if (!dropdown) {
            console.error('Dropdown not found at index', index);
            return;
        }
        
        console.log('Testing dropdown:', dropdown);
        
        const button = dropdown.querySelector('button');
        const panel = dropdown.querySelector('.fi-dropdown-panel, .absolute');
        
        if (button) {
            console.log('Clicking button...');
            button.click();
            
            setTimeout(() => {
                if (panel) {
                    console.log('Panel visibility:', panel.style.display, 'Classes:', panel.className);
                    console.log('Panel computed style:', getComputedStyle(panel).display);
                }
            }, 100);
        }
    },
    
    fixAll: function() {
        console.log('Attempting to fix all dropdowns...');
        
        // Remove all event listeners and re-add simple ones
        document.querySelectorAll('.fi-dropdown').forEach((dropdown, i) => {
            const button = dropdown.querySelector('button');
            const panel = dropdown.querySelector('.fi-dropdown-panel, .absolute');
            
            if (!button || !panel) return;
            
            // Clone button to remove all listeners
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
            
            // Add simple toggle
            let isOpen = false;
            newButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                isOpen = !isOpen;
                
                // Force visibility
                if (isOpen) {
                    panel.style.display = 'block !important';
                    panel.style.visibility = 'visible !important';
                    panel.style.opacity = '1 !important';
                    panel.style.position = 'absolute';
                    panel.style.zIndex = '99999';
                    panel.classList.remove('hidden', 'invisible');
                    console.log(`Dropdown ${i} opened (forced)`);
                } else {
                    panel.style.display = 'none';
                    console.log(`Dropdown ${i} closed`);
                }
            });
            
            console.log(`Fixed dropdown ${i}`);
        });
    },
    
    showWireModels: function() {
        console.log('=== WIRE:MODEL ATTRIBUTES ===');
        document.querySelectorAll('[wire\\:model]').forEach(el => {
            console.log(el.getAttribute('wire:model'), ':', el.tagName, el.className);
        });
    },
    
    trackClicks: function() {
        console.log('Click tracking enabled. Click any element to see info.');
        document.addEventListener('click', function(e) {
            console.log('Clicked:', {
                element: e.target,
                tagName: e.target.tagName,
                classes: e.target.className,
                wireClick: e.target.getAttribute('wire:click'),
                wireModel: e.target.getAttribute('wire:model'),
                xOnClick: e.target.getAttribute('x-on:click'),
                parent: e.target.parentElement?.className
            });
        }, true);
    }
};