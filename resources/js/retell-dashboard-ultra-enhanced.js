// Enhanced Alpine.js functionality for Retell Ultra Dashboard

document.addEventListener('alpine:init', () => {
    Alpine.data('retellUltraDashboard', () => ({
        // State
        expandedGroups: [],
        expandedVersions: {},
        searchQuery: '',
        sortBy: 'version',
        filteredGroups: {},
        
        // Initialize
        init() {
            this.filteredGroups = this.$el.dataset.groups ? JSON.parse(this.$el.dataset.groups) : {};
            this.applyFiltersAndSort();
        },
        
        // Filter agents based on search query
        filterAgents() {
            if (!this.searchQuery) {
                this.filteredGroups = JSON.parse(this.$el.dataset.groups || '{}');
            } else {
                const query = this.searchQuery.toLowerCase();
                const originalGroups = JSON.parse(this.$el.dataset.groups || '{}');
                this.filteredGroups = {};
                
                Object.entries(originalGroups).forEach(([baseName, group]) => {
                    const filteredVersions = group.versions.filter(version => {
                        return (
                            baseName.toLowerCase().includes(query) ||
                            version.agent_name.toLowerCase().includes(query) ||
                            version.agent_id.toLowerCase().includes(query) ||
                            (version.llm_model && version.llm_model.toLowerCase().includes(query))
                        );
                    });
                    
                    if (filteredVersions.length > 0) {
                        this.filteredGroups[baseName] = {
                            ...group,
                            versions: filteredVersions,
                            total_versions: filteredVersions.length
                        };
                    }
                });
            }
            
            this.applySort();
        },
        
        // Sort agents based on selected criteria
        sortAgents() {
            this.applySort();
        },
        
        // Apply sorting to filtered groups
        applySort() {
            Object.values(this.filteredGroups).forEach(group => {
                group.versions.sort((a, b) => {
                    switch(this.sortBy) {
                        case 'name':
                            return a.agent_name.localeCompare(b.agent_name);
                            
                        case 'version':
                            // Active first, then by version number
                            if (a.is_active !== b.is_active) {
                                return a.is_active ? -1 : 1;
                            }
                            // Extract version numbers
                            const versionA = this.extractVersionNumber(a.version);
                            const versionB = this.extractVersionNumber(b.version);
                            return versionB - versionA;
                            
                        case 'modified':
                            const dateA = new Date(a.last_modified || 0);
                            const dateB = new Date(b.last_modified || 0);
                            return dateB - dateA;
                            
                        case 'status':
                            // Active first, then by presence of webhook
                            if (a.is_active !== b.is_active) {
                                return a.is_active ? -1 : 1;
                            }
                            const hasWebhookA = (a.webhook_urls && a.webhook_urls.length > 0) ? 1 : 0;
                            const hasWebhookB = (b.webhook_urls && b.webhook_urls.length > 0) ? 1 : 0;
                            return hasWebhookB - hasWebhookA;
                            
                        default:
                            return 0;
                    }
                });
            });
        },
        
        // Extract version number from version string
        extractVersionNumber(version) {
            const match = version.match(/V(\d+)$/i);
            return match ? parseInt(match[1]) : 0;
        },
        
        // Apply both filters and sort
        applyFiltersAndSort() {
            this.filterAgents();
        },
        
        // Toggle group expansion
        toggleGroup(baseName) {
            if (this.expandedGroups.includes(baseName)) {
                this.expandedGroups = this.expandedGroups.filter(g => g !== baseName);
            } else {
                this.expandedGroups.push(baseName);
            }
        },
        
        // Toggle version expansion
        toggleVersion(versionKey) {
            this.expandedVersions[versionKey] = !this.expandedVersions[versionKey];
        },
        
        // Check if group is expanded
        isGroupExpanded(baseName) {
            return this.expandedGroups.includes(baseName);
        },
        
        // Check if version is expanded
        isVersionExpanded(versionKey) {
            return !!this.expandedVersions[versionKey];
        },
        
        // Copy text to clipboard
        copyToClipboard(text, label = 'Copied!') {
            navigator.clipboard.writeText(text).then(() => {
                // Show toast notification
                this.showToast(label);
            });
        },
        
        // Show toast notification
        showToast(message) {
            // Create toast element
            const toast = document.createElement('div');
            toast.className = 'fixed bottom-4 left-1/2 transform -translate-x-1/2 px-4 py-2 bg-gray-800 text-white rounded-lg shadow-lg z-50 animate-slide-in';
            toast.textContent = message;
            document.body.appendChild(toast);
            
            // Remove after 2 seconds
            setTimeout(() => {
                toast.classList.add('animate-fade-out');
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 300);
            }, 2000);
        },
        
        // Expand all groups
        expandAll() {
            this.expandedGroups = Object.keys(this.filteredGroups);
        },
        
        // Collapse all groups
        collapseAll() {
            this.expandedGroups = [];
            this.expandedVersions = {};
        }
    }));
});

// Keyboard shortcuts
document.addEventListener('keydown', (e) => {
    // Cmd/Ctrl + F to focus search
    if ((e.metaKey || e.ctrlKey) && e.key === 'f') {
        e.preventDefault();
        const searchInput = document.querySelector('input[x-model="searchQuery"]');
        if (searchInput) {
            searchInput.focus();
            searchInput.select();
        }
    }
    
    // Escape to clear search
    if (e.key === 'Escape') {
        const searchInput = document.querySelector('input[x-model="searchQuery"]');
        if (searchInput && searchInput === document.activeElement) {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input'));
            searchInput.blur();
        }
    }
});