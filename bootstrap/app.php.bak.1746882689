<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Funktion nur deklarieren, wenn sie noch nicht existiert
if (!function_exists('bootstrap_debug_log')) {
    function bootstrap_debug_log(string $message): void {
        error_log("BOOTSTRAP DEBUG: {$message}");
    }
}

bootstrap_debug_log("bootstrap/app.php - START");

try {
    $app = Application::configure(basePath: dirname(__DIR__))
        ->withRouting(
            web: __DIR__.'/../routes/web.php',
            api: __DIR__.'/../routes/api.php',
            commands: __DIR__.'/../routes/console.php',
            health: '/up',
        )
        ->withMiddleware(function (Middleware $middleware) {
            bootstrap_debug_log("Configuring Middleware...");
        })
        ->withExceptions(function (Exceptions $exceptions) {
            bootstrap_debug_log("Configuring Exceptions...");
        })->create();

    bootstrap_debug_log("Application instance CREATED.");

} catch (\Throwable $e) {
    bootstrap_debug_log("CRITICAL ERROR DURING BOOTSTRAP (configure/create): " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    throw $e;
}

bootstrap_debug_log("bootstrap/app.php - RETURNING Application instance.");

return $app;
