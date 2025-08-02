/**
 * Unified Portal System
 * 
 * Consolidates all UI interactions into a clean, maintainable system
 * Works with Filament 3.x, Alpine.js, and Livewire
 */

import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import persist from '@alpinejs/persist';

// Register Alpine plugins
Alpine.plugin(focus);
Alpine.plugin(persist);

class UnifiedPortalSystem {
    constructor() {
        this.initialized = false;
        this.components = new Map();
        this.init();
    }

    init() {
        if (this.initialized) return;
        
        // Setup Alpine stores
        this.setupAlpineStores();
        
        // Initialize components
        this.initializeNavigation();
        this.initializeDropdowns();
        this.initializeTables();
        this.initializeForms();
        this.initializeModals();
        
        // Setup global event handlers
        this.setupEventHandlers();
        
        // Initialize Alpine
        this.initializeAlpine();
        
        this.initialized = true;
    }

    setupAlpineStores() {
        // Navigation store
        Alpine.store('navigation', {
            sidebarOpen: Alpine.$persist(false).as('sidebar-open'),
            
            toggleSidebar() {
                this.sidebarOpen = !this.sidebarOpen;
            },
            
            closeSidebar() {
                this.sidebarOpen = false;
            }
        });

        // Theme store
        Alpine.store('theme', {
            darkMode: Alpine.$persist(false).as('dark-mode'),
            
            toggleDarkMode() {
                this.darkMode = !this.darkMode;
                document.documentElement.classList.toggle('dark', this.darkMode);
            }
        });

        // UI preferences
        Alpine.store('preferences', {
            compactMode: Alpine.$persist(false).as('compact-mode'),
            animations: Alpine.$persist(true).as('animations-enabled'),
            
            toggleCompactMode() {
                this.compactMode = !this.compactMode;
                document.body.classList.toggle('compact', this.compactMode);
            },
            
            toggleAnimations() {
                this.animations = !this.animations;
                document.body.classList.toggle('no-animations', !this.animations);
            }
        });
    }

    initializeNavigation() {
        // Mobile navigation handler
        Alpine.data('mobileNav', () => ({
            open: false,
            
            init() {
                // Close on escape
                this.$watch('open', value => {
                    if (value) {
                        document.addEventListener('keydown', this.handleEscape);
                    } else {
                        document.removeEventListener('keydown', this.handleEscape);
                    }
                });
            },
            
            handleEscape(e) {
                if (e.key === 'Escape') {
                    this.open = false;
                }
            },
            
            toggle() {
                this.open = !this.open;
            }
        }));

        // Sidebar component
        Alpine.data('sidebar', () => ({
            expanded: Alpine.$persist(true).as('sidebar-expanded'),
            
            toggle() {
                this.expanded = !this.expanded;
            }
        }));
    }

    initializeDropdowns() {
        // Dropdown component with improved accessibility
        Alpine.data('dropdown', () => ({
            open: false,
            focusedIndex: -1,
            items: [],
            
            init() {
                this.items = this.$el.querySelectorAll('[role="menuitem"]');
            },
            
            toggle() {
                this.open = !this.open;
                if (this.open) {
                    this.$nextTick(() => {
                        this.focusedIndex = 0;
                        this.items[0]?.focus();
                    });
                }
            },
            
            close() {
                this.open = false;
                this.focusedIndex = -1;
            },
            
            focusNext() {
                this.focusedIndex = (this.focusedIndex + 1) % this.items.length;
                this.items[this.focusedIndex]?.focus();
            },
            
            focusPrevious() {
                this.focusedIndex = this.focusedIndex <= 0 
                    ? this.items.length - 1 
                    : this.focusedIndex - 1;
                this.items[this.focusedIndex]?.focus();
            },
            
            handleKeydown(e) {
                switch(e.key) {
                    case 'Escape':
                        this.close();
                        break;
                    case 'ArrowDown':
                        e.preventDefault();
                        this.focusNext();
                        break;
                    case 'ArrowUp':
                        e.preventDefault();
                        this.focusPrevious();
                        break;
                }
            }
        }));
    }

    initializeTables() {
        // Responsive table handler
        Alpine.data('responsiveTable', () => ({
            isScrollable: false,
            scrollPosition: 0,
            
            init() {
                this.checkScrollable();
                window.addEventListener('resize', () => this.checkScrollable());
                
                // Add scroll indicators
                this.$el.addEventListener('scroll', () => {
                    this.scrollPosition = this.$el.scrollLeft;
                });
            },
            
            checkScrollable() {
                const table = this.$el.querySelector('table');
                if (table) {
                    this.isScrollable = table.scrollWidth > this.$el.clientWidth;
                }
            },
            
            scrollLeft() {
                this.$el.scrollBy({ left: -200, behavior: 'smooth' });
            },
            
            scrollRight() {
                this.$el.scrollBy({ left: 200, behavior: 'smooth' });
            }
        }));
    }

    initializeForms() {
        // Form validation component
        Alpine.data('formValidation', () => ({
            errors: {},
            touched: {},
            
            validateField(field, rules) {
                this.touched[field] = true;
                this.errors[field] = [];
                
                // Simple validation rules
                const value = this.$el.querySelector(`[name="${field}"]`)?.value;
                
                if (rules.required && !value) {
                    this.errors[field].push('This field is required');
                }
                
                if (rules.email && value && !this.isValidEmail(value)) {
                    this.errors[field].push('Please enter a valid email');
                }
                
                if (rules.minLength && value && value.length < rules.minLength) {
                    this.errors[field].push(`Minimum length is ${rules.minLength}`);
                }
            },
            
            isValidEmail(email) {
                return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            },
            
            hasError(field) {
                return this.touched[field] && this.errors[field]?.length > 0;
            },
            
            getError(field) {
                return this.errors[field]?.[0] || '';
            }
        }));
    }

    initializeModals() {
        // Modal component
        Alpine.data('modal', () => ({
            open: false,
            
            show() {
                this.open = true;
                document.body.style.overflow = 'hidden';
                this.$nextTick(() => {
                    this.$refs.content?.focus();
                });
            },
            
            hide() {
                this.open = false;
                document.body.style.overflow = '';
            },
            
            handleEscape(e) {
                if (e.key === 'Escape' && this.open) {
                    this.hide();
                }
            }
        }));
    }

    setupEventHandlers() {
        // Global click handler for closing dropdowns
        document.addEventListener('click', (e) => {
            if (!e.target.closest('[x-data*="dropdown"]')) {
                // Close all dropdowns
                document.querySelectorAll('[x-data*="dropdown"]').forEach(el => {
                    el.__x?.close?.();
                });
            }
        });

        // Handle browser back button for modals
        window.addEventListener('popstate', () => {
            document.querySelectorAll('[x-data*="modal"]').forEach(el => {
                el.__x?.hide?.();
            });
        });

        // Improved Livewire error handling
        if (window.Livewire) {
            window.Livewire.on('notify', ({ type, message }) => {
                this.showNotification(type, message);
            });

            // Handle session timeouts
            window.Livewire.onError((error) => {
                if (error.status === 419) {
                    this.handleSessionTimeout();
                    return false; // Prevent default error handling
                }
            });
        }
    }

    initializeAlpine() {
        // Start Alpine when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                Alpine.start();
            });
        } else {
            Alpine.start();
        }
    }

    showNotification(type, message) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification--${type}`;
        notification.textContent = message;
        
        // Add to container
        let container = document.querySelector('.notification-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'notification-container';
            document.body.appendChild(container);
        }
        
        container.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }

    handleSessionTimeout() {
        this.showNotification('error', 'Your session has expired. Refreshing...');
        setTimeout(() => {
            window.location.reload();
        }, 2000);
    }

    // Public API
    register(name, component) {
        this.components.set(name, component);
    }

    get(name) {
        return this.components.get(name);
    }
}

// Create and export singleton instance
const portalSystem = new UnifiedPortalSystem();

// Make available globally
window.portalSystem = portalSystem;
window.Alpine = Alpine;

export default portalSystem;