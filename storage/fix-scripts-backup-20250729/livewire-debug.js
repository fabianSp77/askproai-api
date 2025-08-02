/**
 * Livewire Debug Helper
 * Helps identify what's causing Livewire errors
 */
(function() {
    'use strict';
    
    console.log('🔍 Livewire Debug Helper Active');
    
    // Wait for Livewire to be available
    function waitForLivewire(callback) {
        if (window.Livewire) {
            callback();
        } else {
            setTimeout(() => waitForLivewire(callback), 100);
        }
    }
    
    waitForLivewire(() => {
        // Log all Livewire messages
        Livewire.hook('message.sent', (message, component) => {
            console.log('📤 Livewire Request:', {
                component: component.name,
                fingerprint: component.fingerprint,
                updates: message.updates,
                calls: message.calls
            });
        });
        
        Livewire.hook('message.failed', (message, component) => {
            console.error('❌ Livewire Request Failed:', {
                component: component.name,
                error: message,
                response: message.response
            });
        });
        
        Livewire.hook('message.received', (message, component) => {
            if (message.response && message.response.serverMemo && message.response.serverMemo.errors) {
                console.error('⚠️ Livewire Validation Errors:', message.response.serverMemo.errors);
            }
        });
        
        // Intercept fetch to log failed requests
        const originalFetch = window.fetch;
        window.fetch = function(...args) {
            return originalFetch.apply(this, args)
                .then(response => {
                    if (!response.ok && args[0].includes('livewire')) {
                        console.error('🚨 Failed Livewire Fetch:', {
                            url: args[0],
                            status: response.status,
                            statusText: response.statusText
                        });
                        
                        // Try to get error details
                        response.clone().text().then(text => {
                            try {
                                const data = JSON.parse(text);
                                console.error('Error Response:', data);
                            } catch (e) {
                                console.error('Error Response Text:', text);
                            }
                        });
                    }
                    return response;
                })
                .catch(error => {
                    if (args[0].includes('livewire')) {
                        console.error('🚨 Livewire Network Error:', {
                            url: args[0],
                            error: error.message
                        });
                    }
                    throw error;
                });
        };
        
        // Check for components with errors on page load
        document.addEventListener('DOMContentLoaded', () => {
            const livewireComponents = document.querySelectorAll('[wire\\:id]');
            console.log(`📊 Found ${livewireComponents.length} Livewire components on page`);
            
            livewireComponents.forEach(el => {
                const componentId = el.getAttribute('wire:id');
                const component = window.Livewire.find(componentId);
                if (component) {
                    console.log(`Component ${component.name} (${componentId}):`, {
                        data: component.data,
                        initialized: true
                    });
                }
            });
        });
    });
})();