<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Real-time logging helper for test calls with structured JSON output
 *
 * Usage:
 * - TestCallLogger::webhook('call_started', $callId, $data);
 * - TestCallLogger::functionCall('check_availability', $callId, $args, $response);
 * - TestCallLogger::calcomApi('POST /bookings', $request, $response);
 */
class TestCallLogger
{
    /**
     * Log webhook events with full payload
     */
    public static function webhook(string $event, ?string $callId, array $data): void
    {
        Log::info('🔔 WEBHOOK', [
            'timestamp' => Carbon::now()->toIso8601String(),
            'call_id' => $callId,
            'event' => $event,
            'data_flow' => 'WEBHOOK → AGENT',
            'payload' => $data,
            'payload_size' => strlen(json_encode($data)),
            'log_type' => 'webhook',
        ]);
    }

    /**
     * Log dynamic variables sent to agent
     */
    public static function dynamicVars(string $callId, array $variables): void
    {
        Log::info('📤 DYNAMIC_VARS', [
            'timestamp' => Carbon::now()->toIso8601String(),
            'call_id' => $callId,
            'data_flow' => 'SYSTEM → AGENT',
            'variables' => $variables,
            'variable_count' => count($variables),
            'log_type' => 'dynamic_vars',
        ]);
    }

    /**
     * Log function call with arguments and response
     */
    public static function functionCall(
        string $functionName,
        ?string $callId,
        array $arguments,
        $response = null,
        ?float $durationMs = null
    ): void {
        $logData = [
            'timestamp' => Carbon::now()->toIso8601String(),
            'call_id' => $callId,
            'function' => $functionName,
            'data_flow' => 'AGENT → FUNCTION → AGENT',
            'arguments' => $arguments,
            'log_type' => 'function_call',
        ];

        if ($response !== null) {
            $logData['response'] = is_object($response) && method_exists($response, 'getData')
                ? $response->getData(true)
                : $response;
        }

        if ($durationMs !== null) {
            $logData['duration_ms'] = round($durationMs, 2);
        }

        Log::info('⚡ FUNCTION_CALL', $logData);
    }

    /**
     * Log Cal.com API request and response
     */
    public static function calcomApi(
        string $method,
        string $endpoint,
        ?string $callId,
        array $request,
        $response,
        ?float $durationMs = null
    ): void {
        $logData = [
            'timestamp' => Carbon::now()->toIso8601String(),
            'call_id' => $callId,
            'method' => $method,
            'endpoint' => $endpoint,
            'data_flow' => 'FUNCTION → CALCOM → FUNCTION',
            'request' => $request,
            'log_type' => 'calcom_api',
        ];

        if ($response) {
            $logData['response'] = is_object($response) && method_exists($response, 'json')
                ? $response->json()
                : $response;
            $logData['status_code'] = method_exists($response, 'status') ? $response->status() : null;
        }

        if ($durationMs !== null) {
            $logData['duration_ms'] = round($durationMs, 2);
        }

        Log::info('🔗 CALCOM_API', $logData);
    }

    /**
     * Log errors with full context
     */
    public static function error(
        string $context,
        ?string $callId,
        \Throwable $exception,
        array $additionalData = []
    ): void {
        Log::error('❌ ERROR', [
            'timestamp' => Carbon::now()->toIso8601String(),
            'call_id' => $callId,
            'context' => $context,
            'error_message' => $exception->getMessage(),
            'error_class' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'stack_trace' => $exception->getTraceAsString(),
            'additional_data' => $additionalData,
            'log_type' => 'error',
        ]);
    }

    /**
     * Log data flow checkpoints
     */
    public static function checkpoint(
        string $stage,
        ?string $callId,
        array $data = []
    ): void {
        Log::debug('🔍 CHECKPOINT', [
            'timestamp' => Carbon::now()->toIso8601String(),
            'call_id' => $callId,
            'stage' => $stage,
            'data' => $data,
            'log_type' => 'checkpoint',
        ]);
    }

    /**
     * Format log filter pattern for grep
     */
    public static function filterPattern(string $callId): string
    {
        return sprintf('"call_id":"%s"', $callId);
    }
}
