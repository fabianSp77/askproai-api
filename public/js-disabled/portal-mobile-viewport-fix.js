/**
 * Portal Mobile Viewport Fix
 * Fixes critical mobile viewport issues affecting 40% of users
 * Based on UX analysis showing mobile experience is "completely broken"
 */

(function() {
    'use strict';

    // Mobile viewport fix class
    class MobileViewportFix {
        constructor() {
            this.init();
            this.fixIOSViewportBug();
            this.preventZoomOnFocus();
            this.fixMobileNavigation();
            this.optimizeTouchTargets();
            this.fixScrollingIssues();
        }

        init() {
            // Detect mobile device
            this.isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ||
                           window.innerWidth <= 768;

            if (this.isMobile) {
                document.documentElement.classList.add('is-mobile-device');
                this.applyMobileFixes();
            }

            // Monitor orientation changes
            window.addEventListener('orientationchange', () => this.handleOrientationChange());
            window.addEventListener('resize', this.debounce(() => this.handleResize(), 250));
        }

        // Fix iOS 100vh bug
        fixIOSViewportBug() {
            const setVH = () => {
                const vh = window.innerHeight * 0.01;
                document.documentElement.style.setProperty('--vh', `${vh}px`);
            };

            setVH();
            window.addEventListener('resize', setVH);
            window.addEventListener('orientationchange', setVH);

            // Inject CSS for mobile viewport units
            const style = document.createElement('style');
            style.innerHTML = `
                /* Fix 100vh on mobile */
                .h-screen {
                    height: 100vh;
                    height: calc(var(--vh, 1vh) * 100);
                }
                
                .min-h-screen {
                    min-height: 100vh;
                    min-height: calc(var(--vh, 1vh) * 100);
                }
                
                /* Mobile-specific navigation fixes */
                @media (max-width: 768px) {
                    /* Fix navigation overlap */
                    .mobile-nav {
                        position: fixed;
                        top: 0;
                        left: 0;
                        right: 0;
                        z-index: 50;
                        background: white;
                    }
                    
                    /* Prevent body scroll when menu open */
                    body.mobile-menu-open {
                        position: fixed;
                        width: 100%;
                        overflow: hidden;
                    }
                    
                    /* Fix content behind nav */
                    main {
                        padding-top: 64px; /* Nav height */
                    }
                    
                    /* Ensure touch targets are 44px minimum */
                    button, a, .clickable {
                        min-height: 44px;
                        min-width: 44px;
                    }
                    
                    /* Fix table scrolling */
                    .table-container {
                        -webkit-overflow-scrolling: touch;
                        overflow-x: auto;
                    }
                }
            `;
            document.head.appendChild(style);
        }

        // Prevent zoom on input focus (iOS)
        preventZoomOnFocus() {
            if (!this.isMobile) return;

            const inputs = document.querySelectorAll('input, textarea, select');
            inputs.forEach(input => {
                // Set font size to prevent zoom
                input.style.fontSize = '16px';
                
                input.addEventListener('focus', (e) => {
                    // Prevent default zoom behavior
                    e.target.style.fontSize = '16px';
                    
                    // Scroll input into view
                    setTimeout(() => {
                        const rect = e.target.getBoundingClientRect();
                        if (rect.bottom > window.innerHeight || rect.top < 0) {
                            e.target.scrollIntoView({ 
                                behavior: 'smooth', 
                                block: 'center' 
                            });
                        }
                    }, 300);
                });
            });
        }

        // Fix mobile navigation that's "completely broken"
        fixMobileNavigation() {
            // Find mobile menu toggle
            const menuToggle = document.querySelector('[data-mobile-menu-toggle], #mobile-menu-toggle, .mobile-menu-toggle');
            const mobileMenu = document.querySelector('[data-mobile-menu], #mobile-menu, .mobile-menu');
            
            if (menuToggle && mobileMenu) {
                // Remove any existing listeners
                const newToggle = menuToggle.cloneNode(true);
                menuToggle.parentNode.replaceChild(newToggle, menuToggle);
                
                // Add proper click handler
                newToggle.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const isOpen = mobileMenu.classList.contains('open') || 
                                  mobileMenu.classList.contains('is-open') ||
                                  mobileMenu.classList.contains('show');
                    
                    if (isOpen) {
                        this.closeMobileMenu(mobileMenu);
                    } else {
                        this.openMobileMenu(mobileMenu);
                    }
                });

                // Close on outside click
                document.addEventListener('click', (e) => {
                    if (!mobileMenu.contains(e.target) && !newToggle.contains(e.target)) {
                        this.closeMobileMenu(mobileMenu);
                    }
                });

                // Close on escape
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        this.closeMobileMenu(mobileMenu);
                    }
                });
            }

            // Fix navigation links
            this.fixNavigationLinks();
        }

        openMobileMenu(menu) {
            menu.classList.add('open', 'is-open', 'show');
            menu.classList.remove('closed', 'hidden');
            document.body.classList.add('mobile-menu-open');
            
            // Prevent background scrolling
            document.body.style.overflow = 'hidden';
            
            // Announce to screen readers
            menu.setAttribute('aria-hidden', 'false');
        }

        closeMobileMenu(menu) {
            menu.classList.remove('open', 'is-open', 'show');
            menu.classList.add('closed');
            document.body.classList.remove('mobile-menu-open');
            
            // Restore scrolling
            document.body.style.overflow = '';
            
            // Announce to screen readers
            menu.setAttribute('aria-hidden', 'true');
        }

        fixNavigationLinks() {
            // Remove pointer-events: none from ALL navigation links
            const navLinks = document.querySelectorAll('nav a, .navigation a, [role="navigation"] a');
            navLinks.forEach(link => {
                link.style.pointerEvents = 'auto';
                link.style.cursor = 'pointer';
                
                // Ensure proper touch targets
                if (this.isMobile) {
                    const computedStyle = window.getComputedStyle(link);
                    const height = parseInt(computedStyle.height);
                    if (height < 44) {
                        link.style.minHeight = '44px';
                        link.style.display = 'flex';
                        link.style.alignItems = 'center';
                    }
                }
            });
        }

        // Optimize touch targets for mobile
        optimizeTouchTargets() {
            if (!this.isMobile) return;

            // Find all interactive elements
            const interactiveElements = document.querySelectorAll('button, a, input, select, textarea, [role="button"], [onclick]');
            
            interactiveElements.forEach(element => {
                const rect = element.getBoundingClientRect();
                
                // Check if element is too small
                if (rect.width < 44 || rect.height < 44) {
                    // Add padding to increase touch target
                    element.style.padding = '12px';
                    element.classList.add('mobile-optimized');
                }
            });
        }

        // Fix scrolling issues on mobile
        fixScrollingIssues() {
            // Enable momentum scrolling
            const scrollables = document.querySelectorAll('.overflow-auto, .overflow-y-auto, .overflow-x-auto, .scrollable');
            scrollables.forEach(el => {
                el.style.webkitOverflowScrolling = 'touch';
                el.style.overscrollBehavior = 'contain';
            });

            // Prevent overscroll on body
            document.body.style.overscrollBehavior = 'none';

            // Fix position:fixed elements during scroll
            let ticking = false;
            const fixedElements = document.querySelectorAll('.fixed, [style*="position: fixed"]');
            
            window.addEventListener('scroll', () => {
                if (!ticking) {
                    window.requestAnimationFrame(() => {
                        fixedElements.forEach(el => {
                            // Force repaint to fix rendering issues
                            el.style.transform = 'translateZ(0)';
                        });
                        ticking = false;
                    });
                    ticking = true;
                }
            });
        }

        // Handle orientation changes
        handleOrientationChange() {
            // Wait for orientation change to complete
            setTimeout(() => {
                this.fixIOSViewportBug();
                this.fixNavigationLinks();
                this.optimizeTouchTargets();
            }, 500);
        }

        // Handle window resize
        handleResize() {
            const newIsMobile = window.innerWidth <= 768;
            if (newIsMobile !== this.isMobile) {
                this.isMobile = newIsMobile;
                this.applyMobileFixes();
            }
        }

        // Apply all mobile fixes
        applyMobileFixes() {
            if (this.isMobile) {
                document.documentElement.classList.add('is-mobile-device');
                this.preventZoomOnFocus();
                this.fixMobileNavigation();
                this.optimizeTouchTargets();
                this.fixScrollingIssues();
            } else {
                document.documentElement.classList.remove('is-mobile-device');
            }
        }

        // Utility: Debounce function
        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.mobileViewportFix = new MobileViewportFix();
        });
    } else {
        window.mobileViewportFix = new MobileViewportFix();
    }

    // Reinitialize after Alpine if present
    if (typeof Alpine !== 'undefined') {
        document.addEventListener('alpine:initialized', () => {
            window.mobileViewportFix.fixNavigationLinks();
        });
    }

    console.log('Portal Mobile Viewport Fix loaded - fixing critical mobile issues');

})();