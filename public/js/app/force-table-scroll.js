// Force Table Scroll - Direct DOM manipulation
console.log('[Force Table Scroll] Initializing...');

function forceTableScroll() {
    // Get all table containers
    const containers = document.querySelectorAll('.fi-ta-content');
    
    containers.forEach((container, index) => {
        // Get current computed styles
        const computed = window.getComputedStyle(container);
        
        // Check if table is wider than container
        const table = container.querySelector('table');
        if (!table) return;
        
        const containerWidth = container.clientWidth;
        const tableWidth = table.scrollWidth;
        
        console.log(`[Force Table Scroll] Table ${index + 1}: Container ${containerWidth}px, Table ${tableWidth}px`);
        
        if (tableWidth > containerWidth) {
            console.log(`[Force Table Scroll] Table ${index + 1} needs scrolling`);
            
            // Method 1: Clear all overflow and set only X
            container.style.overflow = '';
            container.style.overflowX = 'auto';
            container.style.overflowY = 'visible';
            
            // Method 2: Use setAttribute for inline styles
            const currentStyle = container.getAttribute('style') || '';
            const newStyle = currentStyle
                .replace(/overflow\s*:\s*[^;]+;?/gi, '')
                .replace(/overflow-x\s*:\s*[^;]+;?/gi, '')
                .replace(/overflow-y\s*:\s*[^;]+;?/gi, '');
            
            container.setAttribute('style', newStyle + '; overflow-x: auto !important; overflow-y: visible !important;');
            
            // Add visual indicator
            container.setAttribute('data-scroll-fixed', 'true');
            
            // Force browser to recalculate
            void container.offsetHeight;
            
            // Verify the fix
            const newComputed = window.getComputedStyle(container);
            console.log(`[Force Table Scroll] Applied fix - overflow-x: ${newComputed.overflowX}`);
        }
    });
}

// Run immediately
forceTableScroll();

// Run after various events
document.addEventListener('DOMContentLoaded', forceTableScroll);
window.addEventListener('load', forceTableScroll);

// Run periodically to catch dynamic content
let attempts = 0;
const interval = setInterval(() => {
    forceTableScroll();
    attempts++;
    
    // Stop after 10 attempts (10 seconds)
    if (attempts > 10) {
        clearInterval(interval);
        console.log('[Force Table Scroll] Stopped periodic checks');
    }
}, 1000);

// Livewire integration
if (window.Livewire) {
    Livewire.hook('message.processed', () => {
        setTimeout(forceTableScroll, 100);
    });
}

// Export for manual use
window.forceTableScroll = forceTableScroll;