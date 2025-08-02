/**
 * Final Wizard Fix - Ultimative Lösung
 */
(function() {
    'use strict';
    
    console.log('🚀 Final Wizard Fix Active');
    
    // Überwache das DOM für Änderungen
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            // Prüfe ob neue Nodes hinzugefügt wurden
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1) { // Element node
                    checkAndFixVisibility(node);
                }
            });
        });
    });
    
    // Starte Beobachtung
    observer.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['style', 'class']
    });
    
    // Funktion um Sichtbarkeit zu prüfen und zu korrigieren
    function checkAndFixVisibility(element) {
        // Suche nach Select-Elementen mit "company" im Namen
        const selects = element.querySelectorAll ? element.querySelectorAll('select') : [];
        selects.forEach(select => {
            const name = select.getAttribute('name') || '';
            if (name.includes('selected_company')) {
                console.log('Found company select, ensuring visibility');
                ensureVisible(select);
            }
        });
        
        // Prüfe ob das Element selbst ein company select ist
        if (element.tagName === 'SELECT') {
            const name = element.getAttribute('name') || '';
            if (name.includes('selected_company')) {
                console.log('Found company select element, ensuring visibility');
                ensureVisible(element);
            }
        }
    }
    
    // Stelle sicher dass ein Element sichtbar ist
    function ensureVisible(element) {
        let current = element;
        while (current) {
            // Entferne alle Versteck-Styles
            if (current.style) {
                current.style.display = '';
                current.style.visibility = 'visible';
            }
            
            // Entferne hidden Klassen
            if (current.classList) {
                current.classList.remove('hidden');
                current.classList.remove('invisible');
            }
            
            current = current.parentElement;
        }
    }
    
    // Überwache Radio-Button Änderungen
    document.addEventListener('change', function(e) {
        if (e.target.type === 'radio' && e.target.value === 'edit') {
            console.log('Edit mode selected, forcing visibility in 500ms');
            
            // Mehrere Versuche mit unterschiedlichen Delays
            [100, 300, 500, 1000].forEach(delay => {
                setTimeout(() => {
                    forceCompanySelectVisible();
                }, delay);
            });
        }
    });
    
    // Erzwinge Sichtbarkeit des Company Select
    function forceCompanySelectVisible() {
        console.log('Forcing company select visibility...');
        
        // Methode 1: Suche nach Name
        document.querySelectorAll('select').forEach(select => {
            const name = select.getAttribute('name') || '';
            if (name.includes('selected_company') || name.includes('company')) {
                console.log('Making select visible:', name);
                ensureVisible(select);
            }
        });
        
        // Methode 2: Suche nach Label
        document.querySelectorAll('label').forEach(label => {
            if (label.textContent.includes('Firma auswählen')) {
                console.log('Found label "Firma auswählen", making parent visible');
                ensureVisible(label.closest('.filament-forms-field-wrapper'));
            }
        });
        
        // Methode 3: Entferne alle x-show Bedingungen temporär
        document.querySelectorAll('[x-show]').forEach(el => {
            const condition = el.getAttribute('x-show');
            if (condition && condition.includes('setup_mode')) {
                console.log('Removing x-show condition:', condition);
                el.removeAttribute('x-show');
                el.style.display = '';
            }
        });
        
        // Methode 4: Alpine.js direkt manipulieren
        if (window.Alpine) {
            document.querySelectorAll('[x-data]').forEach(el => {
                try {
                    const data = Alpine.$data(el);
                    if (data && typeof data === 'object') {
                        // Setze alle möglichen Varianten
                        data.setup_mode = 'edit';
                        data.show_company_selector = true;
                        if (data.data) {
                            data.data.setup_mode = 'edit';
                            data.data.show_company_selector = true;
                        }
                    }
                } catch (e) {
                    // Ignore errors
                }
            });
        }
    }
    
    // Initiale Prüfung
    setTimeout(() => {
        checkAndFixVisibility(document.body);
    }, 1000);
    
    // Globale Funktion
    window.forceCompanySelect = forceCompanySelectVisible;
    
    console.log('💡 Nutze window.forceCompanySelect() um den Selector zu erzwingen');
})();