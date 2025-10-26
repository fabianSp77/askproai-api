<?php

/**
 * Feature Flags Configuration
 *
 * This file contains feature flags for controlling new functionality rollout.
 * All features default to OFF (false) for safe production deployment.
 *
 * Usage:
 *   if (config('features.phonetic_matching_enabled')) {
 *       // Use phonetic matching logic
 *   }
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Phonetic Name Matching (Cologne Phonetic Algorithm)
    |--------------------------------------------------------------------------
    |
    | Enable phonetic name matching for phone-authenticated customers.
    | When enabled, customers identified by phone number can have their names
    | matched using Cologne Phonetic algorithm (German-optimized).
    |
    | Security: Only active for non-anonymous callers with verified phone numbers.
    | Anonymous callers always require exact name match for security.
    |
    | Rollout Plan:
    |   - Week 1: Deploy to production with flag OFF
    |   - Week 2: Enable for 1 test company
    |   - Week 3: Gradual rollout (10% → 50% → 100%)
    |
    | Related:
    |   - Service: App\Services\CustomerIdentification\PhoneticMatcher
    |   - Tests: Tests\Unit\Services\CustomerIdentification\PhoneticMatcherTest
    |   - Docs: ULTRATHINK_SYNTHESIS_PHONE_AUTH_IMPLEMENTATION.md
    |
    | @default false
    | @since 2025-10-06 (Call 691 fix)
    */
    'phonetic_matching_enabled' => env('FEATURE_PHONETIC_MATCHING_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Phonetic Matching - Similarity Threshold
    |--------------------------------------------------------------------------
    |
    | Minimum similarity score (0.0-1.0) required for phonetic name matching.
    |
    | Thresholds:
    |   - 1.0: Exact match
    |   - 0.85: Phonetic match (Cologne Phonetic codes identical)
    |   - 0.65: Levenshtein fallback (e.g., "Sputer" vs "Sputa")
    |
    | @default 0.65
    | @range 0.0-1.0
    */
    'phonetic_matching_threshold' => env('FEATURE_PHONETIC_MATCHING_THRESHOLD', 0.65),

    /*
    |--------------------------------------------------------------------------
    | Phonetic Matching - Test Companies
    |--------------------------------------------------------------------------
    |
    | Comma-separated list of company IDs for controlled rollout testing.
    | If empty, feature applies to all companies (when enabled).
    |
    | Example: "15,42,103"
    |
    | @default empty (all companies)
    */
    'phonetic_matching_test_companies' => array_filter(
        explode(',', env('FEATURE_PHONETIC_MATCHING_TEST_COMPANIES', ''))
    ),

    /*
    |--------------------------------------------------------------------------
    | Phonetic Matching - Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Maximum authentication attempts per hour per caller_id to prevent
    | brute force attacks.
    |
    | @default 3
    | @security CRITICAL - Do not increase without security review
    */
    'phonetic_matching_rate_limit' => env('FEATURE_PHONETIC_MATCHING_RATE_LIMIT', 3),

    /*
    |--------------------------------------------------------------------------
    | Alternative Finding for Voice AI (Retell)
    |--------------------------------------------------------------------------
    |
    | Enable intelligent alternative appointment suggestion when requested
    | time is not available during voice calls.
    |
    | When DISABLED (false): Agent offers alternatives using AppointmentAlternativeFinder
    |   - Checks Cal.com for available slots
    |   - Suggests best 2-3 alternatives (same day, next day, next week)
    |   - Smart ranking by proximity to desired time
    |   - ~1-2s additional latency per availability check
    |
    | When ENABLED (true): Agent skips alternatives (latency optimization)
    |   - Returns "not available" immediately
    |   - Agent asks customer for alternative time
    |   - No Cal.com alternative search overhead
    |   - <1s response time
    |
    | Performance Impact:
    |   - Disabled (alternatives ON): +1-2s per check, higher success rate
    |   - Enabled (alternatives OFF): <1s response, lower success rate
    |
    | Rollout Plan:
    |   - Phase A (Week 1): DISABLE flag (enable alternatives) with caching
    |   - Phase A (Week 1): Optimize timeout to 2s max with fallback
    |   - Monitor: booking success rate, call duration, user satisfaction
    |   - Phase C: Further latency optimization with parallel processing
    |
    | Related:
    |   - Service: App\Services\AppointmentAlternativeFinder
    |   - Controller: App\Http\Controllers\RetellFunctionCallHandler::handleFunctionCall
    |   - Tests: Tests\Unit\RetellFunctionCallHandler\AvailabilityCheckTest
    |   - Docs: PHASE_1_DEPLOYMENT_LINKS.md
    |
    | @default false (alternatives ENABLED for better UX)
    | @since 2025-10-19 (Phase A: Fundamental Fixes)
    */
    'skip_alternatives_for_voice' => env('FEATURE_SKIP_ALTERNATIVES_FOR_VOICE', false),

    /*
    |--------------------------------------------------------------------------
    | Customer Portal (Endkunden-Portal für Friseure)
    |--------------------------------------------------------------------------
    |
    | Aktiviert das Customer Portal unter /portal für Endkunden
    | (Friseure, deren Mitarbeiter, etc.)
    |
    | WICHTIG: Standardmäßig DEAKTIVIERT für sichere Deployments.
    | Erst nach ausgiebigem Testing auf Staging aktivieren!
    |
    | Rollout Plan:
    |   - Week 1-3: Development auf feature/customer-portal Branch
    |   - Week 4: Deploy to production with flag OFF
    |   - Week 5: Enable for 2-3 pilot customers
    |   - Week 6: Gradual rollout based on feedback
    |
    | Related:
    |   - Provider: App\Providers\Filament\CustomerPanelProvider
    |   - Resources: App\Filament\Customer\Resources\*
    |   - Policies: App\Policies\RetellCallSessionPolicy
    |   - Docs: CUSTOMER_PORTAL_GAP_ANALYSIS_2025-10-26.md
    |
    | @default false
    | @since 2025-10-26 (Phase 1: MVP)
    */
    'customer_portal' => env('FEATURE_CUSTOMER_PORTAL', false),

    /**
     * Customer Portal: Call History
     *
     * Erlaubt Kunden, ihre Anruf-Historie mit Transkripten anzusehen
     *
     * @default true (wenn Portal aktiviert)
     */
    'customer_portal_calls' => env('FEATURE_CUSTOMER_PORTAL_CALLS', true),

    /**
     * Customer Portal: Appointments
     *
     * Erlaubt Kunden, ihre Termine anzusehen (Kalender + Liste)
     *
     * @default true (wenn Portal aktiviert)
     */
    'customer_portal_appointments' => env('FEATURE_CUSTOMER_PORTAL_APPOINTMENTS', true),

    /**
     * Customer Portal: Customer Management (Phase 2)
     *
     * Erlaubt Kunden, ihre eigenen Kunden (Endkunden) zu verwalten
     *
     * @default false (Phase 2 Feature)
     */
    'customer_portal_crm' => env('FEATURE_CUSTOMER_PORTAL_CRM', false),

    /**
     * Customer Portal: Service Management (Phase 2)
     *
     * Erlaubt Kunden, ihre Dienstleistungen zu verwalten
     *
     * @default false (Phase 2 Feature)
     */
    'customer_portal_services' => env('FEATURE_CUSTOMER_PORTAL_SERVICES', false),

    /**
     * Customer Portal: Staff Management (Phase 2)
     *
     * Erlaubt Kunden, ihre Mitarbeiter zu verwalten
     *
     * @default false (Phase 2 Feature)
     */
    'customer_portal_staff' => env('FEATURE_CUSTOMER_PORTAL_STAFF', false),

    /**
     * Customer Portal: Analytics (Phase 3)
     *
     * Business Intelligence und Reporting
     *
     * @default false (Phase 3 Feature)
     */
    'customer_portal_analytics' => env('FEATURE_CUSTOMER_PORTAL_ANALYTICS', false),
];
