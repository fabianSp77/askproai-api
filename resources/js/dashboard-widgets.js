// Dashboard Widget Enhancements

document.addEventListener('DOMContentLoaded', function() {
    // Initialize widget enhancements
    initializeKeyboardShortcuts();
    initializeWidgetAnimations();
    initializeChartInteractivity();
    initializeNotifications();
});

// Keyboard shortcuts
function initializeKeyboardShortcuts() {
    const shortcuts = {
        'a': () => window.dispatchEvent(new Event('openQuickAppointment')),
        'c': () => window.dispatchEvent(new Event('openQuickCall')),
        'k': () => window.dispatchEvent(new Event('openQuickCustomer')),
        'r': () => window.location.reload(),
        '/': () => focusSearch(),
        'Escape': () => closeAllModals(),
    };

    document.addEventListener('keydown', (e) => {
        // Skip if user is typing in an input
        if (e.target.matches('input, textarea, select')) return;
        
        const handler = shortcuts[e.key];
        if (handler) {
            e.preventDefault();
            handler();
        }
    });
}

// Widget animations
function initializeWidgetAnimations() {
    // Fade in widgets on load
    const widgets = document.querySelectorAll('[wire\\:id]');
    widgets.forEach((widget, index) => {
        widget.style.opacity = '0';
        widget.style.transform = 'translateY(20px)';
        widget.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        
        setTimeout(() => {
            widget.style.opacity = '1';
            widget.style.transform = 'translateY(0)';
        }, index * 100);
    });

    // Add hover effects
    const cards = document.querySelectorAll('.widget-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
}

// Chart interactivity
function initializeChartInteractivity() {
    // Listen for chart updates
    Livewire.on('chartUpdated', (chartId) => {
        const chartElement = document.getElementById(chartId);
        if (chartElement) {
            // Add update animation
            chartElement.style.opacity = '0.5';
            setTimeout(() => {
                chartElement.style.opacity = '1';
            }, 300);
        }
    });
}

// Notification system
function initializeNotifications() {
    // Listen for custom notifications
    Livewire.on('notify', ({ type, message, duration = 3000 }) => {
        showNotification(type, message, duration);
    });
}

function showNotification(type, message, duration) {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    // Style the notification
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 16px 24px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 9999;
        transform: translateX(400px);
        transition: transform 0.3s ease;
    `;
    
    // Set background color based on type
    const colors = {
        success: '#10b981',
        error: '#ef4444',
        warning: '#f59e0b',
        info: '#3b82f6'
    };
    notification.style.backgroundColor = colors[type] || colors.info;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 10);
    
    // Auto remove
    setTimeout(() => {
        notification.style.transform = 'translateX(400px)';
        setTimeout(() => notification.remove(), 300);
    }, duration);
}

// Helper functions
function focusSearch() {
    const searchInput = document.querySelector('input[type="search"]');
    if (searchInput) {
        searchInput.focus();
        searchInput.select();
    }
}

function closeAllModals() {
    const modals = document.querySelectorAll('[role="dialog"]');
    modals.forEach(modal => {
        const closeButton = modal.querySelector('[aria-label="Close"]');
        if (closeButton) closeButton.click();
    });
}

// Widget resize observer (for responsive charts)
const resizeObserver = new ResizeObserver(entries => {
    entries.forEach(entry => {
        if (entry.target.querySelector('canvas')) {
            // Trigger chart resize
            window.dispatchEvent(new Event('resize'));
        }
    });
});

// Observe all chart containers
document.querySelectorAll('.chart-container').forEach(container => {
    resizeObserver.observe(container);
});

// Export functions for use in Alpine components
window.dashboardWidgets = {
    showNotification,
    focusSearch,
    closeAllModals
};