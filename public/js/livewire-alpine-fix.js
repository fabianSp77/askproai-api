/**
 * Livewire/Alpine.js Click Fix
 * Fixes event binding issues in Filament
 */

console.error('🔧 LIVEWIRE/ALPINE FIX - Restoring event handlers');

(function() {
    'use strict';
    
    function fixLivewireAlpineClicks() {
        // 1. Fix Livewire wire:click handlers
        document.querySelectorAll('[wire\\:click]').forEach(el => {
            const wireClick = el.getAttribute('wire:click');
            if (wireClick && !el.dataset.manualClickAdded) {
                el.dataset.manualClickAdded = 'true';
                
                // Remove any blocking styles
                el.style.pointerEvents = 'auto';
                el.style.cursor = 'pointer';
                
                // Add manual click handler that triggers Livewire
                el.addEventListener('click', function(e) {
                    console.log('Manual wire:click triggered:', wireClick);
                    
                    // Try to find Livewire component
                    const component = el.closest('[wire\\:id]');
                    if (component && window.Livewire) {
                        const componentId = component.getAttribute('wire:id');
                        const livewireComponent = window.Livewire.find(componentId);
                        if (livewireComponent) {
                            // Parse and call the method
                            const match = wireClick.match(/^(\w+)(\((.*)\))?$/);
                            if (match) {
                                const method = match[1];
                                const args = match[3] ? match[3].split(',').map(a => a.trim()) : [];
                                console.log('Calling Livewire method:', method, args);
                                livewireComponent.call(method, ...args);
                            }
                        }
                    }
                }, true);
            }
        });
        
        // 2. Fix Alpine.js x-on:click handlers
        document.querySelectorAll('[x-on\\:click], [@click]').forEach(el => {
            const alpineClick = el.getAttribute('x-on:click') || el.getAttribute('@click');
            if (alpineClick && !el.dataset.alpineClickFixed) {
                el.dataset.alpineClickFixed = 'true';
                
                el.style.pointerEvents = 'auto';
                el.style.cursor = 'pointer';
                
                // Force Alpine to re-evaluate
                if (window.Alpine && window.Alpine.initializeComponent) {
                    try {
                        window.Alpine.initializeComponent(el);
                    } catch (e) {
                        console.warn('Alpine re-init failed:', e);
                    }
                }
            }
        });
        
        // 3. Fix regular links
        document.querySelectorAll('a[href]').forEach(link => {
            link.style.pointerEvents = 'auto';
            link.style.cursor = 'pointer';
            
            if (!link.dataset.manualLinkFixed) {
                link.dataset.manualLinkFixed = 'true';
                link.addEventListener('click', function(e) {
                    console.log('Link clicked:', this.href);
                    // Let default behavior happen
                }, true);
            }
        });
        
        // 4. Fix buttons
        document.querySelectorAll('button').forEach(btn => {
            btn.style.pointerEvents = 'auto';
            btn.style.cursor = 'pointer';
            
            // Remove disabled if not intentional
            if (btn.disabled && !btn.dataset.intentionallyDisabled) {
                btn.disabled = false;
            }
        });
        
        // 5. Fix Filament action buttons
        document.querySelectorAll('.fi-ac-action, .fi-ac-link-action, .fi-ac-icon-btn').forEach(action => {
            action.style.pointerEvents = 'auto';
            action.style.cursor = 'pointer';
            
            // Find the actual button inside
            const btn = action.querySelector('button');
            if (btn) {
                btn.style.pointerEvents = 'auto';
                btn.style.cursor = 'pointer';
            }
        });
        
        // 6. Remove event stopPropagation that might be blocking
        const clickHandler = function(e) {
            console.log('Click detected on:', e.target);
            // Don't stop propagation
        };
        
        document.addEventListener('click', clickHandler, true);
        
        console.log('Livewire/Alpine fix applied');
    }
    
    // Run immediately
    fixLivewireAlpineClicks();
    
    // Run after Livewire loads
    if (window.Livewire) {
        Livewire.hook('message.processed', () => {
            setTimeout(fixLivewireAlpineClicks, 100);
        });
    } else {
        document.addEventListener('livewire:load', () => {
            fixLivewireAlpineClicks();
            Livewire.hook('message.processed', () => {
                setTimeout(fixLivewireAlpineClicks, 100);
            });
        });
    }
    
    // Run after Alpine loads
    if (window.Alpine) {
        document.addEventListener('alpine:initialized', fixLivewireAlpineClicks);
    }
    
    // Expose for manual trigger
    window.fixLivewireAlpineClicks = fixLivewireAlpineClicks;
})();