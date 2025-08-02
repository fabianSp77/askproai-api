// Login Button Fix for Filament Admin Panel
(function() {
    'use strict';
    
    console.log('[LoginButtonFix] Initializing...');
    
    function fixLoginButton() {
        // Find all submit buttons on login page
        const submitButtons = document.querySelectorAll('button[type="submit"], .fi-btn-primary, form[wire\\:submit="authenticate"] button');
        
        submitButtons.forEach(button => {
            if (!button.dataset.fixApplied) {
                // Ensure button is visible
                button.style.visibility = 'visible';
                button.style.opacity = '1';
                button.style.pointerEvents = 'auto';
                
                // Fix button colors
                button.style.backgroundColor = '#FBBf24';
                button.style.color = '#000000';
                button.style.border = '1px solid #D97706';
                
                // Ensure button is clickable
                button.style.position = 'relative';
                button.style.zIndex = '100';
                
                // Find and fix button label
                const label = button.querySelector('.fi-btn-label, span');
                if (label) {
                    label.style.color = '#000000';
                    label.style.opacity = '1';
                }
                
                // Remove any click prevention from parent fixes
                const existingHandler = button.onclick;
                button.onclick = function(e) {
                    console.log('[LoginButtonFix] Button clicked');
                    if (existingHandler) {
                        existingHandler.call(this, e);
                    }
                };
                
                // Ensure form submission works
                const form = button.closest('form');
                if (form && form.hasAttribute('wire:submit')) {
                    console.log('[LoginButtonFix] Found Livewire form');
                    
                    // Remove any interference from other scripts
                    button.addEventListener('click', function(e) {
                        console.log('[LoginButtonFix] Allowing form submission');
                        // Don't prevent default - let Livewire handle it
                    }, true);
                }
                
                button.dataset.fixApplied = 'true';
                console.log('[LoginButtonFix] Fixed button:', button);
            }
        });
    }
    
    // Initial fix
    fixLoginButton();
    
    // Fix after DOM changes
    const observer = new MutationObserver(() => {
        fixLoginButton();
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['style', 'class']
    });
    
    // Fix on various events
    document.addEventListener('DOMContentLoaded', fixLoginButton);
    document.addEventListener('livewire:load', fixLoginButton);
    document.addEventListener('livewire:update', fixLoginButton);
    document.addEventListener('alpine:init', fixLoginButton);
    
    // Periodic check as fallback
    setInterval(fixLoginButton, 1000);
    
    console.log('[LoginButtonFix] Initialized successfully');
})();