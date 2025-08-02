// ========================================
// ULTIMATE PORTAL INTERACTIONS 2025
// Premium animations and micro-interactions
// ========================================

class UltimatePortal {
    constructor() {
        this.init();
    }

    init() {
        // Initialize all components when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setup());
        } else {
            this.setup();
        }

        // Reinitialize on Livewire navigation
        if (window.Livewire) {
            Livewire.hook('message.processed', () => {
                this.reinitialize();
            });
        }
    }

    setup() {
        this.initializeAnimations();
        this.initializeHoverEffects();
        this.initializeParallax();
        this.initializeNumberCounters();
        this.initializeTooltips();
        this.initializePageTransitions();
        this.initializeCardAnimations();
        this.initializeSmoothScroll();
        this.initializeNotifications();
        this.initializeKeyboardShortcuts();
    }

    reinitialize() {
        // Selective reinitialization for Livewire updates
        this.initializeNumberCounters();
        this.initializeTooltips();
        this.initializeCardAnimations();
    }

    // Smooth entrance animations for elements
    initializeAnimations() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.classList.add('animate-in');
                    }, index * 50); // Stagger animations
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        // Observe all animatable elements
        document.querySelectorAll('.stat-card-modern, .company-card-modern, .integration-card-premium').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            observer.observe(el);
        });

        // CSS for animation
        const style = document.createElement('style');
        style.textContent = `
            .animate-in {
                animation: fadeInUp 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
            }
            @keyframes fadeInUp {
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        `;
        document.head.appendChild(style);
    }

    // Advanced hover effects with magnetic attraction
    initializeHoverEffects() {
        const magneticElements = document.querySelectorAll('.btn-premium, .stat-card-modern');
        
        magneticElements.forEach(el => {
            el.addEventListener('mousemove', (e) => {
                const rect = el.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                const deltaX = (x - centerX) / centerX;
                const deltaY = (y - centerY) / centerY;
                
                el.style.transform = `
                    perspective(1000px)
                    rotateY(${deltaX * 5}deg)
                    rotateX(${-deltaY * 5}deg)
                    translateZ(10px)
                `;
            });
            
            el.addEventListener('mouseleave', () => {
                el.style.transform = '';
            });
        });
    }

    // Parallax scrolling for background elements
    initializeParallax() {
        let ticking = false;
        
        function updateParallax() {
            const scrolled = window.pageYOffset;
            const parallaxElements = document.querySelectorAll('.integration-header');
            
            parallaxElements.forEach(el => {
                const speed = 0.5;
                const yPos = -(scrolled * speed);
                el.style.transform = `translateY(${yPos}px)`;
            });
            
            ticking = false;
        }
        
        window.addEventListener('scroll', () => {
            if (!ticking) {
                requestAnimationFrame(updateParallax);
                ticking = true;
            }
        });
    }

    // Animated number counters
    initializeNumberCounters() {
        const counters = document.querySelectorAll('.stat-number');
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !entry.target.classList.contains('counted')) {
                    this.animateNumber(entry.target);
                    entry.target.classList.add('counted');
                }
            });
        }, { threshold: 0.5 });
        
        counters.forEach(counter => observer.observe(counter));
    }

    animateNumber(element) {
        const text = element.textContent;
        const match = text.match(/(\d+)/);
        if (!match) return;
        
        const target = parseInt(match[1]);
        const duration = 2000;
        const step = target / (duration / 16);
        let current = 0;
        
        const updateNumber = () => {
            current += step;
            if (current >= target) {
                element.textContent = text.replace(/\d+/, target);
            } else {
                element.textContent = text.replace(/\d+/, Math.floor(current));
                requestAnimationFrame(updateNumber);
            }
        };
        
        updateNumber();
    }

    // Modern tooltips
    initializeTooltips() {
        const tooltipTriggers = document.querySelectorAll('[data-tooltip]');
        
        tooltipTriggers.forEach(trigger => {
            let tooltip = null;
            
            trigger.addEventListener('mouseenter', (e) => {
                const text = trigger.getAttribute('data-tooltip');
                tooltip = document.createElement('div');
                tooltip.className = 'tooltip-modern';
                tooltip.textContent = text;
                document.body.appendChild(tooltip);
                
                const rect = trigger.getBoundingClientRect();
                tooltip.style.top = `${rect.top - tooltip.offsetHeight - 8}px`;
                tooltip.style.left = `${rect.left + (rect.width - tooltip.offsetWidth) / 2}px`;
                
                setTimeout(() => tooltip.classList.add('show'), 10);
            });
            
            trigger.addEventListener('mouseleave', () => {
                if (tooltip) {
                    tooltip.classList.remove('show');
                    setTimeout(() => tooltip.remove(), 300);
                }
            });
        });
    }

    // Smooth page transitions - DISABLED due to click handler conflicts
    initializePageTransitions() {
        // DISABLED: This was preventing normal clicks from working
        // The preventDefault on all links was causing the double-click issue
        
        // Only add entrance animation
        document.body.classList.add('page-transition-enter');
        setTimeout(() => {
            document.body.classList.remove('page-transition-enter');
            document.body.classList.add('page-transition-enter-active');
        }, 10);
    }

    // Card tilt animations
    initializeCardAnimations() {
        const cards = document.querySelectorAll('.integration-card-premium, .company-card-modern');
        
        cards.forEach(card => {
            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                const angleX = (y - centerY) / 10;
                const angleY = (centerX - x) / 10;
                
                card.style.transform = `perspective(1000px) rotateX(${angleX}deg) rotateY(${angleY}deg) scale(1.02)`;
                
                // Spotlight effect
                const spotlightGradient = `radial-gradient(circle at ${x}px ${y}px, rgba(255,255,255,0.1) 0%, transparent 40%)`;
                card.style.background = `${spotlightGradient}, ${window.getComputedStyle(card).background}`;
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = '';
                card.style.background = '';
            });
        });
    }

    // Smooth scrolling with easing
    initializeSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', (e) => {
                e.preventDefault();
                const target = document.querySelector(anchor.getAttribute('href'));
                if (target) {
                    const targetPosition = target.getBoundingClientRect().top + window.pageYOffset;
                    const startPosition = window.pageYOffset;
                    const distance = targetPosition - startPosition;
                    const duration = 1000;
                    let start = null;
                    
                    const animation = (currentTime) => {
                        if (start === null) start = currentTime;
                        const timeElapsed = currentTime - start;
                        const run = this.easeInOutCubic(timeElapsed, startPosition, distance, duration);
                        window.scrollTo(0, run);
                        if (timeElapsed < duration) requestAnimationFrame(animation);
                    };
                    
                    requestAnimationFrame(animation);
                }
            });
        });
    }

    easeInOutCubic(t, b, c, d) {
        t /= d / 2;
        if (t < 1) return c / 2 * t * t * t + b;
        t -= 2;
        return c / 2 * (t * t * t + 2) + b;
    }

    // Toast notifications
    initializeNotifications() {
        window.showNotification = (message, type = 'success') => {
            const notification = document.createElement('div');
            notification.className = `notification-toast ${type}`;
            notification.innerHTML = `
                <div class="notification-icon">
                    ${type === 'success' ? '✓' : type === 'error' ? '✕' : 'ℹ'}
                </div>
                <div class="notification-message">${message}</div>
            `;
            
            // Add styles
            const style = document.createElement('style');
            style.textContent = `
                .notification-toast {
                    position: fixed;
                    top: 2rem;
                    right: 2rem;
                    background: white;
                    padding: 1rem 1.5rem;
                    border-radius: 0.75rem;
                    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
                    display: flex;
                    align-items: center;
                    gap: 0.75rem;
                    z-index: 9999;
                    animation: slideIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                }
                .notification-toast.success { border-left: 4px solid #10b981; }
                .notification-toast.error { border-left: 4px solid #ef4444; }
                .notification-toast.info { border-left: 4px solid #3b82f6; }
                .notification-icon {
                    width: 1.5rem;
                    height: 1.5rem;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: bold;
                    color: white;
                }
                .notification-toast.success .notification-icon { background: #10b981; }
                .notification-toast.error .notification-icon { background: #ef4444; }
                .notification-toast.info .notification-icon { background: #3b82f6; }
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
            `;
            document.head.appendChild(style);
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s forwards';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        };
    }

    // Keyboard shortcuts
    initializeKeyboardShortcuts() {
        const shortcuts = {
            'cmd+k': () => document.querySelector('[data-search]')?.focus(),
            'cmd+s': (e) => {
                e.preventDefault();
                document.querySelector('[wire\\:click*="save"]')?.click();
            },
            'esc': () => {
                document.querySelector('.modal-close')?.click();
            }
        };
        
        document.addEventListener('keydown', (e) => {
            const key = `${e.metaKey || e.ctrlKey ? 'cmd+' : ''}${e.key.toLowerCase()}`;
            if (shortcuts[key]) {
                shortcuts[key](e);
            }
        });
    }
}

// Initialize the ultimate portal experience
const ultimatePortal = new UltimatePortal();

// Expose global functions for Livewire integration
window.UltimatePortal = ultimatePortal;

// Add confetti celebration effect
window.celebrate = () => {
    const duration = 3000;
    const colors = ['#a855f7', '#8b5cf6', '#7c3aed', '#6d28d9', '#5b21b6'];
    
    const createConfetti = () => {
        const confetti = document.createElement('div');
        confetti.style.cssText = `
            position: fixed;
            width: 10px;
            height: 10px;
            background: ${colors[Math.floor(Math.random() * colors.length)]};
            left: ${Math.random() * 100}%;
            top: -10px;
            transform: rotate(${Math.random() * 360}deg);
            z-index: 9999;
        `;
        document.body.appendChild(confetti);
        
        const animation = confetti.animate([
            { transform: `translateY(0) rotate(0deg)`, opacity: 1 },
            { transform: `translateY(100vh) rotate(${Math.random() * 720}deg)`, opacity: 0 }
        ], {
            duration: duration + Math.random() * 1000,
            easing: 'cubic-bezier(0.25, 0.46, 0.45, 0.94)'
        });
        
        animation.onfinish = () => confetti.remove();
    };
    
    for (let i = 0; i < 100; i++) {
        setTimeout(createConfetti, Math.random() * duration);
    }
};