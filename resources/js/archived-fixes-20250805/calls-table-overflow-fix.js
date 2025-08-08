// Ultimate Calls Table Overflow Fix
console.log('[Table Overflow Fix] Starting...');

function fixTableOverflow() {
    // Find all table content wrappers
    const contentWrappers = document.querySelectorAll('.fi-ta-content');
    
    contentWrappers.forEach(wrapper => {
        // Get computed styles first
        const computed = window.getComputedStyle(wrapper);
        
        // Only fix if overflow-x is not already 'auto'
        if (computed.overflowX !== 'auto') {
            console.log('[Table Overflow Fix] Fixing wrapper with overflow:', computed.overflow);
            
            // Create a style element specific for this fix
            if (!document.getElementById('table-overflow-fix-styles')) {
                const style = document.createElement('style');
                style.id = 'table-overflow-fix-styles';
                style.textContent = `
                    .fi-ta-content.overflow-fix-applied {
                        overflow-x: auto !important;
                        overflow-y: visible !important;
                        max-width: 100% !important;
                        width: 100% !important;
                        display: block !important;
                        -webkit-overflow-scrolling: touch !important;
                    }
                    
                    .fi-ta-content.overflow-fix-applied table {
                        width: max-content !important;
                        min-width: 100% !important;
                        table-layout: auto !important;
                    }
                    
                    /* Ensure parent containers don't restrict */
                    .fi-ta-ctn:has(.overflow-fix-applied) {
                        overflow: visible !important;
                        max-width: 100% !important;
                    }
                `;
                document.head.appendChild(style);
            }
            
            // Add class instead of inline styles
            wrapper.classList.add('overflow-fix-applied');
            
            // Force a reflow to ensure styles are applied
            void wrapper.offsetHeight;
            
            console.log('[Table Overflow Fix] Applied class to wrapper');
        }
    });
}

// Run immediately
fixTableOverflow();

// Run after DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', fixTableOverflow);
}

// Watch for new content with MutationObserver
const observer = new MutationObserver((mutations) => {
    let shouldFix = false;
    
    mutations.forEach(mutation => {
        // Check if new nodes contain table content
        mutation.addedNodes.forEach(node => {
            if (node.nodeType === 1) { // Element node
                if (node.classList?.contains('fi-ta-content') || 
                    node.querySelector?.('.fi-ta-content')) {
                    shouldFix = true;
                }
            }
        });
    });
    
    if (shouldFix) {
        setTimeout(fixTableOverflow, 100);
    }
});

// Start observing
observer.observe(document.body, {
    childList: true,
    subtree: true
});

// Livewire hooks
if (window.Livewire) {
    Livewire.hook('message.processed', () => {
        setTimeout(fixTableOverflow, 50);
    });
}

// Export for manual use
window.fixTableOverflow = fixTableOverflow;

console.log('[Table Overflow Fix] Initialized');