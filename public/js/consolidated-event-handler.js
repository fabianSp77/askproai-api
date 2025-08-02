/**
 * Consolidated Event Handler for AskProAI
 * 
 * Solves button click issues without cloning elements or adding duplicate handlers
 * Compatible with Livewire v3, Alpine.js, and Filament v3
 */

(function() {
    'use strict';
    
    const AskProEventHandler = {
        initialized: false,
        debugMode: window.location.hostname === 'localhost',
        
        log(...args) {
            if (this.debugMode) {
                console.log('[AskProEventHandler]', ...args);
            }
        },
        
        init() {
            if (this.initialized) return;
            
            this.log('Initializing...');
            
            // Wait for all frameworks to be ready
            this.whenReady(() => {
                this.setupEventDelegation();
                this.fixExistingElements();
                this.initialized = true;
                this.log('Initialization complete');
            });
        },
        
        whenReady(callback) {
            let checks = 0;
            const maxChecks = 50; // 5 seconds max wait
            
            const checkFrameworks = () => {
                checks++;
                
                const livewireReady = typeof window.Livewire !== 'undefined';
                const alpineReady = typeof window.Alpine !== 'undefined';
                const domReady = document.readyState !== 'loading';
                
                if (livewireReady && alpineReady && domReady) {
                    this.log('All frameworks ready');
                    callback();
                } else if (checks < maxChecks) {
                    if (!livewireReady) this.log('Waiting for Livewire...');
                    if (!alpineReady) this.log('Waiting for Alpine...');
                    if (!domReady) this.log('Waiting for DOM...');
                    setTimeout(checkFrameworks, 100);
                } else {
                    this.log('Timeout waiting for frameworks, proceeding anyway');
                    callback();
                }
            };
            
            checkFrameworks();
        },
        
        setupEventDelegation() {
            // Use event delegation to avoid duplicate handlers
            document.addEventListener('click', (e) => {
                const target = e.target.closest('button, [wire\\:click], [x-on\\:click], [\\@click]');
                if (!target) return;
                
                // Check if this is a Livewire element
                if (target.hasAttribute('wire:click')) {
                    this.handleLivewireClick(e, target);
                }
                
                // Check if this is an Alpine element
                if (target.hasAttribute('x-on:click') || target.hasAttribute('@click')) {
                    this.handleAlpineClick(e, target);
                }
                
                // Handle form submit buttons
                if (target.type === 'submit' && !target.hasAttribute('wire:click')) {
                    this.handleFormSubmit(e, target);
                }
            }, true); // Use capture phase to intercept early
        },
        
        handleLivewireClick(e, element) {
            // Ensure Livewire processes the click
            if (!element.hasAttribute('data-click-handled')) {
                element.setAttribute('data-click-handled', 'true');
                
                // Remove the flag after a short delay
                setTimeout(() => {
                    element.removeAttribute('data-click-handled');
                }, 300);
                
                this.log('Livewire click:', element.getAttribute('wire:click'));
            }
        },
        
        handleAlpineClick(e, element) {
            // Alpine handles its own clicks, just log for debugging
            this.log('Alpine click:', element.getAttribute('x-on:click') || element.getAttribute('@click'));
        },
        
        handleFormSubmit(e, button) {
            const form = button.closest('form');
            if (!form) return;
            
            // Prevent double submission
            if (button.hasAttribute('data-submitting')) {
                e.preventDefault();
                return;
            }
            
            // For non-Livewire forms, add loading state
            if (!form.hasAttribute('wire:submit')) {
                button.setAttribute('data-submitting', 'true');
                
                // Add visual feedback
                const originalContent = button.innerHTML;
                button.disabled = true;
                button.innerHTML = this.getLoadingTemplate();
                
                // Reset after timeout (in case of validation errors)
                setTimeout(() => {
                    button.removeAttribute('data-submitting');
                    button.disabled = false;
                    button.innerHTML = originalContent;
                }, 5000);
            }
        },
        
        fixExistingElements() {
            // Fix any elements that might have issues
            
            // Remove any duplicate event listeners by using data attributes
            document.querySelectorAll('[wire\\:click]').forEach(el => {
                if (!el.hasAttribute('data-askpro-fixed')) {
                    el.setAttribute('data-askpro-fixed', 'true');
                    el.style.cursor = 'pointer';
                }
            });
            
            // Ensure submit buttons are properly styled
            document.querySelectorAll('button[type="submit"]').forEach(button => {
                if (!button.hasAttribute('data-askpro-fixed')) {
                    button.setAttribute('data-askpro-fixed', 'true');
                    
                    // Ensure button is clickable
                    button.style.pointerEvents = 'auto';
                    button.style.cursor = 'pointer';
                }
            });
        },
        
        getLoadingTemplate() {
            return `
                <span class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Verarbeitung...</span>
                </span>
            `;
        },
        
        // Hook into Livewire lifecycle
        setupLivewireHooks() {
            if (window.Livewire) {
                // Re-fix elements after Livewire updates
                Livewire.hook('message.processed', () => {
                    setTimeout(() => this.fixExistingElements(), 50);
                });
                
                // Log navigation for debugging
                Livewire.hook('navigate', () => {
                    this.log('Livewire navigation occurred');
                    setTimeout(() => this.fixExistingElements(), 100);
                });
            }
        },
        
        // Hook into Alpine lifecycle
        setupAlpineHooks() {
            if (window.Alpine) {
                document.addEventListener('alpine:initialized', () => {
                    this.log('Alpine initialized');
                    this.fixExistingElements();
                });
            }
        }
    };
    
    // Register with AskProAI if available
    if (window.AskProAI) {
        window.AskProAI.registerModule('EventHandler', AskProEventHandler);
    } else {
        // Standalone initialization
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => AskProEventHandler.init());
        } else {
            AskProEventHandler.init();
        }
        
        // Setup framework hooks when available
        if (window.Livewire) {
            AskProEventHandler.setupLivewireHooks();
        } else {
            document.addEventListener('livewire:load', () => {
                AskProEventHandler.setupLivewireHooks();
            });
        }
        
        AskProEventHandler.setupAlpineHooks();
    }
    
    // Export for debugging
    window.AskProEventHandler = AskProEventHandler;
    
})();