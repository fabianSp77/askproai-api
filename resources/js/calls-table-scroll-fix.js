// Calls Table Scroll Fix - Ensures table is scrollable after Livewire loads
console.log('[Calls Table Fix] Initializing...');

function fixCallsTableScroll() {
    console.log('[Calls Table Fix] Attempting to fix table scroll...');
    
    // Try multiple selectors
    const selectors = [
        '.fi-ta-table-wrap',
        '.fi-table-wrap',
        'div:has(> table)',
        '.fi-ta-content',
        '.fi-ta-content-ctn',
        '[wire\\:id] table',
        '.fi-resource-list-records-ctn table'
    ];
    
    let fixed = false;
    
    for (const selector of selectors) {
        try {
            const elements = document.querySelectorAll(selector);
            elements.forEach(el => {
                // Check if this contains a table
                const hasTable = el.querySelector('table') || el.tagName === 'TABLE';
                if (hasTable) {
                    // Get the wrapper (parent of table if el is table)
                    const wrapper = el.tagName === 'TABLE' ? el.parentElement : el;
                    
                    // Apply scroll fix - WICHTIG: overflow muss separat gesetzt werden!
                    // Erst alle overflow-Properties entfernen
                    wrapper.style.removeProperty('overflow');
                    wrapper.style.removeProperty('overflow-x');
                    wrapper.style.removeProperty('overflow-y');
                    
                    // Dann nur overflow-x setzen mit cssText
                    wrapper.style.cssText = wrapper.style.cssText + '; overflow-x: auto !important; overflow-y: visible !important; max-width: 100% !important; width: 100% !important; display: block !important; -webkit-overflow-scrolling: touch !important;';
                    
                    // Ensure table can expand
                    const table = wrapper.querySelector('table') || el;
                    if (table && table.tagName === 'TABLE') {
                        table.style.setProperty('width', 'max-content', 'important');
                        table.style.setProperty('min-width', '100%', 'important');
                        table.style.setProperty('table-layout', 'auto', 'important');
                    }
                    
                    console.log('[Calls Table Fix] Fixed element:', wrapper);
                    fixed = true;
                }
            });
        } catch (e) {
            console.error('[Calls Table Fix] Error with selector:', selector, e);
        }
    }
    
    // Also fix parent containers - aber NICHT die, die fi-ta-content enthalten
    const containers = document.querySelectorAll('.fi-main, .fi-main-ctn, .fi-page, .fi-ta-ctn');
    containers.forEach(container => {
        // Nur wenn es NICHT den scrollbaren content enthält
        if (!container.querySelector('.fi-ta-content')) {
            container.style.setProperty('overflow-x', 'visible', 'important');
            container.style.setProperty('max-width', '100%', 'important');
        }
    });
    
    if (fixed) {
        console.log('[Calls Table Fix] Successfully applied scroll fix');
    } else {
        console.log('[Calls Table Fix] No table elements found');
    }
}

// Minimal Livewire fix - target fi-ta-content specifically
function minimalLivewireFix() {
    console.log('Livewire fix - minimal version active');
    
    const contents = document.querySelectorAll('.fi-ta-content');
    contents.forEach(content => {
        // Remove any existing overflow styles
        content.style.removeProperty('overflow');
        content.style.removeProperty('overflow-x'); 
        content.style.removeProperty('overflow-y');
        
        // Set only overflow-x using cssText to avoid property combination
        const currentStyle = content.style.cssText;
        content.style.cssText = currentStyle + '; overflow-x: auto !important;';
        
        console.log('Fixed fi-ta-content:', content);
    });
}

// Run on different events to catch dynamic content
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(fixCallsTableScroll, 100);
    setTimeout(minimalLivewireFix, 200);
});

// Run after Livewire loads
if (window.Livewire) {
    Livewire.hook('component.initialized', (component) => {
        console.log('[Calls Table Fix] Livewire component initialized');
        setTimeout(fixCallsTableScroll, 100);
    });
    
    Livewire.hook('message.processed', (message, component) => {
        console.log('[Calls Table Fix] Livewire message processed');
        setTimeout(fixCallsTableScroll, 100);
        setTimeout(minimalLivewireFix, 150);
    });
}

// Run on page visibility change
document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
        fixCallsTableScroll();
    }
});

// Run periodically for dynamic content
let fixInterval = setInterval(() => {
    if (document.querySelector('table')) {
        fixCallsTableScroll();
        // Stop after finding and fixing
        setTimeout(() => clearInterval(fixInterval), 5000);
    }
}, 1000);

// Also listen for Filament table updates
document.addEventListener('table-updated', fixCallsTableScroll);

// Export for manual trigger
window.fixCallsTableScroll = fixCallsTableScroll;
window.minimalLivewireFix = minimalLivewireFix;

// Run minimal fix immediately
minimalLivewireFix();