/**
 * Universal Click Handler - Framework-independent solution
 * This ensures ALL links and buttons work regardless of framework issues
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
        // Use capture phase to intercept clicks before frameworks
        document.addEventListener('click', function(e) {
            // Find the closest link or button
            let target = e.target;
            let clickableElement = null;
            
            // Walk up the DOM tree to find a clickable element
            while (target && target !== document.body) {
                if (target.tagName === 'A' || target.tagName === 'BUTTON') {
                    clickableElement = target;
                    break;
                }
                // Also check for elements with wire:navigate or href attributes
                if (target.hasAttribute('href') || target.hasAttribute('wire:navigate')) {
                    clickableElement = target;
                    break;
                }
                target = target.parentElement;
            }
            
            // If we found a link with href, handle it
            if (clickableElement && clickableElement.tagName === 'A' && clickableElement.href) {
                // Skip if it's already being handled by the framework
                if (e.defaultPrevented) return;
                
                // Skip special links
                if (clickableElement.href.startsWith('javascript:')) return;
                if (clickableElement.href.startsWith('#')) return;
                if (clickableElement.target === '_blank') return;
                
                // For admin panel navigation, force page load
                if (clickableElement.href.includes('/admin')) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    window.location.href = clickableElement.href;
                }
            }
        }, true); // true = capture phase, runs before other handlers
        
        // Remove any blocking styles periodically
        function ensureClickability() {
            // Remove pointer-events: none from body and main containers
            const containers = document.querySelectorAll('body, .fi-body, .fi-main, .fi-sidebar');
            containers.forEach(el => {
                if (window.getComputedStyle(el).pointerEvents === 'none') {
                    el.style.pointerEvents = 'auto';
                }
            });
            
            // Ensure all links and buttons are clickable
            const clickables = document.querySelectorAll('a, button, [wire\\:navigate], [href]');
            clickables.forEach(el => {
                if (window.getComputedStyle(el).pointerEvents === 'none') {
                    el.style.pointerEvents = 'auto';
                }
                // Ensure proper cursor
                if (el.tagName === 'A' || el.tagName === 'BUTTON') {
                    el.style.cursor = 'pointer';
                }
            });
        }
        
        // Run immediately and periodically
        ensureClickability();
        setInterval(ensureClickability, 1000);
        
        // Also run after any DOM changes
        const observer = new MutationObserver(ensureClickability);
        observer.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['style', 'class']
        });
    });
})();