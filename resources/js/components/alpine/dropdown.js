export default () => ({
    open: false,
    
    init() {
        // Close dropdown when clicking outside
        this.$watch('open', value => {
            if (value) {
                this.$nextTick(() => {
                    // Focus first item
                    const firstItem = this.$refs.dropdown?.querySelector('[role="menuitem"]');
                    if (firstItem) firstItem.focus();
                });
            }
        });
    },
    
    toggle() {
        this.open = !this.open;
    },
    
    close() {
        this.open = false;
    },
    
    handleKeydown(event) {
        if (!this.open) return;
        
        switch (event.key) {
            case 'Escape':
                this.close();
                this.$refs.trigger?.focus();
                break;
                
            case 'ArrowDown':
                event.preventDefault();
                this.focusNextItem();
                break;
                
            case 'ArrowUp':
                event.preventDefault();
                this.focusPreviousItem();
                break;
                
            case 'Home':
                event.preventDefault();
                this.focusFirstItem();
                break;
                
            case 'End':
                event.preventDefault();
                this.focusLastItem();
                break;
        }
    },
    
    focusNextItem() {
        const items = this.getMenuItems();
        const currentIndex = items.indexOf(document.activeElement);
        const nextIndex = currentIndex + 1 < items.length ? currentIndex + 1 : 0;
        items[nextIndex]?.focus();
    },
    
    focusPreviousItem() {
        const items = this.getMenuItems();
        const currentIndex = items.indexOf(document.activeElement);
        const previousIndex = currentIndex > 0 ? currentIndex - 1 : items.length - 1;
        items[previousIndex]?.focus();
    },
    
    focusFirstItem() {
        const items = this.getMenuItems();
        items[0]?.focus();
    },
    
    focusLastItem() {
        const items = this.getMenuItems();
        items[items.length - 1]?.focus();
    },
    
    getMenuItems() {
        return Array.from(this.$refs.dropdown?.querySelectorAll('[role="menuitem"]:not([disabled])') || []);
    }
});