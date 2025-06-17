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
        then: function ($router) {
            // Load API v2 routes - temporarily disabled due to missing controllers
            // Route::prefix('api')
            //     ->middleware('api')
            //     ->group(base_path('routes/api/v2.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        
        /* ---------------------------------------------------------
         |  Global Middleware (runs on every request)
         * -------------------------------------------------------- */
        $middleware->prepend(\App\Http\Middleware\DebugUltimateSystemCockpit::class);
        $middleware->prepend(\App\Http\Middleware\ComprehensiveErrorLogger::class);
        $middleware->prepend(\App\Http\Middleware\TrustProxies::class);
        $middleware->append(\App\Http\Middleware\SessionManager::class);
        $middleware->append(\App\Http\Middleware\LoginDebugger::class);
        
        /* ---------------------------------------------------------
         |  Web Middleware Group
         * -------------------------------------------------------- */
        // Remove Livewire fix middleware that may be causing issues

        /* ---------------------------------------------------------
         |  WEB-Gruppe  (Standard-Middleware für Browser-Requests)
         * -------------------------------------------------------- */
        // Removed FixLivewireAssets middleware - causing issues

        /* ---------------------------------------------------------
         |  API-Gruppe  (typisch für stateless Requests / SPA)
         * -------------------------------------------------------- */
        $middleware->api(prepend: [
            'throttle:api',
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\CorrelationIdMiddleware::class, // Add correlation ID tracking
            // Temporarily disabled due to errors
            // \App\Http\Middleware\ThreatDetectionMiddleware::class,
            // \App\Http\Middleware\AdaptiveRateLimitMiddleware::class,
        ]);

        /* ---------------------------------------------------------
         |  Middleware-Aliase  (Array-Syntax!)
         * -------------------------------------------------------- */
        $middleware->alias([
            /* ✨ Eigener Alias – Cal.com-Webhook-Signaturprüfung */
            'calcom.signature' => \App\Http\Middleware\VerifyCalcomSignature::class,
            
            /* ✨ Retell Webhook Signature Verification */
            'verify.retell.signature' => \App\Http\Middleware\VerifyRetellSignature::class,

            /* 🔗 Correlation ID Tracking */
            'correlation.id' => \App\Http\Middleware\CorrelationIdMiddleware::class,

            /* 🛡️ Security Layer Middleware */
            'threat.detection' => \App\Http\Middleware\ThreatDetectionMiddleware::class,
            'rate.limit' => \App\Http\Middleware\AdaptiveRateLimitMiddleware::class,
            
            /* 🚀 Performance Optimization Middleware */
            'eager.loading' => \App\Http\Middleware\EagerLoadingMiddleware::class,
            
            /* 📱 Mobile Detection Middleware */
            'mobile.detector' => \App\Http\Middleware\MobileDetector::class,

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
        // Handle custom booking exceptions
        $exceptions->render(function (\App\Exceptions\BookingException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getUserMessage(),
                    'error_code' => $e->getErrorCode(),
                    'context' => app()->environment('local') ? $e->getContext() : null
                ], 400);
            }
        });
        
        // Handle availability exceptions
        $exceptions->render(function (\App\Exceptions\AvailabilityException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getUserMessage(),
                    'error_code' => $e->getErrorCode(),
                    'alternatives' => $e->getAlternatives(),
                    'alternatives_message' => $e->getAlternativesMessage()
                ], 400);
            }
        });
        
        // Log all exceptions for debugging
        $exceptions->report(function (Throwable $e) {
            if (request()->is('admin/*')) {
                \Illuminate\Support\Facades\Log::error('Admin Panel Error', [
                    'url' => request()->fullUrl(),
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            }
        });
    })
    ->create();
