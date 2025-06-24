<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Support\Facades\Log;

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
            // Log Livewire-specific errors with more detail
            if (request()->hasHeader('X-Livewire') || str_contains(request()->path(), 'livewire')) {
                Log::error('Livewire Request Exception', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'url' => request()->fullUrl(),
                    'method' => request()->method(),
                    'headers' => request()->headers->all(),
                    'data' => request()->all(),
                    'trace' => collect($e->getTrace())->take(5)->toArray(),
                ]);
            }
        });
    }
    
    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e)
    {
        // For Livewire requests, return detailed error in development
        if ($request->hasHeader('X-Livewire') && config('app.debug')) {
            return response()->json([
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect($e->getTrace())->take(3)->toArray(),
            ], 500);
        }
        
        return parent::render($request, $e);
    }
}