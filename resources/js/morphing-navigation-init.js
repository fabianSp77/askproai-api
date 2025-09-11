/**
 * Morphing Navigation Initialization with Guards
 * Ensures proper loading and fallback behavior
 */

(function() {
    'use strict';
    
    // Check for required dependencies
    function checkDependencies() {
        const deps = {
            alpine: typeof Alpine !== 'undefined',
            tailwind: document.querySelector('script[src*="tailwindcss"]') !== null || 
                      document.querySelector('link[href*="tailwind"]') !== null
        };
        
        return deps;
    }
    
    // Wait for Alpine.js to be ready
    function waitForAlpine(callback, maxAttempts = 50) {
        let attempts = 0;
        
        const checkAlpine = setInterval(() => {
            attempts++;
            
            if (typeof Alpine !== 'undefined') {
                clearInterval(checkAlpine);
                callback();
            } else if (attempts >= maxAttempts) {
                clearInterval(checkAlpine);
                console.error('Alpine.js failed to load after ' + maxAttempts + ' attempts');
                fallbackNavigation();
            }
        }, 100); // Check every 100ms
    }
    
    // Fallback navigation for when Alpine fails
    function fallbackNavigation() {
        console.warn('Loading fallback navigation...');
        
        // Add basic click handlers without Alpine
        document.querySelectorAll('[data-dropdown]').forEach(trigger => {
            trigger.addEventListener('click', function(e) {
                e.preventDefault();
                const dropdownId = this.getAttribute('data-dropdown');
                toggleDropdownFallback(dropdownId);
            });
        });
        
        // Mobile menu fallback
        const hamburger = document.querySelector('.morph-nav-hamburger');
        if (hamburger) {
            hamburger.addEventListener('click', toggleMobileMenuFallback);
        }
        
        // Close on escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAllFallback();
            }
        });
        
        // Click outside to close
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.morph-nav-bar')) {
                closeAllFallback();
            }
        });
    }
    
    function toggleDropdownFallback(dropdownId) {
        const dropdown = document.getElementById(dropdownId + '-dropdown');
        const wrapper = document.querySelector('.morph-dropdown-wrapper');
        
        if (!dropdown || !wrapper) return;
        
        const isOpen = wrapper.classList.contains('is-open');
        
        if (isOpen && wrapper.getAttribute('data-active') === dropdownId) {
            // Close
            wrapper.classList.remove('is-open');
            wrapper.setAttribute('data-active', '');
            dropdown.style.display = 'none';
        } else {
            // Close any open dropdown first
            closeAllFallback();
            
            // Open new dropdown
            wrapper.classList.add('is-open');
            wrapper.setAttribute('data-active', dropdownId);
            dropdown.style.display = 'block';
            
            // Position dropdown
            positionDropdownFallback(dropdownId);
        }
    }
    
    function positionDropdownFallback(dropdownId) {
        const trigger = document.querySelector(`[data-dropdown="${dropdownId}"]`);
        const wrapper = document.querySelector('.morph-dropdown-wrapper');
        
        if (!trigger || !wrapper) return;
        
        const triggerRect = trigger.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        const dropdownWidth = Math.min(600, viewportWidth - 48);
        
        let left = triggerRect.left + (triggerRect.width / 2) - (dropdownWidth / 2);
        left = Math.max(24, Math.min(left, viewportWidth - dropdownWidth - 24));
        
        wrapper.style.left = left + 'px';
        wrapper.style.width = dropdownWidth + 'px';
    }
    
    function toggleMobileMenuFallback() {
        const mobileMenu = document.querySelector('.morph-mobile-menu');
        if (!mobileMenu) return;
        
        const isOpen = mobileMenu.classList.contains('is-open');
        
        if (isOpen) {
            mobileMenu.classList.remove('is-open');
            document.body.style.overflow = '';
        } else {
            mobileMenu.classList.add('is-open');
            document.body.style.overflow = 'hidden';
        }
    }
    
    function closeAllFallback() {
        // Close dropdowns
        const wrapper = document.querySelector('.morph-dropdown-wrapper');
        if (wrapper) {
            wrapper.classList.remove('is-open');
            wrapper.setAttribute('data-active', '');
        }
        
        // Hide all dropdown panels
        document.querySelectorAll('.morph-dropdown-panel').forEach(panel => {
            panel.style.display = 'none';
        });
        
        // Close mobile menu
        const mobileMenu = document.querySelector('.morph-mobile-menu');
        if (mobileMenu) {
            mobileMenu.classList.remove('is-open');
            document.body.style.overflow = '';
        }
    }
    
    // Add CSS classes for fallback mode
    function addFallbackStyles() {
        const style = document.createElement('style');
        style.textContent = `
            .morph-dropdown-wrapper.is-open {
                opacity: 1;
                visibility: visible;
                transform: scale(1);
            }
            .morph-mobile-menu.is-open {
                transform: translateX(0);
            }
            .morph-dropdown-panel {
                display: none;
            }
            .morph-dropdown-wrapper.is-open .morph-dropdown-panel {
                display: block !important;
            }
        `;
        document.head.appendChild(style);
    }
    
    // Error recovery mechanism
    function setupErrorRecovery() {
        window.addEventListener('error', function(e) {
            if (e.message && e.message.includes('Alpine')) {
                console.error('Alpine error detected, switching to fallback navigation');
                fallbackNavigation();
                addFallbackStyles();
            }
        });
    }
    
    // Main initialization
    function init() {
        const deps = checkDependencies();
        
        if (!deps.alpine) {
            console.warn('Alpine.js not detected, waiting...');
            waitForAlpine(() => {
                console.log('Alpine.js loaded successfully');
                initializeAlpineNavigation();
            });
        } else {
            console.log('Alpine.js already loaded');
            initializeAlpineNavigation();
        }
        
        // Setup error recovery
        setupErrorRecovery();
    }
    
    function initializeAlpineNavigation() {
        // Check if Alpine component is already initialized
        const navElement = document.querySelector('[x-data*="morphingNavigation"]');
        if (navElement && navElement.__x) {
            console.log('Morphing navigation already initialized');
            return;
        }
        
        // If Alpine is ready but component not initialized, dispatch ready event
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                console.log('DOM ready, Alpine navigation should initialize');
            });
        } else {
            console.log('Alpine navigation ready for initialization');
        }
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Expose functions globally for debugging
    window.MorphingNavigationDebug = {
        checkDependencies,
        fallbackNavigation,
        closeAllFallback,
        reinitialize: init
    };
})();