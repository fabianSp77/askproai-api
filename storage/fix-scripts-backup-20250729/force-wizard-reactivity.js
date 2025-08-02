/**
 * Force Wizard Reactivity
 * Erzwingt die Reaktivität im Quick Setup Wizard
 */
(function() {
    'use strict';
    
    console.log('⚡ Force Wizard Reactivity Active');
    
    // Warte auf Alpine und Livewire
    function waitForFrameworks(callback) {
        if (window.Alpine && window.Livewire) {
            callback();
        } else {
            setTimeout(() => waitForFrameworks(callback), 50);
        }
    }
    
    waitForFrameworks(() => {
        // Überschreibe das Change-Event für Setup Mode Radio Buttons
        document.addEventListener('change', function(e) {
            // Prüfe ob es ein setup_mode Radio ist
            if (e.target.name && e.target.name.includes('setup_mode') && e.target.type === 'radio') {
                console.log('Setup mode changing to:', e.target.value);
                
                if (e.target.value === 'edit') {
                    // Finde die Livewire-Komponente
                    const wireElement = e.target.closest('[wire\\:id]');
                    if (wireElement) {
                        const component = Livewire.find(wireElement.getAttribute('wire:id'));
                        
                        if (component) {
                            console.log('Forcing company selector visibility');
                            
                            // Setze den State direkt
                            component.set('data.show_company_selector', true, false);
                            
                            // Warte kurz und zeige dann das Select-Feld
                            setTimeout(() => {
                                // Finde das Company Select Container
                                const selects = document.querySelectorAll('.filament-forms-select-component');
                                selects.forEach(select => {
                                    const input = select.querySelector('select[name*="selected_company"]');
                                    if (input) {
                                        console.log('Found company selector, making visible');
                                        // Entferne alle display:none oder hidden Klassen
                                        select.style.display = '';
                                        select.classList.remove('hidden');
                                        const wrapper = select.closest('.filament-forms-field-wrapper');
                                        if (wrapper) {
                                            wrapper.style.display = '';
                                            wrapper.classList.remove('hidden');
                                        }
                                    }
                                });
                            }, 100);
                        }
                    }
                }
            }
        }, true); // Use capture phase
        
        // Alternative: Direkt Alpine manipulieren
        document.addEventListener('click', function(e) {
            const toggleButton = e.target.closest('.filament-forms-toggle-buttons-component');
            if (toggleButton) {
                const input = e.target.closest('label')?.querySelector('input[type="radio"]');
                if (input && input.value === 'edit') {
                    console.log('Edit toggle clicked, ensuring visibility');
                    
                    // Suche nach Alpine-Komponente
                    const alpineEl = e.target.closest('[x-data]');
                    if (alpineEl && window.Alpine) {
                        try {
                            const alpineData = Alpine.$data(alpineEl);
                            if (alpineData && alpineData.$wire) {
                                console.log('Found Alpine component with $wire');
                                // Trigger update
                                alpineData.$wire.set('data.setup_mode', 'edit');
                                alpineData.$wire.set('data.show_company_selector', true);
                            }
                        } catch (err) {
                            console.warn('Alpine manipulation failed:', err);
                        }
                    }
                }
            }
        });
    });
    
    // Globale Hilfsfunktion zum manuellen Anzeigen
    window.showCompanySelector = function() {
        console.log('Manually showing company selector');
        
        // Zeige alle versteckten Select-Komponenten
        document.querySelectorAll('.filament-forms-field-wrapper').forEach(wrapper => {
            const select = wrapper.querySelector('select[name*="selected_company"]');
            if (select) {
                wrapper.style.display = '';
                wrapper.classList.remove('hidden');
                console.log('Made selector visible:', select);
            }
        });
        
        // Update Livewire state
        const components = document.querySelectorAll('[wire\\:id]');
        components.forEach(el => {
            const component = Livewire.find(el.getAttribute('wire:id'));
            if (component && component.data) {
                component.set('data.show_company_selector', true);
                component.set('data.setup_mode', 'edit');
            }
        });
    };
    
    console.log('💡 Nutze window.showCompanySelector() falls der Selektor nicht erscheint');
})();