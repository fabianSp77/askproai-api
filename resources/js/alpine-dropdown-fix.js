// Fix for Alpine.js dropdown issues
document.addEventListener('alpine:init', () => {
    // Override any problematic dropdown implementations
    Alpine.directive('dropdown', (el, { expression }, { effect, cleanup }) => {
        let getOpen = () => {
            let open = Alpine.evaluate(el, expression);
            // Fix for "Illegal invocation" error
            if (typeof open === 'function') {
                try {
                    open = open();
                } catch (e) {
                    console.warn('Dropdown evaluation error:', e);
                    open = false;
                }
            }
            return open;
        };

        effect(() => {
            let open = getOpen();
            if (open) {
                el.style.display = '';
            } else {
                el.style.display = 'none';
            }
        });
    });

    // Fix for simple dropdown pattern
    Alpine.data('simpleDropdown', () => ({
        open: false,
        toggle() {
            this.open = !this.open;
        },
        close() {
            this.open = false;
        }
    }));
    
    // Smart dropdown with positioning
    Alpine.data('smartDropdown', () => ({
        open: false,
        toggle() {
            this.open = !this.open;
            if (this.open && this.$refs.dropdown) {
                this.$nextTick(() => {
                    const button = this.$refs.button;
                    const dropdown = this.$refs.dropdown;
                    if (button && dropdown) {
                        const buttonRect = button.getBoundingClientRect();
                        const dropdownRect = dropdown.getBoundingClientRect();
                        
                        // Check if dropdown would go off screen
                        if (buttonRect.right + dropdownRect.width > window.innerWidth) {
                            dropdown.style.right = '0';
                            dropdown.style.left = 'auto';
                        } else {
                            dropdown.style.left = '0';
                            dropdown.style.right = 'auto';
                        }
                    }
                });
            }
        },
        close() {
            this.open = false;
        }
    }));
});

// Fix any x-data="{ open: false }" patterns after page load
document.addEventListener('DOMContentLoaded', () => {
    // Find all elements with problematic x-data
    document.querySelectorAll('[x-data*="open"]').forEach(el => {
        let xData = el.getAttribute('x-data');
        // If it's just { open: false } or similar, replace with our working version
        if (xData && xData.match(/^\s*{\s*open\s*:\s*(true|false)\s*}\s*$/)) {
            el.setAttribute('x-data', 'simpleDropdown()');
        }
    });
});