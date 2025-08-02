/**
 * Silent Mobile Navigation Handler
 * Handles mobile navigation without console errors
 */

class SilentMobileNavigation {
    constructor() {
        this.body = document.body;
        this.isOpen = false;
        this.initialized = false;
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
        try {
            // Check if we're in the admin panel
            const isAdminPanel = document.querySelector('.fi-body, .fi-page');
            if (!isAdminPanel) {
                return; // Not in admin panel, exit silently
            }
            
            this.initialized = true;
            
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
        } catch (error) {
            // Silently fail - no console errors
        }
    }
    
    attachToggleHandlers() {
        try {
            // Find hamburger menu button
            const hamburgerSelectors = [
                '.fi-topbar-open-sidebar-btn',
                '.fi-sidebar-toggle',
                'button[x-on\\:click*="sidebar"]',
                'button[onclick*="sidebar"]',
                '.mobile-menu-toggle',
                '.hamburger-menu'
            ];
            
            let foundButtons = false;
            
            hamburgerSelectors.forEach(selector => {
                const buttons = document.querySelectorAll(selector);
                if (buttons.length > 0) {
                    foundButtons = true;
                    buttons.forEach(button => {
                        // Remove existing click handlers
                        const newButton = button.cloneNode(true);
                        button.parentNode?.replaceChild(newButton, button);
                        
                        // Add our handler
                        newButton.addEventListener('click', (e) => {
                            e.preventDefault();
                            e.stopPropagation();
                            this.toggle();
                        });
                    });
                }
            });
            
            // If no buttons found, create a fallback mechanism
            if (!foundButtons && window.innerWidth < 1024) {
                this.createFallbackButton();
            }
            
            // Handle close buttons inside sidebar
            const closeButtons = document.querySelectorAll('.fi-sidebar-close-btn');
            closeButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.close();
                });
            });
        } catch (error) {
            // Silently fail
        }
    }
    
    createFallbackButton() {
        // Check if fallback already exists
        if (document.querySelector('.mobile-nav-fallback')) {
            return;
        }
        
        // Find topbar
        const topbar = document.querySelector('.fi-topbar, .fi-page-header');
        if (!topbar) return;
        
        // Create fallback button
        const fallbackButton = document.createElement('button');
        fallbackButton.className = 'mobile-nav-fallback lg:hidden p-2 rounded-lg text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700';
        fallbackButton.innerHTML = `
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        `;
        fallbackButton.addEventListener('click', () => this.toggle());
        
        // Insert at beginning of topbar
        topbar.insertBefore(fallbackButton, topbar.firstChild);
    }
    
    attachOutsideClickHandler() {
        document.addEventListener('click', (e) => {
            if (!this.isOpen) return;
            
            const sidebar = document.querySelector('.fi-sidebar');
            if (!sidebar) return;
            
            const isClickInsideSidebar = sidebar.contains(e.target);
            const isToggleButton = e.target.closest('.fi-topbar-open-sidebar-btn, .mobile-nav-fallback');
            
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
        const sidebar = document.querySelector('.fi-sidebar');
        if (!sidebar) return;
        
        this.isOpen = true;
        this.body.classList.add('fi-sidebar-open');
        
        // Prevent body scroll
        this.body.style.overflow = 'hidden';
        
        // Apply open styles to sidebar
        sidebar.style.left = '0';
        sidebar.style.transform = 'translateX(0)';
    }
    
    close() {
        const sidebar = document.querySelector('.fi-sidebar');
        if (!sidebar) return;
        
        this.isOpen = false;
        this.body.classList.remove('fi-sidebar-open');
        
        // Restore body scroll
        this.body.style.overflow = '';
        
        // Apply closed styles to sidebar
        sidebar.style.left = '';
        sidebar.style.transform = '';
    }
}

// Initialize only once
if (!window.silentMobileNavInitialized) {
    window.silentMobileNavInitialized = true;
    window.silentMobileNav = new SilentMobileNavigation();
}

// Make available globally
window.SilentMobileNavigation = SilentMobileNavigation;