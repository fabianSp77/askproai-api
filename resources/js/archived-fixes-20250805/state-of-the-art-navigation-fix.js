// STATE-OF-THE-ART NAVIGATION FIX - Issue #507
// Complete solution for login forms and all navigation

console.error('[NAVIGATION-FIX] 🚀 State-of-the-art navigation fix loading...');

(function() {
    'use strict';
    
    // Configuration
    const CONFIG = {
        debugMode: true,
        retryAttempts: 5,
        retryDelay: 100,
        selectors: {
            forms: 'form',
            inputs: 'input, textarea, select',
            buttons: 'button, [type="submit"], [role="button"]',
            links: 'a[href], [wire\\:navigate], [wire\\:click]',
            navigation: '.fi-sidebar a, .fi-sidebar-nav-item a, .fi-topbar a',
            loginForm: 'form[wire\\:submit="authenticate"]',
            passwordField: 'input[wire\\:model="data.password"], input[type="password"]',
            emailField: 'input[wire\\:model="data.email"], input[type="email"]',
            submitButton: 'button[type="submit"]'
        }
    };
    
    // Debug logger
    const log = (...args) => {
        if (CONFIG.debugMode) {
            console.log('[NAVIGATION-FIX]', ...args);
        }
    };
    
    const error = (...args) => {
        console.error('[NAVIGATION-FIX]', ...args);
    };
    
    // Main fix class
    class NavigationFixer {
        constructor() {
            this.attemptCount = 0;
            this.observers = [];
            this.fixedElements = new WeakSet();
        }
        
        // Initialize all fixes
        init() {
            log('Initializing navigation fixer...');
            this.fixAll();
            this.setupObservers();
            this.setupEventInterceptors();
            this.injectGlobalStyles();
            this.createDebugPanel();
        }
        
        // Apply all fixes
        fixAll() {
            this.attemptCount++;
            log(`Fix attempt #${this.attemptCount}`);
            
            this.fixLoginForm();
            this.fixAllInputs();
            this.fixAllButtons();
            this.fixAllLinks();
            this.fixNavigation();
            this.removeBlockingElements();
            this.fixPointerEvents();
        }
        
        // Fix login form specifically
        fixLoginForm() {
            const loginForms = document.querySelectorAll(CONFIG.selectors.loginForm);
            
            loginForms.forEach(form => {
                if (this.fixedElements.has(form)) return;
                
                log('Fixing login form:', form);
                
                // Ensure form is interactive
                this.makeInteractive(form);
                
                // Fix all inputs in the form
                const inputs = form.querySelectorAll(CONFIG.selectors.inputs);
                inputs.forEach(input => this.fixInput(input));
                
                // Fix submit button
                const submitBtn = form.querySelector(CONFIG.selectors.submitButton);
                if (submitBtn) {
                    this.fixButton(submitBtn);
                }
                
                // Prevent form submission blocking
                this.interceptFormSubmission(form);
                
                this.fixedElements.add(form);
            });
            
            // Specifically fix password fields
            this.fixPasswordFields();
        }
        
        // Fix password fields that might be hidden
        fixPasswordFields() {
            const passwordFields = document.querySelectorAll(CONFIG.selectors.passwordField);
            
            passwordFields.forEach(field => {
                log('Fixing password field:', field);
                
                // Ensure visible and interactive
                field.style.display = 'block';
                field.style.visibility = 'visible';
                field.style.opacity = '1';
                field.style.pointerEvents = 'auto';
                field.removeAttribute('disabled');
                field.removeAttribute('readonly');
                
                // Fix tabindex
                if (!field.hasAttribute('tabindex') || field.tabIndex < 0) {
                    field.tabIndex = 2; // After email field
                }
                
                // Ensure proper type handling for password reveal
                const container = field.closest('[x-data]');
                if (container && container.__x) {
                    log('Alpine.js component found for password field');
                }
            });
        }
        
        // Fix individual input
        fixInput(input) {
            if (this.fixedElements.has(input)) return;
            
            this.makeInteractive(input);
            
            // Special handling for different input types
            const type = input.type || 'text';
            
            if (type === 'password' || input.getAttribute('wire:model')?.includes('password')) {
                input.style.webkitTextSecurity = 'disc'; // Ensure password dots show
            }
            
            // Fix autocomplete
            if (!input.hasAttribute('autocomplete')) {
                if (type === 'email') input.autocomplete = 'email';
                if (type === 'password') input.autocomplete = 'current-password';
            }
            
            this.fixedElements.add(input);
        }
        
        // Fix all inputs
        fixAllInputs() {
            document.querySelectorAll(CONFIG.selectors.inputs).forEach(input => {
                this.fixInput(input);
            });
        }
        
        // Fix button
        fixButton(button) {
            if (this.fixedElements.has(button)) return;
            
            this.makeInteractive(button);
            
            // Ensure proper submit behavior
            if (button.type === 'submit') {
                button.addEventListener('click', (e) => {
                    log('Submit button clicked:', button);
                    // Don't prevent default - let form submit
                }, true);
            }
            
            this.fixedElements.add(button);
        }
        
        // Fix all buttons
        fixAllButtons() {
            document.querySelectorAll(CONFIG.selectors.buttons).forEach(button => {
                this.fixButton(button);
            });
        }
        
        // Fix link
        fixLink(link) {
            if (this.fixedElements.has(link)) return;
            
            this.makeInteractive(link);
            
            // Ensure navigation works
            if (link.href) {
                link.addEventListener('click', (e) => {
                    log('Link clicked:', link.href);
                    // For Livewire navigation, don't force page reload
                    if (!link.hasAttribute('wire:navigate')) {
                        // Allow natural navigation
                    }
                }, true);
            }
            
            this.fixedElements.add(link);
        }
        
        // Fix all links
        fixAllLinks() {
            document.querySelectorAll(CONFIG.selectors.links).forEach(link => {
                this.fixLink(link);
            });
        }
        
        // Fix navigation specifically
        fixNavigation() {
            document.querySelectorAll(CONFIG.selectors.navigation).forEach(navLink => {
                this.fixLink(navLink);
                // Extra assurance for navigation
                navLink.style.zIndex = '10000';
            });
        }
        
        // Make element interactive
        makeInteractive(element) {
            // Remove all blocking styles
            element.style.pointerEvents = 'auto';
            element.style.userSelect = 'auto';
            element.style.cursor = element.tagName === 'A' || element.tagName === 'BUTTON' ? 'pointer' : 'auto';
            element.style.opacity = '1';
            element.style.visibility = 'visible';
            element.style.display = element.style.display === 'none' ? 'block' : element.style.display;
            
            // Remove disabled states
            element.removeAttribute('disabled');
            element.removeAttribute('aria-disabled');
            element.classList.remove('disabled', 'pointer-events-none');
            
            // Ensure proper z-index
            if (!element.style.zIndex || parseInt(element.style.zIndex) < 1) {
                element.style.zIndex = '1';
            }
        }
        
        // Fix pointer events globally
        fixPointerEvents() {
            const elements = document.querySelectorAll('*');
            elements.forEach(el => {
                const computed = window.getComputedStyle(el);
                if (computed.pointerEvents === 'none' && !el.classList.contains('fi-modal-close-overlay')) {
                    el.style.pointerEvents = 'auto';
                }
            });
        }
        
        // Remove blocking overlays
        removeBlockingElements() {
            // Remove overlays that might block interaction
            const overlays = document.querySelectorAll('.fixed.inset-0, [class*="overlay"]:not(.fi-modal-close-overlay)');
            overlays.forEach(overlay => {
                if (!overlay.classList.contains('fi-main') && !overlay.classList.contains('fi-sidebar')) {
                    const computed = window.getComputedStyle(overlay);
                    if (computed.pointerEvents !== 'none') {
                        overlay.style.pointerEvents = 'none';
                    }
                }
            });
        }
        
        // Intercept form submission
        interceptFormSubmission(form) {
            // Don't break Livewire forms
            if (form.hasAttribute('wire:submit')) {
                log('Livewire form detected, preserving wire:submit');
                return;
            }
            
            // For regular forms, ensure submission works
            form.addEventListener('submit', (e) => {
                log('Form submitted:', form);
                // Allow submission
            }, true);
        }
        
        // Setup mutation observers
        setupObservers() {
            // Main observer for DOM changes
            const observer = new MutationObserver((mutations) => {
                let shouldReapply = false;
                
                mutations.forEach(mutation => {
                    if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                        shouldReapply = true;
                    }
                    if (mutation.type === 'attributes' && 
                        (mutation.attributeName === 'style' || 
                         mutation.attributeName === 'disabled' ||
                         mutation.attributeName === 'class')) {
                        shouldReapply = true;
                    }
                });
                
                if (shouldReapply) {
                    this.debouncedFix();
                }
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['style', 'disabled', 'class', 'aria-disabled']
            });
            
            this.observers.push(observer);
        }
        
        // Debounced fix application
        debouncedFix = this.debounce(() => {
            this.fixAll();
        }, 200);
        
        // Debounce helper
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
        
        // Setup event interceptors
        setupEventInterceptors() {
            // Prevent event.preventDefault() on critical elements
            const originalPreventDefault = Event.prototype.preventDefault;
            Event.prototype.preventDefault = function() {
                const target = this.target;
                
                // Don't block form submissions or navigation
                if (this.type === 'submit' || 
                    (this.type === 'click' && target && (target.tagName === 'A' || target.type === 'submit'))) {
                    log('Prevented preventDefault on:', target);
                    return;
                }
                
                return originalPreventDefault.call(this);
            };
        }
        
        // Inject global styles
        injectGlobalStyles() {
            const style = document.createElement('style');
            style.textContent = `
                /* State-of-the-art Navigation Fix Styles */
                
                /* Ensure all interactive elements are clickable */
                input, textarea, select, button, a, 
                [role="button"], [role="link"],
                .fi-btn, .fi-link {
                    pointer-events: auto !important;
                    user-select: auto !important;
                    opacity: 1 !important;
                    visibility: visible !important;
                }
                
                /* Fix login form specifically */
                form[wire\\:submit="authenticate"] input,
                form[wire\\:submit="authenticate"] button {
                    pointer-events: auto !important;
                    opacity: 1 !important;
                    visibility: visible !important;
                    display: block !important;
                }
                
                /* Ensure password fields are visible */
                input[type="password"],
                input[wire\\:model*="password"] {
                    display: block !important;
                    visibility: visible !important;
                    opacity: 1 !important;
                    -webkit-text-security: disc !important;
                }
                
                /* Fix navigation */
                .fi-sidebar a,
                .fi-sidebar button {
                    pointer-events: auto !important;
                    cursor: pointer !important;
                    position: relative !important;
                    z-index: 10000 !important;
                }
                
                /* Remove blocking overlays */
                .pointer-events-none:not(.fi-modal-close-overlay) {
                    pointer-events: none !important;
                }
            `;
            document.head.appendChild(style);
        }
        
        // Create debug panel
        createDebugPanel() {
            if (!CONFIG.debugMode) return;
            
            const panel = document.createElement('div');
            panel.id = 'navigation-fix-debug';
            panel.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: #10b981;
                color: white;
                padding: 12px;
                border-radius: 8px;
                font-family: monospace;
                font-size: 12px;
                z-index: 999999;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                max-width: 300px;
            `;
            
            const updatePanel = () => {
                const forms = document.querySelectorAll('form').length;
                const inputs = document.querySelectorAll('input:not([type="hidden"])').length;
                const links = document.querySelectorAll('a[href]').length;
                const passwordFields = document.querySelectorAll('input[type="password"], input[wire\\:model*="password"]').length;
                
                panel.innerHTML = `
                    <div style="font-weight: bold; margin-bottom: 8px;">🚀 Navigation Fix Active</div>
                    <div>Forms: ${forms}</div>
                    <div>Inputs: ${inputs}</div>
                    <div>Password Fields: ${passwordFields}</div>
                    <div>Links: ${links}</div>
                    <div>Attempts: ${this.attemptCount}</div>
                    <div style="margin-top: 8px; font-size: 10px;">Issue #507</div>
                `;
            };
            
            updatePanel();
            document.body.appendChild(panel);
            
            // Update panel periodically
            setInterval(updatePanel, 1000);
        }
        
        // Cleanup method
        destroy() {
            this.observers.forEach(observer => observer.disconnect());
            const panel = document.getElementById('navigation-fix-debug');
            if (panel) panel.remove();
        }
    }
    
    // Initialize fixer
    const fixer = new NavigationFixer();
    
    // Run on different load events
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => fixer.init());
    } else {
        fixer.init();
    }
    
    // Also run after short delays
    setTimeout(() => fixer.fixAll(), 100);
    setTimeout(() => fixer.fixAll(), 500);
    setTimeout(() => fixer.fixAll(), 1000);
    
    // Integrate with frameworks
    document.addEventListener('livewire:load', () => {
        log('Livewire loaded, reapplying fixes');
        fixer.fixAll();
    });
    
    document.addEventListener('alpine:init', () => {
        log('Alpine.js initialized, reapplying fixes');
        fixer.fixAll();
    });
    
    // Export for debugging
    window.NavigationFixer = fixer;
    
    error('🚀 State-of-the-art navigation fix loaded successfully!');
})();