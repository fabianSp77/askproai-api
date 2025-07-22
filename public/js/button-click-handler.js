/**
 * Button Click Handler
 * Stellt sicher, dass alle Buttons korrekt funktionieren
 */
(function() {
    'use strict';
    
    console.log('🔧 Button Click Handler Active');
    
    // Ensure Livewire button clicks work properly
    function ensureButtonClicks() {
        // Find all buttons with wire:click attributes
        const livewireButtons = document.querySelectorAll('[wire\\:click]');
        
        livewireButtons.forEach(button => {
            // Remove any duplicate event listeners
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
            
            // Log clicks for debugging
            newButton.addEventListener('click', function(e) {
                console.log('Button clicked:', {
                    text: this.textContent.trim(),
                    wireClick: this.getAttribute('wire:click')
                });
            }, true);
        });
    }
    
    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ensureButtonClicks);
    } else {
        ensureButtonClicks();
    }
    
    // Re-run after Livewire navigation
    if (window.Livewire) {
        Livewire.hook('message.processed', () => {
            ensureButtonClicks();
        });
    }
    
    // Monitor for dynamically added buttons
    const observer = new MutationObserver((mutations) => {
        let hasNewButtons = false;
        
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === 1 && (
                    node.tagName === 'BUTTON' || 
                    node.querySelector?.('[wire\\:click]')
                )) {
                    hasNewButtons = true;
                }
            });
        });
        
        if (hasNewButtons) {
            setTimeout(ensureButtonClicks, 100);
        }
    });
    
    observer.observe(document.body, { 
        childList: true, 
        subtree: true 
    });
    
    // Also ensure form submissions work
    document.addEventListener('submit', function(e) {
        console.log('Form submitted:', e.target);
    }, true);
})();