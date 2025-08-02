/**
 * Debug Mobile Navigation
 * Use this to troubleshoot mobile navigation issues
 */

window.debugMobileNav = function() {
    console.log('=== Mobile Navigation Debug Report ===');
    
    // 1. Check Alpine.js
    if (typeof Alpine !== 'undefined') {
        console.log('✅ Alpine.js is loaded');
        
        // Check sidebar store
        try {
            const sidebarStore = Alpine.store('sidebar');
            console.log('✅ Sidebar store exists:', sidebarStore);
            console.log('   - isOpen:', sidebarStore?.isOpen);
            console.log('   - toggle function:', typeof sidebarStore?.toggle);
        } catch (e) {
            console.log('❌ Sidebar store error:', e.message);
        }
    } else {
        console.log('❌ Alpine.js NOT loaded');
    }
    
    // 2. Check Elements
    const elements = {
        'Sidebar': '.fi-sidebar',
        'Mobile Button (lg:hidden)': '.lg\\:hidden button',
        'Any Button with SVG': 'button svg',
        'Hamburger Icon': 'button svg path[d*="M4 6h16"]',
        'Body': '.fi-body',
        'Topbar': '.fi-topbar',
        'Alpine Store Script': 'script[src*="sidebar-store"]',
        'Mobile Nav Script': 'script[src*="mobile-navigation"]'
    };
    
    console.log('\n=== Element Check ===');
    for (const [name, selector] of Object.entries(elements)) {
        const element = document.querySelector(selector);
        console.log(`${element ? '✅' : '❌'} ${name}`);
        if (element && name === 'Sidebar') {
            const styles = window.getComputedStyle(element);
            console.log('   Sidebar styles:', {
                position: styles.position,
                left: styles.left,
                width: styles.width,
                display: styles.display,
                visibility: styles.visibility,
                transform: styles.transform
            });
        }
    }
    
    // 3. Find all buttons and check for hamburger
    console.log('\n=== Button Analysis ===');
    const buttons = document.querySelectorAll('button');
    console.log(`Found ${buttons.length} buttons total`);
    
    buttons.forEach((btn, i) => {
        // Check if button has hamburger icon
        const svg = btn.querySelector('svg');
        if (svg) {
            const path = svg.querySelector('path');
            if (path && path.getAttribute('d')?.includes('M4 6h16')) {
                console.log(`✅ Hamburger button found at index ${i}:`);
                console.log('   - Classes:', btn.className);
                console.log('   - Parent classes:', btn.parentElement?.className);
                console.log('   - onclick:', btn.getAttribute('onclick'));
                console.log('   - @click:', btn.getAttribute('@click'));
                console.log('   - x-on:click:', btn.getAttribute('x-on:click'));
                
                // Try to click it
                console.log('\n   Attempting to click hamburger button...');
                btn.click();
                
                setTimeout(() => {
                    const sidebar = document.querySelector('.fi-sidebar');
                    if (sidebar) {
                        const newLeft = window.getComputedStyle(sidebar).left;
                        console.log('   After click - Sidebar left:', newLeft);
                    }
                }, 500);
            }
        }
    });
    
    // 4. Check viewport
    console.log('\n=== Viewport Info ===');
    console.log('Width:', window.innerWidth);
    console.log('Height:', window.innerHeight);
    console.log('Device:', window.innerWidth < 768 ? 'Mobile' : window.innerWidth < 1024 ? 'Tablet' : 'Desktop');
    
    // 5. Check CSS
    console.log('\n=== CSS Check ===');
    const sheets = Array.from(document.styleSheets);
    const hasUnifiedResponsive = sheets.some(sheet => {
        try {
            return sheet.href?.includes('unified-responsive');
        } catch (e) {
            return false;
        }
    });
    console.log(hasUnifiedResponsive ? '✅ Unified responsive CSS loaded' : '❌ Unified responsive CSS NOT found');
    
    // 6. Manual toggle test
    console.log('\n=== Manual Toggle Test ===');
    const sidebar = document.querySelector('.fi-sidebar');
    if (sidebar) {
        console.log('Current sidebar left:', window.getComputedStyle(sidebar).left);
        console.log('Attempting manual toggle...');
        
        if (window.getComputedStyle(sidebar).left === '0px') {
            sidebar.style.left = '-100%';
            console.log('Set sidebar to hidden');
        } else {
            sidebar.style.left = '0px';
            console.log('Set sidebar to visible');
        }
    } else {
        console.log('❌ No sidebar found for manual toggle');
    }
    
    return '=== Debug Complete ===';
};

// Auto-run on load if requested
if (window.location.hash === '#debug-mobile-nav') {
    setTimeout(() => {
        window.debugMobileNav();
    }, 3000);
}

// Make it globally available
window.debugMobileNavigation = window.debugMobileNav;