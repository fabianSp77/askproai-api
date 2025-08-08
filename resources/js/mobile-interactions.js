/**
 * Mobile Interactions for Filament Admin
 * Adds touch gestures and mobile-specific enhancements
 */

class MobileInteractions {
    constructor() {
        this.touchStartX = 0;
        this.touchStartY = 0;
        this.isSwiping = false;
        
        if (this.isMobile()) {
            this.init();
        }
    }

    isMobile() {
        return window.matchMedia('(max-width: 768px)').matches || 
               ('ontouchstart' in window) ||
               (navigator.maxTouchPoints > 0);
    }

    init() {
        // Initialize sidebar swipe
        this.initSidebarSwipe();
        
        // Initialize table row swipe actions
        this.initTableSwipe();
        
        // Improve form inputs
        this.improveFormInputs();
        
        // Add pull to refresh
        this.initPullToRefresh();
        
        // Fix viewport on iOS
        this.fixIOSViewport();
    }

    initSidebarSwipe() {
        const sidebar = document.querySelector('.fi-sidebar');
        const overlay = document.querySelector('.fi-sidebar-overlay');
        
        if (!sidebar) return;

        // Swipe to open from left edge
        document.addEventListener('touchstart', (e) => {
            if (e.touches[0].clientX < 20) {
                this.touchStartX = e.touches[0].clientX;
                this.isSwiping = true;
            }
        });

        document.addEventListener('touchmove', (e) => {
            if (!this.isSwiping) return;
            
            const touchX = e.touches[0].clientX;
            const diff = touchX - this.touchStartX;
            
            if (diff > 0 && diff < 280) {
                sidebar.style.transform = `translateX(${diff - 280}px)`;
            }
        });

        document.addEventListener('touchend', (e) => {
            if (!this.isSwiping) return;
            
            const touchEndX = e.changedTouches[0].clientX;
            const diff = touchEndX - this.touchStartX;
            
            if (diff > 100) {
                // Open sidebar
                document.body.classList.remove('fi-sidebar-closed');
            }
            
            sidebar.style.transform = '';
            this.isSwiping = false;
        });

        // Swipe to close
        if (overlay) {
            overlay.addEventListener('touchstart', (e) => {
                this.touchStartX = e.touches[0].clientX;
            });

            overlay.addEventListener('touchend', (e) => {
                const touchEndX = e.changedTouches[0].clientX;
                if (this.touchStartX - touchEndX > 50) {
                    document.body.classList.add('fi-sidebar-closed');
                }
            });
        }
    }

    initTableSwipe() {
        const tableRows = document.querySelectorAll('.fi-ta-row');
        
        tableRows.forEach(row => {
            let startX = 0;
            let currentX = 0;
            let isSwipingRow = false;

            row.addEventListener('touchstart', (e) => {
                startX = e.touches[0].clientX;
                isSwipingRow = true;
            });

            row.addEventListener('touchmove', (e) => {
                if (!isSwipingRow) return;
                
                currentX = e.touches[0].clientX;
                const diff = startX - currentX;
                
                if (diff > 50) {
                    row.style.transform = `translateX(-${Math.min(diff, 100)}px)`;
                }
            });

            row.addEventListener('touchend', (e) => {
                if (!isSwipingRow) return;
                
                const diff = startX - currentX;
                
                if (diff > 80) {
                    row.classList.add('swiped');
                    // Show action buttons
                } else {
                    row.style.transform = '';
                    row.classList.remove('swiped');
                }
                
                isSwipingRow = false;
            });
        });
    }

    improveFormInputs() {
        // Auto-zoom prevention
        const inputs = document.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], textarea');
        
        inputs.forEach(input => {
            // Prevent zoom on iOS
            input.style.fontSize = '16px';
            
            // Add floating label effect
            input.addEventListener('focus', () => {
                const label = input.closest('.fi-fo-field')?.querySelector('.fi-fo-field-lbl');
                if (label) {
                    label.classList.add('floating');
                }
            });

            input.addEventListener('blur', () => {
                if (!input.value) {
                    const label = input.closest('.fi-fo-field')?.querySelector('.fi-fo-field-lbl');
                    if (label) {
                        label.classList.remove('floating');
                    }
                }
            });
        });

        // Improve select dropdowns on mobile
        const selects = document.querySelectorAll('select.fi-input');
        selects.forEach(select => {
            // Use native select on mobile for better UX
            select.classList.add('native-mobile-select');
        });
    }

    initPullToRefresh() {
        let startY = 0;
        let isPulling = false;
        const threshold = 100;

        document.addEventListener('touchstart', (e) => {
            if (window.scrollY === 0) {
                startY = e.touches[0].clientY;
                isPulling = true;
            }
        });

        document.addEventListener('touchmove', (e) => {
            if (!isPulling) return;
            
            const currentY = e.touches[0].clientY;
            const diff = currentY - startY;
            
            if (diff > 0 && diff < threshold * 2) {
                // Show pull to refresh indicator
                const indicator = this.getPullToRefreshIndicator();
                indicator.style.transform = `translateY(${Math.min(diff, threshold)}px)`;
                indicator.style.opacity = Math.min(diff / threshold, 1);
            }
        });

        document.addEventListener('touchend', (e) => {
            if (!isPulling) return;
            
            const endY = e.changedTouches[0].clientY;
            const diff = endY - startY;
            
            if (diff > threshold) {
                // Trigger refresh
                window.location.reload();
            } else {
                // Hide indicator
                const indicator = this.getPullToRefreshIndicator();
                indicator.style.transform = '';
                indicator.style.opacity = '';
            }
            
            isPulling = false;
        });
    }

    getPullToRefreshIndicator() {
        let indicator = document.querySelector('.pull-to-refresh-indicator');
        
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'pull-to-refresh-indicator';
            indicator.innerHTML = '<div class="spinner"></div>';
            indicator.style.cssText = `
                position: fixed;
                top: -50px;
                left: 50%;
                transform: translateX(-50%);
                width: 40px;
                height: 40px;
                background: white;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                transition: transform 0.2s, opacity 0.2s;
                opacity: 0;
                z-index: 1000;
            `;
            document.body.appendChild(indicator);
        }
        
        return indicator;
    }

    fixIOSViewport() {
        // Prevent iOS bounce scrolling
        document.body.addEventListener('touchmove', (e) => {
            if (e.target.closest('.fi-sidebar, .fi-modal-window')) {
                return; // Allow scrolling in specific containers
            }
            
            if (document.body.scrollHeight <= window.innerHeight) {
                e.preventDefault();
            }
        }, { passive: false });

        // Fix iOS viewport height
        const setViewportHeight = () => {
            const vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);
        };

        setViewportHeight();
        window.addEventListener('resize', setViewportHeight);
        window.addEventListener('orientationchange', setViewportHeight);
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.mobileInteractions = new MobileInteractions();
    });
} else {
    window.mobileInteractions = new MobileInteractions();
}

// Export for use in other modules
export default MobileInteractions;