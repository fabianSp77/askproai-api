<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
    
    /**
     * Override render to avoid view compilation issues
     */
    public function render($request, Throwable $e)
    {
        // For production, return simple JSON error to avoid view compilation
        if ($request->expectsJson() || $request->is('api/*') || $request->is('admin/*')) {
            return response()->json([
                'message' => config('app.debug') ? $e->getMessage() : 'Server Error',
                'exception' => config('app.debug') ? get_class($e) : null,
                'file' => config('app.debug') ? $e->getFile() : null,
                'line' => config('app.debug') ? $e->getLine() : null,
            ], 500);
        }
        
        // For web routes, return simple HTML without Blade compilation
        if (config('app.env') === 'local' || config('app.env') === 'production') {
            return response(
                '<!DOCTYPE html>
                <html>
                <head>
                    <title>Error</title>
                    <style>
                        body { font-family: sans-serif; padding: 2rem; }
                        .error { background: #f8d7da; padding: 1rem; border-radius: 0.25rem; }
                    </style>
                </head>
                <body>
                    <div class="error">
                        <h1>Application Error</h1>
                        <p>' . (config('app.debug') ? $e->getMessage() : 'An error occurred. Please try again later.') . '</p>
                    </div>
                </body>
                </html>',
                500
            );
        }
        
        return parent::render($request, $e);
    }
}