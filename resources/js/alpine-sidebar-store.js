/**
 * Alpine.js Sidebar Store
 * Manages sidebar state for mobile menu
 */

document.addEventListener('alpine:init', () => {
    Alpine.store('sidebar', {
        isOpen: false,
        
        init() {
            // On mobile, sidebar should start closed
            // On desktop, sidebar should be open
            const isMobile = window.innerWidth < 1024;
            this.isOpen = !isMobile;
            
            // Update isOpen when fi-sidebar-open class changes
            const observer = new MutationObserver(() => {
                const hasSidebarOpen = document.body.classList.contains('fi-sidebar-open');
                if (window.innerWidth < 1024 && hasSidebarOpen) {
                    this.isOpen = true;
                }
            });
            
            observer.observe(document.body, {
                attributes: true,
                attributeFilter: ['class']
            });
            
            // Update on resize
            window.addEventListener('resize', () => {
                const isMobile = window.innerWidth < 1024;
                if (!isMobile) {
                    // Desktop - always show
                    this.isOpen = true;
                    document.body.classList.remove('fi-sidebar-open');
                } else {
                    // Mobile - check body class
                    this.isOpen = document.body.classList.contains('fi-sidebar-open');
                }
            });
        },
        
        toggle() {
            const isMobile = window.innerWidth < 1024;
            
            if (isMobile) {
                // On mobile, toggle both isOpen and body class
                this.isOpen = !this.isOpen;
                document.body.classList.toggle('fi-sidebar-open', this.isOpen);
            } else {
                // On desktop, just toggle isOpen
                this.isOpen = !this.isOpen;
            }
            
            console.log('Sidebar toggled - isOpen:', this.isOpen, 'Mobile:', isMobile);
        },
        
        open() {
            this.isOpen = true;
            if (window.innerWidth < 1024) {
                document.body.classList.add('fi-sidebar-open');
            }
        },
        
        close() {
            const isMobile = window.innerWidth < 1024;
            
            if (isMobile) {
                this.isOpen = false;
                document.body.classList.remove('fi-sidebar-open');
            }
            // On desktop, don't close
        },
        
        toggleCollapsedGroup(group) {
            // Handle group toggling
            console.log('Toggle group:', group);
        }
    });
});