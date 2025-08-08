/**
 * Emergency Fix for Login Input Fields
 * Fixes the issue where users cannot enter credentials
 */

// console.log('🚨 Login Input Emergency Fix Loading...');

(function() {
    'use strict';
    
    function fixLoginInputs() {
        // console.log('Fixing login input fields...');
        
        // Find all input fields on login page
        const emailInputs = document.querySelectorAll('input[type="email"], input[name="email"], input[id*="email"]');
        const passwordInputs = document.querySelectorAll('input[type="password"], input[name="password"], input[id*="password"]');
        const allInputs = document.querySelectorAll('input, textarea, select');
        
        // Fix all inputs
        allInputs.forEach(input => {
            // Remove any blocking styles
            input.style.pointerEvents = 'auto';
            input.style.userSelect = 'auto';
            input.style.webkitUserSelect = 'auto';
            input.style.mozUserSelect = 'auto';
            input.style.msUserSelect = 'auto';
            input.style.opacity = '1';
            input.style.visibility = 'visible';
            input.style.position = 'relative';
            input.style.zIndex = 'auto';
            
            // Remove readonly if not intended
            if (input.hasAttribute('readonly') && !input.dataset.intentionallyReadonly) {
                input.removeAttribute('readonly');
            }
            
            // Remove disabled if not intended
            if (input.hasAttribute('disabled') && !input.dataset.intentionallyDisabled) {
                input.removeAttribute('disabled');
            }
            
            // Ensure input is focusable
            if (!input.hasAttribute('tabindex') || input.tabIndex < 0) {
                input.setAttribute('tabindex', '0');
            }
            
            // Remove any event blockers
            const events = ['click', 'focus', 'keydown', 'keyup', 'keypress', 'input', 'change'];
            events.forEach(eventType => {
                input.removeEventListener(eventType, blockEvent, true);
            });
        });
        
        // Fix any overlapping elements
        const overlays = document.querySelectorAll('.fi-modal-overlay, .fixed.inset-0, [class*="overlay"]');
        overlays.forEach(overlay => {
            if (overlay.style.zIndex > 100) {
                overlay.style.pointerEvents = 'none';
            }
        });
        
        // Fix form wrapper
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.style.pointerEvents = 'auto';
            form.style.position = 'relative';
            form.style.zIndex = '10';
        });
        
        // Fix specific Filament wrappers
        const wrappers = document.querySelectorAll('.fi-input-wrapper, .fi-fo-field-wrapper');
        wrappers.forEach(wrapper => {
            wrapper.style.pointerEvents = 'auto';
            wrapper.style.position = 'relative';
        });
        
        // console.log(`Fixed ${allInputs.length} input fields`);
        
        // Focus first email input if found
        if (emailInputs.length > 0) {
            setTimeout(() => {
                emailInputs[0].focus();
                // console.log('Focused email input');
            }, 100);
        }
    }
    
    function blockEvent(e) {
        e.stopPropagation();
        e.preventDefault();
    }
    
    // Run immediately
    fixLoginInputs();
    
    // Run after DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fixLoginInputs);
    }
    
    // Run after a short delay to catch dynamic content
    setTimeout(fixLoginInputs, 500);
    setTimeout(fixLoginInputs, 1000);
    
    // Monitor for changes
    const observer = new MutationObserver(() => {
        fixLoginInputs();
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['style', 'disabled', 'readonly']
    });
    
    // Expose for manual trigger
    window.fixLoginInputs = fixLoginInputs;
    
    // console.log('✅ Login Input Emergency Fix active');
    // console.log('💡 Run fixLoginInputs() manually if needed');
})();