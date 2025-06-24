// Smart dropdown positioning for Company Integration Portal

document.addEventListener('alpine:init', () => {
    Alpine.data('smartDropdown', () => ({
        open: false,
        position: 'bottom-right',
        
        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.$nextTick(() => {
                    this.calculatePosition();
                    // Ensure dropdown is visible
                    if (this.$refs.dropdown) {
                        this.$refs.dropdown.style.display = 'block';
                    }
                });
            }
        },
        
        calculatePosition() {
            const button = this.$refs.button;
            const dropdown = this.$refs.dropdown;
            
            if (!button || !dropdown) return;
            
            const buttonRect = button.getBoundingClientRect();
            const dropdownRect = dropdown.getBoundingClientRect();
            const viewportWidth = window.innerWidth;
            const viewportHeight = window.innerHeight;
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            // Reset position classes
            dropdown.classList.remove('right-0', 'left-0', 'bottom-full', 'top-full', 'dropdown-at-bottom', 'dropdown-at-top');
            
            // Check horizontal position
            if (buttonRect.right + dropdownRect.width > viewportWidth - 10) {
                // Position to the left if it would overflow right
                dropdown.classList.add('right-0');
            } else {
                dropdown.classList.add('left-0');
            }
            
            // Check vertical position with scroll consideration
            const spaceBelow = viewportHeight - buttonRect.bottom;
            const spaceAbove = buttonRect.top - scrollTop;
            
            if (spaceBelow < dropdownRect.height && spaceAbove > dropdownRect.height) {
                // Position above if not enough space below
                dropdown.classList.add('bottom-full', 'dropdown-at-top');
                dropdown.style.marginBottom = '0.5rem';
                dropdown.style.marginTop = '0';
            } else {
                // Position below (default)
                dropdown.classList.add('top-full', 'dropdown-at-bottom');
                dropdown.style.marginTop = '0.5rem';
                dropdown.style.marginBottom = '0';
            }
            
            // For mobile, use fixed positioning
            if (window.innerWidth < 768) {
                dropdown.style.position = 'fixed';
                dropdown.style.maxWidth = 'calc(100vw - 2rem)';
                
                if (spaceBelow < dropdownRect.height) {
                    dropdown.style.bottom = '1rem';
                    dropdown.style.top = 'auto';
                } else {
                    dropdown.style.top = buttonRect.bottom + 'px';
                    dropdown.style.bottom = 'auto';
                }
            }
        },
        
        // Close dropdown when clicking outside
        closeOnOutsideClick(event) {
            if (!this.$el.contains(event.target)) {
                this.open = false;
            }
        },
        
        init() {
            // Add outside click listener
            document.addEventListener('click', this.closeOnOutsideClick.bind(this));
            
            // Recalculate position on scroll
            window.addEventListener('scroll', () => {
                if (this.open) {
                    this.calculatePosition();
                }
            });
        },
        
        destroy() {
            document.removeEventListener('click', this.closeOnOutsideClick.bind(this));
        }
    }));
});

// Ensure inline edit buttons are properly sized on mobile
window.addEventListener('DOMContentLoaded', () => {
    // Add loading state handling
    Livewire.hook('message.sent', (message, component) => {
        if (message.updateQueue && message.updateQueue.length > 0) {
            // Find the element being edited
            const updates = message.updateQueue;
            updates.forEach(update => {
                if (update.payload && update.payload.method && update.payload.method.startsWith('save')) {
                    // Add loading class to the appropriate field
                    const fieldName = update.payload.method.replace('save', '').toLowerCase();
                    const elements = document.querySelectorAll(`[wire\\:model*="${fieldName}"]`);
                    elements.forEach(el => {
                        const container = el.closest('.inline-edit-field');
                        if (container) {
                            container.classList.add('inline-edit-saving');
                        }
                    });
                }
            });
        }
    });
    
    Livewire.hook('message.processed', (message, component) => {
        // Remove all loading states
        document.querySelectorAll('.inline-edit-saving').forEach(el => {
            el.classList.remove('inline-edit-saving');
        });
    });
});

// Fix for mobile viewport height
function setViewportHeight() {
    const vh = window.innerHeight * 0.01;
    document.documentElement.style.setProperty('--vh', `${vh}px`);
}

setViewportHeight();
window.addEventListener('resize', setViewportHeight);
window.addEventListener('orientationchange', setViewportHeight);

// Debug helper for click issues
document.addEventListener('DOMContentLoaded', function() {
    // Log all wire:click buttons to ensure they're properly initialized
    const wireClickButtons = document.querySelectorAll('[wire\\:click]');
    console.log('Found wire:click buttons:', wireClickButtons.length);
    
    // Ensure buttons are not being blocked by invisible overlays
    wireClickButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            console.log('Button clicked:', this.getAttribute('wire:click'));
            // Ensure event propagates to Livewire
            if (window.Livewire) {
                console.log('Livewire is loaded');
            }
        });
        
        // Check if button is actually clickable
        const rect = button.getBoundingClientRect();
        const elementAtPoint = document.elementFromPoint(
            rect.left + rect.width / 2,
            rect.top + rect.height / 2
        );
        
        if (elementAtPoint !== button && !button.contains(elementAtPoint)) {
            console.warn('Button may be blocked by overlay:', button);
        }
    });
});

// Fix potential z-index issues with Filament modals
document.addEventListener('livewire:load', function() {
    Livewire.hook('message.processed', (message, component) => {
        // Ensure modals don't block page interaction
        const modals = document.querySelectorAll('.fi-modal');
        modals.forEach(modal => {
            if (modal.style.display === 'none' || modal.classList.contains('hidden')) {
                modal.style.pointerEvents = 'none';
            }
        });
    });
});

// TEMPORARILY DISABLED - Causing layout issues
// The CSS handles sidebar spacing, no need for JS manipulation
/*
// Fix sidebar overlap issue
function fixSidebarLayout() {
    const sidebar = document.querySelector('.fi-sidebar');
    const main = document.querySelector('.fi-main');
    const page = document.querySelector('.fi-page');
    
    if (sidebar && main) {
        const sidebarWidth = sidebar.offsetWidth;
        const isCollapsed = sidebar.classList.contains('fi-sidebar-collapsed');
        
        // Set proper margin for main content
        if (window.innerWidth >= 1024) {
            main.style.marginLeft = isCollapsed ? '4rem' : `${sidebarWidth}px`;
            main.style.width = isCollapsed ? 'calc(100% - 4rem)' : `calc(100% - ${sidebarWidth}px)`;
        } else {
            main.style.marginLeft = '0';
            main.style.width = '100%';
        }
        
        // Ensure page content doesn't slide
        if (page) {
            page.style.marginLeft = '0';
            page.style.transform = 'none';
        }
    }
}

// Run on load
document.addEventListener('DOMContentLoaded', fixSidebarLayout);
window.addEventListener('resize', fixSidebarLayout);

// Watch for sidebar toggle
document.addEventListener('DOMContentLoaded', function() {
    const observer = new MutationObserver(fixSidebarLayout);
    const sidebar = document.querySelector('.fi-sidebar');
    if (sidebar) {
        observer.observe(sidebar, { 
            attributes: true, 
            attributeFilter: ['class'] 
        });
    }
});

// Handle Livewire navigation
document.addEventListener('livewire:navigated', function() {
    setTimeout(fixSidebarLayout, 100);
});
*/