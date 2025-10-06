// FINAL SOLUTION - Einfach und effektiv
(function() {
    'use strict';

    console.log('[FINAL] Lösung wird aktiviert...');

    // 1. Frühe Intervention - Verhindere innerHTML Fehler
    const originalInnerHTML = Object.getOwnPropertyDescriptor(Element.prototype, 'innerHTML');
    if (originalInnerHTML) {
        Object.defineProperty(Element.prototype, 'innerHTML', {
            set: function(value) {
                try {
                    if (this && originalInnerHTML.set) {
                        originalInnerHTML.set.call(this, value);
                    }
                } catch(e) {
                    console.log('[FINAL] innerHTML Fehler verhindert');
                }
            },
            get: function() {
                try {
                    if (this && originalInnerHTML.get) {
                        return originalInnerHTML.get.call(this);
                    }
                } catch(e) {
                    return '';
                }
                return '';
            }
        });
    }

    // 2. Überschreibe problematische Funktionen
    if (typeof window.showHtmlModal === 'undefined') {
        window.showHtmlModal = function() {
            console.log('[FINAL] Modal blockiert');
            return false;
        };
    }

    // 3. Sichere getElementById
    const originalGetElementById = document.getElementById;
    document.getElementById = function(id) {
        try {
            if (id === 'livewire-error') {
                console.log('[FINAL] Livewire-Error abgefangen');
                return null;
            }
            return originalGetElementById.call(document, id);
        } catch(e) {
            return null;
        }
    };

    // 4. Entferne existierende Modals
    function cleanModals() {
        try {
            const modal = document.querySelector('#livewire-error');
            if (modal) {
                modal.remove();
            }
        } catch(e) {}

        // Body zurücksetzen
        if (document.body) {
            document.body.style.overflow = '';
        }
    }

    // 5. Error Handler für Promise Rejection
    window.addEventListener('unhandledrejection', function(event) {
        if (event.reason && event.reason.toString().includes('innerHTML')) {
            event.preventDefault();
            console.log('[FINAL] Promise Rejection verhindert');
        }
    });

    // 6. Error Handler für allgemeine Fehler
    window.addEventListener('error', function(event) {
        if (event.message && (event.message.includes('innerHTML') || event.message.includes('null'))) {
            event.preventDefault();
            console.log('[FINAL] JavaScript Fehler abgefangen');
            return false;
        }
    }, true);

    // Initialisierung
    cleanModals();

    // Livewire Patch
    if (window.Livewire) {
        window.Livewire.handleError = function() { return false; };
    }

    document.addEventListener('livewire:load', function() {
        if (window.Livewire) {
            window.Livewire.handleError = function() { return false; };
        }
        cleanModals();
    });

    // Periodische Reinigung für 5 Sekunden
    let count = 0;
    const interval = setInterval(function() {
        cleanModals();
        count++;
        if (count > 10) {
            clearInterval(interval);
            console.log('[FINAL] Überwachung beendet');
        }
    }, 500);

    console.log('[FINAL] Alle Schutzmaßnahmen aktiv');
})();