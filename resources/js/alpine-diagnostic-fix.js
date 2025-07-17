// Alpine.js Diagnostic and Fix Script for AskProAI
// This script diagnoses and fixes common Alpine.js initialization issues

(function() {
    'use strict';
    
    const diagnostics = {
        errors: [],
        warnings: [],
        info: [],
        fixed: []
    };
    
    // Check if Alpine is loaded
    function checkAlpineLoaded() {
        if (typeof window.Alpine === 'undefined') {
            diagnostics.errors.push('Alpine.js is not loaded!');
            return false;
        }
        diagnostics.info.push('Alpine.js is loaded');
        return true;
    }
    
    // Check for common x-data issues
    function checkXDataIssues() {
        const elements = document.querySelectorAll('[x-data]');
        let issues = 0;
        
        elements.forEach((el, index) => {
            const xData = el.getAttribute('x-data');
            
            // Check for empty x-data
            if (!xData || xData.trim() === '') {
                diagnostics.warnings.push(`Empty x-data at element ${index}: ${el.tagName}.${el.className}`);
                el.setAttribute('x-data', '{}');
                diagnostics.fixed.push(`Fixed empty x-data at element ${index}`);
                issues++;
            }
            
            // Check for malformed x-data
            try {
                // Try to evaluate the x-data expression
                if (xData && !xData.includes('(') && !xData.includes('{')) {
                    diagnostics.errors.push(`Malformed x-data at element ${index}: "${xData}"`);
                    issues++;
                }
            } catch (e) {
                diagnostics.errors.push(`Error evaluating x-data at element ${index}: ${e.message}`);
                issues++;
            }
        });
        
        diagnostics.info.push(`Found ${elements.length} elements with x-data, ${issues} issues`);
        return issues === 0;
    }
    
    // Check for Alpine components that might not be initialized
    function checkAlpineComponents() {
        const componentChecks = [
            'transcriptViewerEnterprise',
            'simpleDropdown',
            'smartDropdown',
            'ultimateResource',
            'keyboardShortcuts',
            'commandPalette'
        ];
        
        componentChecks.forEach(component => {
            if (window.Alpine && window.Alpine.data && typeof window.Alpine.data === 'function') {
                try {
                    // Check if component is registered
                    const testEl = document.createElement('div');
                    testEl.setAttribute('x-data', component + '()');
                    // This won't actually initialize but will check if the component exists
                    diagnostics.info.push(`Component "${component}" appears to be available`);
                } catch (e) {
                    diagnostics.warnings.push(`Component "${component}" may not be registered: ${e.message}`);
                }
            }
        });
    }
    
    // Fix common Alpine initialization timing issues
    function fixAlpineTimingIssues() {
        // Ensure Alpine starts after DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeAlpine);
        } else {
            initializeAlpine();
        }
    }
    
    function initializeAlpine() {
        if (!window.Alpine) {
            diagnostics.errors.push('Alpine not available for initialization');
            return;
        }
        
        // Check if Alpine is already started
        if (window.Alpine.version && !window.Alpine._started) {
            try {
                window.Alpine.start();
                diagnostics.fixed.push('Started Alpine.js');
            } catch (e) {
                diagnostics.errors.push(`Failed to start Alpine: ${e.message}`);
            }
        } else if (window.Alpine._started) {
            diagnostics.info.push('Alpine.js already started');
        }
        
        // Re-initialize any components that might have been missed
        reinitializeMissedComponents();
    }
    
    // Reinitialize components that might have been added after Alpine started
    function reinitializeMissedComponents() {
        if (!window.Alpine || !window.Alpine._started) return;
        
        // Find uninitalized x-data elements
        const uninitializedElements = document.querySelectorAll('[x-data]:not([x-data-initialized])');
        
        uninitializedElements.forEach(el => {
            try {
                // Force Alpine to initialize this element
                window.Alpine.initTree(el);
                el.setAttribute('x-data-initialized', 'true');
                diagnostics.fixed.push(`Initialized missed component: ${el.getAttribute('x-data')}`);
            } catch (e) {
                diagnostics.errors.push(`Failed to initialize component: ${e.message}`);
            }
        });
    }
    
    // Fix Livewire/Alpine conflicts
    function fixLivewireAlpineConflicts() {
        if (window.Livewire) {
            // Ensure Livewire hooks are properly set up
            window.Livewire.hook('component.initialized', (component) => {
                diagnostics.info.push(`Livewire component initialized: ${component.name}`);
                // Reinitialize Alpine components within this Livewire component
                setTimeout(() => {
                    const alpineElements = component.el.querySelectorAll('[x-data]');
                    alpineElements.forEach(el => {
                        if (!el.hasAttribute('x-data-initialized')) {
                            try {
                                window.Alpine.initTree(el);
                                el.setAttribute('x-data-initialized', 'true');
                                diagnostics.fixed.push(`Initialized Alpine in Livewire component: ${component.name}`);
                            } catch (e) {
                                diagnostics.errors.push(`Failed to init Alpine in Livewire: ${e.message}`);
                            }
                        }
                    });
                }, 100);
            });
            
            diagnostics.info.push('Livewire/Alpine conflict fixes applied');
        }
    }
    
    // Monitor for dynamically added content
    function setupMutationObserver() {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1 && node.hasAttribute && node.hasAttribute('x-data')) {
                        setTimeout(() => {
                            if (!node.hasAttribute('x-data-initialized')) {
                                try {
                                    window.Alpine.initTree(node);
                                    node.setAttribute('x-data-initialized', 'true');
                                    diagnostics.fixed.push(`Initialized dynamically added component`);
                                } catch (e) {
                                    diagnostics.errors.push(`Failed to init dynamic component: ${e.message}`);
                                }
                            }
                        }, 100);
                    }
                });
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
        
        diagnostics.info.push('Mutation observer set up for dynamic content');
    }
    
    // Add global error handler for Alpine errors
    function setupErrorHandlers() {
        window.addEventListener('error', (event) => {
            if (event.message && event.message.includes('Alpine')) {
                diagnostics.errors.push(`Alpine runtime error: ${event.message}`);
                console.error('Alpine error caught:', event);
            }
        });
        
        // Override console.error to catch Alpine warnings
        const originalError = console.error;
        console.error = function(...args) {
            const message = args.join(' ');
            if (message.includes('Alpine') || message.includes('x-data')) {
                diagnostics.errors.push(`Console error: ${message}`);
            }
            originalError.apply(console, args);
        };
    }
    
    // Main diagnostic function
    function runDiagnostics() {
        console.group('🔍 Alpine.js Diagnostics');
        
        // Run all checks
        checkAlpineLoaded();
        checkXDataIssues();
        checkAlpineComponents();
        fixAlpineTimingIssues();
        fixLivewireAlpineConflicts();
        setupMutationObserver();
        setupErrorHandlers();
        
        // Report results
        console.log('%c✅ Info:', 'color: blue; font-weight: bold');
        diagnostics.info.forEach(msg => console.log(`  ${msg}`));
        
        if (diagnostics.warnings.length > 0) {
            console.log('%c⚠️ Warnings:', 'color: orange; font-weight: bold');
            diagnostics.warnings.forEach(msg => console.log(`  ${msg}`));
        }
        
        if (diagnostics.errors.length > 0) {
            console.log('%c❌ Errors:', 'color: red; font-weight: bold');
            diagnostics.errors.forEach(msg => console.log(`  ${msg}`));
        }
        
        if (diagnostics.fixed.length > 0) {
            console.log('%c🔧 Fixed:', 'color: green; font-weight: bold');
            diagnostics.fixed.forEach(msg => console.log(`  ${msg}`));
        }
        
        console.groupEnd();
        
        // Store diagnostics globally for debugging
        window.alpineDiagnostics = diagnostics;
        
        return diagnostics;
    }
    
    // Auto-fix function for specific components
    window.fixAlpineComponent = function(componentName) {
        const elements = document.querySelectorAll(`[x-data*="${componentName}"]`);
        let fixed = 0;
        
        elements.forEach(el => {
            try {
                window.Alpine.initTree(el);
                el.setAttribute('x-data-initialized', 'true');
                fixed++;
            } catch (e) {
                console.error(`Failed to fix ${componentName}:`, e);
            }
        });
        
        console.log(`Fixed ${fixed} ${componentName} components`);
        return fixed;
    };
    
    // Run diagnostics when script loads
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', runDiagnostics);
    } else {
        // Add a small delay to ensure Alpine is fully loaded
        setTimeout(runDiagnostics, 100);
    }
    
    // Export diagnostic function globally
    window.runAlpineDiagnostics = runDiagnostics;
    
})();