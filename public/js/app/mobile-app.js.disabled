// AskProAI Mobile App JavaScript

// Mobile App Class
class AskProAIMobileApp {
    constructor() {
        this.touchStartX = null;
        this.touchStartY = null;
        this.swipeThreshold = 50;
        this.isOnline = navigator.onLine;
        this.init();
    }

    init() {
        this.registerServiceWorker();
        this.setupTouchHandlers();
        this.setupOfflineHandling();
        this.setupPullToRefresh();
        this.setupSwipeGestures();
        this.setupInstallPrompt();
        this.enhanceFormInputs();
        this.setupNotifications();
    }

    // Register Service Worker for PWA
    async registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            try {
                const registration = await navigator.serviceWorker.register('/service-worker.js');
                console.log('ServiceWorker registered:', registration);
                
                // Check for updates
                registration.addEventListener('updatefound', () => {
                    const newWorker = registration.installing;
                    newWorker.addEventListener('statechange', () => {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            // New service worker available
                            this.showUpdateNotification();
                        }
                    });
                });
            } catch (error) {
                console.error('ServiceWorker registration failed:', error);
            }
        }
    }

    // Touch event handlers
    setupTouchHandlers() {
        // Prevent double-tap zoom
        let lastTouchEnd = 0;
        document.addEventListener('touchend', (e) => {
            const now = Date.now();
            if (now - lastTouchEnd <= 300) {
                e.preventDefault();
            }
            lastTouchEnd = now;
        }, { passive: false });

        // Add touch feedback
        document.addEventListener('touchstart', (e) => {
            const target = e.target.closest('.touch-feedback, button, a, .mobile-list-item');
            if (target) {
                target.classList.add('touching');
            }
        }, { passive: true });

        document.addEventListener('touchend', (e) => {
            const target = e.target.closest('.touch-feedback, button, a, .mobile-list-item');
            if (target) {
                setTimeout(() => target.classList.remove('touching'), 100);
            }
        }, { passive: true });
    }

    // Offline handling
    setupOfflineHandling() {
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.showNotification('Back online!', 'success');
            this.syncOfflineData();
        });

        window.addEventListener('offline', () => {
            this.isOnline = false;
            this.showNotification('You are offline', 'warning');
        });
    }

    // Pull to refresh implementation
    setupPullToRefresh() {
        let startY = 0;
        let currentY = 0;
        let refreshing = false;
        const threshold = 80;
        const container = document.querySelector('.mobile-content');
        const indicator = document.getElementById('refreshIndicator');

        if (!container || !indicator) return;

        container.addEventListener('touchstart', (e) => {
            if (window.scrollY === 0) {
                startY = e.touches[0].pageY;
            }
        }, { passive: true });

        container.addEventListener('touchmove', (e) => {
            if (!startY) return;

            currentY = e.touches[0].pageY;
            const diff = currentY - startY;

            if (diff > 0 && window.scrollY === 0) {
                e.preventDefault();
                
                if (diff > threshold && !refreshing) {
                    refreshing = true;
                    indicator.classList.add('visible', 'refreshing');
                    navigator.vibrate && navigator.vibrate(10);
                } else if (diff > 20) {
                    indicator.classList.add('visible');
                    indicator.style.transform = `translateY(${Math.min(diff * 0.5, 60)}px)`;
                }
            }
        }, { passive: false });

        container.addEventListener('touchend', () => {
            if (refreshing) {
                this.performRefresh();
            } else {
                indicator.classList.remove('visible');
                indicator.style.transform = '';
            }
            startY = 0;
            refreshing = false;
        }, { passive: true });
    }

    // Swipe gestures
    setupSwipeGestures() {
        document.addEventListener('touchstart', (e) => {
            this.touchStartX = e.touches[0].clientX;
            this.touchStartY = e.touches[0].clientY;
        }, { passive: true });

        document.addEventListener('touchend', (e) => {
            if (!this.touchStartX || !this.touchStartY) return;

            const touchEndX = e.changedTouches[0].clientX;
            const touchEndY = e.changedTouches[0].clientY;

            const diffX = touchEndX - this.touchStartX;
            const diffY = touchEndY - this.touchStartY;

            // Horizontal swipe
            if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > this.swipeThreshold) {
                if (diffX > 0) {
                    this.onSwipeRight();
                } else {
                    this.onSwipeLeft();
                }
            }

            this.touchStartX = null;
            this.touchStartY = null;
        }, { passive: true });
    }

    // Install prompt for PWA
    setupInstallPrompt() {
        let deferredPrompt;

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            
            // Show install button
            const installButton = document.getElementById('installButton');
            if (installButton) {
                installButton.style.display = 'block';
                installButton.addEventListener('click', async () => {
                    deferredPrompt.prompt();
                    const { outcome } = await deferredPrompt.userChoice;
                    console.log(`User response: ${outcome}`);
                    deferredPrompt = null;
                    installButton.style.display = 'none';
                });
            }
        });

        // iOS install instructions
        if (this.isIOS() && !this.isInStandaloneMode()) {
            setTimeout(() => {
                this.showIOSInstallPrompt();
            }, 5000);
        }
    }

    // Enhance form inputs for mobile
    enhanceFormInputs() {
        // Auto-resize textareas
        document.querySelectorAll('textarea').forEach(textarea => {
            textarea.addEventListener('input', () => {
                textarea.style.height = 'auto';
                textarea.style.height = textarea.scrollHeight + 'px';
            });
        });

        // Numeric keyboard for number inputs
        document.querySelectorAll('input[type="number"], input[type="tel"]').forEach(input => {
            input.setAttribute('pattern', '[0-9]*');
            input.setAttribute('inputmode', 'numeric');
        });

        // Email keyboard for email inputs
        document.querySelectorAll('input[type="email"]').forEach(input => {
            input.setAttribute('inputmode', 'email');
        });
    }

    // Setup push notifications
    async setupNotifications() {
        if ('Notification' in window && 'serviceWorker' in navigator) {
            const permission = await Notification.requestPermission();
            
            if (permission === 'granted') {
                console.log('Notification permission granted');
                // Subscribe to push notifications
                this.subscribeToPushNotifications();
            }
        }
    }

    async subscribeToPushNotifications() {
        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.urlBase64ToUint8Array(
                    'YOUR_PUBLIC_VAPID_KEY' // Replace with actual VAPID key
                )
            });

            // Send subscription to server
            await fetch('/api/notifications/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                credentials: 'include',
                body: JSON.stringify(subscription)
            });
        } catch (error) {
            console.error('Failed to subscribe to push notifications:', error);
        }
    }

    // Helper methods
    performRefresh() {
        setTimeout(() => {
            window.location.reload();
        }, 500);
    }

    onSwipeRight() {
        // Handle right swipe (e.g., go back)
        if (window.history.length > 1) {
            window.history.back();
        }
    }

    onSwipeLeft() {
        // Handle left swipe (e.g., open menu)
        const drawer = document.querySelector('.mobile-drawer');
        if (drawer) {
            drawer.classList.add('open');
        }
    }

    async syncOfflineData() {
        // Sync any offline data when coming back online
        if ('sync' in self.registration) {
            try {
                await self.registration.sync.register('sync-data');
            } catch (error) {
                console.error('Failed to register sync:', error);
            }
        }
    }

    showNotification(message, type = 'info') {
        // Create and show a toast notification
        const toast = document.createElement('div');
        toast.className = `mobile-toast mobile-toast-${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('show');
        }, 100);

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    showUpdateNotification() {
        const notification = document.createElement('div');
        notification.className = 'update-notification';
        notification.innerHTML = `
            <p>A new version of the app is available.</p>
            <button onclick="window.location.reload()">Update Now</button>
        `;
        document.body.appendChild(notification);
    }

    showIOSInstallPrompt() {
        const prompt = document.createElement('div');
        prompt.className = 'ios-install-prompt';
        prompt.innerHTML = `
            <p>Install this app on your iPhone: tap <svg>...</svg> and then "Add to Home Screen".</p>
            <button onclick="this.parentElement.remove()">Got it</button>
        `;
        document.body.appendChild(prompt);
    }

    isIOS() {
        return /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    }

    isInStandaloneMode() {
        return window.navigator.standalone || window.matchMedia('(display-mode: standalone)').matches;
    }

    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.askProAIApp = new AskProAIMobileApp();
    });
} else {
    window.askProAIApp = new AskProAIMobileApp();
}

// Export for use in other modules
export default AskProAIMobileApp;