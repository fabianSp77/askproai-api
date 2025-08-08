/**
 * Performance-optimized Navigation Fix
 * Only runs once and uses event delegation
 */

console.log('⚡ Performance Navigation Fix Loading...');

(function() {
    let isFixed = false;
    
    function fixNavigationOnce() {
        if (isFixed) return;
        
        console.log('🔧 Applying navigation fixes...');
        
        // 1. Use event delegation for ALL navigation clicks
        document.addEventListener('click', function(e) {
            // Check if clicked element is within navigation
            const navLink = e.target.closest('.fi-sidebar-nav a, .fi-sidebar-item a');
            if (!navLink) return;
            
            const href = navLink.getAttribute('href') || navLink.href;
            if (!href || href === '#' || href === 'javascript:void(0)') return;
            
            // Let the browser handle the navigation naturally
            console.log(`🔗 Navigating to: ${href}`);
            
            // Close mobile sidebar after click
            if (window.innerWidth < 1024) {
                setTimeout(() => {
                    if (window.$store && window.$store.sidebar) {
                        window.$store.sidebar.close();
                    }
                }, 50);
            }
        }, true); // Use capture phase
        
        // 2. Fix duplicate icons ONCE
        document.querySelectorAll('.fi-sidebar-nav-group-header').forEach(group => {
            const icons = group.querySelectorAll('svg');
            if (icons.length > 1) {
                for (let i = 1; i < icons.length; i++) {
                    icons[i].remove();
                }
            }
        });
        
        // 3. Fix layout ONCE
        const fixLayout = () => {
            const mainContent = document.querySelector('.fi-main-ctn');
            const sidebar = document.querySelector('.fi-sidebar');
            
            if (mainContent && sidebar && window.innerWidth >= 1024) {
                const sidebarWidth = sidebar.offsetWidth || 256;
                mainContent.style.marginLeft = `${sidebarWidth}px`;
            }
        };
        
        fixLayout();
        
        // Only re-run layout on resize
        window.addEventListener('resize', fixLayout);
        
        isFixed = true;
        console.log('✅ Navigation fixes applied (one-time)');
    }
    
    // Run once when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fixNavigationOnce);
    } else {
        fixNavigationOnce();
    }
})();