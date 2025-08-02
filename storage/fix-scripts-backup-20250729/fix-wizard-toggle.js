/**
 * Fix Wizard Toggle - Direkte Lösung
 * Behebt das Problem mit dem "Bestehende Firma bearbeiten" Toggle
 */
(function() {
    'use strict';
    
    console.log('🔧 Fix Wizard Toggle - Starting');
    
    // Warte bis Livewire und Alpine geladen sind
    function waitForFrameworks(callback) {
        if (window.Livewire && window.Alpine) {
            callback();
        } else {
            setTimeout(() => waitForFrameworks(callback), 50);
        }
    }
    
    waitForFrameworks(() => {
        console.log('✅ Frameworks loaded, applying fixes');
        
        // Überwache Radio-Button-Änderungen
        document.addEventListener('change', function(e) {
            if (e.target.type === 'radio' && e.target.name && e.target.name.includes('setup_mode')) {
                console.log('🔄 Setup mode changed to:', e.target.value);
                
                if (e.target.value === 'edit') {
                    // Warte kurz, dann suche und zeige das Select-Feld
                    setTimeout(() => {
                        showCompanySelectField();
                    }, 100);
                }
            }
        });
        
        // Funktion zum Anzeigen des Company Select Feldes
        function showCompanySelectField() {
            console.log('🔍 Looking for company select field...');
            
            // Suche nach allen Filament Select-Komponenten
            const allSelects = document.querySelectorAll('.filament-forms-field-wrapper');
            let companySelectFound = false;
            
            allSelects.forEach(wrapper => {
                // Prüfe ob es ein Select-Feld ist
                const select = wrapper.querySelector('select');
                if (select) {
                    const name = select.getAttribute('name');
                    console.log('Found select with name:', name);
                    
                    // Prüfe ob es das Company Select ist
                    if (name && (name.includes('selected_company') || name.includes('company'))) {
                        console.log('✅ Found company select field!');
                        companySelectFound = true;
                        
                        // Mache das Feld sichtbar
                        wrapper.style.display = 'block';
                        wrapper.style.visibility = 'visible';
                        wrapper.classList.remove('hidden');
                        
                        // Entferne auch von Parent-Elementen
                        let parent = wrapper.parentElement;
                        while (parent) {
                            if (parent.style.display === 'none' || parent.classList.contains('hidden')) {
                                parent.style.display = '';
                                parent.classList.remove('hidden');
                            }
                            parent = parent.parentElement;
                        }
                        
                        // Scrolle zum Element
                        wrapper.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        
                        // Highlight das Feld kurz
                        wrapper.style.transition = 'background-color 0.3s';
                        wrapper.style.backgroundColor = '#fef3c7';
                        setTimeout(() => {
                            wrapper.style.backgroundColor = '';
                        }, 1000);
                    }
                }
            });
            
            if (!companySelectFound) {
                console.log('❌ Company select field not found in DOM');
                
                // Alternative: Suche nach x-show Attributen
                const xShowElements = document.querySelectorAll('[x-show]');
                xShowElements.forEach(el => {
                    console.log('Element with x-show:', el, 'condition:', el.getAttribute('x-show'));
                });
                
                // Versuche Livewire-Update zu erzwingen
                forceLivewireUpdate();
            }
        }
        
        // Erzwinge Livewire-Update
        function forceLivewireUpdate() {
            console.log('🔄 Forcing Livewire update...');
            
            const wireElements = document.querySelectorAll('[wire\\:id]');
            wireElements.forEach(el => {
                const wireId = el.getAttribute('wire:id');
                const component = window.Livewire.find(wireId);
                
                if (component) {
                    console.log('Found Livewire component:', wireId);
                    
                    // Versuche verschiedene Wege
                    try {
                        // Methode 1: Direkt setzen
                        if (component.$wire) {
                            component.$wire.set('data.setup_mode', 'edit');
                            component.$wire.set('setup_mode', 'edit');
                            component.$wire.set('show_company_selector', true);
                            component.$wire.set('data.show_company_selector', true);
                        }
                        
                        // Methode 2: Call refresh
                        if (component.call) {
                            component.call('$refresh');
                        }
                        
                        // Methode 3: Emit event
                        if (component.$wire && component.$wire.emit) {
                            component.$wire.emit('setupModeChanged', 'edit');
                        }
                    } catch (e) {
                        console.error('Error updating component:', e);
                    }
                }
            });
            
            // Nach Update nochmal suchen
            setTimeout(showCompanySelectField, 200);
        }
        
        // Globale Hilfsfunktionen
        window.forceShowCompanySelect = function() {
            console.log('🚀 Manually forcing company select visibility');
            showCompanySelectField();
            
            // Falls immer noch nicht sichtbar, zeige alle versteckten Elemente
            const hiddenElements = document.querySelectorAll('[style*="display: none"], [style*="display:none"], .hidden');
            hiddenElements.forEach(el => {
                if (el.innerHTML.includes('select') || el.innerHTML.includes('company')) {
                    console.log('Showing hidden element:', el);
                    el.style.display = '';
                    el.classList.remove('hidden');
                }
            });
        };
        
        window.debugWizardState = function() {
            console.log('=== WIZARD DEBUG STATE ===');
            
            // Finde alle Radio Buttons
            const radios = document.querySelectorAll('input[type="radio"]');
            radios.forEach(radio => {
                console.log('Radio:', radio.name, '=', radio.value, 'checked:', radio.checked);
            });
            
            // Finde alle Select-Felder
            const selects = document.querySelectorAll('select');
            selects.forEach(select => {
                const wrapper = select.closest('.filament-forms-field-wrapper');
                console.log('Select:', select.name, 'visible:', wrapper ? getComputedStyle(wrapper).display : 'no wrapper');
            });
            
            // Livewire components
            document.querySelectorAll('[wire\\:id]').forEach(el => {
                const component = Livewire.find(el.getAttribute('wire:id'));
                if (component) {
                    console.log('Livewire component data:', component);
                }
            });
        };
        
        console.log('💡 Befehle verfügbar:');
        console.log('   window.forceShowCompanySelect() - Zeigt Company Select');
        console.log('   window.debugWizardState() - Debug-Informationen');
    });
})();