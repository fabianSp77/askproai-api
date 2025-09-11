// Flowbite Pro Alpine.js Components
// Alpine is loaded globally via Vite/app.js
(function() {
    'use strict';
    
    // Wait for Alpine to be available
    function initFlowbiteComponents() {
        if (typeof Alpine === 'undefined') {
            // Try again in 100ms
            setTimeout(initFlowbiteComponents, 100);
            return;
        }

        // Global Flowbite Alpine components
        Alpine.data('flowbiteDropdown', () => ({
            open: false,
            toggle() {
                this.open = !this.open;
            },
            close() {
                this.open = false;
            }
        }));

        Alpine.data('flowbiteModal', () => ({
            show: false,
            open() {
                this.show = true;
                document.body.style.overflow = 'hidden';
            },
            close() {
                this.show = false;
                document.body.style.overflow = '';
            }
        }));

        Alpine.data('flowbiteTable', () => ({
            search: '',
            sortBy: null,
            sortOrder: 'asc',
            selectedRows: [],
            
            sort(column) {
                if (this.sortBy === column) {
                    this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
                } else {
                    this.sortBy = column;
                    this.sortOrder = 'asc';
                }
            },
            
            toggleRow(id) {
                const index = this.selectedRows.indexOf(id);
                if (index > -1) {
                    this.selectedRows.splice(index, 1);
                } else {
                    this.selectedRows.push(id);
                }
            },
            
            selectAll(ids) {
                this.selectedRows = this.selectedRows.length === ids.length ? [] : [...ids];
            }
        }));

        Alpine.data('flowbiteTabs', () => ({
            activeTab: 0,
            tabs: [],
            
            init() {
                this.tabs = this.$el.querySelectorAll('[role="tabpanel"]');
            },
            
            switchTab(index) {
                this.activeTab = index;
            }
        }));

        Alpine.data('flowbiteForm', () => ({
            formData: {},
            errors: {},
            loading: false,
            
            async submit() {
                this.loading = true;
                this.errors = {};
                
                try {
                    // Form submission logic here
                    console.log('Form submitted:', this.formData);
                } catch (error) {
                    this.errors = error.response?.data?.errors || {};
                } finally {
                    this.loading = false;
                }
            }
        }));

        // Charts data handler
        Alpine.data('flowbiteChart', () => ({
            chartData: null,
            chartInstance: null,
            
            init() {
                this.initChart();
            },
            
            initChart() {
                // Chart initialization logic
                // Can integrate with Chart.js or ApexCharts
            },
            
            updateChart(newData) {
                this.chartData = newData;
                if (this.chartInstance) {
                    this.chartInstance.update();
                }
            }
        }));

        // Notification system
        Alpine.data('flowbiteNotifications', () => ({
            notifications: [],
            
            add(message, type = 'info') {
                const id = Date.now();
                this.notifications.push({ id, message, type });
                
                setTimeout(() => {
                    this.remove(id);
                }, 5000);
            },
            
            remove(id) {
                this.notifications = this.notifications.filter(n => n.id !== id);
            }
        }));

        // Dark mode toggle
        Alpine.data('flowbiteDarkMode', () => ({
            dark: localStorage.getItem('darkMode') === 'true',
            
            toggle() {
                this.dark = !this.dark;
                localStorage.setItem('darkMode', this.dark);
                document.documentElement.classList.toggle('dark', this.dark);
            }
        }));

        // Alpine is already started by the main app.js
        console.log('Flowbite Alpine components initialized');
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFlowbiteComponents);
    } else {
        initFlowbiteComponents();
    }
})();