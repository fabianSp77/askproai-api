/**
 * Enhanced Flowbite Component Initialization
 * Ensures all Flowbite components are properly initialized
 * including dynamically loaded content
 */

// Debug mode - set to false in production
const DEBUG_MODE = false;

function log(message, data = null) {
    if (!DEBUG_MODE) return;
    if (data) {
        console.log(`[Flowbite Init] ${message}`, data);
    } else {
        console.log(`[Flowbite Init] ${message}`);
    }
}

/**
 * Initialize all Flowbite components
 */
function initializeFlowbiteComponents() {
    log('Starting Flowbite component initialization');
    
    try {
        // Check if Flowbite is available
        if (typeof initFlowbite !== 'undefined') {
            initFlowbite();
            log('Flowbite initialized successfully');
            
            // Count initialized components
            const components = {
                modals: document.querySelectorAll('[data-modal-toggle]').length,
                dropdowns: document.querySelectorAll('[data-dropdown-toggle]').length,
                tooltips: document.querySelectorAll('[data-tooltip-target]').length,
                popovers: document.querySelectorAll('[data-popover-target]').length,
                tabs: document.querySelectorAll('[data-tabs-toggle]').length,
                accordions: document.querySelectorAll('[data-accordion-target]').length,
                carousels: document.querySelectorAll('[data-carousel]').length,
                dismissibles: document.querySelectorAll('[data-dismiss-target]').length,
                collapses: document.querySelectorAll('[data-collapse-toggle]').length
            };
            
            log('Component counts:', components);
            
            // Initialize specific components if needed
            if (typeof initModals !== 'undefined') initModals();
            if (typeof initDropdowns !== 'undefined') initDropdowns();
            if (typeof initTooltips !== 'undefined') initTooltips();
            if (typeof initPopovers !== 'undefined') initPopovers();
            if (typeof initTabs !== 'undefined') initTabs();
            if (typeof initAccordions !== 'undefined') initAccordions();
            if (typeof initCarousels !== 'undefined') initCarousels();
            if (typeof initDismisses !== 'undefined') initDismisses();
            if (typeof initCollapses !== 'undefined') initCollapses();
            
        } else {
            log('Warning: Flowbite not found, retrying in 100ms');
            setTimeout(initializeFlowbiteComponents, 100);
        }
    } catch (error) {
        log('Error initializing Flowbite:', error);
    }
}

/**
 * Initialize Alpine.js components
 */
function initializeAlpineComponents() {
    log('Starting Alpine.js component initialization');
    
    try {
        if (typeof Alpine !== 'undefined') {
            // Check if Alpine is already started
            if (!Alpine.version) {
                Alpine.start();
                log('Alpine.js started successfully');
            } else {
                log('Alpine.js already running:', Alpine.version);
            }
            
            // Count Alpine components
            const alpineComponents = document.querySelectorAll('[x-data]').length;
            log(`Found ${alpineComponents} Alpine.js components`);
            
        } else {
            log('Warning: Alpine.js not found, retrying in 100ms');
            setTimeout(initializeAlpineComponents, 100);
        }
    } catch (error) {
        log('Error initializing Alpine.js:', error);
    }
}

/**
 * Initialize all interactive components
 */
function initializeAllComponents() {
    log('=== Starting Full Component Initialization ===');
    
    // Initialize Flowbite
    initializeFlowbiteComponents();
    
    // Initialize Alpine.js
    initializeAlpineComponents();
    
    // Re-initialize on dynamic content changes
    observeDynamicContent();
    
    log('=== Component Initialization Complete ===');
}

/**
 * Observe DOM for dynamic content and re-initialize
 */
function observeDynamicContent() {
    // Create observer for dynamic content
    const observer = new MutationObserver((mutations) => {
        let shouldReinit = false;
        
        mutations.forEach((mutation) => {
            if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        // Check for Flowbite components
                        if (node.querySelector && (
                            node.querySelector('[data-modal-toggle]') ||
                            node.querySelector('[data-dropdown-toggle]') ||
                            node.querySelector('[x-data]')
                        )) {
                            shouldReinit = true;
                        }
                    }
                });
            }
        });
        
        if (shouldReinit) {
            log('Dynamic content detected, re-initializing components');
            setTimeout(() => {
                initializeFlowbiteComponents();
                initializeAlpineComponents();
            }, 100);
        }
    });
    
    // Start observing
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    log('Dynamic content observer started');
}

/**
 * Wait for DOM and initialize
 */
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeAllComponents);
} else {
    // DOM already loaded
    initializeAllComponents();
}

// Also initialize on Alpine init event
window.addEventListener('alpine:init', () => {
    log('Alpine init event fired');
});

// Initialize when Flowbite loads
window.addEventListener('load', () => {
    log('Window load event fired');
    setTimeout(initializeAllComponents, 100);
});

// Export for manual initialization
window.reinitFlowbite = initializeAllComponents;

log('Flowbite initialization script loaded');