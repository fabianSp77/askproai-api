/**
 * Mobile Navigation Fix
 * Ensures mobile navigation works properly on all devices
 */

class MobileNavigationFix {
    constructor() {
        this.init();
    }

    init() {
        // Wait for Alpine.js to be ready
        document.addEventListener('alpine:init', () => {
            this.setupMobileNavigation();
        });

        // Also setup on DOM ready as backup
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.setupMobileNavigation();
            });
        } else {
            this.setupMobileNavigation();
        }

        // Handle viewport changes
        this.handleViewportChanges();
    }

    setupMobileNavigation() {
        // Look for any mobile navigation button
        const mobileNavButton = document.querySelector('[onclick*="toggleMobileSidebar"], .fi-topbar-open-sidebar-btn, .professional-burger-btn');
        
        // Only proceed if we found a button or if we're on mobile
        if (!mobileNavButton && window.innerWidth >= 1024) {
            // Desktop mode, no mobile nav needed
            return;
        }

        // Ensure mobile button is visible on mobile
        if (mobileNavButton && window.innerWidth < 1024) {
            mobileNavButton.style.display = 'inline-flex';
            mobileNavButton.style.visibility = 'visible';
            mobileNavButton.style.opacity = '1';
        }

        // Fix z-index issues
        this.fixZIndexIssues();

        // Ensure touch events work properly
        this.setupTouchEvents();
    }

    fixZIndexIssues() {
        // Ensure proper stacking order
        const elements = {
            '.fi-topbar': 40,
            '.professional-burger-btn': 50,
            '.mobile-menu-backdrop': 45,
            '.professional-mobile-panel': 50,
            '.fi-sidebar': 30
        };

        Object.entries(elements).forEach(([selector, zIndex]) => {
            const el = document.querySelector(selector);
            if (el) {
                el.style.zIndex = zIndex;
            }
        });
    }

    setupTouchEvents() {
        // Find any mobile navigation button
        const mobileNavButton = document.querySelector('[onclick*="toggleMobileSidebar"], .fi-topbar-open-sidebar-btn, .professional-burger-btn');
        if (!mobileNavButton) return;

        // Improve touch responsiveness
        mobileNavButton.addEventListener('touchstart', (e) => {
            e.currentTarget.classList.add('touch-active');
        }, { passive: true });

        mobileNavButton.addEventListener('touchend', (e) => {
            e.currentTarget.classList.remove('touch-active');
        }, { passive: true });
    }

    handleViewportChanges() {
        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                this.onViewportChange();
            }, 250);
        });

        // Initial check
        this.onViewportChange();
    }

    onViewportChange() {
        const isMobile = window.innerWidth < 1024;
        const mobileNavButton = document.querySelector('[onclick*="toggleMobileSidebar"], .fi-topbar-open-sidebar-btn, .professional-burger-btn');
        const desktopSidebar = document.querySelector('.fi-sidebar');

        if (isMobile) {
            // Show mobile navigation button
            if (mobileNavButton) {
                mobileNavButton.style.display = 'inline-flex';
            }
            // Sidebar handled by CSS, no need to hide
        } else {
            // Hide mobile navigation button
            if (mobileNavButton) {
                mobileNavButton.style.display = 'none';
            }
            // Reset sidebar position for desktop
            if (desktopSidebar) {
                desktopSidebar.style.left = '';
                desktopSidebar.style.transform = '';
            }
        }
    }
}

// Initialize
new MobileNavigationFix();

// Export for debugging
window.MobileNavigationFix = MobileNavigationFix;