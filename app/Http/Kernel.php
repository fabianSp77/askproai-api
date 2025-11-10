<?php
namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
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
            // \App\Http\Middleware\VerifyLivewireCsrf::class, // Temporär deaktiviert - verursacht Session-Konflikte
            // \App\Http\Middleware\FixLoginError::class, // Temporär deaktiviert - maskiert echte Fehler
        ],
        'api' => [
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /* -------------------------------------------------------------------- *
     | 2) **Hier** gehören Route-Aliase hin – funktioniert ab Laravel 8-11  |
     * -------------------------------------------------------------------- */
    protected $middlewareAliases = [
        // ✨ unsere neue Signatur-Prüfung
        'calcom.signature' => \App\Http\Middleware\VerifyCalcomSignature::class,
        'livewire.csrf' => \App\Http\Middleware\VerifyLivewireCsrf::class,

        // ✨ V2 API Middleware
        'tenant' => \App\Http\Middleware\TenantMiddleware::class,
        'rate-limit' => \Illuminate\Routing\Middleware\ThrottleRequests::class,

        // ✨ Phase 4 Security & Monitoring Middleware
        'api.rate-limit' => \App\Http\Middleware\RateLimitMiddleware::class,
        'api.performance' => \App\Http\Middleware\PerformanceMonitoringMiddleware::class,
        'api.logging' => \App\Http\Middleware\RequestResponseLoggingMiddleware::class,
        'stripe.signature' => \App\Http\Middleware\VerifyStripeWebhookSignature::class,
        'retell.signature' => \App\Http\Middleware\VerifyRetellWebhookSignature::class,
        'retell.function' => \App\Http\Middleware\VerifyRetellFunctionSignature::class,
        'retell.function.whitelist' => \App\Http\Middleware\VerifyRetellFunctionSignatureWithWhitelist::class,
        'retell.call.ratelimit' => \App\Http\Middleware\RetellCallRateLimiter::class,
        'retell.validate.callid' => \App\Http\Middleware\ValidateRetellCallId::class,

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
