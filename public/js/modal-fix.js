// Modal Fix for Filament
(function() {
    'use strict';
    
    // Prevent outerHTML modifications on detached elements
    const originalOuterHTMLSetter = Object.getOwnPropertyDescriptor(Element.prototype, 'outerHTML').set;
    
    Object.defineProperty(Element.prototype, 'outerHTML', {
        set: function(value) {
            // Check if element has a parent before attempting to set outerHTML
            if (this.parentNode) {
                originalOuterHTMLSetter.call(this, value);
            } else {
                // If no parent, create a temporary container
                const temp = document.createElement('div');
                temp.innerHTML = value;
                const newElement = temp.firstElementChild;
                
                // Copy attributes and content to the current element
                if (newElement) {
                    // Clear existing attributes
                    while (this.attributes.length > 0) {
                        this.removeAttribute(this.attributes[0].name);
                    }
                    
                    // Copy new attributes
                    for (let attr of newElement.attributes) {
                        this.setAttribute(attr.name, attr.value);
                    }
                    
                    // Copy content
                    this.innerHTML = newElement.innerHTML;
                }
            }
        },
        get: function() {
            return originalOuterHTMLSetter;
        }
    });
    
    // Check if document.write hasn't already been overridden
    if (typeof document.write === 'function' && document.write.toString().indexOf('[native code]') > -1) {
        try {
            const originalWrite = document.write;
            const originalWriteln = document.writeln;
            
            // Try to override (may fail if already made read-only)
            document.write = function() {
                // Silently ignore document.write calls
                return;
            };
            
            document.writeln = function() {
                // Silently ignore document.writeln calls
                return;
            };
        } catch (e) {
            // Already overridden, that's fine
            console.log('document.write already overridden');
        }
    }
    
    // Fix modal initialization issues
    document.addEventListener('DOMContentLoaded', function() {
        // Wait for Livewire to be ready
        if (window.Livewire) {
            window.Livewire.hook('message.processed', (message, component) => {
                // Ensure modals are properly initialized after Livewire updates
                if (window.FilamentModals) {
                    window.FilamentModals.init();
                }
            });
        }
    });
    
    // Prevent modal errors during Alpine initialization
    if (window.Alpine) {
        document.addEventListener('alpine:init', () => {
            Alpine.directive('safe-modal', (el, { expression }, { evaluate }) => {
                try {
                    // Safely evaluate modal expressions
                    evaluate(expression);
                } catch (error) {
                    console.warn('Modal initialization error caught:', error);
                }
            });
        });
    }
})();