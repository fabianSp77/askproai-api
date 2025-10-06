<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
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
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(\App\Http\Middleware\PerformanceMonitoring::class);
        $middleware->append(\App\Http\Middleware\ErrorCatcher::class);

        // Rate limiting for specific routes
        $middleware->alias([
            'rate.limit' => \App\Http\Middleware\RateLimiting::class,
            'stripe.webhook' => \App\Http\Middleware\VerifyStripeWebhookSignature::class,
            'retell.webhook' => \App\Http\Middleware\VerifyRetellWebhookSignature::class,
            'retell.signature' => \App\Http\Middleware\VerifyRetellWebhookSignature::class,
            'retell.function' => \App\Http\Middleware\VerifyRetellFunctionSignature::class,
            'retell.function.whitelist' => \App\Http\Middleware\VerifyRetellFunctionSignatureWithWhitelist::class,
            'retell.call.ratelimit' => \App\Http\Middleware\RetellCallRateLimiter::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->reportable(function (\Throwable $e) {
            $handler = new \App\Exceptions\CustomHandler(app());
            $handler->report($e);
        });
    })->create();
