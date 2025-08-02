/**
 * Login Form Fix
 * Spezifischer Fix für Mobile Login-Probleme
 */

(function() {
    'use strict';
    
    console.log('[Login Form Fix] Initializing...');
    
    // Warte bis das Formular da ist
    function waitForForm(callback) {
        const form = document.querySelector('form');
        if (form) {
            callback(form);
        } else {
            setTimeout(() => waitForForm(callback), 100);
        }
    }
    
    // Haupt-Fix-Funktion
    function fixLoginForm(form) {
        console.log('[Login Form Fix] Fixing form...');
        
        // 1. Fix alle Input-Felder
        const inputs = form.querySelectorAll('input:not([type="hidden"])');
        inputs.forEach((input, index) => {
            console.log(`[Login Form Fix] Fixing input ${index}:`, input.type);
            
            // Entferne blockierende Styles
            input.style.pointerEvents = 'auto';
            input.style.userSelect = 'text';
            input.style.webkitUserSelect = 'text';
            input.style.cursor = 'text';
            
            // Entferne readonly wenn nicht beabsichtigt
            if (input.readOnly && !input.hasAttribute('data-readonly')) {
                input.readOnly = false;
            }
            
            // Touch-Event für iOS
            input.addEventListener('touchstart', function(e) {
                e.stopPropagation();
                this.focus();
                console.log('[Login Form Fix] Input touched:', this.type);
            }, { passive: false });
            
            // Click-Event
            input.addEventListener('click', function(e) {
                e.stopPropagation();
                this.focus();
                console.log('[Login Form Fix] Input clicked:', this.type);
            });
            
            // Focus-Event
            input.addEventListener('focus', function() {
                console.log('[Login Form Fix] Input focused:', this.type);
            });
        });
        
        // 2. Fix Submit Button
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            console.log('[Login Form Fix] Fixing submit button');
            
            submitButton.style.pointerEvents = 'auto';
            submitButton.style.cursor = 'pointer';
            submitButton.style.userSelect = 'none';
            
            // Entferne alte Handler und füge neuen hinzu
            const newButton = submitButton.cloneNode(true);
            submitButton.parentNode.replaceChild(newButton, submitButton);
            
            newButton.addEventListener('click', function(e) {
                console.log('[Login Form Fix] Submit button clicked');
                // Lass Filament/Livewire die Submission handhaben
            });
            
            newButton.addEventListener('touchstart', function(e) {
                console.log('[Login Form Fix] Submit button touched');
            }, { passive: true });
        }
        
        // 3. Fix das gesamte Formular
        form.style.pointerEvents = 'auto';
        form.style.position = 'relative';
        form.style.zIndex = '10';
        
        // 4. Entferne alle überlagernden Elemente
        const allElements = document.querySelectorAll('*');
        allElements.forEach(el => {
            const style = getComputedStyle(el);
            const zIndex = parseInt(style.zIndex);
            
            // Wenn ein Element über dem Formular liegt und nicht Teil des Formulars ist
            if (zIndex > 10 && !form.contains(el)) {
                el.style.pointerEvents = 'none';
            }
        });
        
        console.log('[Login Form Fix] Form fixed');
    }
    
    // iOS-spezifische Fixes
    function applyIOSFixes() {
        if (/iPhone|iPad|iPod/.test(navigator.userAgent)) {
            console.log('[Login Form Fix] Applying iOS fixes');
            
            // Verhindere Zoom bei Focus
            const metaViewport = document.querySelector('meta[name="viewport"]');
            if (metaViewport) {
                metaViewport.content = 'width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no';
            }
            
            // Fix für iOS Touch-Verzögerung
            document.addEventListener('touchstart', function() {}, { passive: true });
        }
    }
    
    // Debug-Helfer
    window.loginFormDebug = {
        checkInputs: function() {
            const inputs = document.querySelectorAll('input:not([type="hidden"])');
            inputs.forEach((input, i) => {
                const style = getComputedStyle(input);
                console.log(`Input ${i} (${input.type}):`, {
                    pointerEvents: style.pointerEvents,
                    cursor: style.cursor,
                    userSelect: style.userSelect,
                    readOnly: input.readOnly,
                    disabled: input.disabled,
                    zIndex: style.zIndex
                });
            });
        },
        
        testFocus: function() {
            const firstInput = document.querySelector('input:not([type="hidden"])');
            if (firstInput) {
                console.log('Trying to focus first input...');
                firstInput.focus();
                setTimeout(() => {
                    console.log('Is focused?', document.activeElement === firstInput);
                }, 100);
            }
        },
        
        showOverlays: function() {
            const style = document.createElement('style');
            style.innerHTML = `
                *::before { outline: 2px solid red !important; }
                *::after { outline: 2px solid blue !important; }
            `;
            document.head.appendChild(style);
        }
    };
    
    // Start
    waitForForm(fixLoginForm);
    applyIOSFixes();
    
    // Re-run nach Livewire Updates
    if (window.Livewire) {
        Livewire.hook('message.processed', () => {
            const form = document.querySelector('form');
            if (form) {
                fixLoginForm(form);
            }
        });
    }
    
    console.log('[Login Form Fix] Ready! Debug with: loginFormDebug.checkInputs()');
})();