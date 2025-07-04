<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

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
            // Configure rate limiters
            RateLimiter::for('api', function (Request $request) {
                return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
            });

            RateLimiter::for('webhook', function (Request $request) {
                return Limit::perMinute(100)->by($request->ip());
            });

            RateLimiter::for('global', function (Request $request) {
                return Limit::perMinute(500);
            });

            // Load webhook routes
            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/webhooks.php'));
                
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
        // Replace Laravel's StartSession with our fixed version
        $middleware->replace(
            \Illuminate\Session\Middleware\StartSession::class,
            \App\Http\Middleware\FixStartSession::class
        );
        
        // Add middleware in reverse order since prepend adds to the beginning
        $middleware->prepend(\App\Http\Middleware\TrustProxies::class);
        // $middleware->prepend(\App\Http\Middleware\EnsureProperResponseFormat::class);
        // CRITICAL: ResponseWrapper MUST be first to catch all Livewire errors
        // $middleware->prepend(\App\Http\Middleware\ResponseWrapper::class);
        // $middleware->append(\App\Http\Middleware\SessionManager::class);
        // $middleware->append(\App\Http\Middleware\LoginDebugger::class);
        
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
            
            /* 📊 API Metrics Auth */
            'api.metrics.auth' => \App\Http\Middleware\ApiMetricsAuth::class,
            
            /* 🏢 Multi-tenancy & Context */
            'tenant.context' => \App\Http\Middleware\EnsureTenantContext::class,
            'branch.context' => \App\Http\Middleware\BranchContextMiddleware::class,
            'validate.company.context' => \App\Http\Middleware\ValidateCompanyContext::class,
            
            /* 🔐 API Authentication */
            'api.auth' => \App\Http\Middleware\ApiAuthMiddleware::class,
            
            /* ✅ Webhook Signature Verification */
            'verify.stripe.signature' => \App\Http\Middleware\VerifyStripeSignature::class,
            'webhook.replay.protection' => \App\Http\Middleware\WebhookReplayProtection::class,
            
            /* 🛡️ Additional Security */
            'input.validation' => \App\Http\Middleware\InputValidationMiddleware::class,
            'check.cookie.consent' => \App\Http\Middleware\CheckCookieConsent::class,
            'ip.whitelist' => \App\Http\Middleware\IpWhitelist::class,
            
            /* 📈 Monitoring & Performance */
            'monitoring' => \App\Http\Middleware\MonitoringMiddleware::class,
            'cache.response' => \App\Http\Middleware\CacheResponse::class,
            
            /* 🎯 Application Specific */
            'dashboard.routes' => \App\Http\Middleware\ResolveDashboardRoutes::class,
            'validate.retell' => \App\Http\Middleware\ValidateRetellInput::class,

            /* 🏢 Portal Middleware */
            'portal.auth' => \App\Http\Middleware\PortalAuthenticate::class,
            'portal.permission' => \App\Http\Middleware\PortalPermission::class,

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
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withBindings([
        \Illuminate\Contracts\Console\Kernel::class => \App\Console\Kernel::class,
    ])
    ->withExceptions(function (Exceptions $exceptions) {
        // Handle Livewire headers error globally
        $exceptions->render(function (\ErrorException $e, $request) {
            if (str_contains($e->getMessage(), 'Undefined property') && 
                str_contains($e->getMessage(), 'Livewire') && 
                str_contains($e->getMessage(), 'headers')) {
                // This is the Livewire headers error, just redirect to login
                return redirect('/admin/login');
            }
        });
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
            if (app()->runningInConsole()) {
                return;
            }
            
            try {
                $request = app('request');
                if ($request && ($request->is('admin/*') || $request->is('livewire/*'))) {
                    \Illuminate\Support\Facades\Log::error('Admin Panel Error', [
                        'url' => $request->fullUrl(),
                        'method' => $request->method(),
                        'exception' => get_class($e),
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            } catch (\Exception $logException) {
                // Ignore logging errors during bootstrap
            }
        });
        
        // Handle MissingTenantException
        $exceptions->render(function (\App\Exceptions\MissingTenantException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Missing tenant context'
                ], 403);
            }
            
            // For admin routes, don't redirect, just log
            if ($request->is('admin/*')) {
                \Illuminate\Support\Facades\Log::error('Missing tenant in admin', [
                    'user_id' => auth()->id(),
                    'url' => $request->fullUrl()
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Internal Server Error'
                ], 500);
            }
        });
        
        // Handle missing dashboard routes
        $exceptions->render(function (\Symfony\Component\Routing\Exception\RouteNotFoundException $e, $request) {
            $message = $e->getMessage();
            
            // Check if this is a missing dashboard route
            if (str_contains($message, 'filament.admin.pages.') && 
                str_contains($message, 'dashboard')) {
                
                // Extract the route name from the error message
                preg_match('/\[([^\]]+)\]/', $message, $matches);
                $routeName = $matches[1] ?? null;
                
                if ($routeName && str_starts_with($routeName, 'filament.admin.pages.')) {
                    // Log the missing route
                    \Illuminate\Support\Facades\Log::warning("Missing dashboard route detected: {$routeName}", [
                        'requested_route' => $routeName,
                        'user_id' => auth()->id(),
                        'url' => $request->fullUrl()
                    ]);
                    
                    // Redirect to admin home
                    return redirect('/admin');
                }
            }
            
            // Re-throw if not a dashboard route issue
            throw $e;
        });
        
        // Add a general exception handler for Livewire requests
        $exceptions->render(function (Throwable $e, $request) {
            if ($request->is('livewire/*') && $request->expectsJson()) {
                \Illuminate\Support\Facades\Log::error('Livewire Error', [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'url' => $request->fullUrl(),
                    'user_id' => auth()->id(),
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Internal Server Error',
                    'debug' => app()->environment('local') ? $e->getMessage() : null
                ], 500);
            }
        });
    })
    ->create();
