// Company Integration Portal Ultimate - Interactive JavaScript

document.addEventListener('alpine:init', () => {
    Alpine.data('companyIntegrationPortal', () => ({
        activeTab: 'overview',
        expandedBranches: {},
        
        init() {
            // Initialize animations
            this.initializeAnimations();
            
            // Setup keyboard shortcuts
            this.setupKeyboardShortcuts();
            
            // Initialize tooltips
            this.initializeTooltips();
            
            // Setup smooth scroll
            this.setupSmoothScroll();
            
            // Monitor connection status
            this.monitorConnectionStatus();
        },
        
        initializeAnimations() {
            // Intersection Observer for fade-in animations
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-in');
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -10% 0px'
            });
            
            // Observe all animatable elements
            document.querySelectorAll('.section-wrapper, .integration-card, .branch-card-ultimate').forEach(el => {
                observer.observe(el);
            });
        },
        
        setupKeyboardShortcuts() {
            document.addEventListener('keydown', (e) => {
                // Ctrl/Cmd + K for quick search
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    this.openQuickSearch();
                }
                
                // Escape to close modals
                if (e.key === 'Escape') {
                    this.closeAllModals();
                }
                
                // Ctrl/Cmd + R for refresh
                if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
                    e.preventDefault();
                    Livewire.emit('refreshData');
                }
            });
        },
        
        initializeTooltips() {
            // Initialize Tippy.js tooltips if available
            if (typeof tippy !== 'undefined') {
                tippy('[data-tooltip]', {
                    animation: 'shift-toward',
                    duration: [200, 150],
                    arrow: true,
                    theme: 'portal'
                });
            }
        },
        
        setupSmoothScroll() {
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        },
        
        monitorConnectionStatus() {
            let isOnline = navigator.onLine;
            
            window.addEventListener('online', () => {
                if (!isOnline) {
                    isOnline = true;
                    this.showNotification('Connection restored', 'success');
                }
            });
            
            window.addEventListener('offline', () => {
                if (isOnline) {
                    isOnline = false;
                    this.showNotification('Connection lost', 'error');
                }
            });
        },
        
        showNotification(message, type = 'info') {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `portal-notification ${type}`;
            notification.innerHTML = `
                <div class="notification-content">
                    <span>${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="notification-close">×</button>
                </div>
            `;
            
            // Add to body
            document.body.appendChild(notification);
            
            // Animate in
            requestAnimationFrame(() => {
                notification.classList.add('show');
            });
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 5000);
        },
        
        openQuickSearch() {
            // Implementation for quick search modal
            //console.log('Quick search opened');
        },
        
        closeAllModals() {
            // Close all open modals
            document.querySelectorAll('[x-show]').forEach(el => {
                el.__x.$data.open = false;
            });
        },
        
        toggleBranchExpanded(branchId) {
            this.expandedBranches[branchId] = !this.expandedBranches[branchId];
        },
        
        copyToClipboard(text, button) {
            navigator.clipboard.writeText(text).then(() => {
                // Show success feedback
                const originalText = button.innerHTML;
                button.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
                button.classList.add('copied');
                
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.classList.remove('copied');
                }, 2000);
            });
        }
    }));
    
    // Smart dropdown component
    Alpine.data('smartDropdown', () => ({
        open: false,
        
        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.$nextTick(() => {
                    this.positionDropdown();
                });
            }
        },
        
        positionDropdown() {
            const button = this.$refs.button;
            const dropdown = this.$refs.dropdown;
            
            if (!button || !dropdown) return;
            
            const buttonRect = button.getBoundingClientRect();
            const dropdownRect = dropdown.getBoundingClientRect();
            const viewportHeight = window.innerHeight;
            const viewportWidth = window.innerWidth;
            
            // Reset styles
            dropdown.style.removeProperty('bottom');
            dropdown.style.removeProperty('right');
            dropdown.style.removeProperty('left');
            dropdown.style.removeProperty('top');
            
            // Position vertically
            if (buttonRect.bottom + dropdownRect.height > viewportHeight - 20) {
                // Open upward
                dropdown.style.bottom = '100%';
                dropdown.style.marginBottom = '0.5rem';
            } else {
                // Open downward
                dropdown.style.top = '100%';
                dropdown.style.marginTop = '0.5rem';
            }
            
            // Position horizontally
            if (buttonRect.right + dropdownRect.width > viewportWidth - 20) {
                // Align to right edge
                dropdown.style.right = '0';
            } else {
                // Align to left edge
                dropdown.style.left = '0';
            }
        }
    }));
});

// Animation CSS classes
const animationStyles = `
<style>
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-in {
    animation: fadeIn 0.5s ease-out forwards;
}

.portal-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    padding: 1rem 1.5rem;
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    transform: translateX(400px);
    transition: transform 0.3s ease-out;
}

.portal-notification.show {
    transform: translateX(0);
}

.portal-notification.success {
    border-left: 4px solid #10b981;
}

.portal-notification.error {
    border-left: 4px solid #ef4444;
}

.portal-notification.info {
    border-left: 4px solid #3b82f6;
}

.notification-content {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.notification-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #6b7280;
    padding: 0;
    line-height: 1;
}

.notification-close:hover {
    color: #111827;
}

button.copied {
    background: #10b981 !important;
    color: white !important;
    border-color: #10b981 !important;
}

/* Smooth collapse animation */
[x-collapse] {
    overflow: hidden;
}

[x-collapse].collapse-enter-active,
[x-collapse].collapse-leave-active {
    transition: height 0.3s ease;
}

/* Loading shimmer effect */
.loading::after {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.2),
        transparent
    );
    animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
    0% {
        transform: translateX(-100%);
    }
    100% {
        transform: translateX(100%);
    }
}

/* Pulse animation for live indicators */
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Tippy.js portal theme */
.tippy-box[data-theme~='portal'] {
    background-color: #111827;
    color: white;
    font-size: 0.875rem;
}

.tippy-box[data-theme~='portal'][data-placement^='top'] > .tippy-arrow::before {
    border-top-color: #111827;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .portal-notification {
        left: 20px;
        right: 20px;
        transform: translateY(-100px);
    }
    
    .portal-notification.show {
        transform: translateY(0);
    }
}
</style>`;

// Inject animation styles
document.head.insertAdjacentHTML('beforeend', animationStyles);

// Export functions for use in Livewire hooks
window.PortalHelpers = {
    initializeAfterUpdate() {
        // Re-initialize components after Livewire updates
        if (typeof Alpine !== 'undefined') {
            Alpine.initTree(document.querySelector('.portal-container'));
        }
    },
    
    scrollToElement(selector) {
        const element = document.querySelector(selector);
        if (element) {
            element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    },
    
    highlightElement(selector) {
        const element = document.querySelector(selector);
        if (element) {
            element.classList.add('highlight');
            setTimeout(() => element.classList.remove('highlight'), 2000);
        }
    }
};

// Livewire hooks
document.addEventListener('livewire:load', () => {
    Livewire.hook('message.processed', (message, component) => {
        PortalHelpers.initializeAfterUpdate();
    });
});

// Add highlight animation
const highlightStyle = `
<style>
.highlight {
    animation: highlightPulse 2s ease-out;
}

@keyframes highlightPulse {
    0% {
        box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(245, 158, 11, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(245, 158, 11, 0);
    }
}
</style>`;

document.head.insertAdjacentHTML('beforeend', highlightStyle);