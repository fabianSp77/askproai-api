// Alpine.js Debug Helper
// console.log('🐞 Alpine Debug Helper Loading...');

// Function to check all Alpine components
window.debugAlpineComponents = function() {
    console.log('=== Alpine Component Status ===');
    
    const components = [
        'dateFilterDropdownEnhanced',
        'companyBranchSelect',
        'timeRangeFilter',
        'kpiFilters',
        'adminDropdown',
        'tableActions',
        'dashboardMetrics',
        'realtimeUpdates'
    ];
    
    components.forEach(comp => {
        if (typeof window[comp] === 'function') {
            console.log(`✅ ${comp} - Loaded`);
            try {
                const instance = window[comp]();
                console.log(`   Properties:`, Object.keys(instance));
            } catch (e) {
                console.error(`   ❌ Error creating instance:`, e.message);
            }
        } else {
            console.error(`❌ ${comp} - NOT FOUND`);
        }
    });
    
    // Check Alpine.js status
    if (window.Alpine) {
        console.log('✅ Alpine.js - Loaded');
        console.log('   Version:', Alpine.version);
    } else {
        console.error('❌ Alpine.js - NOT LOADED');
    }
    
    // Check Livewire status
    if (window.Livewire) {
        console.log('✅ Livewire - Loaded');
    } else {
        console.error('❌ Livewire - NOT LOADED');
    }
    
    console.log('=== End Debug Report ===');
};

// Auto-fix missing components
window.fixAlpineComponents = function() {
    console.log('🔧 Attempting to fix missing Alpine components...');
    
    // Check if alpine-components-fix.js needs to be reloaded
    if (typeof window.dateFilterDropdownEnhanced === 'undefined') {
        console.log('Reloading alpine-components-fix.js...');
        const script = document.createElement('script');
        script.src = '/js/alpine-components-fix.js?v=' + Date.now();
        script.onload = () => {
            console.log('✅ Components reloaded');
            window.debugAlpineComponents();
        };
        document.head.appendChild(script);
    }
};

// Monitor Alpine errors
document.addEventListener('alpine:init', () => {
    // console.log('🎿 Alpine.js initializing...');
    
    // Intercept Alpine errors
    const originalConsoleError = console.error;
    console.error = function(...args) {
        if (args[0] && args[0].toString().includes('Alpine Expression Error')) {
            console.warn('🚨 Alpine Expression Error Detected:', args);
            
            // Try to auto-fix
            const match = args[0].toString().match(/(\w+) is not defined/);
            if (match) {
                const missingComponent = match[1];
                console.log(`Attempting to define missing component: ${missingComponent}`);
                
                // Define a minimal fallback
                if (!window[missingComponent]) {
                    window[missingComponent] = () => ({
                        init() {
                            console.warn(`Fallback component ${missingComponent} initialized`);
                        }
                    });
                }
            }
        }
        originalConsoleError.apply(console, args);
    };
});

// Run debug on load
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        // console.log('Running initial Alpine component check...');
        // Auto-check disabled for production
        // window.debugAlpineComponents();
    }, 1000);
});

// console.log('✅ Alpine Debug Helper loaded');
// console.log('💡 Use debugAlpineComponents() to check component status');
// console.log('💡 Use fixAlpineComponents() to attempt auto-fix');

// Debug helpers are available: debugAlpineComponents() and fixAlpineComponents()