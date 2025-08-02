export default () => ({
    query: '',
    results: [],
    loading: false,
    focused: false,
    selectedIndex: -1,
    minChars: 2,
    debounceTime: 300,
    searchTimeout: null,
    
    init() {
        this.portal = Alpine.store('portal');
    },
    
    handleInput() {
        clearTimeout(this.searchTimeout);
        
        if (this.query.length < this.minChars) {
            this.results = [];
            return;
        }
        
        this.loading = true;
        this.searchTimeout = setTimeout(() => {
            this.search();
        }, this.debounceTime);
    },
    
    async search() {
        try {
            const response = await this.portal.get('/search', {
                q: this.query,
                type: this.$el.dataset.searchType || 'all'
            });
            
            this.results = response.data.results || [];
            this.selectedIndex = -1;
        } catch (error) {
            console.error('Search error:', error);
            this.results = [];
        } finally {
            this.loading = false;
        }
    },
    
    selectResult(result) {
        this.$dispatch('search-selected', result);
        this.clear();
        
        // Navigate if result has URL
        if (result.url) {
            window.location.href = result.url;
        }
    },
    
    clear() {
        this.query = '';
        this.results = [];
        this.selectedIndex = -1;
    },
    
    handleKeydown(event) {
        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                this.selectedIndex = Math.min(this.selectedIndex + 1, this.results.length - 1);
                this.scrollToSelected();
                break;
                
            case 'ArrowUp':
                event.preventDefault();
                this.selectedIndex = Math.max(this.selectedIndex - 1, -1);
                this.scrollToSelected();
                break;
                
            case 'Enter':
                event.preventDefault();
                if (this.selectedIndex >= 0 && this.results[this.selectedIndex]) {
                    this.selectResult(this.results[this.selectedIndex]);
                }
                break;
                
            case 'Escape':
                event.preventDefault();
                this.clear();
                this.$refs.input.blur();
                break;
        }
    },
    
    scrollToSelected() {
        if (this.selectedIndex < 0) return;
        
        const selected = this.$refs.results?.children[this.selectedIndex];
        if (selected) {
            selected.scrollIntoView({ block: 'nearest' });
        }
    },
    
    highlightMatch(text) {
        if (!this.query) return text;
        
        const regex = new RegExp(`(${this.query})`, 'gi');
        return text.replace(regex, '<mark class="bg-yellow-200">$1</mark>');
    },
    
    getResultIcon(type) {
        const icons = {
            customer: '👤',
            appointment: '📅',
            call: '📞',
            staff: '👨‍💼',
            service: '✂️'
        };
        return icons[type] || '📌';
    }
});