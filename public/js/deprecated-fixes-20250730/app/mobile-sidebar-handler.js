// Mobile Sidebar Handler - Works with Filament's structure
document.addEventListener('DOMContentLoaded', function() {
    console.log('Mobile sidebar handler initialized');
    
    // Initialize sidebar state
    let sidebarOpen = false;
    
    // Helper function to toggle sidebar
    function toggleSidebar() {
        sidebarOpen = !sidebarOpen;
        const body = document.body;
        const sidebar = document.querySelector('.fi-sidebar');
        
        if (sidebarOpen) {
            body.classList.add('fi-sidebar-open');
            if (sidebar) {
                sidebar.style.left = '0';
            }
        } else {
            body.classList.remove('fi-sidebar-open');
            if (sidebar) {
                sidebar.style.left = '-100%';
            }
        }
        
        console.log('Sidebar toggled:', sidebarOpen ? 'open' : 'closed');
    }
    
    // Find and attach click handler to hamburger button
    function attachHandlers() {
        const hamburgerButton = document.querySelector('.fi-topbar-open-sidebar-btn');
        const closeButton = document.querySelector('.fi-topbar-close-sidebar-btn');
        
        if (hamburgerButton) {
            // Remove any existing Alpine handlers
            hamburgerButton.removeAttribute('x-on:click');
            hamburgerButton.removeAttribute('x-data');
            hamburgerButton.removeAttribute('x-show');
            
            // Ensure it's visible
            hamburgerButton.style.display = window.innerWidth < 1024 ? 'inline-flex' : 'none';
            
            // Add our click handler
            hamburgerButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleSidebar();
            });
            
            console.log('Hamburger button handler attached');
        }
        
        if (closeButton) {
            closeButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleSidebar();
            });
        }
        
        // Close sidebar when clicking overlay
        document.addEventListener('click', function(e) {
            if (sidebarOpen && e.target.classList.contains('fi-sidebar-open')) {
                toggleSidebar();
            }
        });
    }
    
    // Attach handlers immediately
    attachHandlers();
    
    // Re-attach after Livewire updates
    if (window.Livewire) {
        Livewire.hook('message.processed', () => {
            attachHandlers();
        });
    }
    
    // Handle window resize
    window.addEventListener('resize', function() {
        const hamburgerButton = document.querySelector('.fi-topbar-open-sidebar-btn');
        if (hamburgerButton) {
            hamburgerButton.style.display = window.innerWidth < 1024 ? 'inline-flex' : 'none';
        }
        
        // Close sidebar on desktop
        if (window.innerWidth >= 1024 && sidebarOpen) {
            toggleSidebar();
        }
    });
});