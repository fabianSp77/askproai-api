<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyRetellSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        // Skip in testing environment
        if (app()->environment('testing')) {
            return $next($request);
        }

        $signatureHeader = $request->header('X-Retell-Signature');
        $timestamp = $request->header('X-Retell-Timestamp');
        $apiKey = config('services.retell.api_key') ?? config('services.retell.secret');
        
        // Extract signature from header (format: v=timestamp,signature)
        $signature = $signatureHeader;
        if (strpos($signatureHeader, 'v=') === 0) {
            // Parse Retell signature format: v=timestamp,signature
            $parts = explode(',', substr($signatureHeader, 2));
            if (count($parts) >= 2) {
                $timestamp = $timestamp ?? $parts[0]; // Use header timestamp or extracted
                $signature = $parts[1];
            } else {
                // Might be just v=signature
                $signature = $parts[0] ?? $signatureHeader;
            }
        }
        
        // Validate required components
        if (empty($signature) || empty($apiKey)) {
            Log::error('Retell webhook validation failed - missing requirements', [
                'has_signature' => !empty($signature),
                'has_api_key' => !empty($apiKey),
                'ip' => $request->ip(),
            ]);
            
            abort(401, 'Unauthorized - Missing signature or configuration');
        }
        
        // Prevent replay attacks with timestamp validation (5 minute window)
        if ($timestamp && is_numeric($timestamp)) {
            $currentTime = time();
            $webhookTime = (int) $timestamp;
            
            Log::info('Retell webhook timestamp validation', [
                'raw_timestamp' => $timestamp,
                'webhook_time' => $webhookTime,
                'current_time' => $currentTime,
                'timestamp_length' => strlen((string)$timestamp),
            ]);
            
            // Check if timestamp is in milliseconds (13 digits) vs seconds (10 digits)
            if (strlen((string)$timestamp) >= 13) {
                // Convert milliseconds to seconds
                $webhookTime = (int)($timestamp / 1000);
                Log::info('Retell webhook timestamp converted from milliseconds', [
                    'original' => $timestamp,
                    'converted' => $webhookTime
                ]);
            } elseif (strlen((string)$timestamp) >= 16) {
                // Might be microseconds or nanoseconds
                $webhookTime = (int)($timestamp / 1000000);
                Log::info('Retell webhook timestamp converted from microseconds', [
                    'original' => $timestamp,
                    'converted' => $webhookTime
                ]);
            }
            
            // Skip timestamp validation if it seems completely wrong (< year 2020)
            if ($webhookTime < 1577836800) { // Jan 1, 2020
                Log::warning('Retell webhook timestamp seems invalid, skipping validation', [
                    'webhook_time' => $webhookTime,
                    'raw_timestamp' => $timestamp
                ]);
            } else if (abs($currentTime - $webhookTime) > 300) {
                // Only validate if timestamp looks reasonable
                Log::error('Retell webhook timestamp expired', [
                    'current_time' => $currentTime,
                    'webhook_time' => $webhookTime,
                    'difference_seconds' => abs($currentTime - $webhookTime),
                ]);
                
                abort(401, 'Request expired');
            }
        }

        // Build signature payload
        $body = $request->getContent();
        $signaturePayload = $timestamp ? "{$timestamp}.{$body}" : $body;
        
        // Calculate expected signature
        $expectedSignature = hash_hmac('sha256', $signaturePayload, $apiKey);
        
        // Timing-safe comparison
        if (!hash_equals($expectedSignature, $signature)) {
            Log::error('Retell webhook signature mismatch', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'expected_signature' => substr($expectedSignature, 0, 10) . '...',
                'received_signature' => substr($signature, 0, 10) . '...',
                'timestamp' => $timestamp,
                'has_timestamp' => !empty($timestamp),
                'body_length' => strlen($body),
                'first_100_chars' => substr($body, 0, 100),
            ]);
            
            abort(401, 'Invalid signature');
        }
        
        // Mark request as validated
        $request->merge(['webhook_validated' => true]);

        return $next($request);
    }
}
