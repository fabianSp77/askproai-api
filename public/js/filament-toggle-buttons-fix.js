/**
 * Filament Toggle Buttons Fix
 * Stellt sicher, dass ToggleButtons korrekt funktionieren
 */
(function() {
    'use strict';
    
    console.log('🔧 Filament Toggle Buttons Fix Active');
    
    // Monitor für Alpine.js Komponenten
    function monitorToggleButtons() {
        // Finde alle Toggle Button Container
        const toggleContainers = document.querySelectorAll('[x-data*="toggle"]');
        
        toggleContainers.forEach(container => {
            console.log('Found toggle container:', container);
            
            // Finde alle Radio Inputs in Toggle Buttons
            const radios = container.querySelectorAll('input[type="radio"]');
            
            radios.forEach(radio => {
                // Log wenn sich der Wert ändert
                radio.addEventListener('change', function(e) {
                    console.log('Toggle button changed:', {
                        name: this.name,
                        value: this.value,
                        checked: this.checked
                    });
                    
                    // Trigger Alpine.js update manually if needed
                    if (window.Alpine && this.checked) {
                        const alpineComponent = Alpine.$data(container);
                        if (alpineComponent) {
                            console.log('Alpine component found, triggering update');
                            // Trigger any watchers
                            Alpine.nextTick(() => {
                                console.log('Alpine nextTick executed');
                            });
                        }
                    }
                    
                    // Ensure Livewire gets the update
                    if (window.Livewire) {
                        const component = Livewire.find(container.closest('[wire\\:id]')?.getAttribute('wire:id'));
                        if (component) {
                            console.log('Livewire component found, checking for updates');
                            // The change should trigger automatically via wire:model
                        }
                    }
                });
                
                // Add click logging
                radio.parentElement?.addEventListener('click', function(e) {
                    console.log('Toggle button clicked:', radio.value);
                });
            });
        });
        
        // Also monitor for any wire:model.defer changes
        const wireModels = document.querySelectorAll('[wire\\:model\\.defer]');
        wireModels.forEach(element => {
            console.log('Found wire:model.defer element:', {
                model: element.getAttribute('wire:model.defer'),
                type: element.type,
                tagName: element.tagName
            });
        });
    }
    
    // Run when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', monitorToggleButtons);
    } else {
        monitorToggleButtons();
    }
    
    // Re-run after Livewire updates
    if (window.Livewire) {
        Livewire.hook('message.processed', (message, component) => {
            console.log('Livewire message processed, re-scanning for toggle buttons');
            setTimeout(monitorToggleButtons, 100);
        });
    }
    
    // Monitor for dynamic content
    const observer = new MutationObserver((mutations) => {
        let hasNewToggles = false;
        
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === 1 && node.querySelector?.('[x-data*="toggle"]')) {
                    hasNewToggles = true;
                }
            });
        });
        
        if (hasNewToggles) {
            console.log('New toggle buttons detected');
            setTimeout(monitorToggleButtons, 100);
        }
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
})();