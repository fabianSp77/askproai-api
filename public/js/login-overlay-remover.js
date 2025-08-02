/**
 * Login Overlay Remover
 * Entfernt alle Overlays und macht Login-Form klickbar
 */

(function() {
    'use strict';
    
    console.log('[Login Overlay Remover] Starting...');
    
    // Entferne alle blockierenden Overlays
    function removeOverlays() {
        // Entferne ::before und ::after pseudo-elements die blockieren könnten
        const style = document.createElement('style');
        style.innerHTML = `
            body::before,
            body::after,
            .fi-simple-page::before,
            .fi-simple-page::after,
            .fi-login::before,
            .fi-login::after,
            form::before,
            form::after {
                display: none !important;
                content: none !important;
            }
            
            /* Stelle sicher dass alles klickbar ist */
            body,
            .fi-simple-page,
            .fi-login,
            form,
            input,
            button,
            a {
                pointer-events: auto !important;
                user-select: auto !important;
                -webkit-user-select: auto !important;
            }
            
            /* Spezifisch für Inputs */
            input[type="text"],
            input[type="email"],
            input[type="password"],
            input[type="tel"],
            textarea,
            select {
                pointer-events: auto !important;
                cursor: text !important;
                user-select: text !important;
                -webkit-user-select: text !important;
                -webkit-tap-highlight-color: transparent !important;
                touch-action: manipulation !important;
            }
            
            /* Submit Button */
            button[type="submit"] {
                pointer-events: auto !important;
                cursor: pointer !important;
                touch-action: manipulation !important;
            }
            
            /* Remove any z-index issues */
            .fi-simple-page {
                position: relative !important;
                z-index: 1 !important;
            }
            
            form {
                position: relative !important;
                z-index: 10 !important;
            }
        `;
        document.head.appendChild(style);
    }
    
    // Fix Login Form
    function fixLoginForm() {
        const inputs = document.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            // Entferne alle Style-Attribute die blockieren könnten
            if (input.style.pointerEvents === 'none') {
                input.style.pointerEvents = 'auto';
            }
            
            // Stelle sicher dass readonly nur bei hidden inputs ist
            if (input.type !== 'hidden' && input.readOnly && !input.hasAttribute('data-readonly')) {
                input.readOnly = false;
            }
            
            // Touch Event für bessere Mobile-Unterstützung
            input.addEventListener('touchstart', function(e) {
                this.focus();
            }, { passive: true });
        });
        
        // Fix Submit Button
        const submitButtons = document.querySelectorAll('button[type="submit"]');
        submitButtons.forEach(button => {
            button.style.pointerEvents = 'auto';
            button.style.cursor = 'pointer';
            
            // Debug click
            button.addEventListener('click', function(e) {
                console.log('[Login Overlay Remover] Submit clicked');
            });
        });
    }
    
    // Entferne alle inline styles die pointer-events blockieren
    function removeBlockingStyles() {
        document.querySelectorAll('[style*="pointer-events: none"]').forEach(el => {
            el.style.pointerEvents = 'auto';
        });
    }
    
    // Hauptfunktion
    function init() {
        removeOverlays();
        fixLoginForm();
        removeBlockingStyles();
        console.log('[Login Overlay Remover] Login form should be clickable now');
    }
    
    // Sofort ausführen
    init();
    
    // Nochmal nach DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    }
    
    // Und nochmal verzögert für dynamische Inhalte
    setTimeout(init, 500);
    setTimeout(init, 1000);
    
    console.log('[Login Overlay Remover] Ready');
})();