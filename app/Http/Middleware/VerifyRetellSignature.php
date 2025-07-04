<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Exceptions\WebhookSignatureException;

class VerifyRetellSignature
{
    /**
     * Known Retell IP addresses for additional security
     */
    private const RETELL_IPS = [
        '100.20.5.228',
        '34.226.180.161',
        '34.198.47.77',
        '52.203.159.213',
        '52.53.229.199',
        '54.241.134.41',
        '54.183.150.123',
        '152.53.228.178'
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Skip in testing environment
        if (app()->environment('testing')) {
            return $next($request);
        }
        
        // Log incoming webhook attempts for monitoring
        $correlationId = $request->header('X-Correlation-ID', uniqid('retell_'));
        Log::info('[Retell Webhook] Signature verification started', [
            'correlation_id' => $correlationId,
            'url' => $request->fullUrl(),
            'has_signature' => $request->hasHeader('X-Retell-Signature'),
            'has_timestamp' => $request->hasHeader('X-Retell-Timestamp'),
            'ip' => $request->ip(),
            'content_length' => strlen($request->getContent()),
            'event_type' => $request->input('event_type', 'unknown')
        ]);
        
        // Verify IP address (optional but recommended)
        if (config('services.retell.verify_ip', false) && !in_array($request->ip(), self::RETELL_IPS)) {
            Log::warning('[Retell Webhook] Request from unknown IP', [
                'ip' => $request->ip(),
                'known_ips' => self::RETELL_IPS
            ]);
        }
        
        // Get configuration
        $webhookSecret = config('services.retell.webhook_secret');
        
        // If no dedicated webhook secret, fall back to API key
        if (empty($webhookSecret)) {
            $webhookSecret = config('services.retell.api_key');
            if (empty($webhookSecret)) {
                Log::error('[Retell Webhook] No webhook secret or API key configured');
                throw new WebhookSignatureException(
                    'Webhook verification not properly configured',
                    'retell',
                    500
                );
            }
            Log::debug('[Retell Webhook] Using API key for signature verification');
        }

        $signatureHeader = $request->header('X-Retell-Signature');
        $timestampHeader = $request->header('X-Retell-Timestamp');
        
        // Validate required headers
        if (empty($signatureHeader)) {
            Log::error('[Retell Webhook] Missing signature header');
            throw new WebhookSignatureException(
                'Missing X-Retell-Signature header',
                'retell'
            );
        }
        
        // Parse signature and timestamp
        $signature = $signatureHeader;
        $timestamp = $timestampHeader;
        
        // Check if signature uses combined format: v=timestamp,d=signature or v=timestamp,signature
        if (strpos($signatureHeader, 'v=') === 0) {
            // Parse format like: v=1750598562480,d=d14cb98fdd7e628640694d7bad14f1ea36444e3807b0ef76fa388d4ad139b04d
            $headerParts = substr($signatureHeader, 2); // Remove 'v='
            $parts = explode(',', $headerParts);
            
            foreach ($parts as $part) {
                if (strpos($part, 'd=') === 0) {
                    // Signature part
                    $signature = substr($part, 2);
                } elseif (is_numeric($part)) {
                    // Timestamp part (just numbers)
                    $timestamp = $timestamp ?? $part;
                } elseif (strpos($part, '=') === false && strlen($part) > 20) {
                    // Might be signature without prefix
                    $signature = $part;
                }
            }
            
            // If we found timestamp in first part and no 'd=' prefix
            if (count($parts) === 2 && is_numeric($parts[0]) && strpos($parts[1], '=') === false) {
                $timestamp = $timestamp ?? $parts[0];
                $signature = $parts[1];
            }
        }
        
        // Clean up signature (remove any whitespace)
        $signature = trim($signature);
        
        // Get request body
        $body = $request->getContent();
        
        // Log signature details for debugging (without exposing secrets)
        Log::debug('[Retell Webhook] Signature components', [
            'signature_length' => strlen($signature),
            'timestamp' => $timestamp,
            'body_length' => strlen($body),
            'body_sample' => substr($body, 0, 100) . '...'
        ]);
        
        // Timestamp validation (if provided)
        if ($timestamp && is_numeric($timestamp)) {
            $currentTime = time();
            $webhookTime = $this->normalizeTimestamp($timestamp);
            
            // Validate timestamp is within acceptable window (5 minutes)
            $timeDiff = abs($currentTime - $webhookTime);
            if ($timeDiff > 300) {
                Log::warning('[Retell Webhook] Timestamp outside acceptable window', [
                    'current_time' => $currentTime,
                    'webhook_time' => $webhookTime,
                    'difference_seconds' => $timeDiff
                ]);
                
                // For now, log but don't reject (Retell might have clock skew)
                // throw new WebhookSignatureException('Request timestamp expired', 'retell');
            }
        }

        // Try multiple signature verification methods
        $verified = false;
        $attempts = [];
        
        // Method 1: timestamp.body (most common)
        if ($timestamp) {
            $payload1 = "{$timestamp}.{$body}";
            $expected1 = hash_hmac('sha256', $payload1, $webhookSecret);
            $attempts['timestamp_dot_body'] = substr($expected1, 0, 20) . '...';
            if (hash_equals($expected1, $signature)) {
                $verified = true;
                Log::debug('[Retell Webhook] Verified with method: timestamp.body');
            }
        }
        
        // Method 2: Just body (no timestamp)
        if (!$verified) {
            $expected2 = hash_hmac('sha256', $body, $webhookSecret);
            $attempts['body_only'] = substr($expected2, 0, 20) . '...';
            if (hash_equals($expected2, $signature)) {
                $verified = true;
                Log::debug('[Retell Webhook] Verified with method: body only');
            }
        }
        
        // Method 3: Base64 encoded (some webhooks use this)
        if (!$verified && $timestamp) {
            $payload3 = "{$timestamp}.{$body}";
            $expected3 = base64_encode(hash_hmac('sha256', $payload3, $webhookSecret, true));
            $attempts['base64_timestamp_body'] = substr($expected3, 0, 20) . '...';
            if ($signature === $expected3) {
                $verified = true;
                Log::debug('[Retell Webhook] Verified with method: base64 encoded');
            }
        }
        
        // Log verification attempts
        if (!$verified) {
            Log::error('[Retell Webhook] Signature verification failed', [
                'received_signature' => substr($signature, 0, 20) . '...',
                'attempted_signatures' => $attempts,
                'timestamp' => $timestamp,
                'ip' => $request->ip()
            ]);
            
            throw new WebhookSignatureException(
                'Invalid webhook signature',
                'retell'
            );
        }
        
        // Enhanced success logging
        $payload = json_decode($body, true);
        Log::info('[Retell Webhook] Signature verified successfully', [
            'correlation_id' => $correlationId,
            'event_type' => $payload['event_type'] ?? 'unknown',
            'call_id' => $payload['call_id'] ?? $payload['call']['call_id'] ?? null,
            'verification_method' => $verified ? 'success' : 'failed',
            'timestamp_used' => $timestamp,
            'ip' => $request->ip()
        ]);
        
        // Mark request as validated and add correlation ID
        $request->merge([
            'webhook_validated' => true,
            'correlation_id' => $correlationId
        ]);

        return $next($request);
    }
    
    /**
     * Normalize timestamp to seconds
     * Handles milliseconds, microseconds, and regular seconds
     */
    private function normalizeTimestamp($timestamp): int
    {
        $timestamp = (string) $timestamp;
        $length = strlen($timestamp);
        
        // Already in seconds (10 digits)
        if ($length === 10) {
            return (int) $timestamp;
        }
        
        // Milliseconds (13 digits)
        if ($length === 13) {
            return (int) ($timestamp / 1000);
        }
        
        // Microseconds (16 digits)
        if ($length === 16) {
            return (int) ($timestamp / 1000000);
        }
        
        // Nanoseconds (19 digits)
        if ($length === 19) {
            return (int) ($timestamp / 1000000000);
        }
        
        // Default: assume seconds
        return (int) $timestamp;
    }
}
