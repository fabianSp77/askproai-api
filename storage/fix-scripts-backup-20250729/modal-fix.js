/**
 * Fix for Livewire Modal "outerHTML" errors
 * Prevents "Failed to set the 'outerHTML' property" errors
 */
(function() {
    'use strict';
    
    // Track error modal attempts to prevent infinite loops
    let errorModalAttempts = 0;
    const maxErrorModalAttempts = 3;
    
    // Override the problematic outerHTML setter
    const originalOuterHTMLDescriptor = Object.getOwnPropertyDescriptor(Element.prototype, 'outerHTML');
    
    if (originalOuterHTMLDescriptor) {
        Object.defineProperty(Element.prototype, 'outerHTML', {
            get: originalOuterHTMLDescriptor.get,
            set: function(value) {
                // Check if element has a parent before trying to replace
                if (this.parentNode) {
                    try {
                        originalOuterHTMLDescriptor.set.call(this, value);
                    } catch (e) {
                        console.warn('Failed to set outerHTML:', e);
                        // Fallback: try to replace using replaceWith
                        if (this.parentNode && typeof value === 'string') {
                            const template = document.createElement('template');
                            template.innerHTML = value.trim();
                            const newElement = template.content.firstChild;
                            if (newElement) {
                                this.replaceWith(newElement);
                            }
                        }
                    }
                } else {
                    // Special handling for Livewire error modals
                    if (this.id === 'livewire-error') {
                        errorModalAttempts++;
                        
                        if (errorModalAttempts <= maxErrorModalAttempts) {
                            console.warn(`Livewire error modal attempt ${errorModalAttempts}/${maxErrorModalAttempts}`);
                            
                            if (typeof value === 'string') {
                                // Create a new element and append it to body
                                const template = document.createElement('template');
                                template.innerHTML = value.trim();
                                const newElement = template.content.firstChild;
                                if (newElement) {
                                    // Remove any existing error modal
                                    const existing = document.getElementById('livewire-error');
                                    if (existing && existing !== this) {
                                        existing.remove();
                                    }
                                    // Append the new error modal to body
                                    document.body.appendChild(newElement);
                                    
                                    // Reset counter on successful append
                                    errorModalAttempts = 0;
                                }
                            }
                        } else {
                            console.error('Too many Livewire error modal attempts. Stopping to prevent infinite loop.');
                            // Reset counter after some time
                            setTimeout(() => { errorModalAttempts = 0; }, 5000);
                        }
                    } else {
                        // Other elements - just log once
                        console.warn('Attempted to set outerHTML on element with no parent:', this);
                    }
                }
            },
            configurable: true
        });
    }
    
    // Also fix any modal-specific issues
    document.addEventListener('DOMContentLoaded', function() {
        // Ensure modals are properly cleaned up on close
        window.addEventListener('hidden.bs.modal', function(e) {
            // Clean up any orphaned modal elements
            const orphanedModals = document.querySelectorAll('.modal:not([data-bs-backdrop])');
            orphanedModals.forEach(modal => {
                if (!modal.parentNode) {
                    console.warn('Removing orphaned modal:', modal);
                    modal.remove();
                }
            });
        });
        
        // Fix for Livewire modal cleanup
        if (window.Livewire) {
            Livewire.hook('message.processed', (message, component) => {
                // Clean up any detached modal elements after Livewire updates
                const detachedElements = document.querySelectorAll('[wire\\:id]:not(:empty)');
                detachedElements.forEach(el => {
                    if (!el.parentNode && el.classList.contains('modal')) {
                        console.warn('Cleaning up detached Livewire modal:', el);
                        el.remove();
                    }
                });
            });
            
            // Debug Livewire errors
            Livewire.hook('message.failed', (message, component) => {
                console.error('Livewire request failed:', message);
            });
            
            // Intercept Livewire error display when it's available
            const setupLivewireErrorHandler = () => {
                if (window.livewire && window.livewire.onError) {
                    const originalOnError = window.livewire.onError;
                    window.livewire.onError = function(message) {
                        console.error('Livewire error:', message);
                        // Call original handler but catch any errors
                        try {
                            return originalOnError.call(this, message);
                        } catch (e) {
                            console.warn('Error displaying Livewire error modal:', e);
                            // Display error in a simpler way
                            if (message && message.response && message.response.data) {
                                console.error('Livewire Error Details:', message.response.data);
                            }
                        }
                    };
                }
            };
            
            // Try to set up error handler immediately
            setupLivewireErrorHandler();
            
            // Also try after Livewire initializes
            Livewire.hook('init', () => {
                setupLivewireErrorHandler();
            });
        }
    });
})();