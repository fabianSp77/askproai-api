<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;
use App\Services\Logging\StructuredLogger;

/**
 * @method static string getCorrelationId()
 * @method static StructuredLogger setCorrelationId(string $correlationId)
 * @method static StructuredLogger withContext(array $context)
 * @method static void logBookingFlow(string $step, array $context = [])
 * @method static void logApiCall(string $service, string $endpoint, string $method = 'POST', array $requestData = [], $response = null, ?float $duration = null, ?string $error = null)
 * @method static void logWebhook(string $source, string $event, array $payload, array $context = [])
 * @method static void logError(\Throwable $exception, array $context = [])
 * @method static void logPerformance(string $operation, float $duration, array $metrics = [])
 * @method static void logSecurity(string $event, string $severity = 'warning', array $details = [])
 * @method static void log(string $channel, string $level, string $message, array $context = [])
 * @method static void success(string $message, array $context = [])
 * @method static void failure(string $message, array $context = [])
 * @method static void warning(string $message, array $context = [])
 * @method static void info(string $message, array $context = [])
 * @method static void debug(string $message, array $context = [])
 * @method static StructuredLogger withAdditionalContext(array $context)
 * 
 * @see \App\Services\Logging\StructuredLogger
 */
class StructuredLog extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return StructuredLogger::class;
    }
}