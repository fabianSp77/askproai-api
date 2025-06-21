<?php

namespace App\Http\Middleware;

use App\Services\CookieConsentService;
use Closure;
use Illuminate\Http\Request;

class CheckCookieConsent
{
    protected CookieConsentService $consentService;

    public function __construct(CookieConsentService $consentService)
    {
        $this->consentService = $consentService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $requiredCategory = null)
    {
        // Skip for API routes and cookie consent routes
        if ($request->is('api/*') || $request->is('portal/cookie-consent/*')) {
            return $next($request);
        }

        // Check if consent is required for specific category
        if ($requiredCategory && !$this->consentService->hasConsent($requiredCategory)) {
            // For AJAX requests, return JSON response
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'error' => 'Cookie consent required',
                    'category' => $requiredCategory,
                ], 403);
            }

            // For regular requests, you might want to redirect or show a notice
            session()->flash('cookie_consent_required', $requiredCategory);
        }

        // Share consent status with all views
        view()->share('cookieConsent', $this->consentService->getCurrentConsent());
        view()->share('showCookieBanner', $this->consentService->shouldShowBanner());

        return $next($request);
    }
}