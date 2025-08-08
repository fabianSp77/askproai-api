/**
 * Portal Alpine.js Fix
 * Fixes missing Alpine components and initializations
 */

// Ensure Alpine is loaded
if (typeof Alpine === 'undefined') {
    console.error('Alpine.js not loaded. Loading from CDN...');
    const script = document.createElement('script');
    script.src = 'https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js';
    script.defer = true;
    document.head.appendChild(script);
}

// Fix missing Alpine components
document.addEventListener('alpine:init', () => {
    // Mobile Navigation Component
    Alpine.data('mobileNav', () => ({
        open: false,
        toggle() {
            this.open = !this.open;
            // Prevent body scroll when menu is open
            document.body.style.overflow = this.open ? 'hidden' : '';
        },
        close() {
            this.open = false;
            document.body.style.overflow = '';
        },
        init() {
            // Close on escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.open) {
                    this.close();
                }
            });
            
            // Close on outside click
            document.addEventListener('click', (e) => {
                if (this.open && !this.$el.contains(e.target)) {
                    this.close();
                }
            });
        }
    }));
    
    // Dropdown Component Fix
    Alpine.data('dropdown', () => ({
        open: false,
        toggle() {
            this.open = !this.open;
        },
        close() {
            this.open = false;
        }
    }));
    
    // Tab Component Fix
    Alpine.data('tabs', () => ({
        activeTab: 'tab1',
        isActive(tab) {
            return this.activeTab === tab;
        },
        setActive(tab) {
            this.activeTab = tab;
        }
    }));
    
    // Modal Component Fix
    Alpine.data('modal', () => ({
        open: false,
        show() {
            this.open = true;
            document.body.style.overflow = 'hidden';
        },
        hide() {
            this.open = false;
            document.body.style.overflow = '';
        }
    }));
    
    // Loading State Manager
    Alpine.data('loadingState', () => ({
        loading: false,
        error: null,
        async load(callback) {
            this.loading = true;
            this.error = null;
            try {
                await callback();
            } catch (error) {
                this.error = error.message;
                console.error('Loading error:', error);
            } finally {
                this.loading = false;
            }
        }
    }));
});

// Fix for navigation links not working
document.addEventListener('DOMContentLoaded', () => {
    // Remove any pointer-events: none from navigation
    const navLinks = document.querySelectorAll('nav a, .navigation a, [role="navigation"] a');
    navLinks.forEach(link => {
        const style = window.getComputedStyle(link);
        if (style.pointerEvents === 'none') {
            link.style.pointerEvents = 'auto';
            console.warn('Fixed pointer-events on navigation link:', link.href);
        }
    });
    
    // Ensure all navigation items are clickable
    const navItems = document.querySelectorAll('[x-data*="navigation"], [x-data*="menu"]');
    navItems.forEach(item => {
        if (!item.hasAttribute('x-data') || item.getAttribute('x-data') === '') {
            item.setAttribute('x-data', '{ open: false }');
        }
    });
    
    // Fix mobile menu toggle if exists
    const mobileMenuToggle = document.querySelector('[data-mobile-menu-toggle], #mobile-menu-toggle');
    if (mobileMenuToggle && !mobileMenuToggle.hasAttribute('@click')) {
        mobileMenuToggle.setAttribute('@click', '$dispatch("toggle-mobile-menu")');
    }
    
    // Add error boundary for Alpine components
    window.addEventListener('error', (e) => {
        if (e.message.includes('Alpine') || e.message.includes('x-')) {
            console.error('Alpine.js Error:', e.message);
            // Attempt to reinitialize Alpine if it crashes
            if (window.Alpine && !document.body.hasAttribute('x-data')) {
                window.Alpine.start();
            }
        }
    });
});

// Global Alpine error handler
if (window.Alpine) {
    window.Alpine.onError = (error) => {
        console.error('Alpine Global Error:', error);
        // Send to error tracking if available
        if (window.errorTracking) {
            window.errorTracking.log(error);
        }
    };
}

console.log('Portal Alpine.js fixes loaded successfully');