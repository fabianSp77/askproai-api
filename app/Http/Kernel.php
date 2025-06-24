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
        \App\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        // \App\Http\Middleware\FixLivewireHeadersIssue::class, // Fix Livewire headers issue - must be early
        // \App\Http\Middleware\CorrelationIdMiddleware::class, // Add correlation ID to all requests
        // \App\Http\Middleware\EnsureTenantContext::class, // Add tenant context globally
        // \App\Http\Middleware\ThreatDetectionMiddleware::class, // Add threat detection
        // \App\Http\Middleware\MonitoringMiddleware::class, // Performance and security monitoring
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
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\CheckCookieConsent::class,
            \App\Http\Middleware\LivewireErrorHandler::class,
            \App\Http\Middleware\ResponseCompressionMiddleware::class,
        ],
        'api' => [
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\ResponseCompressionMiddleware::class,
        ],
    ];

    /* -------------------------------------------------------------------- *
     | 2) **Hier** gehören Route-Aliase hin – funktioniert ab Laravel 8-11  |
     * -------------------------------------------------------------------- */
    protected array $middlewareAliases = [
        // ✨ unsere neue Signatur-Prüfung
        'calcom.signature' => \App\Http\Middleware\VerifyCalcomSignature::class,
        'tenant.context' => \App\Http\Middleware\EnsureTenantContext::class,
        'api.auth' => \App\Http\Middleware\ApiAuthMiddleware::class,
        'verify.retell.signature' => \App\Http\Middleware\VerifyRetellSignature::class,
        'verify.retell.signature.bypass' => \App\Http\Middleware\VerifyRetellSignatureTemporary::class,
        'verify.retell.signature.debug' => \App\Http\Middleware\VerifyRetellSignatureDebug::class,
        'verify.stripe.signature' => \App\Http\Middleware\VerifyStripeSignature::class,
        'input.validation' => \App\Http\Middleware\InputValidationMiddleware::class,
        'threat.detection' => \App\Http\Middleware\ThreatDetectionMiddleware::class,
        'rate.limit' => \App\Http\Middleware\AdaptiveRateLimitMiddleware::class,
        'webhook.replay.protection' => \App\Http\Middleware\WebhookReplayProtection::class,
        'monitoring' => \App\Http\Middleware\MonitoringMiddleware::class,
        'check.cookie.consent' => \App\Http\Middleware\CheckCookieConsent::class,
        'ip.whitelist' => \App\Http\Middleware\IpWhitelist::class,
        'api.metrics.auth' => \App\Http\Middleware\ApiMetricsAuth::class,
        'dashboard.routes' => \App\Http\Middleware\ResolveDashboardRoutes::class,

        // ── Laravel-Standard ──────────────────────────────────────────────
        'auth'              => \App\Http\Middleware\Authenticate::class,
        'auth.basic'        => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'cache.headers'     => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can'               => \Illuminate\Auth\Middleware\Authorize::class,
        'guest'             => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm'  => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed'            => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle'          => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified'          => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
    ];
}
