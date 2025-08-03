/**
 * Navigation Fix - Clean version without debug output
 * Only fixes navigation issues without console spam
 */

(function() {
    'use strict';
    
    // Wait for DOM
    function ready(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }
    
    ready(function() {
        // 1. Remove ALL pointer-events: none styles
        function fixPointerEvents() {
            // Remove from stylesheets
            const styleSheets = document.styleSheets;
            for (let i = 0; i < styleSheets.length; i++) {
                try {
                    const rules = styleSheets[i].cssRules || styleSheets[i].rules;
                    if (rules) {
                        for (let j = rules.length - 1; j >= 0; j--) {
                            const rule = rules[j];
                            if (rule.style && rule.style.pointerEvents === 'none') {
                                rule.style.pointerEvents = 'auto';
                            }
                        }
                    }
                } catch (e) {
                    // Cross-origin stylesheets - ignore
                }
            }
            
            // Fix inline styles
            document.querySelectorAll('*').forEach(el => {
                if (window.getComputedStyle(el).pointerEvents === 'none') {
                    el.style.pointerEvents = 'auto';
                }
            });
        }
        
        // 2. Ensure navigation links work
        function fixNavigationLinks() {
            const navLinks = document.querySelectorAll('.fi-sidebar-nav a, .fi-sidebar-item a, .fi-sidebar-nav button');
            
            navLinks.forEach((link) => {
                // Force clickability
                link.style.pointerEvents = 'auto';
                link.style.cursor = 'pointer';
                link.style.position = 'relative';
                link.style.zIndex = '10';
                
                // Add click handler for links with href
                link.addEventListener('click', function(e) {
                    // If it has href and no wire:navigate, force navigation
                    if (this.getAttribute('href') && !this.hasAttribute('wire:navigate')) {
                        e.preventDefault();
                        e.stopPropagation();
                        window.location.href = this.getAttribute('href');
                    }
                }, true);
            });
        }
        
        // 3. Monitor for dynamic changes
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    // Re-apply fixes for new elements
                    fixPointerEvents();
                    fixNavigationLinks();
                }
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
        
        // Apply all fixes
        fixPointerEvents();
        fixNavigationLinks();
        
        // Re-apply periodically (every 2 seconds)
        setInterval(() => {
            fixPointerEvents();
            fixNavigationLinks();
        }, 2000);
    });
})();