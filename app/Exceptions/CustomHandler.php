<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;

class CustomHandler extends ExceptionHandler
{
    /**
     * Report or log an exception.
     */
    public function report(Throwable $exception): void
    {
        // Log ALL 500 errors with full details
        if ($this->isHttpException($exception) && $exception->getStatusCode() == 500) {
            Log::error('🔴 500 ERROR CAPTURED:', [
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'user' => auth()->user()?->email ?? 'guest',
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ]);
        }

        // Also log any exception on admin pages
        if (str_contains(request()->path(), 'admin/')) {
            Log::error('🔴 ADMIN PAGE ERROR:', [
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'user' => auth()->user()?->email ?? 'guest',
                'exception_class' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace_first_5' => array_slice($exception->getTrace(), 0, 5)
            ]);
        }

        parent::report($exception);
    }
}