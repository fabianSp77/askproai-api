/**
 * Portal Mobile Menu Handler
 * Fixed version that works with Alpine.js
 */
(function() {
    'use strict';
    
    console.log('[Portal Mobile Menu] Initializing...');
    
    // Wait for Alpine.js to be ready
    document.addEventListener('alpine:init', () => {
        console.log('[Portal Mobile Menu] Alpine.js is ready');
    });
    
    // Fallback for browsers without Alpine.js support
    document.addEventListener('DOMContentLoaded', function() {
        // Only initialize if Alpine.js failed to load
        setTimeout(() => {
            if (typeof Alpine === 'undefined') {
                console.warn('[Portal Mobile Menu] Alpine.js not found, using fallback');
                initializeFallbackMenu();
            } else {
                console.log('[Portal Mobile Menu] Alpine.js detected, using Alpine for menu handling');
            }
        }, 100);
    });
    
    function initializeFallbackMenu() {
        const menuButton = document.querySelector('button[class*="md:hidden"]');
        const sidebar = document.querySelector('.sidebar-mobile');
        const overlay = document.querySelector('.sidebar-overlay');
        
        if (!menuButton || !sidebar) {
            console.warn('[Portal Mobile Menu] Required elements not found');
            return;
        }
        
        let isOpen = false;
        
        menuButton.addEventListener('click', function(e) {
            e.preventDefault();
            isOpen = !isOpen;
            
            if (isOpen) {
                sidebar.classList.add('open');
                if (overlay) overlay.style.display = 'block';
            } else {
                sidebar.classList.remove('open');
                if (overlay) overlay.style.display = 'none';
            }
        });
        
        // Close on overlay click
        if (overlay) {
            overlay.addEventListener('click', () => {
                isOpen = false;
                sidebar.classList.remove('open');
                overlay.style.display = 'none';
            });
        }
        
        // Close on link click (mobile only)
        const links = sidebar.querySelectorAll('a');
        links.forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 768) {
                    isOpen = false;
                    sidebar.classList.remove('open');
                    if (overlay) overlay.style.display = 'none';
                }
            });
        });
    }
    
    console.log('[Portal Mobile Menu] Script loaded successfully');
})();