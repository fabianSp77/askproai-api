<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withSchedule(function ($schedule) {
        // Load schedule from Console Kernel
        $kernel = new \App\Console\Kernel(app(), app('events'));
        $kernel->schedule($schedule);
    })
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Load test routes in non-production environments
            if (file_exists(__DIR__.'/../routes/test-routes.php')) {
                require __DIR__.'/../routes/test-routes.php';
            }
        }
    )
    ->withProviders([
        App\Providers\ViewBindingFixServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(\App\Http\Middleware\PerformanceMonitoring::class);
        $middleware->append(\App\Http\Middleware\ErrorCatcher::class);

        // Rate limiting for specific routes
        $middleware->alias([
            /* ── Project-specific middleware ───────────────────── */
            'rate.limit' => \App\Http\Middleware\RateLimiting::class,
            'stripe.webhook' => \App\Http\Middleware\VerifyStripeWebhookSignature::class,
            'retell.webhook' => \App\Http\Middleware\VerifyRetellWebhookSignature::class,
            'retell.signature' => \App\Http\Middleware\VerifyRetellWebhookSignature::class,
            'retell.function' => \App\Http\Middleware\VerifyRetellFunctionSignature::class,
            'retell.function.whitelist' => \App\Http\Middleware\VerifyRetellFunctionSignatureWithWhitelist::class,
            'retell.call.ratelimit' => \App\Http\Middleware\RetellCallRateLimiter::class,

            /* ✨ Eigener Alias – Cal.com-Webhook-Signaturprüfung */
            'calcom.signature' => \App\Http\Middleware\VerifyCalcomSignature::class,

            /* 🔒 Admin panel security - internal network only */
            'restrict.internal' => \App\Http\Middleware\RestrictToInternalNetwork::class,

            /* 🎯 Feature flags - Customer Portal Security */
            'feature' => \App\Http\Middleware\CheckFeatureFlag::class,

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
        $exceptions->reportable(function (\Throwable $e) {
            $handler = new \App\Exceptions\CustomHandler(app());
            $handler->report($e);
        });
    })->create();
