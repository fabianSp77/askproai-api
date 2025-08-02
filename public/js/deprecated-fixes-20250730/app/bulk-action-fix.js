/**
 * Dropdown Stacking Context Fix
 * This script ensures dropdowns appear above sidebars by managing stacking contexts
 */

document.addEventListener('DOMContentLoaded', function() {
    // Function to fix dropdown z-index and stacking contexts
    function fixDropdownZIndex() {
        // Remove transform from table rows to prevent stacking context issues
        const tableRows = document.querySelectorAll('.fi-ta-row');
        tableRows.forEach(row => {
            const transform = window.getComputedStyle(row).transform;
            if (transform && transform !== 'none') {
                row.style.transform = 'none';
                console.log('Removed transform from table row');
            }
        });
        
        // Find all dropdown panels
        const dropdownPanels = document.querySelectorAll('.fi-dropdown-panel');
        
        dropdownPanels.forEach(panel => {
            // Use reasonable z-index values from our hierarchy
            panel.style.zIndex = '56';
            panel.style.position = 'absolute';
            
            // Don't force visibility - let Alpine.js handle it
            // Remove any forced opacity/visibility
            if (panel.style.opacity === '1' && !panel.getAttribute('x-show')) {
                panel.style.removeProperty('opacity');
                panel.style.removeProperty('visibility');
            }
        });
        
        // Fix bulk actions container
        const bulkActions = document.querySelectorAll('.fi-ta-bulk-actions');
        bulkActions.forEach(container => {
            container.style.position = 'relative';
            container.style.zIndex = '20';
        });
    }
    
    // Run on page load
    fixDropdownZIndex();
    
    // Run whenever DOM changes (for dynamic content)
    const observer = new MutationObserver(function(mutations) {
        fixDropdownZIndex();
    });
    
    // Start observing
    observer.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['x-show', 'style']
    });
    
    // Also run on click events (when dropdowns open)
    document.addEventListener('click', function(e) {
        if (e.target.closest('.fi-dropdown-trigger') || 
            e.target.closest('.fi-ac-btn-action') ||
            e.target.closest('[wire\\:click]')) {
            setTimeout(fixDropdownZIndex, 100);
        }
    });
});

console.log('Dropdown stacking context fix loaded');

// Debug helper function
window.debugStackingContexts = function() {
    console.log('=== Stacking Context Debug ===');
    
    // Check sidebar z-index
    const sidebar = document.querySelector('.fi-sidebar');
    if (sidebar) {
        const styles = window.getComputedStyle(sidebar);
        console.log('Sidebar:', {
            zIndex: styles.zIndex,
            position: styles.position,
            transform: styles.transform
        });
    }
    
    // Check dropdown panels
    const dropdowns = document.querySelectorAll('.fi-dropdown-panel');
    dropdowns.forEach((dropdown, index) => {
        const styles = window.getComputedStyle(dropdown);
        const parent = dropdown.closest('.fi-ta-bulk-actions') || dropdown.parentElement;
        const parentStyles = parent ? window.getComputedStyle(parent) : null;
        
        console.log(`Dropdown ${index}:`, {
            zIndex: styles.zIndex,
            position: styles.position,
            display: styles.display,
            visibility: styles.visibility,
            parent: parent?.tagName,
            parentZIndex: parentStyles?.zIndex,
            parentPosition: parentStyles?.position,
            parentTransform: parentStyles?.transform
        });
    });
    
    // Check for elements creating stacking contexts
    const problematicElements = document.querySelectorAll('[style*="transform"], .fi-ta-row');
    console.log('Elements with transforms:', problematicElements.length);
    problematicElements.forEach(el => {
        const styles = window.getComputedStyle(el);
        if (styles.transform !== 'none' || styles.willChange === 'transform') {
            console.log('Problematic element:', el.className, {
                transform: styles.transform,
                willChange: styles.willChange,
                position: styles.position,
                zIndex: styles.zIndex
            });
        }
    });
};