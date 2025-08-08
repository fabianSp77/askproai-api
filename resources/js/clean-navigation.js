// CLEAN NAVIGATION FIX - Issue #510
// Minimale, saubere Lösung ohne Debug-Elemente

(function() {
    'use strict';
    
    // Einfache Funktion um sicherzustellen, dass Links funktionieren
    function ensureClickable() {
        // Finde alle Links und Buttons
        const interactiveElements = document.querySelectorAll('a, button, input, select, textarea, [role="button"], [role="link"]');
        
        interactiveElements.forEach(element => {
            // Nur wenn pointer-events none ist, korrigieren
            const computed = window.getComputedStyle(element);
            if (computed.pointerEvents === 'none') {
                element.style.pointerEvents = 'auto';
            }
            
            // Entferne disabled wenn es nicht sein sollte
            if (element.hasAttribute('disabled') && !element.classList.contains('opacity-50')) {
                element.removeAttribute('disabled');
            }
        });
        
        // Spezielle Behandlung für Filament Navigation
        const sidebarLinks = document.querySelectorAll('.fi-sidebar-nav a, .fi-sidebar-nav button');
        sidebarLinks.forEach(link => {
            link.style.cursor = 'pointer';
        });
    }
    
    // Führe Fix aus wenn DOM bereit ist
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ensureClickable);
    } else {
        ensureClickable();
    }
    
    // Führe Fix nochmal aus nach kurzer Verzögerung für dynamische Inhalte
    setTimeout(ensureClickable, 500);
    
    // Überwache Änderungen für neue Elemente
    const observer = new MutationObserver(() => {
        ensureClickable();
    });
    
    // Beobachte nur relevante Änderungen
    if (document.body) {
        observer.observe(document.body, {
            childList: true,
            subtree: true,
            attributeFilter: ['disabled', 'style']
        });
    }
    
    // Keine console.logs, keine Debug-Divs, keine visuellen Indikatoren
})();