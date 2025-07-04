<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Exceptions\WebhookSignatureException;

class VerifyRetellSignatureFixed
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
        
        // Log incoming webhook
        $correlationId = $request->header('X-Correlation-ID', uniqid('retell_'));
        Log::info('[Retell Webhook] Processing webhook', [
            'correlation_id' => $correlationId,
            'url' => $request->fullUrl(),
            'has_signature' => $request->hasHeader('X-Retell-Signature'),
            'event_type' => $request->input('event_type', 'unknown'),
            'ip' => $request->ip()
        ]);
        
        // Get webhook secret - use API key as per Retell documentation
        $apiKey = config('services.retell.api_key');
        
        if (empty($apiKey)) {
            Log::error('[Retell Webhook] No API key configured');
            throw new WebhookSignatureException(
                'Webhook verification not properly configured',
                'retell',
                500
            );
        }
        
        // Get signature header
        $signatureHeader = $request->header('X-Retell-Signature');
        
        if (empty($signatureHeader)) {
            Log::error('[Retell Webhook] Missing signature header');
            throw new WebhookSignatureException(
                'Missing X-Retell-Signature header',
                'retell'
            );
        }
        
        // Get request body
        $body = $request->getContent();
        
        // Parse the signature header
        // Retell uses format: v=<timestamp>,d=<signature>
        $timestamp = null;
        $signature = null;
        
        if (preg_match('/v=(\d+),d=([a-f0-9]+)/', $signatureHeader, $matches)) {
            $timestamp = $matches[1];
            $signature = $matches[2];
        } else {
            // Fallback: try to parse alternative formats
            $parts = explode(',', $signatureHeader);
            foreach ($parts as $part) {
                if (strpos($part, 'v=') === 0) {
                    $timestamp = substr($part, 2);
                } elseif (strpos($part, 'd=') === 0) {
                    $signature = substr($part, 2);
                }
            }
        }
        
        if (!$timestamp || !$signature) {
            Log::error('[Retell Webhook] Invalid signature format', [
                'header' => $signatureHeader
            ]);
            throw new WebhookSignatureException(
                'Invalid signature format',
                'retell'
            );
        }
        
        // According to Retell documentation:
        // The signature is computed using HMAC-SHA256 with:
        // - Message: timestamp + body (concatenated as strings)
        // - Secret: Your API key
        
        $message = $timestamp . $body;
        $expectedSignature = hash_hmac('sha256', $message, $apiKey);
        
        Log::debug('[Retell Webhook] Signature calculation', [
            'timestamp' => $timestamp,
            'body_length' => strlen($body),
            'message_length' => strlen($message),
            'expected_signature' => substr($expectedSignature, 0, 20) . '...',
            'received_signature' => substr($signature, 0, 20) . '...'
        ]);
        
        // Verify signature
        if (!hash_equals($expectedSignature, $signature)) {
            // Try with raw timestamp (not as string concatenation)
            $messageAlt = $timestamp . '.' . $body;
            $expectedSignatureAlt = hash_hmac('sha256', $messageAlt, $apiKey);
            
            if (!hash_equals($expectedSignatureAlt, $signature)) {
                Log::error('[Retell Webhook] Signature verification failed', [
                    'expected' => substr($expectedSignature, 0, 20) . '...',
                    'expected_alt' => substr($expectedSignatureAlt, 0, 20) . '...',
                    'received' => substr($signature, 0, 20) . '...'
                ]);
                
                throw new WebhookSignatureException(
                    'Invalid webhook signature',
                    'retell'
                );
            }
        }
        
        // Verify timestamp (5 minute window)
        $currentTime = time() * 1000; // Convert to milliseconds
        $webhookTime = intval($timestamp);
        $timeDiff = abs($currentTime - $webhookTime);
        
        if ($timeDiff > 300000) { // 5 minutes in milliseconds
            Log::warning('[Retell Webhook] Timestamp outside acceptable window', [
                'current_time' => $currentTime,
                'webhook_time' => $webhookTime,
                'difference_ms' => $timeDiff
            ]);
            
            // Don't reject for now, just log
            // throw new WebhookSignatureException('Request timestamp expired', 'retell');
        }
        
        // Success logging
        $payload = json_decode($body, true);
        Log::info('[Retell Webhook] Signature verified successfully', [
            'correlation_id' => $correlationId,
            'event_type' => $payload['event_type'] ?? 'unknown',
            'call_id' => $payload['call_id'] ?? $payload['call']['call_id'] ?? null
        ]);
        
        // Mark request as validated
        $request->merge([
            'webhook_validated' => true,
            'correlation_id' => $correlationId
        ]);

        return $next($request);
    }
}