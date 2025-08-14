<style>
/* EMERGENCY FIX FOR ISSUE #578 - Navigation Overlap */
/* This fix is injected inline to ensure immediate application */
.fi-layout {
    display: grid !important;
    grid-template-columns: 16rem 1fr !important;
    min-height: 100vh !important;
}

.fi-sidebar {
    grid-column: 1 !important;
    position: sticky !important;
    top: 0 !important;
    height: 100vh !important;
    overflow-y: auto !important;
    background: white !important;
    border-right: 1px solid rgb(229 231 235) !important;
    z-index: 40 !important;
}

.fi-main {
    grid-column: 2 !important;
    min-height: 100vh !important;
    overflow-x: hidden !important;
}

/* Ensure navigation items are clickable */
.fi-sidebar-nav {
    padding: 0.5rem !important;
}

.fi-sidebar-item {
    margin-bottom: 0.125rem !important;
}

.fi-sidebar-item a {
    display: flex !important;
    align-items: center !important;
    padding: 0.625rem 0.75rem !important;
    border-radius: 0.5rem !important;
    pointer-events: auto !important;
    position: relative !important;
    z-index: 10 !important;
}

/* Remove any blocking overlays */
.fi-sidebar::before,
.fi-sidebar::after,
.fi-layout::before,
.fi-layout::after {
    display: none !important;
}

/* Mobile responsive */
@media (max-width: 1024px) {
    .fi-layout {
        grid-template-columns: 1fr !important;
    }
    
    .fi-sidebar {
        position: fixed !important;
        left: 0 !important;
        top: 0 !important;
        width: 16rem !important;
        transform: translateX(-100%) !important;
        transition: transform 0.3s !important;
        z-index: 50 !important;
    }
    
    .fi-sidebar.fi-sidebar-open {
        transform: translateX(0) !important;
    }
    
    .fi-main {
        grid-column: 1 !important;
    }
}
</style>

<script>
// Emergency JavaScript fix for navigation
document.addEventListener('DOMContentLoaded', function() {
    // Ensure all navigation links are clickable
    const navLinks = document.querySelectorAll('.fi-sidebar-item a');
    navLinks.forEach(link => {
        link.style.pointerEvents = 'auto';
        link.style.position = 'relative';
        link.style.zIndex = '10';
    });
    
    // Fix layout if needed
    const layout = document.querySelector('.fi-layout');
    if (layout) {
        layout.style.display = 'grid';
        layout.style.gridTemplateColumns = '16rem 1fr';
    }
    
    const sidebar = document.querySelector('.fi-sidebar');
    if (sidebar) {
        sidebar.style.gridColumn = '1';
        sidebar.style.position = 'sticky';
        sidebar.style.top = '0';
        sidebar.style.height = '100vh';
    }
    
    const main = document.querySelector('.fi-main');
    if (main) {
        main.style.gridColumn = '2';
    }
    
    console.log('✅ Navigation fix applied via JavaScript');
});
</script>