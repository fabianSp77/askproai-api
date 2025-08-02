/**
 * Animation Enhancer - Quick fixes for broken animations
 * Immediately improves user interactions in the admin panel
 * Created: 2025-08-01
 */

(function() {
    'use strict';
    
    // Configuration
    const config = {
        // Reduced motion support
        respectReducedMotion: true,
        
        // Default durations (in ms)
        durations: {
            quick: 150,
            normal: 250,
            slow: 400
        },
        
        // Easing functions
        easings: {
            smooth: 'cubic-bezier(0.4, 0, 0.2, 1)',
            bounce: 'cubic-bezier(0.68, -0.55, 0.265, 1.55)',
            ease: 'ease-out'
        }
    };
    
    // Check for reduced motion preference
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    
    function init() {
        console.log('Animation Enhancer loading...');
        
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', enhance);
        } else {
            enhance();
        }
    }
    
    function enhance() {
        fixButtonHoverEffects();
        fixLoadingSpinners();
        fixDropdownAnimations();
        fixModalAnimations();
        fixTooltipAnimations();
        fixTableRowHovers();
        fixFormFeedback();
        addGlobalStyles();
        
        console.log('Animation Enhancer active');
    }
    
    function fixButtonHoverEffects() {
        // Add enhanced hover effects to buttons missing them
        const buttons = document.querySelectorAll('.fi-btn, button, [role="button"]');
        
        buttons.forEach(button => {
            if (button.dataset.animationEnhanced) return;
            button.dataset.animationEnhanced = 'true';
            
            // Add transition if missing
            if (!button.style.transition) {
                button.style.transition = `all ${config.durations.quick}ms ${config.easings.smooth}`;
            }
            
            // Hover handlers
            button.addEventListener('mouseenter', function() {
                if (this.disabled || this.getAttribute('disabled') !== null) return;
                
                if (!prefersReducedMotion) {
                    this.style.transform = 'translateY(-1px)';
                    this.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.15)';
                }
            });
            
            button.addEventListener('mouseleave', function() {
                this.style.transform = '';
                this.style.boxShadow = '';
            });
            
            // Active state
            button.addEventListener('mousedown', function() {
                if (this.disabled || this.getAttribute('disabled') !== null) return;
                
                if (!prefersReducedMotion) {
                    this.style.transform = 'translateY(0px) scale(0.98)';
                }
            });
            
            button.addEventListener('mouseup', function() {
                if (this.matches(':hover') && !prefersReducedMotion) {
                    this.style.transform = 'translateY(-1px)';
                } else {
                    this.style.transform = '';
                }
            });
        });
    }
    
    function fixLoadingSpinners() {
        // Enhanced spinning animation for loading indicators
        const spinners = document.querySelectorAll('.fi-spinner, .fi-loading-indicator, [class*="spinner"]');
        
        spinners.forEach(spinner => {
            if (spinner.dataset.animationEnhanced) return;
            spinner.dataset.animationEnhanced = 'true';
            
            // Add enhanced spin animation
            spinner.style.animation = prefersReducedMotion 
                ? 'none' 
                : 'delightfulSpin 1.2s cubic-bezier(0.4, 0, 0.2, 1) infinite';
        });
        
        // Watch for new spinners
        const spinnerObserver = new MutationObserver(mutations => {
            mutations.forEach(mutation => {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === 1) {
                        const newSpinners = node.querySelectorAll('.fi-spinner, .fi-loading-indicator, [class*="spinner"]');
                        newSpinners.forEach(spinner => {
                            if (!spinner.dataset.animationEnhanced) {
                                spinner.dataset.animationEnhanced = 'true';
                                spinner.style.animation = prefersReducedMotion 
                                    ? 'none' 
                                    : 'delightfulSpin 1.2s cubic-bezier(0.4, 0, 0.2, 1) infinite';
                            }
                        });
                    }
                });
            });
        });
        
        spinnerObserver.observe(document.body, { childList: true, subtree: true });
    }
    
    function fixDropdownAnimations() {
        // Enhance dropdown animations
        const dropdowns = document.querySelectorAll('.fi-dropdown, [x-data*="dropdown"]');
        
        dropdowns.forEach(dropdown => {
            if (dropdown.dataset.animationEnhanced) return;
            dropdown.dataset.animationEnhanced = 'true';
            
            const panel = dropdown.querySelector('.fi-dropdown-panel, [x-ref="panel"]');
            if (!panel) return;
            
            // Add transition styles
            panel.style.transition = `all ${config.durations.quick}ms ${config.easings.smooth}`;
            
            // Watch for visibility changes
            const observer = new MutationObserver(mutations => {
                mutations.forEach(mutation => {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        const target = mutation.target;
                        const isVisible = !target.classList.contains('hidden') && 
                                         !target.classList.contains('invisible') &&
                                         target.classList.contains('opacity-100');
                        
                        if (isVisible && !prefersReducedMotion) {
                            // Animate in
                            target.style.transform = 'translateY(-10px) scale(0.95)';
                            target.style.opacity = '0';
                            
                            requestAnimationFrame(() => {
                                target.style.transform = 'translateY(0) scale(1)';
                                target.style.opacity = '1';
                            });
                        }
                    }
                });
            });
            
            observer.observe(panel, { attributes: true, attributeFilter: ['class'] });
        });
    }
    
    function fixModalAnimations() {
        // Enhanced modal entrance animations
        const modals = document.querySelectorAll('.fi-modal, [role="dialog"]');
        
        modals.forEach(modal => {
            if (modal.dataset.animationEnhanced) return;
            modal.dataset.animationEnhanced = 'true';
            
            const modalWindow = modal.querySelector('.fi-modal-window');
            if (!modalWindow) return;
            
            modalWindow.style.transition = `all ${config.durations.normal}ms ${config.easings.smooth}`;
            
            // Watch for modal visibility
            const observer = new MutationObserver(mutations => {
                mutations.forEach(mutation => {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        const isVisible = !modal.classList.contains('hidden');
                        
                        if (isVisible && !prefersReducedMotion) {
                            // Animate in
                            modalWindow.style.transform = 'translateY(20px) scale(0.95)';
                            modalWindow.style.opacity = '0';
                            
                            requestAnimationFrame(() => {
                                modalWindow.style.transform = 'translateY(0) scale(1)';
                                modalWindow.style.opacity = '1';
                            });
                        }
                    }
                });
            });
            
            observer.observe(modal, { attributes: true, attributeFilter: ['class'] });
        });
    }
    
    function fixTooltipAnimations() {
        // Enhanced tooltip animations
        const tooltips = document.querySelectorAll('.fi-tooltip, [data-tooltip]');
        
        tooltips.forEach(tooltip => {
            if (tooltip.dataset.animationEnhanced) return;
            tooltip.dataset.animationEnhanced = 'true';
            
            tooltip.style.transition = `all ${config.durations.quick}ms ${config.easings.smooth}`;
            
            // Add entrance animation
            if (!prefersReducedMotion) {
                tooltip.style.animation = 'tooltipFadeIn 200ms ease-out';
            }
        });
    }
    
    function fixTableRowHovers() {
        // Enhanced table row hover effects
        const tableRows = document.querySelectorAll('.fi-ta-row');
        
        tableRows.forEach(row => {
            if (row.dataset.animationEnhanced) return;
            row.dataset.animationEnhanced = 'true';
            
            row.style.transition = `all ${config.durations.quick}ms ${config.easings.smooth}`;
            
            row.addEventListener('mouseenter', function() {
                if (!prefersReducedMotion) {
                    this.style.transform = 'translateY(-1px)';
                    this.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.1)';
                }
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.transform = '';
                this.style.boxShadow = '';
            });
        });
    }
    
    function fixFormFeedback() {
        // Watch for success/error states and animate them
        const feedbackObserver = new MutationObserver(mutations => {
            mutations.forEach(mutation => {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === 1) {
                        // Success notifications
                        if (node.classList && (
                            node.classList.contains('fi-color-success') ||
                            node.classList.contains('fi-notification') ||
                            node.classList.contains('success')
                        )) {
                            if (!prefersReducedMotion) {
                                node.style.animation = 'successPulse 600ms ease-out';
                            }
                        }
                        
                        // Error notifications
                        if (node.classList && (
                            node.classList.contains('fi-color-danger') ||
                            node.classList.contains('error') ||
                            node.classList.contains('fi-error')
                        )) {
                            if (!prefersReducedMotion) {
                                node.style.animation = 'errorShake 500ms ease-in-out';
                            }
                        }
                    }
                });
            });
        });
        
        feedbackObserver.observe(document.body, { childList: true, subtree: true });
    }
    
    function addGlobalStyles() {
        // Add keyframe animations if they don't exist
        const existingStyles = document.querySelector('#animation-enhancer-styles');
        if (existingStyles) return;
        
        const styles = document.createElement('style');
        styles.id = 'animation-enhancer-styles';
        styles.textContent = `
        @keyframes delightfulSpin {
            0% { 
                transform: rotate(0deg) scale(1);
                opacity: 0.8;
            }
            50% { 
                transform: rotate(180deg) scale(1.1);
                opacity: 1;
            }
            100% { 
                transform: rotate(360deg) scale(1);
                opacity: 0.8;
            }
        }
        
        @keyframes tooltipFadeIn {
            from {
                opacity: 0;
                transform: translateY(-4px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes successPulse {
            0% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
            }
            50% {
                transform: scale(1.05);
                box-shadow: 0 0 0 10px rgba(34, 197, 94, 0);
            }
            100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(34, 197, 94, 0);
            }
        }
        
        @keyframes errorShake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-2px); }
            20%, 40%, 60%, 80% { transform: translateX(2px); }
        }
        
        /* Hardware acceleration for better performance */
        .fi-btn,
        .fi-dropdown-panel,
        .fi-modal-window,
        .fi-ta-row,
        button,
        [role="button"] {
            will-change: transform, opacity;
            backface-visibility: hidden;
            transform: translateZ(0);
        }
        
        /* Respect reduced motion preferences */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
        `;
        
        document.head.appendChild(styles);
    }
    
    // Auto-initialize when script loads
    init();
    
    // Export for manual initialization if needed
    window.AnimationEnhancer = {
        init: init,
        enhance: enhance,
        config: config
    };
    
})();