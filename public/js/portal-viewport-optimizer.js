// Portal Viewport Optimizer for High-Resolution Displays
(function() {
    'use strict';

    // Detect high-resolution displays
    function isHighResolution() {
        return window.screen.width >= 2560 || window.devicePixelRatio > 1.5;
    }

    // Detect ultra-wide displays (21:9 aspect ratio)
    function isUltraWide() {
        const aspectRatio = window.screen.width / window.screen.height;
        return aspectRatio > 2.3; // 21:9 = 2.33
    }

    // Optimize viewport for better readability
    function optimizeViewport() {
        if (isHighResolution()) {
            // Add class for CSS targeting
            document.body.classList.add('high-res-display');
            
            if (isUltraWide()) {
                document.body.classList.add('ultra-wide-display');
            }

            // Adjust base font size for better readability
            const screenWidth = window.screen.width;
            if (screenWidth >= 3840) {
                document.documentElement.style.fontSize = '18px';
                document.body.classList.add('display-4k');
            } else if (screenWidth >= 2560) {
                document.documentElement.style.fontSize = '17px';
                document.body.classList.add('display-2k');
            }
        }
    }

    // Optimize table layouts for wide screens
    function optimizeTableLayouts() {
        const containers = document.querySelectorAll('.max-w-7xl');
        
        if (window.innerWidth >= 2560) {
            containers.forEach(container => {
                container.style.maxWidth = Math.min(window.innerWidth * 0.85, 2560) + 'px';
            });
        }
    }

    // Fix grid layouts for stats cards
    function optimizeGridLayouts() {
        const grids = document.querySelectorAll('.grid');
        
        grids.forEach(grid => {
            if (window.innerWidth >= 3840) {
                // For 4K displays, ensure proper spacing
                grid.style.gap = '2rem';
            }
        });
    }

    // Improve text readability
    function improveTextContrast() {
        if (isHighResolution()) {
            // Slightly increase font weights for better readability
            const textElements = document.querySelectorAll('.text-gray-500, .text-gray-400');
            textElements.forEach(el => {
                const currentWeight = window.getComputedStyle(el).fontWeight;
                if (currentWeight === '400') {
                    el.style.fontWeight = '500';
                }
            });
        }
    }

    // Handle dynamic content loading
    function observeDynamicContent() {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    // Reapply optimizations for dynamically loaded content
                    setTimeout(() => {
                        optimizeTableLayouts();
                        optimizeGridLayouts();
                        improveTextContrast();
                    }, 100);
                }
            });
        });

        // Observe the main content area
        const mainContent = document.querySelector('main');
        if (mainContent) {
            observer.observe(mainContent, {
                childList: true,
                subtree: true
            });
        }
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        optimizeViewport();
        optimizeTableLayouts();
        optimizeGridLayouts();
        improveTextContrast();
        observeDynamicContent();

        // Re-optimize on window resize
        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                optimizeTableLayouts();
                optimizeGridLayouts();
            }, 250);
        });
    }

    // Debug info for development
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
        console.log('Portal Viewport Optimizer loaded', {
            screenWidth: window.screen.width,
            screenHeight: window.screen.height,
            devicePixelRatio: window.devicePixelRatio,
            isHighRes: isHighResolution(),
            isUltraWide: isUltraWide()
        });
    }
})();