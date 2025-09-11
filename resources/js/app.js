import './bootstrap';
import Alpine from 'alpinejs';
import { initFlowbite, initModals, initDropdowns, initTabs, initTooltips } from 'flowbite';

// Initialize Alpine.js
window.Alpine = Alpine;
Alpine.start();

// Initialize Flowbite components on DOM ready
function initializeComponents() {
    // Initialize all Flowbite components
    initFlowbite();
    
    // Initialize specific components if needed
    if (document.querySelectorAll('[data-modal-toggle]').length) initModals();
    if (document.querySelectorAll('[data-dropdown-toggle]').length) initDropdowns();
    if (document.querySelectorAll('[data-tabs-toggle]').length) initTabs();
    if (document.querySelectorAll('[data-tooltip-target]').length) initTooltips();
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeComponents);
} else {
    initializeComponents();
}

// Re-initialize on dynamic content (Livewire/AJAX)
document.addEventListener('livewire:navigated', initializeComponents);
document.addEventListener('turbo:load', initializeComponents);

// Observe for dynamically added content
const observer = new MutationObserver((mutations) => {
    let shouldReinit = false;
    
    mutations.forEach((mutation) => {
        if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === Node.ELEMENT_NODE && node.querySelector) {
                    if (node.querySelector('[data-modal-toggle], [data-dropdown-toggle], [x-data]')) {
                        shouldReinit = true;
                    }
                }
            });
        }
    });
    
    if (shouldReinit) {
        setTimeout(initializeComponents, 100);
    }
});

// Start observing only after initial load
if (document.body) {
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
}

// Export for manual initialization if needed
window.reinitFlowbite = initializeComponents;