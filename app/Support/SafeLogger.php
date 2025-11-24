<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * SafeLogger Trait
 *
 * Provides resilient logging with automatic fallback mechanisms.
 * Prevents job crashes due to logging failures (e.g., permission errors).
 *
 * Usage:
 *   class MyJob implements ShouldQueue
 *   {
 *       use SafeLogger;
 *
 *       public function handle() {
 *           $this->safeLog('info', 'Processing started', ['id' => 123], 'calcom');
 *       }
 *   }
 *
 * @created 2025-11-22
 * @see /tmp/CRITICAL_FINDING_SYNC_JOB_PERMISSION_ERROR_2025-11-22.md
 */
trait SafeLogger
{
    /**
     * Safely log a message with automatic fallback on failure.
     *
     * Fallback chain:
     * 1. Try specified channel (e.g., 'calcom')
     * 2. Fall back to default Log facade
     * 3. Last resort: error_log (PHP error log)
     *
     * @param string $level Log level (debug, info, warning, error, critical)
     * @param string $message Log message
     * @param array $context Additional context data
     * @param string|null $channel Optional channel name (e.g., 'calcom', 'consistency')
     * @return void
     */
    protected function safeLog(
        string $level,
        string $message,
        array $context = [],
        ?string $channel = null
    ): void {
        // Validate log level
        $validLevels = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];
        if (!in_array($level, $validLevels)) {
            $level = 'info';
        }

        // Try primary channel
        if ($channel) {
            try {
                Log::channel($channel)->$level($message, $context);
                return; // Success
            } catch (\Throwable $e) {
                // Channel failed - will fall back
                $channelError = $e->getMessage();
            }
        }

        // Fallback to default Log facade
        try {
            $fallbackContext = $context;

            // Add metadata about fallback if channel was attempted
            if (isset($channelError)) {
                $fallbackContext['_log_fallback'] = true;
                $fallbackContext['_attempted_channel'] = $channel;
                $fallbackContext['_channel_error'] = substr($channelError, 0, 100);
            }

            Log::$level("[FALLBACK] {$message}", $fallbackContext);
            return; // Success
        } catch (\Throwable $e) {
            // Default logger also failed - last resort
        }

        // Last resort: PHP error_log
        try {
            $logData = [
                'level' => $level,
                'message' => $message,
                'context' => $context,
                'attempted_channel' => $channel ?? 'default',
                'timestamp' => now()->toIso8601String(),
            ];

            error_log('SAFE_LOGGER_LAST_RESORT: ' . json_encode($logData));
        } catch (\Throwable $e) {
            // Even error_log failed - silently continue
            // The job should not crash due to logging failures
        }
    }

    /**
     * Shorthand methods for common log levels
     */

    protected function safeInfo(string $message, array $context = [], ?string $channel = null): void
    {
        $this->safeLog('info', $message, $context, $channel);
    }

    protected function safeError(string $message, array $context = [], ?string $channel = null): void
    {
        $this->safeLog('error', $message, $context, $channel);
    }

    protected function safeWarning(string $message, array $context = [], ?string $channel = null): void
    {
        $this->safeLog('warning', $message, $context, $channel);
    }

    protected function safeDebug(string $message, array $context = [], ?string $channel = null): void
    {
        $this->safeLog('debug', $message, $context, $channel);
    }

    protected function safeCritical(string $message, array $context = [], ?string $channel = null): void
    {
        $this->safeLog('critical', $message, $context, $channel);
    }
}
