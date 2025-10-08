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
        
        // 🔒 Admin panel security - internal network only
        'restrict.internal' => \App\Http\Middleware\RestrictToInternalNetwork::class,

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
