/**
 * AskProAI Reusable UI Component Library
 * World-class components for consistent UI/UX across the platform
 */

// Alpine.js component registry
document.addEventListener('alpine:init', () => {
    
    /**
     * StandardCard Component
     * A reusable card component with consistent styling
     */
    Alpine.data('standardCard', (config = {}) => ({
        title: config.title || '',
        description: config.description || '',
        status: config.status || 'default', // default, success, warning, danger
        actions: config.actions || [],
        expanded: config.expanded || false,
        
        toggleExpanded() {
            this.expanded = !this.expanded;
        },
        
        getStatusClasses() {
            const statusMap = {
                default: 'border-gray-200 bg-white',
                success: 'border-green-500 bg-green-50',
                warning: 'border-yellow-500 bg-yellow-50',
                danger: 'border-red-500 bg-red-50'
            };
            return statusMap[this.status] || statusMap.default;
        }
    }));
    
    /**
     * InlineEdit Component
     * Enables inline editing with validation and saving
     */
    Alpine.data('inlineEdit', (config = {}) => ({
        value: config.value || '',
        field: config.field || '',
        type: config.type || 'text', // text, email, tel, number, select
        options: config.options || [],
        editing: false,
        loading: false,
        originalValue: '',
        errors: [],
        
        startEdit() {
            this.editing = true;
            this.originalValue = this.value;
            this.$nextTick(() => {
                this.$refs.input?.focus();
                this.$refs.input?.select();
            });
        },
        
        async save() {
            this.loading = true;
            this.errors = [];
            
            try {
                // Validate input
                if (!this.validate()) {
                    this.loading = false;
                    return;
                }
                
                // Call the save callback
                if (config.onSave) {
                    await config.onSave(this.value, this.field);
                }
                
                this.editing = false;
                this.originalValue = this.value;
                
                // Show success feedback
                this.showSuccess();
            } catch (error) {
                this.errors.push(error.message || 'Save failed');
            } finally {
                this.loading = false;
            }
        },
        
        cancel() {
            this.value = this.originalValue;
            this.editing = false;
            this.errors = [];
        },
        
        validate() {
            this.errors = [];
            
            // Required validation
            if (config.required && !this.value) {
                this.errors.push('This field is required');
                return false;
            }
            
            // Type-specific validation
            switch (this.type) {
                case 'email':
                    if (this.value && !this.isValidEmail(this.value)) {
                        this.errors.push('Invalid email address');
                        return false;
                    }
                    break;
                    
                case 'tel':
                    if (this.value && !this.isValidPhone(this.value)) {
                        this.errors.push('Invalid phone number');
                        return false;
                    }
                    break;
                    
                case 'number':
                    if (this.value && isNaN(this.value)) {
                        this.errors.push('Must be a valid number');
                        return false;
                    }
                    break;
            }
            
            // Custom validation
            if (config.validate) {
                const customError = config.validate(this.value);
                if (customError) {
                    this.errors.push(customError);
                    return false;
                }
            }
            
            return true;
        },
        
        isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        },
        
        isValidPhone(phone) {
            return /^[\d\s\+\-\(\)]+$/.test(phone);
        },
        
        showSuccess() {
            // Add a temporary success class
            this.$el.classList.add('inline-edit-success');
            setTimeout(() => {
                this.$el.classList.remove('inline-edit-success');
            }, 2000);
        },
        
        handleKeydown(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                this.save();
            } else if (event.key === 'Escape') {
                this.cancel();
            }
        }
    }));
    
    /**
     * SmartDropdown Component
     * Dropdown with intelligent positioning
     */
    Alpine.data('smartDropdown', (config = {}) => ({
        open: false,
        items: config.items || [],
        selected: config.selected || null,
        search: '',
        filteredItems: [],
        
        init() {
            this.filteredItems = this.items;
            
            // Close on outside click
            document.addEventListener('click', (e) => {
                if (!this.$el.contains(e.target)) {
                    this.open = false;
                }
            });
        },
        
        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.$nextTick(() => {
                    this.positionDropdown();
                    this.$refs.search?.focus();
                });
            }
        },
        
        positionDropdown() {
            const dropdown = this.$refs.dropdown;
            if (!dropdown) return;
            
            const rect = this.$el.getBoundingClientRect();
            const dropdownRect = dropdown.getBoundingClientRect();
            
            // Reset positioning
            dropdown.style.position = 'absolute';
            dropdown.style.top = '100%';
            dropdown.style.left = '0';
            dropdown.style.right = 'auto';
            dropdown.style.bottom = 'auto';
            
            // Check if dropdown would overflow viewport
            const viewportHeight = window.innerHeight;
            const viewportWidth = window.innerWidth;
            
            // Vertical positioning
            if (rect.bottom + dropdownRect.height > viewportHeight - 20) {
                dropdown.style.top = 'auto';
                dropdown.style.bottom = '100%';
            }
            
            // Horizontal positioning
            if (rect.left + dropdownRect.width > viewportWidth - 20) {
                dropdown.style.left = 'auto';
                dropdown.style.right = '0';
            }
        },
        
        filterItems() {
            if (!this.search) {
                this.filteredItems = this.items;
                return;
            }
            
            const searchLower = this.search.toLowerCase();
            this.filteredItems = this.items.filter(item => 
                item.label.toLowerCase().includes(searchLower) ||
                (item.description && item.description.toLowerCase().includes(searchLower))
            );
        },
        
        selectItem(item) {
            this.selected = item;
            this.open = false;
            this.search = '';
            this.filteredItems = this.items;
            
            if (config.onSelect) {
                config.onSelect(item);
            }
        },
        
        getSelectedLabel() {
            return this.selected ? this.selected.label : (config.placeholder || 'Select an option');
        }
    }));
    
    /**
     * ResponsiveGrid Component
     * Automatically adjusts grid columns based on viewport
     */
    Alpine.data('responsiveGrid', (config = {}) => ({
        columns: config.columns || { sm: 1, md: 2, lg: 3, xl: 4 },
        gap: config.gap || 'gap-4',
        currentColumns: 1,
        
        init() {
            this.updateColumns();
            window.addEventListener('resize', () => this.updateColumns());
        },
        
        updateColumns() {
            const width = window.innerWidth;
            
            if (width < 640) {
                this.currentColumns = this.columns.sm || 1;
            } else if (width < 768) {
                this.currentColumns = this.columns.md || 2;
            } else if (width < 1024) {
                this.currentColumns = this.columns.lg || 3;
            } else {
                this.currentColumns = this.columns.xl || 4;
            }
            
            this.$el.style.gridTemplateColumns = `repeat(${this.currentColumns}, minmax(0, 1fr))`;
        }
    }));
    
    /**
     * StatusBadge Component
     * Consistent status indicators
     */
    Alpine.data('statusBadge', (config = {}) => ({
        status: config.status || 'default',
        size: config.size || 'md', // sm, md, lg
        animated: config.animated || false,
        
        getClasses() {
            const baseClasses = 'inline-flex items-center font-medium rounded-full';
            
            const sizeClasses = {
                sm: 'px-2 py-0.5 text-xs',
                md: 'px-2.5 py-1 text-sm',
                lg: 'px-3 py-1.5 text-base'
            };
            
            const statusClasses = {
                default: 'bg-gray-100 text-gray-800',
                success: 'bg-green-100 text-green-800',
                warning: 'bg-yellow-100 text-yellow-800',
                danger: 'bg-red-100 text-red-800',
                info: 'bg-blue-100 text-blue-800',
                primary: 'bg-amber-100 text-amber-800'
            };
            
            const animationClass = this.animated ? 'animate-pulse' : '';
            
            return `${baseClasses} ${sizeClasses[this.size]} ${statusClasses[this.status]} ${animationClass}`;
        }
    }));
});

// Export for use in other scripts
window.AskProAIComponents = {
    version: '1.0.0',
    
    // Utility functions
    utils: {
        formatPhone(phone) {
            // Format phone number for display
            const cleaned = phone.replace(/\D/g, '');
            const match = cleaned.match(/^(\d{2})(\d{3})(\d{3})(\d{4})$/);
            if (match) {
                return `+${match[1]} ${match[2]} ${match[3]} ${match[4]}`;
            }
            return phone;
        },
        
        formatCurrency(amount, currency = 'EUR') {
            return new Intl.NumberFormat('de-DE', {
                style: 'currency',
                currency: currency
            }).format(amount);
        },
        
        formatDate(date, format = 'short') {
            const options = format === 'short' 
                ? { day: '2-digit', month: '2-digit', year: 'numeric' }
                : { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
                
            return new Intl.DateTimeFormat('de-DE', options).format(new Date(date));
        },
        
        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    },
    
    // CSS classes for consistency
    classes: {
        button: {
            primary: 'bg-amber-600 text-white hover:bg-amber-700 focus:ring-amber-500',
            secondary: 'bg-gray-600 text-white hover:bg-gray-700 focus:ring-gray-500',
            success: 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500',
            danger: 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
            ghost: 'bg-transparent text-gray-700 hover:bg-gray-100 focus:ring-gray-500'
        },
        
        input: {
            base: 'block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500',
            error: 'border-red-300 text-red-900 placeholder-red-300 focus:border-red-500 focus:ring-red-500'
        }
    }
};