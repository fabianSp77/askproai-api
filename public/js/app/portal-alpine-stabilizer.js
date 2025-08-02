/**
 * Business Portal Alpine.js Stabilizer
 * Ensures robust Alpine.js initialization for the Business Portal
 */

(function() {
    'use strict';
    
    const PortalAlpineStabilizer = {
        initialized: false,
        retryCount: 0,
        maxRetries: 5,
        components: new Map(),
        
        /**
         * Initialize the stabilizer
         */
        init() {
            if (this.initialized) return;
            
            console.log('🚀 Initializing Portal Alpine Stabilizer');
            
            // Wait for Alpine to be available
            this.waitForAlpine().then(() => {
                this.setupAlpineHooks();
                this.registerPortalComponents();
                this.fixExistingComponents();
                this.setupDynamicContentHandler();
                this.setupErrorRecovery();
                this.initialized = true;
                console.log('✅ Portal Alpine Stabilizer initialized');
            }).catch(error => {
                console.error('❌ Failed to initialize Alpine Stabilizer:', error);
            });
        },
        
        /**
         * Wait for Alpine.js to be available
         */
        waitForAlpine() {
            return new Promise((resolve, reject) => {
                const checkAlpine = () => {
                    if (window.Alpine) {
                        resolve();
                    } else if (this.retryCount < this.maxRetries) {
                        this.retryCount++;
                        setTimeout(checkAlpine, 100);
                    } else {
                        reject(new Error('Alpine.js not found after maximum retries'));
                    }
                };
                checkAlpine();
            });
        },
        
        /**
         * Setup Alpine lifecycle hooks
         */
        setupAlpineHooks() {
            // Hook into Alpine's lifecycle
            if (window.Alpine.version && window.Alpine.version.startsWith('3')) {
                // Alpine 3.x hooks
                document.addEventListener('alpine:init', () => {
                    console.log('🎯 Alpine init event detected');
                });
                
                document.addEventListener('alpine:initialized', () => {
                    console.log('🎯 Alpine initialized event detected');
                    this.validateAllComponents();
                });
            }
        },
        
        /**
         * Register Business Portal specific components
         */
        registerPortalComponents() {
            // Call Detail Page Components
            this.registerComponent('callDetailPage', () => ({
                translating: false,
                translated: false,
                translatedText: '',
                originalText: '',
                showingNotes: false,
                expandedSections: Alpine.$persist({
                    customer: true,
                    transcript: true,
                    activity: true
                }),
                
                async translateSummary() {
                    // Translation logic handled by existing code
                },
                
                toggleSection(section) {
                    this.expandedSections[section] = !this.expandedSections[section];
                },
                
                copyToClipboard(text, button) {
                    navigator.clipboard.writeText(text).then(() => {
                        const originalText = button.innerText;
                        button.innerText = '✓ Kopiert';
                        button.classList.add('bg-green-100', 'text-green-800');
                        setTimeout(() => {
                            button.innerText = originalText;
                            button.classList.remove('bg-green-100', 'text-green-800');
                        }, 2000);
                    });
                }
            }));
            
            // Call List Components
            this.registerComponent('callsTable', () => ({
                selectedCalls: [],
                showBulkExportModal: false,
                sortField: 'created_at',
                sortDirection: 'desc',
                filters: Alpine.$persist({
                    status: '',
                    dateFrom: '',
                    dateTo: '',
                    search: ''
                }),
                
                toggleCallSelection(callId) {
                    const index = this.selectedCalls.indexOf(callId);
                    if (index > -1) {
                        this.selectedCalls.splice(index, 1);
                    } else {
                        this.selectedCalls.push(callId);
                    }
                },
                
                selectAllCalls(callIds) {
                    if (this.selectedCalls.length === callIds.length) {
                        this.selectedCalls = [];
                    } else {
                        this.selectedCalls = [...callIds];
                    }
                },
                
                applyFilters() {
                    // Trigger form submission
                    this.$el.closest('form').submit();
                },
                
                clearFilters() {
                    this.filters = {
                        status: '',
                        dateFrom: '',
                        dateTo: '',
                        search: ''
                    };
                    this.applyFilters();
                }
            }));
            
            // Dropdown Components
            this.registerComponent('portalDropdown', () => ({
                open: false,
                
                toggle() {
                    this.open = !this.open;
                },
                
                close() {
                    this.open = false;
                },
                
                init() {
                    // Close dropdown when clicking outside
                    this.$watch('open', (value) => {
                        if (value) {
                            this.$nextTick(() => {
                                document.addEventListener('click', this.handleClickOutside);
                            });
                        } else {
                            document.removeEventListener('click', this.handleClickOutside);
                        }
                    });
                },
                
                handleClickOutside: function(event) {
                    if (!this.$el.contains(event.target)) {
                        this.close();
                    }
                }.bind(this)
            }));
            
            // Modal Components
            this.registerComponent('portalModal', () => ({
                show: false,
                
                open() {
                    this.show = true;
                    document.body.style.overflow = 'hidden';
                },
                
                close() {
                    this.show = false;
                    document.body.style.overflow = '';
                },
                
                handleEscape(event) {
                    if (event.key === 'Escape' && this.show) {
                        this.close();
                    }
                }
            }));
            
            // Form Components
            this.registerComponent('portalForm', () => ({
                processing: false,
                errors: {},
                
                submitForm() {
                    this.processing = true;
                    this.errors = {};
                    // Form submission handled by form action
                },
                
                hasError(field) {
                    return this.errors.hasOwnProperty(field);
                },
                
                getError(field) {
                    return this.errors[field] ? this.errors[field][0] : '';
                }
            }));
        },
        
        /**
         * Register a component with Alpine
         */
        registerComponent(name, factory) {
            if (window.Alpine && window.Alpine.data) {
                window.Alpine.data(name, factory);
                this.components.set(name, factory);
                console.log(`✅ Registered component: ${name}`);
            }
        },
        
        /**
         * Fix components that may have been rendered before Alpine initialized
         */
        fixExistingComponents() {
            // Find all x-data elements
            const elements = document.querySelectorAll('[x-data]');
            let fixed = 0;
            
            elements.forEach(el => {
                if (!el._x_dataStack) {
                    try {
                        // Re-initialize the component
                        window.Alpine.initTree(el);
                        fixed++;
                    } catch (error) {
                        console.warn(`Failed to initialize component:`, error);
                    }
                }
            });
            
            if (fixed > 0) {
                console.log(`🔧 Fixed ${fixed} uninitialized components`);
            }
        },
        
        /**
         * Handle dynamically added content
         */
        setupDynamicContentHandler() {
            // Use MutationObserver to detect new Alpine components
            const observer = new MutationObserver((mutations) => {
                let hasNewComponents = false;
                
                mutations.forEach(mutation => {
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeType === 1 && node.hasAttribute && node.hasAttribute('x-data')) {
                            hasNewComponents = true;
                        }
                        // Also check children
                        if (node.querySelectorAll) {
                            const childComponents = node.querySelectorAll('[x-data]');
                            if (childComponents.length > 0) {
                                hasNewComponents = true;
                            }
                        }
                    });
                });
                
                if (hasNewComponents) {
                    // Debounce to avoid multiple initializations
                    clearTimeout(this.reinitTimeout);
                    this.reinitTimeout = setTimeout(() => {
                        this.fixExistingComponents();
                    }, 100);
                }
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
            
            console.log('👀 Dynamic content observer active');
        },
        
        /**
         * Setup error recovery mechanisms
         */
        setupErrorRecovery() {
            // Global error handler for Alpine errors
            window.addEventListener('error', (event) => {
                if (event.message && (event.message.includes('Alpine') || event.message.includes('x-data'))) {
                    console.error('Alpine error detected:', event.message);
                    
                    // Try to recover
                    setTimeout(() => {
                        this.fixExistingComponents();
                    }, 500);
                }
            });
            
            // Intercept console errors
            const originalError = console.error;
            console.error = (...args) => {
                const message = args.join(' ');
                if (message.includes('Alpine Expression Error')) {
                    console.warn('Alpine expression error detected, attempting recovery...');
                    setTimeout(() => {
                        this.fixExistingComponents();
                    }, 500);
                }
                originalError.apply(console, args);
            };
        },
        
        /**
         * Validate all registered components
         */
        validateAllComponents() {
            const results = {
                total: 0,
                valid: 0,
                invalid: 0,
                errors: []
            };
            
            document.querySelectorAll('[x-data]').forEach(el => {
                results.total++;
                
                if (el._x_dataStack && el._x_dataStack.length > 0) {
                    results.valid++;
                } else {
                    results.invalid++;
                    results.errors.push({
                        element: el,
                        data: el.getAttribute('x-data')
                    });
                }
            });
            
            console.log('🔍 Component validation results:', results);
            
            if (results.invalid > 0) {
                console.warn(`Found ${results.invalid} invalid components, attempting to fix...`);
                this.fixExistingComponents();
            }
        },
        
        /**
         * Public API for manual fixes
         */
        fixComponent(selector) {
            const elements = document.querySelectorAll(selector);
            let fixed = 0;
            
            elements.forEach(el => {
                if (el.hasAttribute('x-data') && !el._x_dataStack) {
                    try {
                        window.Alpine.initTree(el);
                        fixed++;
                    } catch (error) {
                        console.error(`Failed to fix component:`, error);
                    }
                }
            });
            
            console.log(`Fixed ${fixed} components matching "${selector}"`);
            return fixed;
        },
        
        /**
         * Force refresh all components
         */
        refreshAll() {
            console.log('🔄 Refreshing all Alpine components...');
            this.fixExistingComponents();
            this.validateAllComponents();
        }
    };
    
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            PortalAlpineStabilizer.init();
        });
    } else {
        // DOM already loaded
        setTimeout(() => {
            PortalAlpineStabilizer.init();
        }, 100);
    }
    
    // Expose to global scope for debugging
    window.PortalAlpineStabilizer = PortalAlpineStabilizer;
    
})();