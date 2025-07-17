<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     */
    protected $middleware = [
        // \App\Http\Middleware\TrustHosts::class,
        // \App\Http\Middleware\ResponseWrapper::class, // Disabled - interferes with Livewire
        // \App\Http\Middleware\EnsureProperResponseFormat::class, // Disabled - interferes with Livewire
        Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        Middleware\ReleaseDbConnection::class, // Release DB connections after request
        // \App\Http\Middleware\FixLivewireHeadersIssue::class, // Fix Livewire headers issue - must be early
        // \App\Http\Middleware\CorrelationIdMiddleware::class, // Add correlation ID to all requests
        // \App\Http\Middleware\EnsureTenantContext::class, // Add tenant context globally
        // \App\Http\Middleware\ThreatDetectionMiddleware::class, // Add threat detection
        // \App\Http\Middleware\MonitoringMiddleware::class, // Performance and security monitoring
        // Middleware\MetricsMiddleware::class, // Prometheus metrics collection - temporarily disabled
    ];

    /* -------------------------------------------------------------------- *
     | 1) Web & API Gruppen (unverändert)                                   |
     * -------------------------------------------------------------------- */
    protected $middlewareGroups = [
        'web' => [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            Middleware\CheckCookieConsent::class,
            // \App\Http\Middleware\ResponseCompressionMiddleware::class, // Temporarily disabled - may interfere with Livewire
        ],
        'api' => [
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            Middleware\AdaptiveRateLimitMiddleware::class,
            Middleware\ResponseCompressionMiddleware::class,
        ],

        'api-no-csrf' => [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            Middleware\AdaptiveRateLimitMiddleware::class,
        ],
        
        'admin-api' => [
            // Keine Session oder CSRF für Admin API
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
        
        'business-portal' => [
            \App\Http\Middleware\PortalSessionConfig::class,
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \App\Http\Middleware\RefreshCsrfTokenForPortal::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
        
        'business-api' => [
            \App\Http\Middleware\PortalSessionConfig::class,
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \App\Http\Middleware\SharePortalSession::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /* -------------------------------------------------------------------- *
     | 2) **Hier** gehören Route-Aliase hin – funktioniert ab Laravel 8-11  |
     * -------------------------------------------------------------------- */
    protected $routeMiddleware = [
        // ✨ unsere neue Signatur-Prüfung
        'calcom.signature' => Middleware\VerifyCalcomSignature::class,
        'tenant.context' => Middleware\EnsureTenantContext::class,
        'api.auth' => Middleware\ApiAuthMiddleware::class,
        'verify.retell.signature' => Middleware\VerifyRetellSignature::class,
        'verify.stripe.signature' => Middleware\VerifyStripeSignature::class,
        'input.validation' => Middleware\InputValidationMiddleware::class,
        'threat.detection' => Middleware\ThreatDetectionMiddleware::class,
        'rate.limit' => Middleware\AdaptiveRateLimitMiddleware::class,
        'webhook.replay.protection' => Middleware\WebhookReplayProtection::class,
        'monitoring' => Middleware\MonitoringMiddleware::class,
        'check.cookie.consent' => Middleware\CheckCookieConsent::class,
        'ip.whitelist' => Middleware\IpWhitelist::class,
        'api.metrics.auth' => Middleware\ApiMetricsAuth::class,
        'dashboard.routes' => Middleware\ResolveDashboardRoutes::class,
        'validate.retell' => Middleware\ValidateRetellInput::class,
        'validate.company.context' => Middleware\ValidateCompanyContext::class,
        'cache.response' => Middleware\CacheResponse::class,
        'branch.context' => Middleware\BranchContextMiddleware::class,
        'check.appointment.booking' => Middleware\CheckAppointmentBookingRequired::class,
        
        // Portal Middleware - Unified
        'portal.auth' => Middleware\PortalAuth::class,
        'portal.auth.optional' => Middleware\PortalAuth::class . ':optional',
        'portal.auth.api' => Middleware\PortalAuth::class,
        'portal.api.auth' => Middleware\PortalAuth::class,
        'portal.permission' => Middleware\PortalPermission::class,
        'allow.dual.auth' => Middleware\AllowDualAuth::class,
        'fix.portal.auth' => Middleware\FixPortalAuth::class,
        'share.portal.session' => Middleware\SharePortalSession::class,
        
        // Session Configuration Middleware
        'portal.session' => Middleware\PortalSessionConfig::class,
        'admin.session' => Middleware\AdminSessionConfig::class,
        
        // Admin Impersonation
        'admin.impersonation' => Middleware\AdminImpersonation::class,
        
        // Admin API
        'disable.csrf.admin' => Middleware\DisableCSRFForAdminAPI::class,
        'disable.tenant.scope' => Middleware\DisableTenantScopeForAdminApi::class,
        
        // Legacy aliases for backward compatibility
        'portal.authenticate' => Middleware\PortalAuth::class,

        // ── Laravel-Standard ──────────────────────────────────────────────
        'auth' => Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'admin.bypass' => \App\Http\Middleware\AdminBypass::class,
    ];
}
