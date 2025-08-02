/**
 * Admin Portal Tooltip System
 * Provides enhanced tooltips with touch device support
 */

class AdminTooltips {
    constructor() {
        this.tooltipClass = 'askproai-tooltip';
        this.activeTooltip = null;
        this.touchTimeout = null;
        this.init();
    }

    init() {
        // Initialize tooltips on page load
        this.setupTooltips();
        
        // Re-initialize on Livewire updates
        if (window.Livewire) {
            Livewire.hook('message.processed', () => {
                this.setupTooltips();
            });
        }

        // Re-initialize on Alpine component updates
        if (window.Alpine) {
            document.addEventListener('alpine:initialized', () => {
                this.setupTooltips();
            });
        }

        // Setup global styles
        this.injectStyles();
    }

    setupTooltips() {
        // Find all elements with data-tooltip attribute
        const elements = document.querySelectorAll('[data-tooltip], [title]:not([data-tooltip-processed])');
        
        elements.forEach(element => {
            // Skip if already processed
            if (element.hasAttribute('data-tooltip-processed')) {
                return;
            }

            // Convert title to data-tooltip if needed
            if (element.hasAttribute('title') && !element.hasAttribute('data-tooltip')) {
                element.setAttribute('data-tooltip', element.getAttribute('title'));
                element.removeAttribute('title');
            }

            // Mark as processed
            element.setAttribute('data-tooltip-processed', 'true');

            // Add event listeners
            this.attachTooltipEvents(element);
        });

        // Also handle Filament action buttons
        this.enhanceFilamentActions();
    }

    attachTooltipEvents(element) {
        // Mouse events for desktop
        element.addEventListener('mouseenter', (e) => this.showTooltip(e));
        element.addEventListener('mouseleave', (e) => this.hideTooltip(e));
        element.addEventListener('mousemove', (e) => this.updateTooltipPosition(e));

        // Touch events for mobile
        element.addEventListener('touchstart', (e) => this.handleTouchStart(e), { passive: true });
        element.addEventListener('touchend', (e) => this.handleTouchEnd(e), { passive: true });

        // Keyboard navigation
        element.addEventListener('focus', (e) => this.showTooltip(e));
        element.addEventListener('blur', (e) => this.hideTooltip(e));
    }

    showTooltip(event) {
        const element = event.currentTarget;
        const tooltipText = element.getAttribute('data-tooltip');
        
        if (!tooltipText) return;

        // Remove any existing tooltip
        this.hideTooltip();

        // Create tooltip element
        const tooltip = document.createElement('div');
        tooltip.className = this.tooltipClass;
        tooltip.textContent = tooltipText;
        tooltip.setAttribute('role', 'tooltip');
        tooltip.setAttribute('aria-hidden', 'false');

        // Add to body
        document.body.appendChild(tooltip);
        this.activeTooltip = tooltip;

        // Position tooltip
        this.positionTooltip(element, tooltip);

        // Add show class for animation
        requestAnimationFrame(() => {
            tooltip.classList.add('show');
        });
    }

    hideTooltip() {
        if (this.activeTooltip) {
            this.activeTooltip.classList.remove('show');
            
            // Remove after animation
            setTimeout(() => {
                if (this.activeTooltip && this.activeTooltip.parentNode) {
                    this.activeTooltip.parentNode.removeChild(this.activeTooltip);
                }
                this.activeTooltip = null;
            }, 200);
        }
    }

    updateTooltipPosition(event) {
        if (!this.activeTooltip) return;
        
        const element = event.currentTarget;
        this.positionTooltip(element, this.activeTooltip);
    }

    positionTooltip(element, tooltip) {
        const rect = element.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();
        
        // Default position above element
        let top = rect.top - tooltipRect.height - 10;
        let left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);

        // Check if tooltip goes off screen
        if (top < 10) {
            // Position below instead
            top = rect.bottom + 10;
            tooltip.classList.add('below');
        } else {
            tooltip.classList.remove('below');
        }

        if (left < 10) {
            left = 10;
        } else if (left + tooltipRect.width > window.innerWidth - 10) {
            left = window.innerWidth - tooltipRect.width - 10;
        }

        // Apply position
        tooltip.style.top = `${top}px`;
        tooltip.style.left = `${left}px`;
    }

    handleTouchStart(event) {
        const element = event.currentTarget;
        
        // Clear any existing timeout
        if (this.touchTimeout) {
            clearTimeout(this.touchTimeout);
        }

        // Show tooltip after 500ms hold
        this.touchTimeout = setTimeout(() => {
            this.showTooltip({ currentTarget: element });
            
            // Hide after 3 seconds
            setTimeout(() => {
                this.hideTooltip();
            }, 3000);
        }, 500);
    }

    handleTouchEnd(event) {
        // Clear the timeout if touch ends before tooltip shows
        if (this.touchTimeout) {
            clearTimeout(this.touchTimeout);
            this.touchTimeout = null;
        }
    }

    enhanceFilamentActions() {
        // Add tooltips to Filament action buttons
        const actionButtons = document.querySelectorAll(`
            .filament-tables-actions button,
            .filament-tables-header-actions button,
            .filament-page-actions button,
            .filament-form-actions button
        `);

        actionButtons.forEach(button => {
            // Skip if already has tooltip
            if (button.hasAttribute('data-tooltip-processed')) {
                return;
            }

            // Try to determine tooltip from aria-label or text content
            let tooltipText = button.getAttribute('aria-label');
            
            if (!tooltipText) {
                // Try to get from icon + text
                const icon = button.querySelector('svg');
                const text = button.textContent.trim();
                
                if (icon && !text) {
                    // Icon-only button, try to determine action
                    const classList = button.className;
                    
                    if (classList.includes('edit')) {
                        tooltipText = 'Bearbeiten';
                    } else if (classList.includes('delete')) {
                        tooltipText = 'Löschen';
                    } else if (classList.includes('view')) {
                        tooltipText = 'Ansehen';
                    } else if (classList.includes('refresh')) {
                        tooltipText = 'Aktualisieren';
                    } else if (classList.includes('download')) {
                        tooltipText = 'Herunterladen';
                    } else if (classList.includes('export')) {
                        tooltipText = 'Exportieren';
                    }
                }
            }

            if (tooltipText) {
                button.setAttribute('data-tooltip', tooltipText);
                this.attachTooltipEvents(button);
                button.setAttribute('data-tooltip-processed', 'true');
            }
        });
    }

    injectStyles() {
        if (document.getElementById('askproai-tooltip-styles')) {
            return;
        }

        const style = document.createElement('style');
        style.id = 'askproai-tooltip-styles';
        style.textContent = `
            .${this.tooltipClass} {
                position: fixed;
                z-index: 99999;
                padding: 8px 12px;
                background-color: rgba(31, 41, 55, 0.95);
                color: white;
                font-size: 14px;
                line-height: 1.4;
                border-radius: 6px;
                pointer-events: none;
                white-space: normal;
                max-width: 300px;
                word-wrap: break-word;
                opacity: 0;
                transform: translateY(-5px);
                transition: opacity 0.2s ease, transform 0.2s ease;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            }

            .${this.tooltipClass}.show {
                opacity: 1;
                transform: translateY(0);
            }

            .${this.tooltipClass}::before {
                content: '';
                position: absolute;
                top: 100%;
                left: 50%;
                transform: translateX(-50%);
                border: 6px solid transparent;
                border-top-color: rgba(31, 41, 55, 0.95);
            }

            .${this.tooltipClass}.below::before {
                top: auto;
                bottom: 100%;
                border-top-color: transparent;
                border-bottom-color: rgba(31, 41, 55, 0.95);
            }

            /* Dark mode support */
            .dark .${this.tooltipClass} {
                background-color: rgba(229, 231, 235, 0.95);
                color: rgb(31, 41, 55);
            }

            .dark .${this.tooltipClass}::before {
                border-top-color: rgba(229, 231, 235, 0.95);
            }

            .dark .${this.tooltipClass}.below::before {
                border-top-color: transparent;
                border-bottom-color: rgba(229, 231, 235, 0.95);
            }

            /* Mobile adjustments */
            @media (max-width: 640px) {
                .${this.tooltipClass} {
                    font-size: 13px;
                    padding: 6px 10px;
                    max-width: 250px;
                }
            }

            /* Accessibility - High contrast mode */
            @media (prefers-contrast: high) {
                .${this.tooltipClass} {
                    background-color: black;
                    color: white;
                    border: 2px solid white;
                }
            }
        `;

        document.head.appendChild(style);
    }
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.adminTooltips = new AdminTooltips();
    });
} else {
    window.adminTooltips = new AdminTooltips();
}