/**
 * Enhanced Animation Manager for AskProAI Admin Panel
 * Provides smooth, delightful animations for all UI interactions
 * Created: 2025-08-01
 */

class EnhancedAnimationManager {
    constructor() {
        this.activeAnimations = new Map();
        this.observers = new Map();
        this.initialized = false;
        
        // Animation configurations
        this.animations = {
            // Button animations
            buttonHover: {
                duration: 200,
                easing: 'cubic-bezier(0.4, 0, 0.2, 1)',
                properties: {
                    transform: 'translateY(-1px)',
                    boxShadow: '0 4px 12px rgba(0, 0, 0, 0.15)'
                }
            },
            
            buttonPress: {
                duration: 100,
                easing: 'cubic-bezier(0.4, 0, 0.2, 1)',
                properties: {
                    transform: 'translateY(0px) scale(0.98)'
                }
            },
            
            // Dropdown animations
            dropdownShow: {
                duration: 200,
                easing: 'cubic-bezier(0.4, 0, 0.2, 1)',
                keyframes: [
                    { opacity: 0, transform: 'translateY(-10px) scale(0.95)' },
                    { opacity: 1, transform: 'translateY(0) scale(1)' }
                ]
            },
            
            dropdownHide: {
                duration: 150,
                easing: 'cubic-bezier(0.4, 0, 0.2, 1)',
                keyframes: [
                    { opacity: 1, transform: 'translateY(0) scale(1)' },
                    { opacity: 0, transform: 'translateY(-5px) scale(0.98)' }
                ]
            },
            
            // Modal animations
            modalShow: {
                duration: 300,
                easing: 'cubic-bezier(0.4, 0, 0.2, 1)',
                keyframes: [
                    { opacity: 0, transform: 'translateY(20px) scale(0.95)' },
                    { opacity: 1, transform: 'translateY(0) scale(1)' }
                ]
            },
            
            modalHide: {
                duration: 200,
                easing: 'cubic-bezier(0.4, 0, 0.2, 1)',
                keyframes: [
                    { opacity: 1, transform: 'translateY(0) scale(1)' },
                    { opacity: 0, transform: 'translateY(10px) scale(0.98)' }
                ]
            },
            
            // Loading animations
            spin: {
                duration: 1200,
                easing: 'cubic-bezier(0.4, 0, 0.2, 1)',
                keyframes: [
                    { transform: 'rotate(0deg) scale(1)', opacity: 0.8 },
                    { transform: 'rotate(180deg) scale(1.1)', opacity: 1 },
                    { transform: 'rotate(360deg) scale(1)', opacity: 0.8 }
                ],
                iterations: Infinity
            },
            
            // Success/Error animations
            successPulse: {
                duration: 600,
                easing: 'ease-out',
                keyframes: [
                    { transform: 'scale(1)', boxShadow: '0 0 0 0 rgba(34, 197, 94, 0.7)' },
                    { transform: 'scale(1.05)', boxShadow: '0 0 0 10px rgba(34, 197, 94, 0)' },
                    { transform: 'scale(1)', boxShadow: '0 0 0 0 rgba(34, 197, 94, 0)' }
                ]
            },
            
            errorShake: {
                duration: 500,
                easing: 'ease-in-out',
                keyframes: [
                    { transform: 'translateX(0)' },
                    { transform: 'translateX(-2px)' },
                    { transform: 'translateX(2px)' },
                    { transform: 'translateX(-2px)' },
                    { transform: 'translateX(2px)' },
                    { transform: 'translateX(0)' }
                ]
            }
        };
        
        this.init();
    }
    
    init() {
        if (this.initialized) return;
        this.initialized = true;
        
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setupAnimations());
        } else {
            this.setupAnimations();
        }
    }
    
    setupAnimations() {
        this.setupButtonAnimations();
        this.setupDropdownAnimations();
        this.setupModalAnimations();
        this.setupLoadingAnimations();
        this.setupFeedbackAnimations();
        this.setupPageTransitions();
        this.setupObservers();
        
        console.log('EnhancedAnimationManager initialized');
    }
    
    setupButtonAnimations() {
        // Enhanced button hover effects
        this.addEventListeners('mouseenter', '.fi-btn, button, [role="button"]', (element) => {
            if (element.disabled || element.classList.contains('disabled')) return;
            
            this.animate(element, 'buttonHover');
        });
        
        this.addEventListeners('mouseleave', '.fi-btn, button, [role="button"]', (element) => {
            this.cancelAnimation(element, 'buttonHover');
            element.style.transform = '';
            element.style.boxShadow = '';
        });
        
        // Button press effects
        this.addEventListeners('mousedown', '.fi-btn, button, [role="button"]', (element) => {
            if (element.disabled || element.classList.contains('disabled')) return;
            
            this.animate(element, 'buttonPress');
        });
        
        this.addEventListeners('mouseup', '.fi-btn, button, [role="button"]', (element) => {
            this.cancelAnimation(element, 'buttonPress');
            // Return to hover state if still hovering
            if (element.matches(':hover')) {
                this.animate(element, 'buttonHover');
            }
        });
    }
    
    setupDropdownAnimations() {
        // Observe dropdown state changes
        this.observeAttribute('[x-data*="dropdown"], .fi-dropdown', 'class', (element, oldValue, newValue) => {
            const panel = element.querySelector('.fi-dropdown-panel, [x-ref="panel"]');
            if (!panel) return;
            
            // Check if dropdown is opening or closing
            const wasHidden = oldValue && (oldValue.includes('hidden') || oldValue.includes('invisible'));
            const isHidden = newValue && (newValue.includes('hidden') || newValue.includes('invisible'));
            
            if (wasHidden && !isHidden) {
                // Opening
                this.animate(panel, 'dropdownShow');
            } else if (!wasHidden && isHidden) {
                // Closing
                this.animate(panel, 'dropdownHide');
            }
        });
    }
    
    setupModalAnimations() {
        // Modal entrance/exit animations
        this.observeAttribute('.fi-modal, [role="dialog"]', 'class', (element, oldValue, newValue) => {
            const wasHidden = oldValue && (oldValue.includes('hidden') || oldValue.includes('invisible'));
            const isHidden = newValue && (newValue.includes('hidden') || newValue.includes('invisible'));
            
            if (wasHidden && !isHidden) {
                // Opening
                this.animate(element, 'modalShow');
            } else if (!wasHidden && isHidden) {
                // Closing
                this.animate(element, 'modalHide');
            }
        });
    }
    
    setupLoadingAnimations() {
        // Enhanced loading spinners
        this.addMutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1) {
                        // Check for loading spinners
                        const spinners = node.querySelectorAll('.fi-spinner, .fi-loading-indicator, [class*="spinner"], [class*="loading"]');
                        spinners.forEach(spinner => {
                            this.animate(spinner, 'spin');
                        });
                    }
                });
            });
        });
    }
    
    setupFeedbackAnimations() {
        // Success/error feedback animations
        this.addMutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1) {
                        // Success states
                        if (node.classList && (node.classList.contains('fi-color-success') || node.classList.contains('success'))) {
                            this.animate(node, 'successPulse');
                        }
                        
                        // Error states
                        if (node.classList && (node.classList.contains('fi-color-danger') || node.classList.contains('error'))) {
                            this.animate(node, 'errorShake');
                        }
                    }
                });
            });
        });
    }
    
    setupPageTransitions() {
        // Smooth page content fade-ins
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animation = 'pageContentFadeIn 0.3s ease-out';
                }
            });
        });
        
        document.querySelectorAll('.fi-page, .fi-main').forEach(element => {
            observer.observe(element);
        });
        
        this.observers.set('pageTransition', observer);
    }
    
    setupObservers() {
        // Setup mutation observer for dynamic content
        this.addMutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1) {
                        // Re-initialize animations for new elements
                        this.initializeNewElement(node);
                    }
                });
            });
        });
    }
    
    initializeNewElement(element) {
        // Apply hardware acceleration to animated elements
        if (element.matches('.fi-btn, button, [role="button"], .fi-dropdown, .fi-modal')) {
            element.style.willChange = 'transform, opacity';
            element.style.backfaceVisibility = 'hidden';
            element.style.transform = 'translateZ(0)';
        }
        
        // Initialize child elements
        element.querySelectorAll('.fi-btn, button, [role="button"], .fi-dropdown, .fi-modal').forEach(child => {
            child.style.willChange = 'transform, opacity';
            child.style.backfaceVisibility = 'hidden';
            child.style.transform = 'translateZ(0)';
        });
    }
    
    animate(element, animationType, options = {}) {
        if (!element || !this.animations[animationType]) return;
        
        const config = { ...this.animations[animationType], ...options };
        
        // Cancel any existing animation on this element
        this.cancelAnimation(element, animationType);
        
        let animation;
        
        if (config.keyframes) {
            // Use keyframe animation
            animation = element.animate(config.keyframes, {
                duration: config.duration,
                easing: config.easing,
                iterations: config.iterations || 1,
                fill: 'forwards'
            });
        } else if (config.properties) {
            // Use property-based animation
            const keyframes = [
                {},
                config.properties
            ];
            
            animation = element.animate(keyframes, {
                duration: config.duration,
                easing: config.easing,
                fill: 'forwards'
            });
        }
        
        if (animation) {
            // Store animation reference
            if (!this.activeAnimations.has(element)) {
                this.activeAnimations.set(element, new Map());
            }
            this.activeAnimations.get(element).set(animationType, animation);
            
            // Clean up when animation finishes
            animation.addEventListener('finish', () => {
                if (this.activeAnimations.has(element)) {
                    this.activeAnimations.get(element).delete(animationType);
                }
            });
        }
        
        return animation;
    }
    
    cancelAnimation(element, animationType) {
        if (!this.activeAnimations.has(element)) return;
        
        const elementAnimations = this.activeAnimations.get(element);
        const animation = elementAnimations.get(animationType);
        
        if (animation) {
            animation.cancel();
            elementAnimations.delete(animationType);
        }
    }
    
    addEventListeners(event, selector, callback) {
        document.addEventListener(event, (e) => {
            if (e.target.matches(selector)) {
                callback(e.target, e);
            }
        });
    }
    
    observeAttribute(selector, attribute, callback) {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === attribute) {
                    callback(mutation.target, mutation.oldValue, mutation.target.getAttribute(attribute));
                }
            });
        });
        
        document.querySelectorAll(selector).forEach(element => {
            observer.observe(element, {
                attributes: true,
                attributeOldValue: true,
                attributeFilter: [attribute]
            });
        });
        
        this.observers.set(`${selector}-${attribute}`, observer);
    }
    
    addMutationObserver(callback) {
        const observer = new MutationObserver(callback);
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
        
        this.observers.set(`mutation-${Date.now()}`, observer);
        return observer;
    }
    
    // Public API methods
    showSuccess(element, message) {
        this.animate(element, 'successPulse');
    }
    
    showError(element, message) {
        this.animate(element, 'errorShake');
    }
    
    showLoading(element) {
        this.animate(element, 'spin');
    }
    
    hideLoading(element) {
        this.cancelAnimation(element, 'spin');
    }
    
    destroy() {
        // Cancel all active animations
        this.activeAnimations.forEach((elementAnimations) => {
            elementAnimations.forEach((animation) => {
                animation.cancel();
            });
        });
        
        // Disconnect all observers
        this.observers.forEach((observer) => {
            observer.disconnect();
        });
        
        // Clear maps
        this.activeAnimations.clear();
        this.observers.clear();
        
        this.initialized = false;
    }
}

// Initialize and make globally available
window.enhancedAnimationManager = new EnhancedAnimationManager();

// Export for modules
export default EnhancedAnimationManager;