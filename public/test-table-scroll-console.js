// Browser Console Test Command - Copy & Paste in Console
// This will immediately force horizontal scrolling on ALL tables

(function() {
    console.log('🔧 Testing table scroll fix...');
    
    // Find all table containers
    const containers = document.querySelectorAll('.fi-ta-content');
    console.log(`Found ${containers.length} table containers`);
    
    containers.forEach((container, index) => {
        // Get the table inside
        const table = container.querySelector('table');
        if (!table) {
            console.log(`Container ${index}: No table found`);
            return;
        }
        
        // Check current dimensions
        const containerWidth = container.clientWidth;
        const tableWidth = table.scrollWidth;
        console.log(`Table ${index}: Container=${containerWidth}px, Table=${tableWidth}px`);
        
        // Force the fix
        container.style.cssText = `
            overflow-x: auto !important;
            overflow-y: visible !important;
            max-width: 100% !important;
            width: 100% !important;
            display: block !important;
            -webkit-overflow-scrolling: touch !important;
        `;
        
        // Test scroll
        if (tableWidth > containerWidth) {
            console.log(`✅ Table ${index}: Scroll enabled (table is ${tableWidth - containerWidth}px wider)`);
            
            // Scroll a bit to show it works
            container.scrollLeft = 50;
            setTimeout(() => container.scrollLeft = 0, 1000);
            
            // Add visual indicator
            container.style.border = '2px solid #10b981';
            container.style.position = 'relative';
            
            const indicator = document.createElement('div');
            indicator.innerHTML = '↔ Scroll aktiviert';
            indicator.style.cssText = `
                position: absolute;
                top: 0;
                right: 0;
                background: #10b981;
                color: white;
                padding: 4px 8px;
                font-size: 12px;
                z-index: 1000;
            `;
            container.appendChild(indicator);
        } else {
            console.log(`❌ Table ${index}: No scroll needed (table fits in container)`);
        }
        
        // List all columns
        const headers = table.querySelectorAll('thead th');
        console.log(`Columns found: ${headers.length}`);
        headers.forEach((th, i) => {
            const text = th.textContent.trim();
            const rect = th.getBoundingClientRect();
            const containerRect = container.getBoundingClientRect();
            const isVisible = rect.right <= containerRect.right;
            console.log(`  ${i}: "${text}" - ${isVisible ? 'VISIBLE' : 'HIDDEN (right side)'}`);
        });
    });
    
    // Check if any CSS is overriding
    const firstContainer = containers[0];
    if (firstContainer) {
        const computed = window.getComputedStyle(firstContainer);
        console.log('\nComputed styles:');
        console.log('overflow-x:', computed.overflowX);
        console.log('overflow-y:', computed.overflowY);
        console.log('max-width:', computed.maxWidth);
        console.log('width:', computed.width);
    }
    
    console.log('\n📋 Summary: If you see HIDDEN columns above, try scrolling the table horizontally.');
    console.log('If scrolling doesn\'t work, the table structure might be preventing it.');
    
    return 'Test complete - check console output above';
})();