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
    
    // Fix for Filament user dropdown specifically
    const userDropdown = document.querySelector('.fi-user-dropdown');
    if (userDropdown) {
        // Add click-outside listener
        document.addEventListener('click', (e) => {
            if (!userDropdown.contains(e.target)) {
                // Close the dropdown
                const panel = userDropdown.querySelector('.fi-dropdown-panel');
                if (panel && !panel.classList.contains('invisible')) {
                    panel.classList.add('invisible');
                    panel.classList.remove('visible');
                    
                    // Update button state
                    const button = userDropdown.querySelector('button[aria-expanded="true"]');
                    if (button) {
                        button.setAttribute('aria-expanded', 'false');
                    }
                }
            }
        });
        
        // Fix toggle button
        const toggleButton = userDropdown.querySelector('button');
        if (toggleButton) {
            toggleButton.addEventListener('click', (e) => {
                e.stopPropagation();
                const panel = userDropdown.querySelector('.fi-dropdown-panel');
                if (panel) {
                    const isOpen = !panel.classList.contains('invisible');
                    if (isOpen) {
                        panel.classList.add('invisible');
                        panel.classList.remove('visible');
                        toggleButton.setAttribute('aria-expanded', 'false');
                    } else {
                        panel.classList.remove('invisible');
                        panel.classList.add('visible');
                        toggleButton.setAttribute('aria-expanded', 'true');
                    }
                }
            });
        }
    }
});