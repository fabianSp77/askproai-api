export default () => ({
    activeTab: '',
    tabs: [],
    
    init() {
        // Find all tabs
        this.tabs = Array.from(this.$el.querySelectorAll('[role="tab"]')).map(tab => ({
            id: tab.getAttribute('aria-controls'),
            label: tab.textContent.trim(),
            element: tab
        }));
        
        // Set initial active tab
        if (this.tabs.length > 0) {
            const activeTab = this.tabs.find(tab => tab.element.getAttribute('aria-selected') === 'true');
            this.activeTab = activeTab ? activeTab.id : this.tabs[0].id;
        }
        
        // Update aria attributes
        this.updateAria();
    },
    
    selectTab(tabId) {
        this.activeTab = tabId;
        this.updateAria();
        this.$dispatch('tab-changed', { tabId });
    },
    
    isActive(tabId) {
        return this.activeTab === tabId;
    },
    
    updateAria() {
        this.tabs.forEach(tab => {
            const isActive = tab.id === this.activeTab;
            tab.element.setAttribute('aria-selected', isActive);
            tab.element.setAttribute('tabindex', isActive ? '0' : '-1');
            
            // Update panel
            const panel = document.getElementById(tab.id);
            if (panel) {
                panel.setAttribute('aria-hidden', !isActive);
                panel.style.display = isActive ? 'block' : 'none';
            }
        });
    },
    
    handleKeydown(event) {
        const currentIndex = this.tabs.findIndex(tab => tab.id === this.activeTab);
        
        switch (event.key) {
            case 'ArrowRight':
                event.preventDefault();
                const nextIndex = (currentIndex + 1) % this.tabs.length;
                this.selectTab(this.tabs[nextIndex].id);
                this.tabs[nextIndex].element.focus();
                break;
                
            case 'ArrowLeft':
                event.preventDefault();
                const prevIndex = currentIndex === 0 ? this.tabs.length - 1 : currentIndex - 1;
                this.selectTab(this.tabs[prevIndex].id);
                this.tabs[prevIndex].element.focus();
                break;
                
            case 'Home':
                event.preventDefault();
                this.selectTab(this.tabs[0].id);
                this.tabs[0].element.focus();
                break;
                
            case 'End':
                event.preventDefault();
                this.selectTab(this.tabs[this.tabs.length - 1].id);
                this.tabs[this.tabs.length - 1].element.focus();
                break;
        }
    }
});