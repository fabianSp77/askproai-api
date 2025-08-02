<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     */
    protected $middleware = [
        Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        Middleware\ReleaseDbConnection::class,
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
            // Use individual middleware instead of inheriting from web
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
        
        'business-api' => [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \App\Http\Middleware\PortalAuth::class,
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
        'auth.rate.limit' => Middleware\AuthenticationRateLimiter::class,
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
        'query.performance' => Middleware\QueryPerformanceMiddleware::class,
        
        // Portal Middleware - Simplified
        'portal.auth' => Middleware\PortalAuth::class,
        'portal.permission' => Middleware\PortalPermission::class,
        'portal.2fa' => Middleware\PortalTwoFactorAuth::class,
        
        // Admin Middleware
        'admin.impersonation' => Middleware\AdminImpersonation::class,

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
