{{-- INLINE NAVIGATION FIX - Remove after CSS fix is confirmed working --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inline Navigation Fix Activated');
    
    // Force all navigation links to be clickable
    const fixNavigation = () => {
        // Remove all pointer-events: none
        document.querySelectorAll('*').forEach(el => {
            const style = window.getComputedStyle(el);
            if (style.pointerEvents === 'none') {
                el.style.pointerEvents = 'auto';
            }
        });
        
        // Specifically fix navigation
        document.querySelectorAll('.fi-sidebar-nav a, .fi-sidebar-nav button').forEach(link => {
            link.style.pointerEvents = 'auto';
            link.style.cursor = 'pointer';
            link.style.position = 'relative';
            link.style.zIndex = '9999';
        });
        
        // Remove duplicate arrows
        document.querySelectorAll('.fi-sidebar-nav-group-header').forEach(header => {
            const icons = header.querySelectorAll('svg');
            if (icons.length > 1) {
                for (let i = 1; i < icons.length; i++) {
                    icons[i].style.display = 'none';
                }
            }
        });
        
        // Fix spacing
        const mainContent = document.querySelector('.fi-main-ctn');
        if (mainContent) {
            const sidebar = document.querySelector('.fi-sidebar');
            if (sidebar) {
                const sidebarWidth = sidebar.offsetWidth;
                mainContent.style.marginLeft = sidebarWidth + 'px';
            }
        }
    };
    
    // Run fix immediately and after any DOM changes
    fixNavigation();
    
    // Re-run on Alpine/Livewire updates
    if (window.Alpine) {
        document.addEventListener('alpine:initialized', fixNavigation);
    }
    
    if (window.Livewire) {
        Livewire.hook('message.processed', fixNavigation);
    }
    
    // Re-run periodically as a safety measure
    setInterval(fixNavigation, 1000);
});
</script>