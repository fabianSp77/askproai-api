/**
 * State-of-the-Art Navigation System
 * Inspired by Stripe.com with advanced gestures and animations
 */

import Fuse from 'fuse.js';

class StripeMenu {
    constructor() {
        this.state = {
            isOpen: false,
            isMegaOpen: false,
            currentMega: null,
            searchOpen: false,
            touchStartX: 0,
            touchStartY: 0,
            touchEndX: 0,
            touchEndY: 0,
            isDragging: false,
            dragOffset: 0,
        };

        this.config = {
            swipeThreshold: 75,
            edgeSwipeZone: 20,
            hoverDelay: 300,
            animationDuration: 400,
            springTension: 0.3,
            dragResistance: 0.7,
        };

        this.elements = {};
        this.timers = {};
        this.observers = [];
        
        this.init();
    }

    init() {
        this.cacheElements();
        this.bindEvents();
        this.setupGestures();
        this.setupKeyboardShortcuts();
        this.setupIntersectionObservers();
        this.initCommandPalette();
        this.setupActiveLinkHighlighting();
        this.setupEnhancedKeyboardNav();
        this.syncWithFilamentSidebar();
    }

    cacheElements() {
        this.elements = {
            menu: document.querySelector('.stripe-menu'),
            mobileMenu: document.querySelector('.stripe-mobile-menu'),
            megaMenu: document.querySelector('.stripe-mega-menu'),
            overlay: document.querySelector('.stripe-menu-overlay'),
            hamburger: document.querySelector('.stripe-hamburger'),
            searchBar: document.querySelector('.stripe-search'),
            commandPalette: document.querySelector('.stripe-command-palette'),
            menuItems: document.querySelectorAll('[data-mega-trigger]'),
        };
        
        // Create missing elements if they don't exist
        if (!this.elements.hamburger) {
            console.warn('Hamburger button not found, searching for it...');
            // Try to find it within the stripe-menu
            this.elements.hamburger = document.querySelector('.stripe-menu .stripe-hamburger');
        }
        
        if (!this.elements.overlay && this.elements.mobileMenu) {
            console.warn('Creating overlay element');
            const overlay = document.createElement('div');
            overlay.className = 'stripe-menu-overlay';
            document.body.appendChild(overlay);
            this.elements.overlay = overlay;
        }
    }

    bindEvents() {
        // Desktop mega menu hover
        this.elements.menuItems.forEach(item => {
            item.addEventListener('mouseenter', (e) => this.handleMegaHover(e, item));
            item.addEventListener('mouseleave', (e) => this.handleMegaLeave(e, item));
            item.addEventListener('focus', (e) => this.handleMegaFocus(e, item));
        });

        // Mobile menu toggle
        this.elements.hamburger?.addEventListener('click', () => this.toggleMobileMenu());
        this.elements.overlay?.addEventListener('click', () => this.closeMobileMenu());

        // Resize handler with debouncing
        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => this.handleResize(), 250);
        });

        // Escape key handler
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeAll();
            }
        });
    }

    /**
     * Advanced Touch Gesture Support
     */
    setupGestures() {
        if (!this.elements.mobileMenu) return;

        const menu = this.elements.mobileMenu;
        
        // Touch start
        menu.addEventListener('touchstart', (e) => {
            this.state.touchStartX = e.touches[0].clientX;
            this.state.touchStartY = e.touches[0].clientY;
            this.state.isDragging = true;
            menu.style.transition = 'none';
        }, { passive: true });

        // Touch move - live drag feedback
        menu.addEventListener('touchmove', (e) => {
            if (!this.state.isDragging) return;
            
            const currentX = e.touches[0].clientX;
            const diffX = currentX - this.state.touchStartX;
            
            // Only allow dragging to close (left direction)
            if (diffX < 0) {
                // Apply resistance
                const resistance = Math.abs(diffX) * this.config.dragResistance;
                this.state.dragOffset = -resistance;
                
                // Apply transform with boundary
                const transform = Math.max(-menu.offsetWidth, this.state.dragOffset);
                menu.style.transform = `translateX(${transform}px)`;
                
                // Adjust overlay opacity based on drag
                const opacity = 1 - (Math.abs(transform) / menu.offsetWidth);
                this.elements.overlay.style.opacity = opacity;
            }
        }, { passive: true });

        // Touch end
        menu.addEventListener('touchend', (e) => {
            if (!this.state.isDragging) return;
            
            this.state.isDragging = false;
            this.state.touchEndX = e.changedTouches[0].clientX;
            
            const diffX = this.state.touchStartX - this.state.touchEndX;
            
            // Determine if swipe should close menu
            if (diffX > this.config.swipeThreshold) {
                this.closeMobileMenu();
            } else {
                // Spring back
                menu.style.transition = `transform ${this.config.animationDuration}ms cubic-bezier(0.68, -0.55, 0.265, 1.55)`;
                menu.style.transform = 'translateX(0)';
                this.elements.overlay.style.opacity = 1;
            }
            
            this.state.dragOffset = 0;
        }, { passive: true });

        // Edge swipe to open
        document.addEventListener('touchstart', (e) => {
            const touch = e.touches[0];
            if (touch.clientX < this.config.edgeSwipeZone && !this.state.isOpen) {
                this.state.touchStartX = touch.clientX;
                this.edgeSwipeActive = true;
            }
        }, { passive: true });

        document.addEventListener('touchmove', (e) => {
            if (!this.edgeSwipeActive) return;
            
            const touch = e.touches[0];
            const diffX = touch.clientX - this.state.touchStartX;
            
            if (diffX > this.config.swipeThreshold) {
                this.openMobileMenu();
                this.edgeSwipeActive = false;
            }
        }, { passive: true });
    }

    /**
     * Mega Menu Hover with Intent Detection
     */
    handleMegaHover(e, item) {
        const megaContent = item.dataset.megaTrigger;
        
        // Clear any existing timer
        clearTimeout(this.timers.megaHover);
        clearTimeout(this.timers.megaLeave);
        
        // Hover intent delay
        this.timers.megaHover = setTimeout(() => {
            this.showMegaMenu(megaContent, item);
        }, this.config.hoverDelay);
    }

    handleMegaLeave(e, item) {
        clearTimeout(this.timers.megaHover);
        
        // Delay before hiding to allow cursor to move to mega menu
        this.timers.megaLeave = setTimeout(() => {
            // Check if cursor is not over mega menu
            if (!this.elements.megaMenu.matches(':hover')) {
                this.hideMegaMenu();
            }
        }, 100);
    }

    handleMegaFocus(e, item) {
        const megaContent = item.dataset.megaTrigger;
        this.showMegaMenu(megaContent, item);
    }

    /**
     * Show Mega Menu with Animation
     */
    showMegaMenu(contentId, trigger) {
        if (this.state.currentMega === contentId && this.state.isMegaOpen) return;
        
        const megaMenu = this.elements.megaMenu;
        const content = document.querySelector(`[data-mega-content="${contentId}"]`);
        
        if (!content) return;
        
        // Hide all content sections
        megaMenu.querySelectorAll('[data-mega-content]').forEach(el => {
            el.style.display = 'none';
        });
        
        // Position mega menu
        const triggerRect = trigger.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        
        // Smart positioning
        let leftPosition = triggerRect.left;
        const menuWidth = 800; // Approximate mega menu width
        
        if (leftPosition + menuWidth > viewportWidth) {
            leftPosition = viewportWidth - menuWidth - 20;
        }
        
        megaMenu.style.left = `${leftPosition}px`;
        megaMenu.style.top = `${triggerRect.bottom}px`;
        
        // Show content
        content.style.display = 'block';
        
        // Animate in
        megaMenu.style.display = 'block';
        megaMenu.style.opacity = '0';
        megaMenu.style.transform = 'translateY(-10px)';
        
        requestAnimationFrame(() => {
            megaMenu.style.transition = `opacity 200ms ease, transform 200ms ease`;
            megaMenu.style.opacity = '1';
            megaMenu.style.transform = 'translateY(0)';
        });
        
        this.state.isMegaOpen = true;
        this.state.currentMega = contentId;
        
        // Add active state to trigger
        this.elements.menuItems.forEach(item => item.classList.remove('active'));
        trigger.classList.add('active');
    }

    hideMegaMenu() {
        const megaMenu = this.elements.megaMenu;
        
        megaMenu.style.transition = `opacity 150ms ease, transform 150ms ease`;
        megaMenu.style.opacity = '0';
        megaMenu.style.transform = 'translateY(-10px)';
        
        setTimeout(() => {
            megaMenu.style.display = 'none';
        }, 150);
        
        this.state.isMegaOpen = false;
        this.state.currentMega = null;
        
        // Remove active states
        this.elements.menuItems.forEach(item => item.classList.remove('active'));
    }

    /**
     * Sync with Filament's sidebar state
     */
    syncWithFilamentSidebar() {
        // Only in Filament admin
        if (window.Alpine && window.Alpine.store('sidebar')) {
            const sidebarStore = window.Alpine.store('sidebar');
            
            // Watch for sidebar state changes
            window.Alpine.effect(() => {
                const isOpen = sidebarStore.isOpen;
                this.animateHamburger(isOpen);
                this.state.isOpen = isOpen;
            });
            
            // Set initial state
            if (sidebarStore.isOpen) {
                this.animateHamburger(true);
                this.state.isOpen = true;
            }
        }
    }

    /**
     * Mobile Menu Controls
     */
    toggleMobileMenu() {
        // Check if we're in Filament admin panel
        const isFilamentAdmin = window.Alpine && window.Alpine.store('sidebar');
        
        if (isFilamentAdmin) {
            // Toggle Filament's sidebar using Alpine store
            const sidebarStore = window.Alpine.store('sidebar');
            if (sidebarStore.isOpen) {
                sidebarStore.close();
                this.animateHamburger(false);
                this.state.isOpen = false;
            } else {
                sidebarStore.open();
                this.animateHamburger(true);
                this.state.isOpen = true;
            }
        } else if (this.state.isOpen) {
            this.closeMobileMenu();
        } else {
            this.openMobileMenu();
        }
    }

    openMobileMenu() {
        const menu = this.elements.mobileMenu;
        const overlay = this.elements.overlay;
        
        // Show elements
        menu.style.display = 'block';
        overlay.style.display = 'block';
        
        // Animate hamburger to X
        this.animateHamburger(true);
        
        // Animate menu slide
        requestAnimationFrame(() => {
            menu.style.transition = `transform ${this.config.animationDuration}ms cubic-bezier(0.68, -0.55, 0.265, 1.55)`;
            menu.style.transform = 'translateX(0)';
            
            overlay.style.transition = `opacity ${this.config.animationDuration}ms ease`;
            overlay.style.opacity = '1';
        });
        
        // Stagger animate menu items
        this.animateMenuItems(true);
        
        // Lock body scroll
        document.body.style.overflow = 'hidden';
        
        // Update state
        this.state.isOpen = true;
        
        // Track in analytics
        this.trackEvent('mobile_menu_open');
    }

    closeMobileMenu() {
        const menu = this.elements.mobileMenu;
        const overlay = this.elements.overlay;
        
        // Animate hamburger back
        this.animateHamburger(false);
        
        // Animate menu slide
        menu.style.transition = `transform ${this.config.animationDuration}ms ease`;
        menu.style.transform = 'translateX(-100%)';
        
        overlay.style.transition = `opacity ${this.config.animationDuration}ms ease`;
        overlay.style.opacity = '0';
        
        // Hide after animation
        setTimeout(() => {
            menu.style.display = 'none';
            overlay.style.display = 'none';
        }, this.config.animationDuration);
        
        // Unlock body scroll
        document.body.style.overflow = '';
        
        // Update state
        this.state.isOpen = false;
        
        // Track in analytics
        this.trackEvent('mobile_menu_close');
    }

    /**
     * Hamburger to X Animation
     */
    animateHamburger(toX) {
        const hamburger = this.elements.hamburger;
        const bars = hamburger.querySelectorAll('.bar');
        
        if (toX) {
            bars[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
            bars[1].style.opacity = '0';
            bars[2].style.transform = 'rotate(-45deg) translate(5px, -5px)';
        } else {
            bars[0].style.transform = '';
            bars[1].style.opacity = '1';
            bars[2].style.transform = '';
        }
    }

    /**
     * Stagger Animation for Menu Items
     */
    animateMenuItems(show) {
        const items = this.elements.mobileMenu.querySelectorAll('.menu-item');
        
        items.forEach((item, index) => {
            if (show) {
                item.style.opacity = '0';
                item.style.transform = 'translateX(-20px)';
                
                setTimeout(() => {
                    item.style.transition = 'opacity 300ms ease, transform 300ms ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateX(0)';
                }, index * 50);
            } else {
                item.style.opacity = '0';
                item.style.transform = 'translateX(-20px)';
            }
        });
    }

    /**
     * Command Palette (CMD+K)
     */
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // CMD+K or CTRL+K for command palette
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                this.toggleCommandPalette();
            }
            
            // / for search (when not in input)
            if (e.key === '/' && !this.isInputFocused()) {
                e.preventDefault();
                this.focusSearch();
            }
        });
    }

    initCommandPalette() {
        // Initialize fuzzy search
        if (this.elements.commandPalette) {
            this.searchIndex = new Fuse(window.navigationData?.search || [], {
                keys: ['label', 'description', 'keywords'],
                threshold: 0.3,
            });
        }
    }

    toggleCommandPalette() {
        const palette = this.elements.commandPalette;
        
        if (!palette) return;
        
        if (this.state.searchOpen) {
            this.closeCommandPalette();
        } else {
            this.openCommandPalette();
        }
    }

    openCommandPalette() {
        const palette = this.elements.commandPalette;
        
        palette.style.display = 'flex';
        requestAnimationFrame(() => {
            palette.style.opacity = '1';
            palette.querySelector('input')?.focus();
        });
        
        this.state.searchOpen = true;
    }

    closeCommandPalette() {
        const palette = this.elements.commandPalette;
        
        palette.style.opacity = '0';
        setTimeout(() => {
            palette.style.display = 'none';
        }, 200);
        
        this.state.searchOpen = false;
    }

    /**
     * Intersection Observer for Lazy Loading
     */
    setupIntersectionObservers() {
        if ('IntersectionObserver' in window) {
            const options = {
                root: null,
                rootMargin: '50px',
                threshold: 0.1
            };
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.lazyLoadContent(entry.target);
                    }
                });
            }, options);
            
            // Observe mega menu sections
            document.querySelectorAll('[data-lazy-load]').forEach(el => {
                observer.observe(el);
            });
            
            this.observers.push(observer);
        }
    }

    lazyLoadContent(element) {
        // Load content dynamically
        const contentUrl = element.dataset.lazyLoad;
        
        if (contentUrl && !element.dataset.loaded) {
            fetch(contentUrl)
                .then(response => response.text())
                .then(html => {
                    element.innerHTML = html;
                    element.dataset.loaded = 'true';
                });
        }
    }

    /**
     * Active Link Highlighting
     */
    setupActiveLinkHighlighting() {
        this.highlightActiveLinks();
        
        // Re-highlight on navigation
        window.addEventListener('popstate', () => this.highlightActiveLinks());
        
        // For SPA-style navigation, also listen to URL changes
        const originalPushState = history.pushState;
        const originalReplaceState = history.replaceState;
        
        history.pushState = function() {
            originalPushState.apply(history, arguments);
            setTimeout(() => this.highlightActiveLinks(), 0);
        }.bind(this);
        
        history.replaceState = function() {
            originalReplaceState.apply(history, arguments);
            setTimeout(() => this.highlightActiveLinks(), 0);
        }.bind(this);
    }
    
    highlightActiveLinks() {
        const currentPath = window.location.pathname;
        const links = document.querySelectorAll('.stripe-menu a, .stripe-mobile-menu a, .stripe-mega-menu a');
        
        // Remove all active classes
        links.forEach(link => {
            link.classList.remove('active', 'bg-indigo-50', 'text-indigo-600', 'border-indigo-500');
            link.parentElement?.classList.remove('active');
        });
        
        // Find matching links and add active classes
        links.forEach(link => {
            const linkPath = new URL(link.href).pathname;
            
            if (this.isActivePath(currentPath, linkPath)) {
                // Main navigation
                if (link.closest('.stripe-menu')) {
                    link.classList.add('active', 'text-indigo-600', 'border-indigo-500');
                    link.style.borderBottomWidth = '2px';
                }
                
                // Mobile menu
                if (link.closest('.stripe-mobile-menu')) {
                    link.classList.add('active', 'bg-indigo-50', 'text-indigo-600');
                }
                
                // Mega menu
                if (link.closest('.stripe-mega-menu')) {
                    link.classList.add('active', 'bg-indigo-50', 'text-indigo-600');
                    link.style.borderLeft = '3px solid rgb(99 102 241)';
                }
            }
        });
    }
    
    isActivePath(currentPath, linkPath) {
        // Exact match
        if (currentPath === linkPath) return true;
        
        // Dashboard special case
        if (currentPath === '/admin' && linkPath === '/admin') return true;
        if (currentPath === '/admin/' && linkPath === '/admin') return true;
        
        // Sub-path matching (e.g., /admin/users/1/edit matches /admin/users)
        if (linkPath !== '/admin' && currentPath.startsWith(linkPath)) {
            // Make sure it's a real sub-path, not just a prefix
            const remainder = currentPath.slice(linkPath.length);
            return remainder.startsWith('/') || remainder === '';
        }
        
        return false;
    }
    
    /**
     * Enhanced Keyboard Navigation
     */
    setupEnhancedKeyboardNav() {
        let currentFocus = -1;
        const focusableElements = [];
        
        document.addEventListener('keydown', (e) => {
            // Arrow key navigation in mega menu
            if (this.state.isMegaOpen) {
                const megaLinks = Array.from(this.elements.megaMenu.querySelectorAll('a'));
                
                switch (e.key) {
                    case 'ArrowDown':
                        e.preventDefault();
                        currentFocus = Math.min(currentFocus + 1, megaLinks.length - 1);
                        megaLinks[currentFocus]?.focus();
                        break;
                    case 'ArrowUp':
                        e.preventDefault();
                        currentFocus = Math.max(currentFocus - 1, 0);
                        megaLinks[currentFocus]?.focus();
                        break;
                    case 'Tab':
                        if (!e.shiftKey && currentFocus === megaLinks.length - 1) {
                            this.hideMegaMenu();
                        }
                        break;
                }
            }
            
            // Enhanced shortcuts
            if (e.altKey) {
                switch (e.key) {
                    case 'h':
                        e.preventDefault();
                        window.location.href = '/admin';
                        break;
                    case 'c':
                        e.preventDefault();
                        window.location.href = '/admin/customers';
                        break;
                    case 'p':
                        e.preventDefault();
                        window.location.href = '/admin/calls';
                        break;
                    case 'a':
                        e.preventDefault();
                        window.location.href = '/admin/appointments';
                        break;
                }
            }
        });
    }
    
    /**
     * Utility Methods
     */
    handleResize() {
        // Close mobile menu on desktop resize
        if (window.innerWidth >= 1024 && this.state.isOpen) {
            this.closeMobileMenu();
        }
        
        // Reposition mega menu if open
        if (this.state.isMegaOpen) {
            this.hideMegaMenu();
        }
        
        // Re-highlight active links on resize
        this.highlightActiveLinks();
    }

    closeAll() {
        this.closeMobileMenu();
        this.hideMegaMenu();
        this.closeCommandPalette();
    }

    isInputFocused() {
        const activeElement = document.activeElement;
        return activeElement.tagName === 'INPUT' || 
               activeElement.tagName === 'TEXTAREA' ||
               activeElement.contentEditable === 'true';
    }

    focusSearch() {
        this.elements.searchBar?.focus();
    }

    trackEvent(eventName, data = {}) {
        // Analytics tracking
        if (window.gtag) {
            window.gtag('event', eventName, data);
        }
    }

    /**
     * Destroy method for cleanup
     */
    destroy() {
        // Clear timers
        Object.values(this.timers).forEach(timer => clearTimeout(timer));
        
        // Disconnect observers
        this.observers.forEach(observer => observer.disconnect());
        
        // Remove event listeners
        // ... cleanup code
    }
}

// Initialize on DOM ready with better timing
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.stripeMenu = new StripeMenu();
    });
} else {
    // DOM is already loaded
    setTimeout(() => {
        window.stripeMenu = new StripeMenu();
    }, 0);
}

// Alpine.js integration
if (window.Alpine) {
    window.Alpine.data('stripeMenu', () => ({
        open: false,
        megaOpen: false,
        currentMega: null,
        
        toggleMenu() {
            this.open = !this.open;
        },
        
        showMega(content) {
            this.currentMega = content;
            this.megaOpen = true;
        },
        
        hideMega() {
            this.megaOpen = false;
            this.currentMega = null;
        }
    }));
}