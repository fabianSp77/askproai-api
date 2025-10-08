<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Hier konfigurieren wir die Laravel-Instanz. Am Ende liefern wir das
| Application-Objekt zurück, damit der Aufrufer Services auflösen kann.
|
*/

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web:      __DIR__.'/../routes/web.php',
        api:      __DIR__.'/../routes/api.php',      // ← API-Routes einbinden
        commands: __DIR__.'/../routes/console.php',
        health:   '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        /* ---------------------------------------------------------
         |  WEB-Gruppe  (Standard-Middleware für Browser-Requests)
         * -------------------------------------------------------- */
        $middleware->web(append: [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        /* ---------------------------------------------------------
         |  API-Gruppe  (typisch für stateless Requests / SPA)
         * -------------------------------------------------------- */
        $middleware->api(prepend: [
            'throttle:api',
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        /* ---------------------------------------------------------
         |  Middleware-Aliase  (Array-Syntax!)
         * -------------------------------------------------------- */
        $middleware->alias([
            /* ✨ Eigener Alias – Cal.com-Webhook-Signaturprüfung */
            'calcom.signature' => \App\Http\Middleware\VerifyCalcomSignature::class,

            /* 🔒 Admin panel security - internal network only */
            'restrict.internal' => \App\Http\Middleware\RestrictToInternalNetwork::class,

            /* ── Laravel-Standard ─────────────────────────────── */
            'auth'              => \App\Http\Middleware\Authenticate::class,
            'auth.basic'        => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'cache.headers'     => \Illuminate\Http\Middleware\SetCacheHeaders::class,
            'can'               => \Illuminate\Auth\Middleware\Authorize::class,
            'guest'             => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'password.confirm'  => \Illuminate\Auth\Middleware\RequirePassword::class,
            'signed'            => \Illuminate\Routing\Middleware\ValidateSignature::class,
            'throttle'          => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'verified'          => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Hier könntest du z. B. eigene Exception-Handler registrieren
    })
    ->create();
