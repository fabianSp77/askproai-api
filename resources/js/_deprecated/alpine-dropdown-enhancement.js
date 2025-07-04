/**
 * Alpine.js Dropdown Enhancement
 * Fixes dropdown closing issues (GitHub #212)
 * Improves dropdown behavior portal-wide
 */

// Wait for Alpine to be ready
document.addEventListener('alpine:init', () => {
    console.log('Alpine Dropdown Enhancement loaded');

    // Enhanced dropdown component for branch selector
    Alpine.data('enhancedBranchSwitcher', () => ({
        open: false,
        search: '',
        isNavigating: false,
        
        toggle() {
            this.open = !this.open;
        },
        
        close() {
            if (!this.isNavigating) {
                this.open = false;
                this.search = '';
            }
        },
        
        selectBranch(url) {
            this.isNavigating = true;
            this.open = false;
            
            // Small delay for animation
            setTimeout(() => {
                window.location.href = url;
            }, 150);
        },
        
        // Better click outside handler
        handleClickOutside(event) {
            // Check if click is within the component
            const withinBoundaries = event.composedPath().includes(this.$el);
            
            if (!withinBoundaries && this.open) {
                this.close();
            }
        },
        
        init() {
            // Use capture phase for better event handling
            document.addEventListener('click', this.handleClickOutside.bind(this), true);
            
            // Also listen for Filament-specific events
            document.addEventListener('filament:dropdown-closed', () => {
                this.close();
            });
            
            // Cleanup on component destroy
            this.$cleanup(() => {
                document.removeEventListener('click', this.handleClickOutside.bind(this), true);
            });
        },
        
        get filteredBranches() {
            if (!this.search) return this.branches || [];
            
            return (this.branches || []).filter(branch => 
                branch.name.toLowerCase().includes(this.search.toLowerCase())
            );
        }
    }));

    // Generic enhanced dropdown for all dropdowns
    Alpine.data('enhancedDropdown', () => ({
        open: false,
        
        toggle() {
            this.open = !this.open;
            
            if (this.open) {
                this.positionDropdown();
            }
        },
        
        close() {
            this.open = false;
        },
        
        positionDropdown() {
            // Calculate optimal position to avoid being hidden
            this.$nextTick(() => {
                const button = this.$refs.button;
                const dropdown = this.$refs.dropdown;
                
                if (!button || !dropdown) return;
                
                const buttonRect = button.getBoundingClientRect();
                const dropdownRect = dropdown.getBoundingClientRect();
                const viewportHeight = window.innerHeight;
                const viewportWidth = window.innerWidth;
                
                // Check if dropdown would go off screen
                if (buttonRect.bottom + dropdownRect.height > viewportHeight) {
                    // Position above button
                    dropdown.style.bottom = `${button.offsetHeight + 8}px`;
                    dropdown.style.top = 'auto';
                } else {
                    // Position below button
                    dropdown.style.top = `${button.offsetHeight + 8}px`;
                    dropdown.style.bottom = 'auto';
                }
                
                // Horizontal positioning
                if (buttonRect.right + dropdownRect.width > viewportWidth) {
                    dropdown.style.right = '0';
                    dropdown.style.left = 'auto';
                } else {
                    dropdown.style.left = '0';
                    dropdown.style.right = 'auto';
                }
            });
        }
    }));
});

// Fix for Filament action dropdowns in tables
document.addEventListener('DOMContentLoaded', () => {
    // Move dropdown panels outside of table containers
    const moveDropdownToBody = (dropdown) => {
        if (!dropdown.dataset.moved && dropdown.classList.contains('fi-dropdown-panel')) {
            const rect = dropdown.getBoundingClientRect();
            const parent = dropdown.offsetParent;
            const parentRect = parent ? parent.getBoundingClientRect() : { top: 0, left: 0 };
            
            // Calculate absolute position
            dropdown.style.setProperty('--dropdown-top', `${rect.top}px`);
            dropdown.style.setProperty('--dropdown-left', `${rect.left}px`);
            
            // Move to body
            document.body.appendChild(dropdown);
            dropdown.dataset.moved = 'true';
            
            // Update position after move
            requestAnimationFrame(() => {
                const newRect = dropdown.getBoundingClientRect();
                if (newRect.bottom > window.innerHeight) {
                    dropdown.style.setProperty('--dropdown-top', `${rect.top - dropdown.offsetHeight - 10}px`);
                }
            });
        }
    };
    
    // Observer for new dropdowns
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === 1) {
                    if (node.classList?.contains('fi-dropdown-panel')) {
                        moveDropdownToBody(node);
                    }
                    
                    // Also check children
                    const dropdowns = node.querySelectorAll?.('.fi-dropdown-panel');
                    dropdowns?.forEach(moveDropdownToBody);
                }
            });
        });
    });
    
    // Start observing
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // Handle existing dropdowns
    document.querySelectorAll('.fi-dropdown-panel').forEach(moveDropdownToBody);
});

// Fix for Alpine.js event propagation issues
document.addEventListener('DOMContentLoaded', () => {
    // Intercept clicks on dropdown items
    document.addEventListener('click', (event) => {
        const dropdownItem = event.target.closest('.fi-dropdown-list-item');
        
        if (dropdownItem) {
            // Find parent dropdown
            const dropdown = dropdownItem.closest('[x-data]');
            
            if (dropdown && dropdown.__x) {
                // Close dropdown after action
                setTimeout(() => {
                    if (dropdown.__x.$data.close) {
                        dropdown.__x.$data.close();
                    } else if (dropdown.__x.$data.open !== undefined) {
                        dropdown.__x.$data.open = false;
                    }
                }, 100);
            }
        }
    }, true);
});

// Export for use in other modules
export function closeAllDropdowns() {
    // Close Alpine dropdowns
    document.querySelectorAll('[x-data]').forEach(el => {
        if (el.__x && el.__x.$data.open !== undefined) {
            el.__x.$data.open = false;
        }
    });
    
    // Close Filament dropdowns
    document.querySelectorAll('[data-state="open"]').forEach(dropdown => {
        dropdown.setAttribute('data-state', 'closed');
        dropdown.classList.add('invisible');
        dropdown.classList.remove('visible');
    });
}

// Global helper for debugging
window.debugDropdowns = () => {
    console.log('Open dropdowns:', document.querySelectorAll('[x-data][x-show]:not(.invisible)'));
    console.log('Alpine components:', Alpine.components);
};