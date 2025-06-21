<?php

namespace App\Services;

use App\Models\CookieConsent;
use App\Models\Customer;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;

class CookieConsentService
{
    const CONSENT_COOKIE_NAME = 'askproai_cookie_consent';
    const CONSENT_DURATION_DAYS = 365;

    /**
     * Check if user has given consent
     */
    public function hasConsent(?string $category = null): bool
    {
        $consent = $this->getCurrentConsent();
        
        if (!$consent) {
            return false;
        }

        if ($category === null) {
            return true;
        }

        return $this->hasConsentForCategory($consent, $category);
    }

    /**
     * Get current consent status
     */
    public function getCurrentConsent(): ?array
    {
        // First check database if user is logged in
        if (auth('customer')->check()) {
            $consent = CookieConsent::where('customer_id', auth('customer')->id())
                ->whereNull('withdrawn_at')
                ->latest('consented_at')
                ->first();
                
            if ($consent) {
                return $consent->toArray();
            }
        }

        // Check cookie for anonymous users
        $cookieValue = Cookie::get(self::CONSENT_COOKIE_NAME);
        if ($cookieValue) {
            return json_decode($cookieValue, true);
        }

        return null;
    }

    /**
     * Save consent preferences
     */
    public function saveConsent(array $preferences): CookieConsent
    {
        Log::info('Saving cookie consent', [
            'customer_id' => auth('customer')->id(),
            'preferences' => $preferences,
        ]);

        // Save to database
        $consent = CookieConsent::createFromRequest($preferences);

        // Also save to cookie for quick access
        $cookieData = [
            'necessary' => true,
            'functional' => $preferences['functional_cookies'] ?? false,
            'analytics' => $preferences['analytics_cookies'] ?? false,
            'marketing' => $preferences['marketing_cookies'] ?? false,
            'timestamp' => now()->toIso8601String(),
        ];

        Cookie::queue(
            self::CONSENT_COOKIE_NAME,
            json_encode($cookieData),
            60 * 24 * self::CONSENT_DURATION_DAYS,
            '/',
            null,
            true, // secure
            false // httponly - need JS access
        );

        // Clean non-consented cookies
        $this->cleanNonConsentedCookies($cookieData);

        return $consent;
    }

    /**
     * Withdraw consent
     */
    public function withdrawConsent(): void
    {
        Log::info('Withdrawing cookie consent', [
            'customer_id' => auth('customer')->id(),
        ]);

        // Update database records
        if (auth('customer')->check()) {
            CookieConsent::where('customer_id', auth('customer')->id())
                ->whereNull('withdrawn_at')
                ->update(['withdrawn_at' => now()]);
        }

        // Remove consent cookie
        Cookie::queue(Cookie::forget(self::CONSENT_COOKIE_NAME));

        // Remove all non-necessary cookies
        $this->cleanNonConsentedCookies(['necessary' => true]);
    }

    /**
     * Check if consent given for specific category
     */
    private function hasConsentForCategory($consent, string $category): bool
    {
        $mapping = [
            'necessary' => 'necessary_cookies',
            'functional' => 'functional_cookies',
            'analytics' => 'analytics_cookies',
            'marketing' => 'marketing_cookies',
        ];

        if (is_array($consent)) {
            return $consent[$category] ?? false;
        }

        $field = $mapping[$category] ?? null;
        return $field ? ($consent->$field ?? false) : false;
    }

    /**
     * Clean cookies that don't have consent
     */
    private function cleanNonConsentedCookies(array $consentedCategories): void
    {
        $cookieCategories = config('gdpr.cookie_categories', [
            'functional' => ['askproai_session', 'XSRF-TOKEN'],
            'analytics' => ['_ga', '_gid', '_gat', 'gtag'],
            'marketing' => ['_fbp', 'fr', 'tr'],
        ]);

        foreach ($cookieCategories as $category => $cookies) {
            if (!($consentedCategories[$category] ?? false)) {
                foreach ($cookies as $cookieName) {
                    Cookie::queue(Cookie::forget($cookieName));
                }
            }
        }
    }

    /**
     * Get cookie categories with descriptions
     */
    public function getCookieCategories(): array
    {
        return [
            'necessary' => [
                'name' => __('Notwendige Cookies'),
                'description' => __('Diese Cookies sind für die Grundfunktionen der Website erforderlich und können nicht deaktiviert werden.'),
                'required' => true,
                'cookies' => [
                    'askproai_session' => __('Sitzungs-Cookie für die Anmeldung'),
                    'XSRF-TOKEN' => __('Sicherheits-Cookie zum Schutz vor Cross-Site-Request-Forgery'),
                    self::CONSENT_COOKIE_NAME => __('Speichert Ihre Cookie-Einstellungen'),
                ],
            ],
            'functional' => [
                'name' => __('Funktionale Cookies'),
                'description' => __('Diese Cookies ermöglichen erweiterte Funktionen und Personalisierung.'),
                'required' => false,
                'cookies' => [
                    'locale' => __('Speichert Ihre Spracheinstellung'),
                    'timezone' => __('Speichert Ihre Zeitzone für korrekte Terminanzeige'),
                ],
            ],
            'analytics' => [
                'name' => __('Analyse-Cookies'),
                'description' => __('Diese Cookies helfen uns zu verstehen, wie Besucher mit unserer Website interagieren.'),
                'required' => false,
                'cookies' => [
                    '_ga' => __('Google Analytics - Eindeutige Besucher-ID'),
                    '_gid' => __('Google Analytics - Unterscheidung von Besuchern'),
                ],
            ],
            'marketing' => [
                'name' => __('Marketing-Cookies'),
                'description' => __('Diese Cookies werden verwendet, um Werbung relevanter für Sie zu machen.'),
                'required' => false,
                'cookies' => [
                    '_fbp' => __('Facebook Pixel - Tracking für Werbezwecke'),
                ],
            ],
        ];
    }

    /**
     * Check if cookie banner should be shown
     */
    public function shouldShowBanner(): bool
    {
        return !$this->hasConsent();
    }

    /**
     * Get consent statistics for a company
     */
    public function getConsentStatistics(int $companyId): array
    {
        $baseQuery = CookieConsent::join('customers', 'cookie_consents.customer_id', '=', 'customers.id')
            ->where('customers.company_id', $companyId)
            ->whereNull('cookie_consents.withdrawn_at');

        return [
            'total_consents' => $baseQuery->count(),
            'functional_enabled' => $baseQuery->where('functional_cookies', true)->count(),
            'analytics_enabled' => $baseQuery->where('analytics_cookies', true)->count(),
            'marketing_enabled' => $baseQuery->where('marketing_cookies', true)->count(),
            'withdrawn' => CookieConsent::join('customers', 'cookie_consents.customer_id', '=', 'customers.id')
                ->where('customers.company_id', $companyId)
                ->whereNotNull('cookie_consents.withdrawn_at')
                ->count(),
        ];
    }
}