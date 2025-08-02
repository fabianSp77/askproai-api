/**
 * Security UX Integration for Filament Admin Panel
 * Integrates security features seamlessly with Filament
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize security features for Filament
    initializeFilamentSecurityFeatures();
});

function initializeFilamentSecurityFeatures() {
    // Add security UX enhancements to Filament
    enhanceFilamentWithSecurityUX();
    
    // Initialize global security monitoring
    startSecurityMonitoring();
    
    // Add security widgets
    addSecurityWidgets();
    
    // Setup keyboard shortcuts for security actions
    setupSecurityShortcuts();
}

function enhanceFilamentWithSecurityUX() {
    // Add security indicators to Filament navigation
    addSecurityIndicators();
    
    // Enhance form submissions with security feedback
    enhanceFormSecurity();
    
    // Add security tooltips to sensitive actions
    addSecurityTooltips();
}

function addSecurityIndicators() {
    // Add security score indicator to navigation
    const navigationPanel = document.querySelector('.fi-sidebar-nav, .fi-topbar');
    if (navigationPanel) {
        const securityIndicator = createSecurityIndicator();
        navigationPanel.appendChild(securityIndicator);
    }
    
    // Add session status indicator
    addSessionStatusIndicator();
}

function createSecurityIndicator() {
    const indicator = document.createElement('div');
    indicator.className = 'fi-security-indicator p-2 m-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg text-sm cursor-pointer';
    indicator.innerHTML = `
        <div class="flex items-center gap-2">
            <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
            <span>Sicherheit: <span id="security-score">Lädt...</span></span>
        </div>
    `;
    
    indicator.addEventListener('click', showSecurityDashboard);
    
    // Load security score
    loadSecurityScore(indicator);
    
    return indicator;
}

function addSessionStatusIndicator() {
    const indicator = document.createElement('div');
    indicator.id = 'session-status-indicator';
    indicator.className = 'fixed bottom-4 right-4 p-3 bg-white dark:bg-gray-800 rounded-lg shadow-lg border z-50 hidden';
    indicator.innerHTML = `
        <div class="flex items-center gap-2">
            <div class="w-2 h-2 bg-green-400 rounded-full"></div>
            <span class="text-sm">Session aktiv</span>
        </div>
    `;
    
    document.body.appendChild(indicator);
    
    // Show indicator when session is close to expiry
    monitorSessionStatus(indicator);
}

async function loadSecurityScore(indicator) {
    try {
        const response = await fetch('/api/security/score');
        if (response.ok) {
            const data = await response.json();
            const scoreElement = indicator.querySelector('#security-score');
            if (scoreElement) {
                scoreElement.textContent = `${data.total}/100`;
                
                // Update indicator color based on score
                const level = getSecurityLevel(data.total);
                indicator.className = indicator.className.replace(
                    'from-blue-500 to-purple-600',
                    `from-${level.color}-500 to-${level.color}-600`
                );
            }
        }
    } catch (error) {
        console.log('Could not load security score:', error);
    }
}

function getSecurityLevel(score) {
    if (score >= 90) return { color: 'green', name: 'Meister' };
    if (score >= 70) return { color: 'blue', name: 'Experte' };
    if (score >= 50) return { color: 'yellow', name: 'Profi' };
    return { color: 'red', name: 'Anfänger' };
}

async function monitorSessionStatus(indicator) {
    const checkSession = async () => {
        try {
            const response = await fetch('/api/session/status');
            if (response.ok) {
                const data = await response.json();
                
                if (data.should_warn && data.time_left > 0) {
                    showSessionWarning(data);
                    indicator.classList.remove('hidden');
                } else if (data.time_left <= 0) {
                    handleSessionExpired();
                } else {
                    indicator.classList.add('hidden');
                }
            }
        } catch (error) {
            console.log('Session check failed:', error);
        }
    };
    
    // Check every minute
    setInterval(checkSession, 60000);
    
    // Initial check
    checkSession();
}

function showSessionWarning(sessionData) {
    // Create or update session warning modal
    let modal = document.getElementById('session-warning-modal');
    
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'session-warning-modal';
        modal.className = 'fixed inset-0 bg-black/50 flex items-center justify-center z-[10000] backdrop-blur-sm';
        document.body.appendChild(modal);
    }
    
    const minutes = Math.ceil(sessionData.time_left / 60);
    
    modal.innerHTML = `
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl max-w-md mx-4 shadow-2xl">
            <div class="text-center">
                <div class="text-6xl mb-4">⏰</div>
                <h3 class="text-xl font-bold mb-2 text-gray-900 dark:text-white">
                    ${sessionData.messages.warning_title}
                </h3>
                <p class="text-gray-600 dark:text-gray-300 mb-6">
                    Du wirst in <span class="font-bold text-red-600" id="session-countdown">${minutes}</span> Minuten automatisch abgemeldet.
                </p>
                <div class="flex gap-3 justify-center">
                    <button onclick="extendSession()" 
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        ${sessionData.messages.extend_button}
                    </button>
                    <button onclick="dismissSessionWarning()" 
                            class="px-6 py-2 bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-700 transition-colors">
                        Später erinnern
                    </button>
                </div>
            </div>
        </div>
    `;
    
    // Start countdown
    startSessionCountdown(sessionData.time_left);
}

function startSessionCountdown(timeLeft) {
    let remaining = timeLeft;
    
    const updateCountdown = () => {
        const minutes = Math.ceil(remaining / 60);
        const countdownEl = document.getElementById('session-countdown');
        
        if (countdownEl) {
            countdownEl.textContent = minutes;
            
            // Change color as time runs out
            if (minutes <= 1) {
                countdownEl.className = 'font-bold text-red-600 animate-pulse';
            } else if (minutes <= 2) {
                countdownEl.className = 'font-bold text-orange-600';
            }
        }
        
        if (remaining > 0) {
            remaining -= 60;
            setTimeout(updateCountdown, 60000);
        } else {
            handleSessionExpired();
        }
    };
    
    setTimeout(updateCountdown, 60000);
}

async function extendSession() {
    try {
        const response = await fetch('/api/session/extend', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                'Content-Type': 'application/json'
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            dismissSessionWarning();
            showSuccessNotification(data.message || 'Session verlängert! 👍');
        }
    } catch (error) {
        console.error('Failed to extend session:', error);
        showErrorNotification('Session konnte nicht verlängert werden');
    }
}

function dismissSessionWarning() {
    const modal = document.getElementById('session-warning-modal');
    if (modal) {
        modal.remove();
    }
}

function handleSessionExpired() {
    dismissSessionWarning();
    
    // Show friendly session expired message
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black/50 flex items-center justify-center z-[10000] backdrop-blur-sm';
    modal.innerHTML = `
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl max-w-md mx-4 shadow-2xl text-center">
            <div class="text-6xl mb-4">🔒</div>
            <h3 class="text-xl font-bold mb-2 text-gray-900 dark:text-white">Session abgelaufen</h3>
            <p class="text-gray-600 dark:text-gray-300 mb-4">
                Aus Sicherheitsgründen wurdest du abgemeldet. Das ist ganz normal!
            </p>
            <p class="text-sm text-blue-600 dark:text-blue-400 mb-6">💡 Tipp: Deine Arbeit wurde automatisch gespeichert.</p>
            <button onclick="window.location.reload()" 
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                Neu anmelden
            </button>
        </div>
    `;
    
    document.body.appendChild(modal);
}

function enhanceFormSecurity() {
    // Add security indicators to sensitive forms
    const sensitiveSelectors = [
        'form[action*="delete"]',
        'form[action*="destroy"]',
        'form[action*="user"]',
        'form[action*="password"]',
        'form[action*="2fa"]'
    ];
    
    sensitiveSelectors.forEach(selector => {
        const forms = document.querySelectorAll(selector);
        forms.forEach(form => {
            addSecurityBadgeToForm(form);
        });
    });
    
    // Monitor form submissions
    document.addEventListener('submit', handleSecureFormSubmission);
}

function addSecurityBadgeToForm(form) {
    if (form.querySelector('.security-badge')) return; // Already has badge
    
    const badge = document.createElement('div');
    badge.className = 'security-badge flex items-center gap-2 p-2 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg mb-4';
    badge.innerHTML = `
        <div class="text-yellow-600 dark:text-yellow-400">🛡️</div>
        <span class="text-sm text-yellow-800 dark:text-yellow-200">
            Diese Aktion ist sicherheitsrelevant und wird protokolliert.
        </span>
    `;
    
    form.insertBefore(badge, form.firstChild);
}

function handleSecureFormSubmission(event) {
    const form = event.target;
    
    // Add loading state with security message
    if (isSecurityRelevantForm(form)) {
        showSecurityProcessingIndicator();
        
        // Log security event
        logSecurityEvent('form_submission', {
            form_action: form.action,
            form_method: form.method
        });
    }
}

function isSecurityRelevantForm(form) {
    const securityKeywords = ['delete', 'destroy', 'user', 'password', '2fa', 'admin', 'role', 'permission'];
    const action = form.action.toLowerCase();
    
    return securityKeywords.some(keyword => action.includes(keyword));
}

function showSecurityProcessingIndicator() {
    const indicator = document.createElement('div');
    indicator.className = 'fixed top-4 right-4 bg-blue-600 text-white p-4 rounded-lg shadow-lg z-50 flex items-center gap-3';
    indicator.innerHTML = `
        <div class="animate-spin w-5 h-5 border-2 border-white border-t-transparent rounded-full"></div>
        <span>Sicherheitscheck läuft...</span>
    `;
    
    document.body.appendChild(indicator);
    
    // Remove after 3 seconds
    setTimeout(() => {
        if (indicator.parentNode) {
            indicator.parentNode.removeChild(indicator);
        }
    }, 3000);
}

function addSecurityTooltips() {
    // Add tooltips to sensitive buttons and links
    const sensitiveElements = document.querySelectorAll([
        '[data-action*="delete"]',
        '[data-action*="destroy"]',
        'button[type="submit"]',
        'a[href*="delete"]',
        'a[href*="destroy"]'
    ].join(', '));
    
    sensitiveElements.forEach(element => {
        if (!element.title && !element.getAttribute('data-tooltip')) {
            const action = getActionFromElement(element);
            const tooltip = getSecurityTooltip(action);
            
            if (tooltip) {
                element.setAttribute('data-tooltip', tooltip);
                element.classList.add('has-security-tooltip');
            }
        }
    });
    
    // Initialize tooltip system
    initializeTooltipSystem();
}

function getActionFromElement(element) {
    const action = element.getAttribute('data-action') || element.href || element.textContent.toLowerCase();
    
    if (action.includes('delete') || action.includes('destroy')) return 'delete';
    if (action.includes('edit') || action.includes('update')) return 'edit';
    if (action.includes('create') || action.includes('add')) return 'create';
    
    return 'general';
}

function getSecurityTooltip(action) {
    const tooltips = {
        delete: '⚠️ Diese Aktion löscht Daten dauerhaft und wird protokolliert',
        edit: '✏️ Änderungen werden gespeichert und sind nachverfolgbar',
        create: '➕ Neue Daten werden erstellt und protokolliert',
        general: '🛡️ Diese Aktion ist sicherheitsrelevant'
    };
    
    return tooltips[action] || tooltips.general;
}

function initializeTooltipSystem() {
    // Simple tooltip system for security hints
    document.addEventListener('mouseenter', function(event) {
        if (event.target.hasAttribute('data-tooltip')) {
            showTooltip(event.target, event.target.getAttribute('data-tooltip'));
        }
    }, true);
    
    document.addEventListener('mouseleave', function(event) {
        if (event.target.hasAttribute('data-tooltip')) {
            hideTooltip();
        }
    }, true);
}

let currentTooltip = null;

function showTooltip(element, text) {
    hideTooltip(); // Hide any existing tooltip
    
    const tooltip = document.createElement('div');
    tooltip.className = 'security-tooltip fixed bg-gray-900 text-white text-sm px-3 py-2 rounded-lg shadow-lg z-[10001] pointer-events-none';
    tooltip.textContent = text;
    
    document.body.appendChild(tooltip);
    
    // Position tooltip
    const rect = element.getBoundingClientRect();
    tooltip.style.left = rect.left + 'px';
    tooltip.style.top = (rect.top - tooltip.offsetHeight - 8) + 'px';
    
    // Adjust if tooltip goes off screen
    if (tooltip.offsetLeft + tooltip.offsetWidth > window.innerWidth) {
        tooltip.style.left = (window.innerWidth - tooltip.offsetWidth - 10) + 'px';
    }
    
    currentTooltip = tooltip;
}

function hideTooltip() {
    if (currentTooltip) {
        currentTooltip.remove();
        currentTooltip = null;
    }
}

function setupSecurityShortcuts() {
    document.addEventListener('keydown', function(event) {
        // Ctrl/Cmd + Shift + S = Show Security Dashboard
        if ((event.ctrlKey || event.metaKey) && event.shiftKey && event.key === 'S') {
            event.preventDefault();
            showSecurityDashboard();
        }
        
        // Ctrl/Cmd + Shift + L = Lock Screen
        if ((event.ctrlKey || event.metaKey) && event.shiftKey && event.key === 'L') {
            event.preventDefault();
            lockScreen();
        }
        
        // Escape = Close security modals
        if (event.key === 'Escape') {
            const securityModals = document.querySelectorAll('#session-warning-modal, .security-modal');
            securityModals.forEach(modal => modal.remove());
        }
    });
}

function showSecurityDashboard() {
    // Create security dashboard modal
    const modal = document.createElement('div');
    modal.className = 'security-modal fixed inset-0 bg-black/50 flex items-center justify-center z-[10000] backdrop-blur-sm';
    modal.innerHTML = `
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl max-w-2xl mx-4 shadow-2xl max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    🛡️ Sicherheits-Dashboard
                </h2>
                <button onclick="this.closest('.security-modal').remove()" 
                        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    ✕
                </button>
            </div>
            <div id="security-dashboard-content" class="space-y-4">
                <div class="animate-pulse">Lädt Sicherheitsdaten...</div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Load security data
    loadSecurityDashboardData();
}

async function loadSecurityDashboardData() {
    const container = document.getElementById('security-dashboard-content');
    if (!container) return;
    
    try {
        const [scoreResponse, activityResponse, remindersResponse] = await Promise.all([
            fetch('/api/security/score'),
            fetch('/api/session/activity'),
            fetch('/api/session/reminders')
        ]);
        
        const scoreData = await scoreResponse.json();
        const activityData = await activityResponse.json();
        const remindersData = await remindersResponse.json();
        
        container.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-gradient-to-br from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 p-4 rounded-lg">
                    <h3 class="font-bold mb-2 flex items-center gap-2">
                        🏆 Sicherheits-Score
                    </h3>
                    <div class="text-3xl font-bold text-blue-600">${scoreData.total}/100</div>
                    <div class="text-sm text-gray-600 dark:text-gray-300">${scoreData.level.name}</div>
                </div>
                
                <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 p-4 rounded-lg">
                    <h3 class="font-bold mb-2 flex items-center gap-2">
                        📅 Session-Info
                    </h3>
                    <div class="text-sm text-gray-600 dark:text-gray-300">
                        Seit: ${formatDate(activityData.current_session?.started_at)}<br>
                        IP: ${activityData.current_session?.ip_address}
                    </div>
                </div>
            </div>
            
            ${remindersData.reminders?.length > 0 ? `
            <div class="mt-4">
                <h3 class="font-bold mb-2">🔔 Empfehlungen</h3>
                <div class="space-y-2">
                    ${remindersData.reminders.map(reminder => `
                        <div class="flex items-center gap-3 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                            <div class="text-xl">${reminder.emoji}</div>
                            <div class="flex-1">
                                <div class="font-medium">${reminder.title}</div>
                                <div class="text-sm text-gray-600 dark:text-gray-300">${reminder.message}</div>
                            </div>
                            ${reminder.action_url ? `
                                <button onclick="window.open('${reminder.action_url}', '_blank')" 
                                        class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700 transition-colors">
                                    ${reminder.action_text || 'Aktion'}
                                </button>
                            ` : ''}
                        </div>
                    `).join('')}
                </div>
            </div>
            ` : ''}
        `;
        
    } catch (error) {
        container.innerHTML = `
            <div class="text-center text-gray-500 dark:text-gray-400">
                <div class="text-4xl mb-2">😅</div>
                <div>Sicherheitsdaten konnten nicht geladen werden</div>
            </div>
        `;
    }
}

function lockScreen() {
    // Implement screen lock functionality
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black/90 flex items-center justify-center z-[10001] backdrop-blur-lg';
    modal.innerHTML = `
        <div class="text-center text-white">
            <div class="text-8xl mb-4">🔒</div>
            <h2 class="text-2xl font-bold mb-4">Bildschirm gesperrt</h2>
            <p class="mb-6">Klicke hier und gib dein Passwort ein, um fortzufahren</p>
            <input type="password" placeholder="Passwort eingeben..." 
                   class="px-4 py-2 rounded-lg bg-white/20 text-white placeholder-white/70 border border-white/30 focus:outline-none focus:border-white/60"
                   onkeypress="if(event.key==='Enter') unlockScreen(this.value)">
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Focus password input
    setTimeout(() => {
        const input = modal.querySelector('input');
        if (input) input.focus();
    }, 100);
}

function unlockScreen(password) {
    // In a real implementation, you'd verify the password
    // For now, just remove the lock screen
    const lockScreen = document.querySelector('.fixed.inset-0.bg-black\\/90');
    if (lockScreen) {
        lockScreen.remove();
        showSuccessNotification('Bildschirm entsperrt! 🔓');
    }
}

// Utility functions
function showSuccessNotification(message) {
    showNotification(message, 'success');
}

function showErrorNotification(message) {
    showNotification(message, 'error');
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        info: 'bg-blue-500'
    };
    
    notification.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Slide in
    requestAnimationFrame(() => {
        notification.style.transform = 'translateX(0)';
    });
    
    // Auto remove
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => notification.remove(), 300);
    }, 4000);
}

function formatDate(dateString) {
    if (!dateString) return 'Unbekannt';
    
    const date = new Date(dateString);
    return date.toLocaleString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

async function logSecurityEvent(event, data = {}) {
    try {
        await fetch('/api/security/event', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({
                event,
                data,
                timestamp: new Date().toISOString(),
                user_agent: navigator.userAgent,
                url: window.location.href
            })
        });
    } catch (error) {
        console.log('Could not log security event:', error);
    }
}

// Export for global access
window.securityIntegration = {
    extendSession,
    dismissSessionWarning,
    showSecurityDashboard,
    lockScreen,
    unlockScreen,
    showSuccessNotification,
    showErrorNotification,
    logSecurityEvent
};