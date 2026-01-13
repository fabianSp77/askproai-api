/**
 * AskPro Session Manager
 *
 * State-of-the-Art Session-Timeout-Handling für Filament/Livewire.
 * Zeigt Countdown-Warning vor Session-Ablauf und handhabt graceful logout.
 *
 * Features:
 * - Aktivitätserkennung (mousemove, keydown, scroll, click, touch)
 * - Debounced Session-Ping (max 1x/Minute bei Aktivität)
 * - Warning Modal 5 Minuten vor Ablauf mit Countdown
 * - "Sitzung verlängern" + "Abmelden" Buttons
 * - Auto-Logout bei Countdown = 0
 * - Livewire 419-Error Interceptor
 * - FormData-Sicherung in localStorage
 * - Dark Mode Support
 * - wire:navigate (SPA-Modus) kompatibel
 *
 * @version 1.0.0
 * @author AskPro AI Team
 * @since 2025-12-29
 */

class SessionManager {
    constructor(options = {}) {
        // Konfiguration (vom Server übergeben)
        this.sessionLifetime = options.lifetime || 120; // Minuten
        this.warningTime = options.warningTime || 5;    // Minuten vor Ablauf
        this.pingInterval = options.pingInterval || 60; // Sekunden
        this.pingEndpoint = options.pingEndpoint || '/api/session/ping';
        this.logoutUrl = options.logoutUrl || '/admin/logout';
        this.loginUrl = options.loginUrl || '/admin/login';

        // State
        this.lastActivity = Date.now();
        this.hasActivity = false;
        this.warningShown = false;
        this.countdownInterval = null;
        this.sessionCheckInterval = null;
        this.pingDebounceTimer = null;

        // Session-Ablaufzeit berechnen
        this.sessionExpiresAt = Date.now() + (this.sessionLifetime * 60 * 1000);

        this.init();
    }

    init() {
        this.bindActivityListeners();
        this.startSessionChecker();
        this.interceptLivewireErrors();
        this.handleNavigateEvents();

        // Debug-Logging (nur in Development)
        if (window.location.hostname === 'localhost' || window.location.hostname.includes('test')) {
            console.log('[SessionManager] Initialized:', {
                lifetime: this.sessionLifetime + ' min',
                warningAt: this.warningTime + ' min before expiry',
                expiresAt: new Date(this.sessionExpiresAt).toLocaleTimeString()
            });
        }
    }

    bindActivityListeners() {
        const activityEvents = ['mousemove', 'keydown', 'scroll', 'click', 'touchstart'];

        activityEvents.forEach(event => {
            document.addEventListener(event, () => this.onActivity(), { passive: true });
        });
    }

    onActivity() {
        this.lastActivity = Date.now();
        this.hasActivity = true;

        // Debounced Ping (max 1x pro Minute)
        if (!this.pingDebounceTimer) {
            this.pingDebounceTimer = setTimeout(() => {
                if (this.hasActivity) {
                    this.pingSession();
                }
                this.hasActivity = false;
                this.pingDebounceTimer = null;
            }, this.pingInterval * 1000);
        }
    }

    async pingSession() {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                console.warn('[SessionManager] CSRF token not found');
                return;
            }

            const response = await fetch(this.pingEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken.content,
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
            });

            if (response.ok) {
                const data = await response.json();
                // Session-Ablaufzeit aktualisieren
                this.sessionExpiresAt = Date.now() + (data.remaining * 1000);

                // Warning-Modal ausblenden wenn Session verlängert
                if (this.warningShown) {
                    this.hideWarningModal();
                }
            } else if (response.status === 401 || response.status === 419) {
                this.showExpiredModal();
            }
        } catch (error) {
            console.error('[SessionManager] Ping failed:', error);
        }
    }

    startSessionChecker() {
        this.sessionCheckInterval = setInterval(() => {
            const now = Date.now();
            const timeUntilExpiry = this.sessionExpiresAt - now;
            const warningThreshold = this.warningTime * 60 * 1000;

            if (timeUntilExpiry <= 0) {
                this.showExpiredModal();
            } else if (timeUntilExpiry <= warningThreshold && !this.warningShown) {
                this.showWarningModal(timeUntilExpiry);
            }
        }, 1000);
    }

    /**
     * Erstellt ein SVG-Icon-Element
     */
    createIcon(pathD) {
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('fill', 'none');
        svg.setAttribute('viewBox', '0 0 24 24');
        svg.setAttribute('stroke-width', '1.5');
        svg.setAttribute('stroke', 'currentColor');

        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('stroke-linecap', 'round');
        path.setAttribute('stroke-linejoin', 'round');
        path.setAttribute('d', pathD);

        svg.appendChild(path);
        return svg;
    }

    /**
     * Erstellt ein Button-Element
     */
    createButton(text, className, onClick) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = className;
        btn.textContent = text;
        btn.addEventListener('click', onClick);
        return btn;
    }

    showWarningModal(timeRemaining) {
        this.warningShown = true;

        // Modal-Container
        const modal = document.createElement('div');
        modal.id = 'session-warning-modal';

        // Backdrop
        const backdrop = document.createElement('div');
        backdrop.className = 'session-modal-backdrop';
        modal.appendChild(backdrop);

        // Content Container
        const content = document.createElement('div');
        content.className = 'session-modal-content';
        content.setAttribute('role', 'alertdialog');
        content.setAttribute('aria-labelledby', 'session-modal-title');
        content.setAttribute('aria-describedby', 'session-modal-desc');

        // Icon
        const iconWrapper = document.createElement('div');
        iconWrapper.className = 'session-modal-icon';
        const clockIcon = this.createIcon('M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z');
        iconWrapper.appendChild(clockIcon);
        content.appendChild(iconWrapper);

        // Title
        const title = document.createElement('h2');
        title.id = 'session-modal-title';
        title.className = 'session-modal-title';
        title.textContent = 'Sitzung läuft ab';
        content.appendChild(title);

        // Message mit Countdown
        const message = document.createElement('p');
        message.id = 'session-modal-desc';
        message.className = 'session-modal-message';
        message.textContent = 'Ihre Sitzung läuft in ';

        const countdown = document.createElement('span');
        countdown.id = 'session-countdown';
        countdown.className = 'session-countdown';
        message.appendChild(countdown);

        const messageEnd = document.createTextNode(' ab.');
        message.appendChild(messageEnd);
        content.appendChild(message);

        // Submessage
        const submessage = document.createElement('p');
        submessage.className = 'session-modal-submessage';
        submessage.textContent = 'Möchten Sie angemeldet bleiben?';
        content.appendChild(submessage);

        // Actions
        const actions = document.createElement('div');
        actions.className = 'session-modal-actions';

        const logoutBtn = this.createButton('Abmelden', 'session-btn session-btn-secondary', () => {
            this.logout();
        });
        logoutBtn.id = 'session-logout-btn';

        const continueBtn = this.createButton('Sitzung verlängern', 'session-btn session-btn-primary', () => {
            this.extendSession();
        });
        continueBtn.id = 'session-continue-btn';

        actions.appendChild(logoutBtn);
        actions.appendChild(continueBtn);
        content.appendChild(actions);

        modal.appendChild(content);
        document.body.appendChild(modal);

        // Keyboard-Navigation (Escape = Verlängern)
        modal.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.extendSession();
            }
        });

        // Focus auf "Sitzung verlängern" setzen
        setTimeout(() => {
            continueBtn.focus();
        }, 100);

        // Countdown starten
        this.startCountdown();
    }

    startCountdown() {
        const updateCountdown = () => {
            const remaining = Math.max(0, this.sessionExpiresAt - Date.now());
            const minutes = Math.floor(remaining / 60000);
            const seconds = Math.floor((remaining % 60000) / 1000);

            const countdownEl = document.getElementById('session-countdown');
            if (countdownEl) {
                countdownEl.textContent = minutes + ':' + seconds.toString().padStart(2, '0');
            }

            if (remaining <= 0) {
                clearInterval(this.countdownInterval);
                this.showExpiredModal();
            }
        };

        updateCountdown();
        this.countdownInterval = setInterval(updateCountdown, 1000);
    }

    hideWarningModal() {
        const modal = document.getElementById('session-warning-modal');
        if (modal) {
            modal.remove();
        }
        this.warningShown = false;
        if (this.countdownInterval) {
            clearInterval(this.countdownInterval);
        }
    }

    async extendSession() {
        const btn = document.getElementById('session-continue-btn');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Wird verlängert...';
        }

        await this.pingSession();
        this.hideWarningModal();
    }

    showExpiredModal() {
        // Alle Intervals stoppen
        if (this.sessionCheckInterval) clearInterval(this.sessionCheckInterval);
        if (this.countdownInterval) clearInterval(this.countdownInterval);

        // Warning-Modal entfernen falls vorhanden
        const warningModal = document.getElementById('session-warning-modal');
        if (warningModal) warningModal.remove();

        // Bereits existierendes Expired-Modal nicht duplizieren
        if (document.getElementById('session-expired-modal')) return;

        // Formulardaten sichern
        this.saveFormData();

        // Modal-Container
        const modal = document.createElement('div');
        modal.id = 'session-expired-modal';

        // Backdrop
        const backdrop = document.createElement('div');
        backdrop.className = 'session-modal-backdrop';
        modal.appendChild(backdrop);

        // Content Container
        const content = document.createElement('div');
        content.className = 'session-modal-content session-expired';
        content.setAttribute('role', 'alertdialog');
        content.setAttribute('aria-labelledby', 'expired-title');
        content.setAttribute('aria-describedby', 'expired-desc');

        // Icon (Schloss)
        const iconWrapper = document.createElement('div');
        iconWrapper.className = 'session-modal-icon session-expired-icon';
        const lockIcon = this.createIcon('M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z');
        iconWrapper.appendChild(lockIcon);
        content.appendChild(iconWrapper);

        // Title
        const title = document.createElement('h2');
        title.id = 'expired-title';
        title.className = 'session-modal-title';
        title.textContent = 'Sitzung abgelaufen';
        content.appendChild(title);

        // Message
        const message = document.createElement('p');
        message.id = 'expired-desc';
        message.className = 'session-modal-message';
        message.textContent = 'Ihre Sitzung ist aus Sicherheitsgründen abgelaufen.';
        content.appendChild(message);

        // Submessage
        const submessage = document.createElement('p');
        submessage.className = 'session-modal-submessage';
        submessage.textContent = 'Bitte melden Sie sich erneut an, um fortzufahren.';
        content.appendChild(submessage);

        // Actions
        const actions = document.createElement('div');
        actions.className = 'session-modal-actions';

        const loginLink = document.createElement('a');
        loginLink.href = this.loginUrl;
        loginLink.className = 'session-btn session-btn-primary';
        loginLink.textContent = 'Zur Anmeldung';

        actions.appendChild(loginLink);
        content.appendChild(actions);

        modal.appendChild(content);
        document.body.appendChild(modal);

        // Focus auf Link setzen
        setTimeout(() => {
            loginLink.focus();
        }, 100);
    }

    saveFormData() {
        try {
            const forms = document.querySelectorAll('form');
            const savedData = {};

            forms.forEach((form, formIndex) => {
                const formId = form.id || form.getAttribute('wire:id') || 'form-' + formIndex;
                const formData = {};

                form.querySelectorAll('input, textarea, select').forEach(input => {
                    // Keine Passwörter, Hidden-Fields oder CSRF-Tokens speichern
                    if (input.name &&
                        input.type !== 'password' &&
                        input.type !== 'hidden' &&
                        !input.name.includes('_token')) {
                        formData[input.name] = input.value;
                    }
                });

                if (Object.keys(formData).length > 0) {
                    savedData[formId] = formData;
                }
            });

            if (Object.keys(savedData).length > 0) {
                localStorage.setItem('askpro_unsaved_forms', JSON.stringify({
                    url: window.location.pathname,
                    timestamp: Date.now(),
                    data: savedData
                }));
            }
        } catch (error) {
            console.warn('[SessionManager] Could not save form data:', error);
        }
    }

    logout() {
        // Logout-Formular erstellen und absenden
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = this.logoutUrl;

        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        csrfInput.value = csrfMeta ? csrfMeta.content : '';
        form.appendChild(csrfInput);

        document.body.appendChild(form);
        form.submit();
    }

    interceptLivewireErrors() {
        // Warte bis Livewire geladen ist
        const setupInterceptor = () => {
            if (typeof Livewire === 'undefined') {
                setTimeout(setupInterceptor, 100);
                return;
            }

            // Livewire 3 Hook API
            try {
                Livewire.hook('request', ({ fail }) => {
                    fail(({ status, preventDefault }) => {
                        if (status === 419) {
                            preventDefault();
                            this.showExpiredModal();
                        }
                    });
                });
            } catch (e) {
                // Fallback für ältere Livewire-Versionen
                document.addEventListener('livewire:failed', (event) => {
                    if (event.detail && event.detail.status === 419) {
                        event.preventDefault();
                        this.showExpiredModal();
                    }
                });
            }
        };

        setupInterceptor();
    }

    handleNavigateEvents() {
        // wire:navigate (SPA-Modus) Events
        document.addEventListener('livewire:navigated', () => {
            // Session-Status nach Navigation prüfen
            const timeUntilExpiry = this.sessionExpiresAt - Date.now();
            if (timeUntilExpiry <= 0) {
                this.showExpiredModal();
            }
        });

        // Bei Seiten-Sichtbarkeit (Tab-Wechsel)
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                // Session-Status bei Tab-Rückkehr prüfen
                const timeUntilExpiry = this.sessionExpiresAt - Date.now();
                if (timeUntilExpiry <= 0) {
                    this.showExpiredModal();
                } else if (timeUntilExpiry <= this.warningTime * 60 * 1000 && !this.warningShown) {
                    this.showWarningModal(timeUntilExpiry);
                }
            }
        });
    }

    // Öffentliche API
    destroy() {
        if (this.sessionCheckInterval) clearInterval(this.sessionCheckInterval);
        if (this.countdownInterval) clearInterval(this.countdownInterval);
        if (this.pingDebounceTimer) clearTimeout(this.pingDebounceTimer);
        this.hideWarningModal();
    }
}

// Global exportieren
window.SessionManager = SessionManager;
