// Debug script to understand loading sequence
(function() {
    'use strict';
    
    const log = (msg, data = null) => {
        const timestamp = new Date().toISOString();
        if (data) {
            //console.log(`[${timestamp}] ${msg}`, data);
        } else {
            //console.log(`[${timestamp}] ${msg}`);
        }
    };
    
    // Track when various frameworks become available
    const checkFrameworks = () => {
        return {
            Alpine: typeof window.Alpine !== 'undefined',
            AlpineVersion: window.Alpine?.version || 'N/A',
            Livewire: typeof window.Livewire !== 'undefined',
            livewire: typeof window.livewire !== 'undefined',
            jQuery: typeof window.$ !== 'undefined',
            axios: typeof window.axios !== 'undefined'
        };
    };
    
    // Initial state
    log('Page loading started', {
        readyState: document.readyState,
        frameworks: checkFrameworks()
    });
    
    // Monitor DOM ready
    document.addEventListener('DOMContentLoaded', () => {
        log('DOMContentLoaded event fired', {
            frameworks: checkFrameworks()
        });
    });
    
    // Monitor window load
    window.addEventListener('load', () => {
        log('Window load event fired', {
            frameworks: checkFrameworks()
        });
    });
    
    // Monitor Alpine events
    document.addEventListener('alpine:init', () => {
        log('Alpine init event fired', {
            frameworks: checkFrameworks()
        });
    });
    
    document.addEventListener('alpine:initialized', () => {
        log('Alpine initialized event fired', {
            frameworks: checkFrameworks()
        });
    });
    
    // Monitor Livewire events
    document.addEventListener('livewire:init', () => {
        log('Livewire init event fired', {
            frameworks: checkFrameworks()
        });
    });
    
    document.addEventListener('livewire:load', () => {
        log('Livewire load event fired', {
            frameworks: checkFrameworks()
        });
    });
    
    document.addEventListener('livewire:initialized', () => {
        log('Livewire initialized event fired', {
            frameworks: checkFrameworks()
        });
    });
    
    // Check periodically
    let checkCount = 0;
    const checkInterval = setInterval(() => {
        checkCount++;
        const frameworks = checkFrameworks();
        
        log(`Periodic check #${checkCount}`, frameworks);
        
        // Stop after 20 checks (10 seconds)
        if (checkCount >= 20) {
            clearInterval(checkInterval);
            log('Stopped periodic checks');
        }
        
        // Or stop when everything is loaded
        if (frameworks.Alpine && (frameworks.Livewire || frameworks.livewire)) {
            clearInterval(checkInterval);
            log('All frameworks detected, stopped checking');
        }
    }, 500);
    
    // Expose debug function
    window.debugLoadingSequence = {
        check: () => {
            const frameworks = checkFrameworks();
            log('Manual check', frameworks);
            return frameworks;
        },
        events: []
    };
    
})();