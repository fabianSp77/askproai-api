<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ErrorHandlingService
{
    /**
     * Handle and log errors with context
     */
    public static function handle(Throwable $e, array $context = []): array
    {
        $errorId = Str::uuid()->toString();
        
        // Determine error level
        $level = self::getErrorLevel($e);
        
        // Build error context
        $errorContext = array_merge([
            'error_id' => $errorId,
            'error_class' => get_class($e),
            'error_message' => $e->getMessage(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'user_id' => auth()->id(),
            'company_id' => auth()->user()?->company_id,
            'request_url' => request()->fullUrl(),
            'request_method' => request()->method(),
            'ip_address' => request()->ip(),
        ], $context);
        
        // Log based on level
        match ($level) {
            'critical' => Log::critical('Critical error occurred', $errorContext),
            'error' => Log::error('Error occurred', $errorContext),
            'warning' => Log::warning('Warning occurred', $errorContext),
            default => Log::info('Info level error', $errorContext),
        };
        
        // Store critical errors in database
        if (in_array($level, ['critical', 'error'])) {
            self::storeError($errorId, $e, $errorContext);
        }
        
        return [
            'error_id' => $errorId,
            'message' => self::getUserMessage($e),
            'level' => $level,
        ];
    }
    
    /**
     * Get appropriate error level
     */
    private static function getErrorLevel(Throwable $e): string
    {
        return match (true) {
            $e instanceof \PDOException => 'critical',
            $e instanceof \InvalidArgumentException => 'warning',
            $e instanceof \App\Exceptions\CircuitBreakerOpenException => 'error',
            $e->getCode() >= 500 => 'error',
            $e->getCode() >= 400 => 'warning',
            default => 'info',
        };
    }
    
    /**
     * Get user-friendly error message
     */
    private static function getUserMessage(Throwable $e): string
    {
        // Map specific exceptions to user messages
        $messages = [
            \PDOException::class => 'A database error occurred. Please try again later.',
            \App\Exceptions\CircuitBreakerOpenException::class => 'The service is temporarily unavailable. Please try again in a few minutes.',
            \Illuminate\Auth\AuthenticationException::class => 'Please log in to continue.',
            \Illuminate\Auth\Access\AuthorizationException::class => 'You do not have permission to perform this action.',
            \Illuminate\Validation\ValidationException::class => 'Please check your input and try again.',
        ];
        
        $exceptionClass = get_class($e);
        
        return $messages[$exceptionClass] ?? 'An unexpected error occurred. Please try again.';
    }
    
    /**
     * Store error in database for monitoring
     */
    private static function storeError(string $errorId, Throwable $e, array $context): void
    {
        try {
            \DB::table('critical_errors')->insert([
                'error_id' => $errorId,
                'type' => get_class($e),
                'message' => $e->getMessage(),
                'context' => json_encode($context),
                'stack_trace' => $e->getTraceAsString(),
                'created_at' => now(),
            ]);
        } catch (\Exception $dbError) {
            Log::emergency('Failed to store critical error', [
                'original_error' => $errorId,
                'storage_error' => $dbError->getMessage(),
            ]);
        }
    }
}