/**
 * Unified Mobile Navigation Handler
 * Simple, dependency-free mobile navigation for Filament
 */

class UnifiedMobileNavigation {
    constructor() {
        this.body = document.body;
        this.isOpen = false;
        this.init();
    }
    
    init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setup());
        } else {
            this.setup();
        }
    }
    
    setup() {
        console.log('Unified Mobile Navigation initialized');
        
        // Find all elements that can toggle the sidebar
        this.attachToggleHandlers();
        
        // Handle clicks outside sidebar to close it
        this.attachOutsideClickHandler();
        
        // Handle escape key
        this.attachEscapeHandler();
        
        // Handle window resize
        this.attachResizeHandler();
        
        // Handle Livewire updates
        if (window.Livewire) {
            Livewire.hook('message.processed', () => {
                this.attachToggleHandlers();
            });
        }
    }
    
    attachToggleHandlers() {
        // Find all elements that should toggle the sidebar
        const toggleButtons = document.querySelectorAll(
            '.fi-topbar-open-sidebar-btn, .fi-sidebar-toggle, [onclick*="sidebar"]'
        );
        
        toggleButtons.forEach(button => {
            // Remove any existing handlers
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
            
            // Add our handler
            newButton.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.toggle();
            });
        });
        
        // Also handle close buttons inside sidebar
        const closeButtons = document.querySelectorAll('.fi-sidebar-close-btn');
        closeButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.close();
            });
        });
    }
    
    attachOutsideClickHandler() {
        document.addEventListener('click', (e) => {
            if (!this.isOpen) return;
            
            const sidebar = document.querySelector('.fi-sidebar');
            const isClickInsideSidebar = sidebar && sidebar.contains(e.target);
            const isToggleButton = e.target.closest('.fi-topbar-open-sidebar-btn');
            
            if (!isClickInsideSidebar && !isToggleButton) {
                this.close();
            }
        });
    }
    
    attachEscapeHandler() {
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.close();
            }
        });
    }
    
    attachResizeHandler() {
        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                // Close sidebar when resizing to desktop
                if (window.innerWidth >= 1024 && this.isOpen) {
                    this.close();
                }
            }, 250);
        });
    }
    
    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }
    
    open() {
        this.isOpen = true;
        this.body.classList.add('fi-sidebar-open');
        
        // Prevent body scroll
        this.body.style.overflow = 'hidden';
        
        // Announce to screen readers
        this.announce('Navigation menu opened');
        
        console.log('Sidebar opened');
    }
    
    close() {
        this.isOpen = false;
        this.body.classList.remove('fi-sidebar-open');
        
        // Restore body scroll
        this.body.style.overflow = '';
        
        // Announce to screen readers
        this.announce('Navigation menu closed');
        
        console.log('Sidebar closed');
    }
    
    announce(message) {
        // Create or update ARIA live region for screen readers
        let announcer = document.getElementById('mobile-nav-announcer');
        if (!announcer) {
            announcer = document.createElement('div');
            announcer.id = 'mobile-nav-announcer';
            announcer.setAttribute('aria-live', 'polite');
            announcer.setAttribute('aria-atomic', 'true');
            announcer.style.position = 'absolute';
            announcer.style.left = '-10000px';
            announcer.style.width = '1px';
            announcer.style.height = '1px';
            announcer.style.overflow = 'hidden';
            document.body.appendChild(announcer);
        }
        announcer.textContent = message;
    }
}

// Initialize when ready
const unifiedMobileNav = new UnifiedMobileNavigation();

// Export for global access if needed
window.UnifiedMobileNavigation = UnifiedMobileNavigation;