<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CookieConsentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CookieConsentController extends Controller
{
    protected CookieConsentService $consentService;

    public function __construct(CookieConsentService $consentService)
    {
        $this->consentService = $consentService;
    }

    /**
     * Get current consent status
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'has_consent' => $this->consentService->hasConsent(),
            'consent_details' => $this->consentService->getCurrentConsent(),
            'categories' => $this->consentService->getCookieCategories(),
        ]);
    }

    /**
     * Save cookie consent via AJAX
     */
    public function save(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'functional_cookies' => 'boolean',
            'analytics_cookies' => 'boolean',
            'marketing_cookies' => 'boolean',
        ]);

        $consent = $this->consentService->saveConsent($validated);

        return response()->json([
            'success' => true,
            'message' => __('Cookie-Einstellungen gespeichert'),
            'consent_id' => $consent->id,
        ]);
    }

    /**
     * Accept all cookies
     */
    public function acceptAll(): JsonResponse
    {
        $consent = $this->consentService->saveConsent([
            'functional_cookies' => true,
            'analytics_cookies' => true,
            'marketing_cookies' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('Alle Cookies akzeptiert'),
            'consent_id' => $consent->id,
        ]);
    }

    /**
     * Reject all non-essential cookies
     */
    public function rejectAll(): JsonResponse
    {
        $consent = $this->consentService->saveConsent([
            'functional_cookies' => false,
            'analytics_cookies' => false,
            'marketing_cookies' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('Nur notwendige Cookies akzeptiert'),
            'consent_id' => $consent->id,
        ]);
    }

    /**
     * Withdraw consent
     */
    public function withdraw(): JsonResponse
    {
        $this->consentService->withdrawConsent();

        return response()->json([
            'success' => true,
            'message' => __('Cookie-Einwilligung zurückgezogen'),
        ]);
    }
}