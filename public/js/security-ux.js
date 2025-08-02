/**
 * Security UX Enhancements for AskProAI
 * Provides delightful user experiences for security features
 */

class SecurityUXManager {
    constructor() {
        this.rateLimitWarningThreshold = 80; // Show warning at 80% of limit
        this.sessionWarningTime = 5 * 60; // 5 minutes before expiry
        this.init();
    }

    init() {
        this.setupRateLimitMonitoring();
        this.setupSessionMonitoring();
        this.setupTwoFactorWizard();
        this.setupTenantSwitcher();
        this.setupSecurityScore();
        this.setupSuccessAnimations();
        this.loadMessages();
    }

    // Load localized messages
    loadMessages() {
        this.messages = window.securityMessages || {
            rateLimit: {
                warning: 'Du warst gerade sehr aktiv! 🚀 Kurze Pause gefällig?',
                blocked: 'Kurze Verschnaufpause! ☕ Du kannst gleich weiter machen.',
                countdown: 'Weiter in {seconds} Sekunden'
            },
            session: {
                warning: 'Session läuft in {minutes} Minuten ab',
                expired: 'Session abgelaufen - bitte melde dich erneut an'
            },
            twofa: {
                step1: 'QR-Code mit deiner App scannen 📱',
                step2: '6-stelligen Code eingeben 🔢',
                step3: 'Fertig! Du bist jetzt super sicher! 🎉'
            }
        };
    }

    // Rate Limiting UX
    setupRateLimitMonitoring() {
        // Monitor API response headers for rate limit info
        const originalFetch = window.fetch;
        window.fetch = async (...args) => {
            const response = await originalFetch(...args);
            this.handleRateLimitHeaders(response);
            return response;
        };

        // Also monitor Axios if available
        if (window.axios) {
            window.axios.interceptors.response.use(
                response => {
                    this.handleRateLimitHeaders(response);
                    return response;
                },
                error => {
                    if (error.response?.status === 429) {
                        this.showRateLimitBlock(error.response);
                    }
                    return Promise.reject(error);
                }
            );
        }
    }

    handleRateLimitHeaders(response) {
        const limit = parseInt(response.headers.get('X-RateLimit-Limit'));
        const remaining = parseInt(response.headers.get('X-RateLimit-Remaining'));
        const retryAfter = parseInt(response.headers.get('Retry-After'));

        if (limit && remaining !== null) {
            const usedPercentage = ((limit - remaining) / limit) * 100;
            
            if (usedPercentage >= this.rateLimitWarningThreshold && remaining > 0) {
                this.showRateLimitWarning(remaining, limit);
            }
        }

        if (response.status === 429 && retryAfter) {
            this.showRateLimitBlock(response, retryAfter);
        }
    }

    showRateLimitWarning(remaining, limit) {
        const indicator = this.createRateLimitIndicator('warning');
        const percentage = (remaining / limit) * 100;
        
        indicator.innerHTML = `
            <div class="flex items-center justify-between mb-2">
                <span class="font-medium">Gleich ist Pause! 🚀</span>
                <span class="text-sm opacity-75">${remaining}/${limit}</span>
            </div>
            <div class="rate-limit-progress">
                <div class="rate-limit-progress-bar" style="width: ${percentage}%"></div>
            </div>
            <div class="text-sm mt-2 opacity-90">Noch ${remaining} Anfragen verfügbar</div>
        `;

        this.showIndicator(indicator, 5000);
    }

    showRateLimitBlock(response, retryAfter = 60) {
        const indicator = this.createRateLimitIndicator('danger');
        let timeLeft = retryAfter;

        const updateCountdown = () => {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            const timeString = minutes > 0 ? `${minutes}m ${seconds}s` : `${seconds}s`;

            indicator.innerHTML = `
                <div class="text-center">
                    <div class="text-2xl mb-2">☕</div>
                    <div class="font-bold mb-2">Kurze Kaffeepause!</div>
                    <div class="rate-limit-countdown">${timeString}</div>
                    <div class="text-sm opacity-90">Du warst richtig fleißig heute! 💪</div>
                    <div class="rate-limit-progress mt-3">
                        <div class="rate-limit-progress-bar" style="width: ${((retryAfter - timeLeft) / retryAfter) * 100}%"></div>
                    </div>
                </div>
            `;

            if (timeLeft > 0) {
                timeLeft--;
                setTimeout(updateCountdown, 1000);
            } else {
                this.hideIndicator(indicator);
                this.showSuccessMessage('Bereit! Du kannst wieder loslegen 🚀');
            }
        };

        updateCountdown();
        this.showIndicator(indicator);
    }

    createRateLimitIndicator(type = 'info') {
        const indicator = document.createElement('div');
        indicator.className = `rate-limit-indicator ${type}`;
        indicator.style.zIndex = '10000';
        return indicator;
    }

    showIndicator(indicator, duration = null) {
        document.body.appendChild(indicator);
        
        // Trigger animation
        requestAnimationFrame(() => {
            indicator.classList.add('show');
        });

        if (duration) {
            setTimeout(() => this.hideIndicator(indicator), duration);
        }
    }

    hideIndicator(indicator) {
        indicator.classList.remove('show');
        setTimeout(() => {
            if (indicator.parentNode) {
                indicator.parentNode.removeChild(indicator);
            }
        }, 300);
    }

    // Session Management UX
    setupSessionMonitoring() {
        this.sessionWarningShown = false;
        
        // Check session status periodically
        setInterval(() => {
            this.checkSessionStatus();
        }, 60000); // Check every minute
    }

    async checkSessionStatus() {
        try {
            const response = await fetch('/api/session/status', {
                method: 'GET',
                credentials: 'same-origin'
            });
            
            if (response.ok) {
                const data = await response.json();
                const timeLeft = data.time_left; // seconds until expiry
                
                if (timeLeft <= this.sessionWarningTime && !this.sessionWarningShown) {
                    this.showSessionWarning(timeLeft);
                }
            }
        } catch (error) {
            console.log('Session check failed:', error);
        }
    }

    showSessionWarning(timeLeft) {
        this.sessionWarningShown = true;
        const minutes = Math.ceil(timeLeft / 60);
        
        const modal = document.createElement('div');
        modal.className = 'session-warning-modal';
        modal.innerHTML = `
            <div class="session-warning-content">
                <div class="text-6xl mb-4">⏰</div>
                <h3 class="text-xl font-bold mb-2">Session läuft bald ab</h3>
                <p class="text-gray-600 mb-4">Du wirst in <span id="session-countdown">${minutes}</span> Minuten automatisch abgemeldet.</p>
                <div class="flex gap-3 justify-center">
                    <button onclick="securityUX.extendSession()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Session verlängern
                    </button>
                    <button onclick="securityUX.dismissSessionWarning()" class="px-6 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition-colors">
                        Später erinnern
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Update countdown
        let countdown = timeLeft;
        const updateSessionCountdown = () => {
            const mins = Math.ceil(countdown / 60);
            const countdownEl = document.getElementById('session-countdown');
            if (countdownEl) {
                countdownEl.textContent = mins;
            }
            
            if (countdown > 0) {
                countdown -= 60;
                setTimeout(updateSessionCountdown, 60000);
            } else {
                this.handleSessionExpired();
            }
        };
        
        setTimeout(updateSessionCountdown, 60000);
    }

    async extendSession() {
        try {
            const response = await fetch('/api/session/extend', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                }
            });
            
            if (response.ok) {
                this.dismissSessionWarning();
                this.showSuccessMessage('Session verlängert! Du kannst weiter arbeiten 👍');
                this.sessionWarningShown = false;
            }
        } catch (error) {
            console.error('Failed to extend session:', error);
        }
    }

    dismissSessionWarning() {
        const modal = document.querySelector('.session-warning-modal');
        if (modal) {
            modal.remove();
        }
        // Show warning again in 2 minutes
        setTimeout(() => {
            this.sessionWarningShown = false;
        }, 120000);
    }

    handleSessionExpired() {
        this.dismissSessionWarning();
        this.showSessionExpiredMessage();
    }

    showSessionExpiredMessage() {
        const modal = document.createElement('div');
        modal.className = 'session-warning-modal';
        modal.innerHTML = `
            <div class="session-warning-content">
                <div class="text-6xl mb-4">🔒</div>
                <h3 class="text-xl font-bold mb-2">Session abgelaufen</h3>
                <p class="text-gray-600 mb-4">Aus Sicherheitsgründen wurdest du abgemeldet. Das ist ganz normal!</p>
                <p class="text-sm text-blue-600 mb-4">💡 Tipp: Deine Arbeit wurde automatisch gespeichert.</p>
                <button onclick="window.location.reload()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Neu anmelden
                </button>
            </div>
        `;
        
        document.body.appendChild(modal);
    }

    // Two-Factor Authentication Wizard
    setupTwoFactorWizard() {
        const wizard = document.querySelector('.twofa-wizard');
        if (!wizard) return;

        this.currentStep = 0;
        this.totalSteps = wizard.querySelectorAll('.twofa-step').length;
        
        this.showTwoFactorStep(0);
        this.setupTwoFactorNavigation();
    }

    showTwoFactorStep(stepIndex) {
        const steps = document.querySelectorAll('.twofa-step');
        const progressDots = document.querySelectorAll('.twofa-progress-dot');
        
        // Hide all steps
        steps.forEach(step => step.classList.remove('active'));
        
        // Show current step
        if (steps[stepIndex]) {
            steps[stepIndex].classList.add('active');
        }
        
        // Update progress
        progressDots.forEach((dot, index) => {
            dot.classList.remove('active', 'completed');
            if (index < stepIndex) {
                dot.classList.add('completed');
            } else if (index === stepIndex) {
                dot.classList.add('active');
            }
        });

        this.currentStep = stepIndex;
    }

    setupTwoFactorNavigation() {
        // Auto-advance on QR code scan (simulated)
        setTimeout(() => {
            if (this.currentStep === 0) {
                this.nextTwoFactorStep();
            }
        }, 3000);

        // Setup code input validation
        const codeInput = document.querySelector('#twofa-code');
        if (codeInput) {
            codeInput.addEventListener('input', (e) => {
                if (e.target.value.length === 6) {
                    this.validateTwoFactorCode(e.target.value);
                }
            });
        }
    }

    nextTwoFactorStep() {
        if (this.currentStep < this.totalSteps - 1) {
            this.showTwoFactorStep(this.currentStep + 1);
        }
    }

    async validateTwoFactorCode(code) {
        try {
            const response = await fetch('/api/2fa/validate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify({ code })
            });

            if (response.ok) {
                this.nextTwoFactorStep();
                setTimeout(() => {
                    this.showTwoFactorSuccess();
                }, 1000);
            } else {
                this.showTwoFactorError('Code ist nicht korrekt. Versuche es nochmal! 🤔');
            }
        } catch (error) {
            this.showTwoFactorError('Etwas ist schief gelaufen. Probiere es nochmal! 🔄');
        }
    }

    showTwoFactorSuccess() {
        this.triggerConfetti();
        this.showSuccessMessage('🎉 Zwei-Faktor-Auth aktiviert! Du bist jetzt ein Sicherheits-Champion! 🏆');
        
        // Update security score
        this.updateSecurityScore(30);
    }

    showTwoFactorError(message) {
        const errorEl = document.querySelector('.twofa-error');
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.style.display = 'block';
            setTimeout(() => {
                errorEl.style.display = 'none';
            }, 3000);
        }
    }

    // Tenant/Company Switcher
    setupTenantSwitcher() {
        const switcher = document.querySelector('.tenant-switcher');
        if (!switcher) return;

        const button = switcher.querySelector('.tenant-switcher-button');
        const dropdown = switcher.querySelector('.tenant-switcher-dropdown');

        button?.addEventListener('click', () => {
            dropdown?.classList.toggle('show');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!switcher.contains(e.target)) {
                dropdown?.classList.remove('show');
            }
        });

        // Handle tenant selection
        const options = switcher.querySelectorAll('.tenant-option');
        options.forEach(option => {
            option.addEventListener('click', (e) => {
                const tenantId = e.target.dataset.tenantId;
                const tenantName = e.target.dataset.tenantName;
                this.switchTenant(tenantId, tenantName);
            });
        });
    }

    async switchTenant(tenantId, tenantName) {
        this.showTenantSwitchLoading(tenantName);

        try {
            const response = await fetch('/api/tenant/switch', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify({ tenant_id: tenantId })
            });

            if (response.ok) {
                this.showTenantSwitchSuccess(tenantName);
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                this.showTenantSwitchError(tenantName);
            }
        } catch (error) {
            this.showTenantSwitchError(tenantName);
        }
    }

    showTenantSwitchLoading(tenantName) {
        const modal = document.createElement('div');
        modal.className = 'session-warning-modal';
        modal.id = 'tenant-switch-modal';
        
        const steps = [
            'Authentifizierung...',
            'Daten laden...',
            'Oberfläche vorbereiten...',
            'Bereit!'
        ];

        let currentStep = 0;
        modal.innerHTML = `
            <div class="security-loading">
                <div class="security-loading-spinner"></div>
                <h3 class="text-lg font-bold mb-2">Wechsle zu ${tenantName}</h3>
                <div class="security-loading-steps">
                    ${steps.map((step, index) => `
                        <div class="security-loading-step ${index === 0 ? 'active' : ''}" data-step="${index}">
                            ${step}
                        </div>
                    `).join('')}
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Animate through steps
        const interval = setInterval(() => {
            currentStep++;
            if (currentStep < steps.length) {
                const stepEls = modal.querySelectorAll('.security-loading-step');
                stepEls.forEach((el, index) => {
                    el.classList.toggle('active', index === currentStep);
                });
            } else {
                clearInterval(interval);
            }
        }, 500);
    }

    showTenantSwitchSuccess(tenantName) {
        const modal = document.getElementById('tenant-switch-modal');
        if (modal) {
            modal.innerHTML = `
                <div class="security-success">
                    <div class="security-success-icon">🎉</div>
                    <h3 class="text-xl font-bold mb-2">Erfolgreich gewechselt!</h3>
                    <p class="text-gray-600">Du arbeitest jetzt als ${tenantName}</p>
                </div>
            `;
        }
    }

    showTenantSwitchError(tenantName) {
        const modal = document.getElementById('tenant-switch-modal');
        if (modal) {
            modal.innerHTML = `
                <div class="security-loading">
                    <div class="text-6xl mb-4">😅</div>
                    <h3 class="text-xl font-bold mb-2">Wechsel nicht möglich</h3>
                    <p class="text-gray-600 mb-4">Der Wechsel zu ${tenantName} hat nicht geklappt.</p>
                    <button onclick="this.closest('.session-warning-modal').remove()" 
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        OK, verstanden
                    </button>
                </div>
            `;
        }
    }

    // Security Score Management
    setupSecurityScore() {
        this.securityScore = this.loadSecurityScore();
        this.updateSecurityScoreDisplay();
    }

    loadSecurityScore() {
        const saved = localStorage.getItem('askproai_security_score');
        return saved ? JSON.parse(saved) : {
            total: 40,
            breakdown: {
                basic_auth: 20,
                regular_activity: 10,
                device_trust: 10
            }
        };
    }

    updateSecurityScore(points) {
        this.securityScore.total = Math.min(100, this.securityScore.total + points);
        localStorage.setItem('askproai_security_score', JSON.stringify(this.securityScore));
        this.updateSecurityScoreDisplay();
        this.showSecurityScoreImprovement(points);
    }

    updateSecurityScoreDisplay() {
        const widget = document.querySelector('.security-score-widget');
        if (!widget) return;

        const level = this.getSecurityLevel(this.securityScore.total);
        const progressBar = widget.querySelector('.security-score-progress-bar');
        const levelEl = widget.querySelector('.security-score-level');
        const emojiEl = widget.querySelector('.security-score-emoji');

        if (progressBar) {
            progressBar.style.width = `${this.securityScore.total}%`;
        }

        if (levelEl) {
            levelEl.innerHTML = `
                <div class="security-score-emoji">${level.emoji}</div>
                <div>
                    <div class="font-bold">${level.name}</div>
                    <div class="text-sm opacity-75">${level.description}</div>
                </div>
            `;
        }
    }

    getSecurityLevel(score) {
        if (score >= 90) return { name: 'Sicherheits-Meister', emoji: '👑', description: 'Legendär! Du bist die Benchmark!' };
        if (score >= 70) return { name: 'Sicherheits-Experte', emoji: '🏆', description: 'Wow! Du bist ein Champion!' };
        if (score >= 50) return { name: 'Sicherheits-Profi', emoji: '🔒', description: 'Sehr gut! Du kennst dich aus.' };
        return { name: 'Sicherheits-Anfänger', emoji: '🛡️', description: 'Du hast die Grundlagen drauf!' };
    }

    showSecurityScoreImprovement(points) {
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-4 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300';
        notification.innerHTML = `
            <div class="flex items-center gap-3">
                <div class="text-2xl">🎯</div>
                <div>
                    <div class="font-bold">+${points} Sicherheitspunkte!</div>
                    <div class="text-sm opacity-90">Dein Score: ${this.securityScore.total}/100</div>
                </div>
            </div>
        `;

        document.body.appendChild(notification);

        requestAnimationFrame(() => {
            notification.style.transform = 'translateX(0)';
        });

        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // Success Animations & Celebrations
    setupSuccessAnimations() {
        // Listen for success events
        document.addEventListener('security-success', (e) => {
            this.showSuccessMessage(e.detail.message);
            if (e.detail.celebrate) {
                this.triggerConfetti();
            }
        });
    }

    showSuccessMessage(message) {
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-4 rounded-lg shadow-lg z-50 transform -translate-y-full transition-all duration-500';
        notification.innerHTML = `
            <div class="flex items-center gap-3">
                <div class="text-xl">✅</div>
                <div class="font-medium">${message}</div>
            </div>
        `;

        document.body.appendChild(notification);

        requestAnimationFrame(() => {
            notification.style.transform = 'translateY(0)';
        });

        setTimeout(() => {
            notification.style.transform = 'translateY(-100%)';
            setTimeout(() => notification.remove(), 500);
        }, 4000);
    }

    triggerConfetti() {
        const colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'];
        const confettiCount = 50;

        for (let i = 0; i < confettiCount; i++) {
            const confetti = document.createElement('div');
            confetti.className = 'confetti';
            confetti.style.left = Math.random() * 100 + 'vw';
            confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
            confetti.style.animationDelay = Math.random() * 3 + 's';
            confetti.style.animationDuration = (Math.random() * 3 + 2) + 's';
            
            document.body.appendChild(confetti);

            setTimeout(() => confetti.remove(), 5000);
        }
    }

    // Utility Methods
    showPermissionDenied(reason = 'general') {
        const modal = document.createElement('div');
        modal.className = 'session-warning-modal';
        modal.innerHTML = `
            <div class="permission-denied-screen">
                <div class="permission-denied-icon">🚫</div>
                <h3 class="text-2xl font-bold mb-4">Diese Funktion ist gerade nicht verfügbar</h3>
                <p class="text-gray-600 mb-6">Du hast momentan keine Berechtigung für diese Aktion.</p>
                <div class="permission-action-cards">
                    <a href="/contact" class="permission-action-card">
                        <div class="text-2xl mb-2">👥</div>
                        <div class="font-bold">Administrator kontaktieren</div>
                        <div class="text-sm text-gray-600">Lass dir Zugriff geben</div>
                    </a>
                    <button onclick="history.back()" class="permission-action-card">
                        <div class="text-2xl mb-2">↩️</div>
                        <div class="font-bold">Zurück gehen</div>
                        <div class="text-sm text-gray-600">Zur vorherigen Seite</div>
                    </button>
                    <a href="/help" class="permission-action-card">
                        <div class="text-2xl mb-2">📖</div>
                        <div class="font-bold">Hilfe anzeigen</div>
                        <div class="text-sm text-gray-600">Mehr über Berechtigungen</div>
                    </a>
                </div>
                <button onclick="this.closest('.session-warning-modal').remove()" 
                        class="mt-6 px-6 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition-colors">
                    Schließen
                </button>
            </div>
        `;

        document.body.appendChild(modal);
    }
}

// Initialize Security UX Manager
const securityUX = new SecurityUXManager();

// Export for global access
window.securityUX = securityUX;