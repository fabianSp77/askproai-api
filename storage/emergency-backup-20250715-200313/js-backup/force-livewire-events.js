// Force Livewire Events - Last resort fix
(function() {
    'use strict';
    
    console.log('[Force Livewire Events] Starting...');
    
    // Force wire:click to work
    document.addEventListener('click', function(e) {
        const target = e.target;
        const wireClickElement = target.closest('[wire\\:click]');
        
        if (wireClickElement) {
            const wireClickValue = wireClickElement.getAttribute('wire:click');
            console.log('[Force Livewire Events] Detected wire:click:', wireClickValue);
            
            // Check if Livewire processed this click
            setTimeout(() => {
                // If nothing happened, force it
                if (window.Livewire && !e.defaultPrevented) {
                    console.log('[Force Livewire Events] Forcing Livewire event:', wireClickValue);
                    
                    // Find the component
                    const component = wireClickElement.closest('[wire\\:id]');
                    if (component) {
                        const componentId = component.getAttribute('wire:id');
                        const livewireComponent = window.Livewire.find(componentId);
                        
                        if (livewireComponent) {
                            // Parse the wire:click value
                            const match = wireClickValue.match(/^(\w+)(?:\((.*)\))?$/);
                            if (match) {
                                const method = match[1];
                                const params = match[2] ? match[2].split(',').map(p => p.trim().replace(/['"]/g, '')) : [];
                                
                                console.log('[Force Livewire Events] Calling method:', method, 'with params:', params);
                                livewireComponent.call(method, ...params);
                            }
                        }
                    }
                }
            }, 100);
        }
    }, true); // Use capture phase
    
    // Fix wire:model for selects
    document.addEventListener('change', function(e) {
        const target = e.target;
        if (target.hasAttribute('wire:model')) {
            const wireModel = target.getAttribute('wire:model');
            const value = target.value;
            
            console.log('[Force Livewire Events] Select changed:', wireModel, '=', value);
            
            // Find the Livewire component
            const component = target.closest('[wire\\:id]');
            if (component && window.Livewire) {
                const componentId = component.getAttribute('wire:id');
                const livewireComponent = window.Livewire.find(componentId);
                
                if (livewireComponent) {
                    console.log('[Force Livewire Events] Updating model:', wireModel);
                    livewireComponent.set(wireModel, value);
                }
            }
        }
    }, true);
    
    console.log('[Force Livewire Events] Ready');
})();