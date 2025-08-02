/**
 * Debug Wizard Form
 * Hilft beim Debuggen des Quick Setup Wizard
 */
(function() {
    'use strict';
    
    console.log('🔍 Debug Wizard Form Active');
    
    // Finde alle Livewire-Komponenten
    function debugLivewireState() {
        if (!window.Livewire) {
            console.warn('Livewire not loaded yet');
            return;
        }
        
        // Finde die Wizard-Komponente
        const wizardElement = document.querySelector('[wire\\:id]');
        if (wizardElement) {
            const wireId = wizardElement.getAttribute('wire:id');
            const component = Livewire.find(wireId);
            
            if (component) {
                console.log('Livewire Component Found:', {
                    id: wireId,
                    data: component.data,
                    fingerprint: component.fingerprint
                });
                
                // Speziell nach setup_mode schauen
                if (component.data) {
                    console.log('Component data.setup_mode:', component.data.setup_mode);
                    console.log('Component data.show_company_selector:', component.data.show_company_selector);
                    console.log('Full data object:', component.data);
                }
                
                // Expose für manuelles Debugging
                window.wizardComponent = component;
                console.log('💡 Tipp: Nutze window.wizardComponent für manuelles Debugging');
            }
        }
    }
    
    // Überwache Änderungen an Radio Buttons
    document.addEventListener('change', function(e) {
        if (e.target.type === 'radio') {
            console.log('Radio changed:', {
                name: e.target.name,
                value: e.target.value,
                checked: e.target.checked
            });
            
            // Debug Livewire state nach Änderung
            setTimeout(() => {
                console.log('--- State after radio change ---');
                debugLivewireState();
            }, 100);
        }
    });
    
    // Initial debug
    setTimeout(debugLivewireState, 1000);
    
    // Debug nach Livewire Updates
    if (window.Livewire) {
        Livewire.hook('message.sent', (message, component) => {
            console.log('📤 Livewire message sent:', message);
        });
        
        Livewire.hook('message.received', (message, component) => {
            console.log('📥 Livewire message received:', message);
            if (message.response && message.response.effects) {
                console.log('Response effects:', message.response.effects);
            }
        });
        
        Livewire.hook('message.processed', (message, component) => {
            console.log('✅ Message processed, current state:');
            debugLivewireState();
        });
    }
    
    // Manueller Toggle-Test
    window.testToggleEdit = function() {
        const editRadio = document.querySelector('input[type="radio"][value="edit"]');
        if (editRadio) {
            editRadio.checked = true;
            editRadio.dispatchEvent(new Event('change', { bubbles: true }));
            console.log('Manually triggered edit mode');
        } else {
            console.error('Edit radio button not found');
        }
    };
    
    console.log('💡 Nutze window.testToggleEdit() um Edit-Modus manuell zu aktivieren');
})();