/**
 * Premium Analytics Dashboard JavaScript
 * Advanced interactions, animations, and data visualization
 */

class PremiumAnalyticsDashboard {
    constructor() {
        this.charts = {};
        this.animationQueue = [];
        this.isVisible = true;
        this.refreshInterval = null;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.initializeAnimations();
        this.setupRealtimeUpdates();
        this.initializeInteractions();
    }

    setupEventListeners() {
        // Visibility API for performance optimization
        document.addEventListener('visibilitychange', () => {
            this.isVisible = !document.hidden;
            this.handleVisibilityChange();
        });

        // Resize handler for responsive charts
        window.addEventListener('resize', this.debounce(() => {
            this.resizeCharts();
        }, 300));

        // Scroll animations
        window.addEventListener('scroll', this.throttle(() => {
            this.handleScrollAnimations();
        }, 16));

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            this.handleKeyboardShortcuts(e);
        });
    }

    initializeAnimations() {
        // Stagger animation for metric cards
        this.staggerElements('.metric-card', 100);
        
        // Fade in charts
        this.fadeInElements('.chart-container', 200);
        
        // Slide in activity items
        this.slideInElements('.activity-item', 50);
    }

    setupRealtimeUpdates() {
        // Start real-time updates every 30 seconds
        this.refreshInterval = setInterval(() => {
            if (this.isVisible) {
                this.refreshDashboard();
            }
        }, 30000);

        // WebSocket connection for real-time data (if available)
        this.setupWebSocket();
    }

    initializeInteractions() {
        // Metric card hover effects
        this.setupMetricCardInteractions();
        
        // Chart interactions
        this.setupChartInteractions();
        
        // Activity timeline interactions
        this.setupActivityInteractions();
        
        // Heatmap interactions
        this.setupHeatmapInteractions();
    }

    // Animation Utilities
    staggerElements(selector, delay = 100) {
        const elements = document.querySelectorAll(selector);
        elements.forEach((element, index) => {
            element.style.animationDelay = `${index * delay}ms`;
            element.classList.add('animate-slide-up');
        });
    }

    fadeInElements(selector, delay = 0) {
        const elements = document.querySelectorAll(selector);
        elements.forEach((element, index) => {
            setTimeout(() => {
                element.classList.add('animate-fade-in');
            }, index * delay);
        });
    }

    slideInElements(selector, delay = 50) {
        const elements = document.querySelectorAll(selector);
        elements.forEach((element, index) => {
            setTimeout(() => {
                element.classList.add('animate-slide-in-left');
            }, index * delay);
        });
    }

    // Metric Card Interactions
    setupMetricCardInteractions() {
        const metricCards = document.querySelectorAll('.metric-card, .metric-card-premium');
        
        metricCards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                this.animateMetricCard(card, 'enter');
            });
            
            card.addEventListener('mouseleave', () => {
                this.animateMetricCard(card, 'leave');
            });
            
            card.addEventListener('click', () => {
                this.handleMetricCardClick(card);
            });
        });
    }

    animateMetricCard(card, action) {
        const value = card.querySelector('.metric-value, .metric-value-premium');
        const indicator = card.querySelector('.growth-indicator, .growth-indicator-premium');
        
        if (action === 'enter') {
            value.style.transform = 'scale(1.05)';
            value.style.textShadow = '0 6px 25px rgba(0, 0, 0, 0.4)';
            if (indicator) {
                indicator.style.animation = 'pulse 0.6s ease-in-out';
            }
        } else {
            value.style.transform = 'scale(1)';
            value.style.textShadow = '0 4px 20px rgba(0, 0, 0, 0.3)';
            if (indicator) {
                indicator.style.animation = '';
            }
        }
    }

    handleMetricCardClick(card) {
        // Add click ripple effect
        const ripple = document.createElement('div');
        ripple.className = 'click-ripple';
        card.appendChild(ripple);
        
        setTimeout(() => {
            ripple.remove();
        }, 600);
        
        // Show detailed modal or navigate (implement based on needs)
        this.showMetricDetails(card);
    }

    // Chart Interactions
    setupChartInteractions() {
        // Add custom hover effects for charts
        Chart.defaults.interaction = {
            intersect: false,
            mode: 'index'
        };
        
        // Custom tooltip styling
        Chart.defaults.plugins.tooltip = {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            backdropFilter: 'blur(10px)',
            borderColor: 'rgba(255, 255, 255, 0.3)',
            borderWidth: 1,
            cornerRadius: 12,
            displayColors: true,
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            titleFont: {
                size: 14,
                weight: 'bold'
            },
            bodyFont: {
                size: 12
            },
            padding: 12
        };
    }

    // Activity Timeline Interactions
    setupActivityInteractions() {
        const activityItems = document.querySelectorAll('.activity-item, .activity-item-premium');
        
        activityItems.forEach(item => {
            item.addEventListener('mouseenter', () => {
                this.highlightActivity(item);
            });
            
            item.addEventListener('mouseleave', () => {
                this.unhighlightActivity(item);
            });
            
            item.addEventListener('click', () => {
                this.showActivityDetails(item);
            });
        });
    }

    highlightActivity(item) {
        item.style.borderLeft = '4px solid #3b82f6';
        item.style.paddingLeft = '1.75rem';
        item.style.boxShadow = '0 4px 20px rgba(59, 130, 246, 0.3)';
    }

    unhighlightActivity(item) {
        item.style.borderLeft = '';
        item.style.paddingLeft = '';
        item.style.boxShadow = '';
    }

    // Heatmap Interactions
    setupHeatmapInteractions() {
        const heatmapCells = document.querySelectorAll('.heatmap-cell, .heatmap-cell-premium');
        
        heatmapCells.forEach(cell => {
            cell.addEventListener('mouseenter', (e) => {
                this.showHeatmapTooltip(e, cell);
            });
            
            cell.addEventListener('mouseleave', () => {
                this.hideHeatmapTooltip();
            });
        });
    }

    showHeatmapTooltip(event, cell) {
        const tooltip = document.createElement('div');
        tooltip.className = 'heatmap-tooltip';
        tooltip.innerHTML = cell.getAttribute('title');
        tooltip.style.cssText = `
            position: fixed;
            top: ${event.clientY - 40}px;
            left: ${event.clientX + 10}px;
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            z-index: 1000;
            pointer-events: none;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        `;
        document.body.appendChild(tooltip);
    }

    hideHeatmapTooltip() {
        const tooltip = document.querySelector('.heatmap-tooltip');
        if (tooltip) {
            tooltip.remove();
        }
    }

    // WebSocket Connection
    setupWebSocket() {
        // Implement WebSocket connection for real-time updates
        // This would connect to your Laravel WebSocket server
        if (typeof window.Echo !== 'undefined') {
            window.Echo.channel('analytics-updates')
                .listen('.dashboard-updated', (e) => {
                    this.handleRealtimeUpdate(e);
                });
        }
    }

    handleRealtimeUpdate(data) {
        // Update specific metrics without full page refresh
        this.updateMetricValue('total_revenue', data.revenue);
        this.updateMetricValue('total_calls', data.calls);
        
        // Show notification
        this.showUpdateNotification('Dashboard updated with latest data');
    }

    updateMetricValue(metricId, newValue) {
        const element = document.querySelector(`[data-metric="${metricId}"] .metric-value`);
        if (element) {
            this.animateCounterUpdate(element, newValue);
        }
    }

    animateCounterUpdate(element, targetValue) {
        const currentValue = parseInt(element.textContent.replace(/[€,%]/g, ''));
        const increment = (targetValue - currentValue) / 30; // 30 frames
        let current = currentValue;
        
        const updateCounter = () => {
            current += increment;
            if (
                (increment > 0 && current >= targetValue) || 
                (increment < 0 && current <= targetValue)
            ) {
                current = targetValue;
            }
            
            element.textContent = this.formatMetricValue(current, element);
            
            if (current !== targetValue) {
                requestAnimationFrame(updateCounter);
            }
        };
        
        requestAnimationFrame(updateCounter);
    }

    formatMetricValue(value, element) {
        const originalText = element.textContent;
        if (originalText.includes('€')) {
            return '€' + Math.floor(value).toLocaleString();
        } else if (originalText.includes('%')) {
            return Math.floor(value) + '%';
        }
        return Math.floor(value).toLocaleString();
    }

    // Utility Functions
    refreshDashboard() {
        // Trigger Livewire refresh (v3 uses dispatch instead of emit)
        if (typeof Livewire !== 'undefined') {
            // Livewire v3 syntax
            if (Livewire.dispatch) {
                Livewire.dispatch('refresh');
            } 
            // Fallback for Livewire v2
            else if (Livewire.emit) {
                Livewire.emit('refresh');
            }
        }
    }

    handleVisibilityChange() {
        if (this.isVisible) {
            // Resume animations and updates
            this.resumeAnimations();
        } else {
            // Pause animations and updates for performance
            this.pauseAnimations();
        }
    }

    resizeCharts() {
        // Resize all Chart.js instances
        Object.values(this.charts).forEach(chart => {
            if (chart && chart.resize) {
                chart.resize();
            }
        });
    }

    handleScrollAnimations() {
        // Implement scroll-based animations
        const elements = document.querySelectorAll('.glass-card, .metric-card');
        elements.forEach(element => {
            const rect = element.getBoundingClientRect();
            const isVisible = rect.top < window.innerHeight && rect.bottom > 0;
            
            if (isVisible && !element.classList.contains('in-view')) {
                element.classList.add('in-view');
                element.style.animation = 'fadeInUp 0.6s ease-out';
            }
        });
    }

    handleKeyboardShortcuts(event) {
        // Implement keyboard shortcuts
        if (event.ctrlKey || event.metaKey) {
            switch (event.key) {
                case 'r':
                    event.preventDefault();
                    this.refreshDashboard();
                    break;
                case 'f':
                    event.preventDefault();
                    this.toggleFullscreen();
                    break;
            }
        }
    }

    toggleFullscreen() {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen();
        } else {
            document.exitFullscreen();
        }
    }

    showMetricDetails(card) {
        // Implement metric details modal
        console.log('Show metric details for:', card);
    }

    showActivityDetails(item) {
        // Implement activity details modal
        console.log('Show activity details for:', item);
    }

    showUpdateNotification(message) {
        const notification = document.createElement('div');
        notification.className = 'update-notification';
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(16, 185, 129, 0.9);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            font-weight: 500;
            z-index: 1000;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: slideInRight 0.5s ease-out;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.5s ease-out';
            setTimeout(() => notification.remove(), 500);
        }, 3000);
    }

    pauseAnimations() {
        document.querySelectorAll('*').forEach(el => {
            el.style.animationPlayState = 'paused';
        });
    }

    resumeAnimations() {
        document.querySelectorAll('*').forEach(el => {
            el.style.animationPlayState = 'running';
        });
    }

    // Utility: Debounce function
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Utility: Throttle function
    throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }

    // Cleanup
    destroy() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }
        
        // Remove event listeners
        window.removeEventListener('resize', this.resizeCharts);
        window.removeEventListener('scroll', this.handleScrollAnimations);
        document.removeEventListener('keydown', this.handleKeyboardShortcuts);
        
        // Destroy charts
        Object.values(this.charts).forEach(chart => {
            if (chart && chart.destroy) {
                chart.destroy();
            }
        });
    }
}

// CSS Animations (to be added to the page)
const animationStyles = `
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(100%);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes slideOutRight {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(100%);
        }
    }

    @keyframes pulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.05);
        }
    }

    .click-ripple {
        position: absolute;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.3);
        animation: ripple 0.6s linear;
        pointer-events: none;
    }

    @keyframes ripple {
        0% {
            width: 0;
            height: 0;
            opacity: 1;
        }
        100% {
            width: 200px;
            height: 200px;
            opacity: 0;
        }
    }

    .animate-slide-up {
        animation: fadeInUp 0.6s ease-out both;
    }

    .animate-fade-in {
        animation: fadeIn 0.8s ease-out both;
    }

    .animate-slide-in-left {
        animation: slideInLeft 0.5s ease-out both;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes slideInLeft {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
`;

// Add styles to the page
const styleSheet = document.createElement('style');
styleSheet.textContent = animationStyles;
document.head.appendChild(styleSheet);

// Initialize the dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.premiumDashboard = new PremiumAnalyticsDashboard();
});

// Export for module use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PremiumAnalyticsDashboard;
}