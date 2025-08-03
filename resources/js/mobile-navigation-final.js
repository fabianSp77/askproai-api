/**
 * Mobile Navigation - Final Clean Implementation
 * No more emergency fixes, just clean, working code
 */

class MobileNavigation {
    constructor() {
        this.sidebar = document.querySelector('.fi-sidebar');
        this.trigger = document.querySelector('.fi-sidebar-nav-toggle, [x-ref="navButton"], [x-on\\:click*="sidebarOpen"]');
        this.body = document.body;
        this.isOpen = false;
        
        // Create overlay element
        this.overlay = this.createOverlay();
        
        // Initialize if elements exist
        if (this.sidebar && this.trigger) {
            this.init();
        }
    }
    
    init() {
        // Toggle button click
        this.trigger.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.toggle();
        });
        
        // Overlay click to close
        this.overlay.addEventListener('click', () => {
            this.close();
        });
        
        // Escape key to close
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.close();
            }
        });
        
        // Close on navigation (for Livewire page changes)
        document.addEventListener('livewire:navigated', () => {
            this.close();
        });
        
        // Handle window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024 && this.isOpen) {
                this.close();
            }
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
        this.sidebar.classList.add('translate-x-0');
        this.overlay.classList.remove('hidden');
        this.body.classList.add('overflow-hidden', 'fi-sidebar-open');
        
        // Announce to screen readers
        this.sidebar.setAttribute('aria-hidden', 'false');
        
        // Focus management
        this.sidebar.focus();
    }
    
    close() {
        this.isOpen = false;
        this.sidebar.classList.remove('translate-x-0');
        this.overlay.classList.add('hidden');
        this.body.classList.remove('overflow-hidden', 'fi-sidebar-open');
        
        // Announce to screen readers
        this.sidebar.setAttribute('aria-hidden', 'true');
        
        // Return focus to trigger
        this.trigger.focus();
    }
    
    createOverlay() {
        // Check if overlay already exists
        let overlay = document.querySelector('.fi-sidebar-overlay');
        
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'fi-sidebar-overlay fixed inset-0 bg-black/50 z-40 hidden lg:hidden';
            overlay.setAttribute('aria-hidden', 'true');
            document.body.appendChild(overlay);
        }
        
        return overlay;
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.mobileNav = new MobileNavigation();
    });
} else {
    window.mobileNav = new MobileNavigation();
}

// Re-initialize after Livewire updates
if (window.Livewire) {
    Livewire.hook('message.processed', () => {
        if (!window.mobileNav || !document.querySelector('.fi-sidebar')) {
            window.mobileNav = new MobileNavigation();
        }
    });
}