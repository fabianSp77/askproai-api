export default () => ({
    expanded: {},
    
    init() {
        // Initialize expanded state from localStorage
        const saved = localStorage.getItem('sidebar-expanded');
        if (saved) {
            try {
                this.expanded = JSON.parse(saved);
            } catch (e) {
                this.expanded = {};
            }
        }
        
        // Highlight active menu item
        this.highlightActive();
    },
    
    toggle(key) {
        this.expanded[key] = !this.expanded[key];
        this.saveState();
    },
    
    isExpanded(key) {
        return !!this.expanded[key];
    },
    
    saveState() {
        localStorage.setItem('sidebar-expanded', JSON.stringify(this.expanded));
    },
    
    highlightActive() {
        const currentPath = window.location.pathname;
        const links = this.$el.querySelectorAll('a[href]');
        
        links.forEach(link => {
            const href = link.getAttribute('href');
            const isActive = href === currentPath || 
                            (href !== '/business' && currentPath.startsWith(href));
            
            if (isActive) {
                link.classList.add('bg-blue-50', 'text-blue-700', 'border-l-4', 'border-blue-700');
                link.classList.remove('text-gray-700', 'hover:bg-gray-50');
                
                // Expand parent if in submenu
                const parent = link.closest('[x-data]');
                if (parent && parent !== this.$el) {
                    const parentKey = parent.getAttribute('data-menu-key');
                    if (parentKey) {
                        this.expanded[parentKey] = true;
                    }
                }
            }
        });
    },
    
    handleKeydown(event) {
        const current = document.activeElement;
        const items = Array.from(this.$el.querySelectorAll('a[href], button[aria-expanded]'));
        const currentIndex = items.indexOf(current);
        
        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                const nextIndex = (currentIndex + 1) % items.length;
                items[nextIndex]?.focus();
                break;
                
            case 'ArrowUp':
                event.preventDefault();
                const prevIndex = currentIndex === 0 ? items.length - 1 : currentIndex - 1;
                items[prevIndex]?.focus();
                break;
                
            case 'Home':
                event.preventDefault();
                items[0]?.focus();
                break;
                
            case 'End':
                event.preventDefault();
                items[items.length - 1]?.focus();
                break;
        }
    }
});