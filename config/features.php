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
    | Processing Time / Split Appointments (Bearbeitungszeit)
    |--------------------------------------------------------------------------
    |
    | Enable service phase splitting (Initial → Processing → Final)
    | where staff is AVAILABLE during processing phase for parallel bookings.
    |
    | Use Case: Hairdresser can book another customer during hair dye processing time
    |
    | When ENABLED: Services with has_processing_time=1 create 3 phases:
    |   - Initial Phase: Staff required (e.g., applying dye - 15 min)
    |   - Processing Phase: Staff available (e.g., dye processing - 30 min)
    |   - Final Phase: Staff required (e.g., washing out - 15 min)
    |
    | Architecture:
    |   - Service Model: has_processing_time column + processing_time_minutes
    |   - AppointmentPhase Model: Stores individual phases with staff_required flag
    |   - AppointmentPhaseObserver: Auto-creates phases on appointment creation
    |   - WeeklyAvailabilityService: Cache isolation with :pt_{0|1} suffix
    |   - Cal.com Sync: Syncs as single event with metadata
    |
    | Rollout Strategy:
    |   - Phase 1 (Week 1): Internal testing (service whitelist only)
    |   - Phase 2 (Week 2): Pilot customers (company whitelist)
    |   - Phase 3 (Week 3+): General availability (full rollout)
    |
    | Related:
    |   - Models: App\Models\Service, AppointmentPhase, Appointment
    |   - Services: App\Services\Appointments\WeeklyAvailabilityService
    |   - Services: App\Services\AppointmentPhaseCreationService
    |   - Observer: App\Observers\AppointmentPhaseObserver
    |   - Widgets: App\Filament\Resources\AppointmentResource\Widgets\AppointmentCalendar
    |   - Docs: claudedocs/02_BACKEND/Processing_Time/
    |
    | @default false (safe deployment - opt-in per service)
    | @since 2025-10-28 (Phase 1: MVP Implementation)
    */

    /*
     * Master Toggle - Global feature enablement
     *
     * When FALSE: Processing Time completely disabled (safe mode)
     * When TRUE: Feature enabled according to whitelist/company rules
     *
     * @default false
     */
    'processing_time_enabled' => env('FEATURE_PROCESSING_TIME_ENABLED', false),

    /**
     * Service Whitelist - Specific services for controlled rollout
     *
     * Array of service UUIDs that can use Processing Time
     * regardless of global enabled state.
     *
     * Useful for:
     *   - Internal testing (specific test services)
     *   - Pilot rollout (selected services only)
     *   - Emergency disable (remove from whitelist)
     *
     * Format: Comma-separated UUIDs in .env
     * Example: FEATURE_PROCESSING_TIME_SERVICE_WHITELIST="uuid1,uuid2,uuid3"
     *
     * @default empty (no services whitelisted)
     */
    'processing_time_service_whitelist' => array_filter(
        explode(',', env('FEATURE_PROCESSING_TIME_SERVICE_WHITELIST', ''))
    ),

    /**
     * Company Whitelist - Pilot companies for gradual rollout
     *
     * Array of company IDs with access to Processing Time feature.
     * Only services from these companies can use the feature.
     *
     * Rollout phases:
     *   - Phase 1: Empty (service whitelist only)
     *   - Phase 2: [1,2,3] (3 pilot companies)
     *   - Phase 3: Empty (all companies - general availability)
     *
     * Format: Comma-separated IDs in .env
     * Example: FEATURE_PROCESSING_TIME_COMPANY_WHITELIST="1,5,12"
     *
     * @default empty (all companies when enabled=true)
     */
    'processing_time_company_whitelist' => array_filter(
        array_map('intval', explode(',', env('FEATURE_PROCESSING_TIME_COMPANY_WHITELIST', '')))
    ),

    /**
     * Frontend UI Display - Show Processing Time visualizations
     *
     * Controls visibility of phase breakdowns in:
     *   - Appointment Detail View (InfoSection with phase timeline)
     *   - Calendar Day View (phase badges under appointments)
     *   - Staff Schedule Widget (timeline view)
     *
     * Can be disabled separately for A/B testing UI impact
     * without affecting backend phase creation logic.
     *
     * @default true (show UI when feature enabled)
     */
    'processing_time_show_ui' => env('FEATURE_PROCESSING_TIME_SHOW_UI', true),

    /**
     * Cal.com Sync - Sync phases to Cal.com
     *
     * When TRUE: Appointments with phases sync to Cal.com as single events
     * When FALSE: Skip Cal.com sync (testing/development mode)
     *
     * Note: Processing Time appointments always sync as SINGLE events
     * to Cal.com with phase metadata. Cal.com handles availability
     * calculation including interleaving slots automatically.
     *
     * @default true (sync enabled)
     */
    'processing_time_calcom_sync_enabled' => env('FEATURE_PROCESSING_TIME_CALCOM_SYNC', true),

    /**
     * Automatic Phase Creation - Observer-based phase generation
     *
     * When TRUE: AppointmentPhaseObserver auto-creates phases on appointment creation
     * When FALSE: Manual phase management (testing mode)
     *
     * Disable only for:
     *   - Testing custom phase configurations
     *   - Debugging phase creation logic
     *   - Development/migration scenarios
     *
     * @default true (auto-create enabled)
     */
    'processing_time_auto_create_phases' => env('FEATURE_PROCESSING_TIME_AUTO_PHASES', true),

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
