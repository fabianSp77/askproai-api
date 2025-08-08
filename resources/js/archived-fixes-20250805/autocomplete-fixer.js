/**
 * Autocomplete Fixer
 * Automatically adds autocomplete="off" to all dropdown search inputs
 * to prevent browser warnings
 */

class AutocompleteFixer {
    constructor() {
        this.init();
        this.setupObserver();
    }

    init() {
        // Fix all existing inputs
        this.fixAllInputs();
        
        // Listen for Livewire updates
        if (window.Livewire) {
            Livewire.hook('message.processed', () => {
                setTimeout(() => this.fixAllInputs(), 100);
            });
        }
        
        // Listen for Alpine initializations
        document.addEventListener('alpine:initialized', () => {
            setTimeout(() => this.fixAllInputs(), 100);
        });
    }

    fixAllInputs() {
        // Fix dropdown search inputs
        const dropdownSearchInputs = document.querySelectorAll([
            '.fi-dropdown-panel input[type="search"]',
            '.fi-dropdown-panel input[type="text"]',
            '.choices__input',
            'input[x-ref="searchInput"]',
            'input[x-model="search"]'
        ].join(', '));
        
        dropdownSearchInputs.forEach(input => {
            if (!input.hasAttribute('autocomplete')) {
                input.setAttribute('autocomplete', 'off');
            }
        });
        
        // Fix specific form inputs that need autocomplete
        this.fixFormInputs();
    }

    fixFormInputs() {
        // Email inputs
        document.querySelectorAll('input[type="email"]').forEach(input => {
            if (!input.hasAttribute('autocomplete')) {
                input.setAttribute('autocomplete', 'email');
            }
        });
        
        // Password inputs
        document.querySelectorAll('input[type="password"]').forEach(input => {
            if (!input.hasAttribute('autocomplete')) {
                // Check if it's a new password field
                const isNewPassword = input.name?.includes('new') || 
                                    input.name?.includes('confirm') ||
                                    input.id?.includes('new') ||
                                    input.id?.includes('confirm');
                input.setAttribute('autocomplete', isNewPassword ? 'new-password' : 'current-password');
            }
        });
        
        // Username/email login inputs
        const loginInputs = document.querySelectorAll([
            'input[name="email"]',
            'input[name="username"]',
            'input[name="login"]'
        ].join(', '));
        
        loginInputs.forEach(input => {
            if (!input.hasAttribute('autocomplete')) {
                input.setAttribute('autocomplete', 'username');
            }
        });
        
        // Phone inputs
        document.querySelectorAll('input[type="tel"]').forEach(input => {
            if (!input.hasAttribute('autocomplete')) {
                input.setAttribute('autocomplete', 'tel');
            }
        });
        
        // Name inputs
        document.querySelectorAll([
            'input[name*="name"]',
            'input[id*="name"]'
        ].join(', ')).forEach(input => {
            if (!input.hasAttribute('autocomplete') && input.type === 'text') {
                if (input.name?.includes('first') || input.id?.includes('first')) {
                    input.setAttribute('autocomplete', 'given-name');
                } else if (input.name?.includes('last') || input.id?.includes('last')) {
                    input.setAttribute('autocomplete', 'family-name');
                } else if (input.name?.includes('company') || input.id?.includes('company')) {
                    input.setAttribute('autocomplete', 'organization');
                } else {
                    input.setAttribute('autocomplete', 'name');
                }
            }
        });
    }

    setupObserver() {
        // Watch for dynamically added inputs
        const observer = new MutationObserver((mutations) => {
            let shouldFix = false;
            
            mutations.forEach(mutation => {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === 1 && (
                        node.tagName === 'INPUT' ||
                        node.querySelector?.('input')
                    )) {
                        shouldFix = true;
                    }
                });
            });
            
            if (shouldFix) {
                setTimeout(() => this.fixAllInputs(), 50);
            }
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => new AutocompleteFixer());
} else {
    new AutocompleteFixer();
}

export default AutocompleteFixer;