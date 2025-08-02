/**
 * Cookie Consent Manager
 * Manages cookies based on user consent preferences
 */
class CookieConsentManager {
    constructor() {
        this.consentKey = 'askproai_cookie_consent';
        this.consent = this.loadConsent();
        this.initializeThirdPartyScripts();
    }

    /**
     * Load consent from localStorage or cookie
     */
    loadConsent() {
        try {
            const stored = localStorage.getItem(this.consentKey);
            if (stored) {
                return JSON.parse(stored);
            }
        } catch (e) {
            console.error('Error loading consent:', e);
        }
        return null;
    }

    /**
     * Save consent preferences
     */
    saveConsent(preferences) {
        this.consent = {
            ...preferences,
            timestamp: new Date().toISOString()
        };
        
        try {
            localStorage.setItem(this.consentKey, JSON.stringify(this.consent));
        } catch (e) {
            console.error('Error saving consent:', e);
        }
    }

    /**
     * Check if category has consent
     */
    hasConsent(category) {
        if (!this.consent) return false;
        
        switch(category) {
            case 'necessary':
                return true; // Always true
            case 'functional':
                return this.consent.functional_cookies || false;
            case 'analytics':
                return this.consent.analytics_cookies || false;
            case 'marketing':
                return this.consent.marketing_cookies || false;
            default:
                return false;
        }
    }

    /**
     * Initialize third-party scripts based on consent
     */
    initializeThirdPartyScripts() {
        // Google Analytics
        if (this.hasConsent('analytics') && window.GA_TRACKING_ID) {
            this.loadGoogleAnalytics();
        }

        // Facebook Pixel
        if (this.hasConsent('marketing') && window.FB_PIXEL_ID) {
            this.loadFacebookPixel();
        }

        // Other third-party scripts
        this.initializeConsentedScripts();
    }

    /**
     * Load Google Analytics
     */
    loadGoogleAnalytics() {
        // Prevent loading if already loaded
        if (window.gtag) return;

        const script = document.createElement('script');
        script.async = true;
        script.src = `https://www.googletagmanager.com/gtag/js?id=${window.GA_TRACKING_ID}`;
        document.head.appendChild(script);

        window.dataLayer = window.dataLayer || [];
        window.gtag = function() { dataLayer.push(arguments); }
        window.gtag('js', new Date());
        window.gtag('config', window.GA_TRACKING_ID, {
            'anonymize_ip': true, // GDPR requirement
            'cookie_flags': 'SameSite=None;Secure'
        });
    }

    /**
     * Load Facebook Pixel
     */
    loadFacebookPixel() {
        // Prevent loading if already loaded
        if (window.fbq) return;

        !function(f,b,e,v,n,t,s) {
            if(f.fbq)return;n=f.fbq=function(){n.callMethod?
            n.callMethod.apply(n,arguments):n.queue.push(arguments)};
            if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
            n.queue=[];t=b.createElement(e);t.async=!0;
            t.src=v;s=b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t,s)
        }(window, document,'script','https://connect.facebook.net/en_US/fbevents.js');
        
        window.fbq('init', window.FB_PIXEL_ID);
        window.fbq('track', 'PageView');
    }

    /**
     * Initialize other consented scripts
     */
    initializeConsentedScripts() {
        // Functional cookies - enable enhanced features
        if (this.hasConsent('functional')) {
            // Enable localStorage preferences
            this.enableEnhancedFeatures();
        }

        // Marketing cookies - enable chat widgets, etc.
        if (this.hasConsent('marketing')) {
            // Load marketing tools
            this.loadMarketingTools();
        }
    }

    /**
     * Enable enhanced features that require functional cookies
     */
    enableEnhancedFeatures() {
        // Save user preferences
        window.saveUserPreference = (key, value) => {
            if (this.hasConsent('functional')) {
                localStorage.setItem(`user_pref_${key}`, value);
            }
        };

        // Load user preferences
        window.loadUserPreference = (key, defaultValue = null) => {
            if (this.hasConsent('functional')) {
                return localStorage.getItem(`user_pref_${key}`) || defaultValue;
            }
            return defaultValue;
        };
    }

    /**
     * Load marketing tools
     */
    loadMarketingTools() {
        // Example: Load Intercom, Drift, or other chat tools
        // if (window.INTERCOM_APP_ID) {
        //     this.loadIntercom();
        // }
    }

    /**
     * Delete all cookies except necessary ones
     */
    deleteNonEssentialCookies() {
        const cookies = document.cookie.split(';');
        const necessaryCookies = ['askproai_session', 'XSRF-TOKEN', this.consentKey];
        
        cookies.forEach(cookie => {
            const eqPos = cookie.indexOf('=');
            const name = eqPos > -1 ? cookie.substr(0, eqPos).trim() : cookie.trim();
            
            if (!necessaryCookies.includes(name)) {
                // Delete cookie
                document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/`;
                document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; domain=${window.location.hostname}`;
                document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; domain=.${window.location.hostname}`;
            }
        });
    }

    /**
     * Handle consent update
     */
    updateConsent(preferences) {
        const oldConsent = { ...this.consent };
        this.saveConsent(preferences);

        // If consent was revoked, delete cookies and reload
        if (oldConsent && (
            (oldConsent.analytics_cookies && !preferences.analytics_cookies) ||
            (oldConsent.marketing_cookies && !preferences.marketing_cookies)
        )) {
            this.deleteNonEssentialCookies();
            // Reload to ensure scripts are not loaded
            window.location.reload();
        } else {
            // If new consent granted, initialize scripts
            this.initializeThirdPartyScripts();
        }
    }
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.cookieConsentManager = new CookieConsentManager();
    });
} else {
    window.cookieConsentManager = new CookieConsentManager();
}

// Listen for consent updates
window.addEventListener('cookie-consent-updated', (event) => {
    if (window.cookieConsentManager) {
        window.cookieConsentManager.updateConsent(event.detail);
    }
});