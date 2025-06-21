<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Debug version of Retell signature verification
 * Logs detailed information but still validates signatures
 */
class VerifyRetellSignatureDebug
{
    public function handle(Request $request, Closure $next): Response
    {
        // Skip in testing environment
        if (app()->environment('testing')) {
            return $next($request);
        }
        
        $debugInfo = [
            'timestamp' => date('Y-m-d H:i:s'),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'headers' => [],
            'body_sample' => substr($request->getContent(), 0, 200),
            'signature_attempts' => []
        ];
        
        // Collect all headers
        foreach ($request->headers->all() as $key => $value) {
            $debugInfo['headers'][$key] = is_array($value) ? $value[0] : $value;
        }
        
        // Get configuration
        $webhookSecret = config('services.retell.webhook_secret');
        if (empty($webhookSecret)) {
            $webhookSecret = config('services.retell.api_key');
            $debugInfo['using_api_key_as_secret'] = true;
        }
        
        if (empty($webhookSecret)) {
            $debugInfo['error'] = 'No webhook secret configured';
            Log::error('[Retell Debug] Configuration error', $debugInfo);
            abort(500, 'Webhook verification not configured');
        }
        
        // Get signature and timestamp
        $signatureHeader = $request->header('X-Retell-Signature');
        $timestampHeader = $request->header('X-Retell-Timestamp');
        
        if (empty($signatureHeader)) {
            $debugInfo['error'] = 'Missing X-Retell-Signature header';
            Log::error('[Retell Debug] Missing signature', $debugInfo);
            abort(401, 'Missing signature header');
        }
        
        // Parse signature
        $signature = $signatureHeader;
        $timestamp = $timestampHeader;
        
        if (strpos($signatureHeader, 'v=') === 0) {
            $parts = explode(',', substr($signatureHeader, 2), 2);
            if (count($parts) === 2) {
                $timestamp = $timestamp ?? $parts[0];
                $signature = $parts[1];
                $debugInfo['signature_format'] = 'v=timestamp,signature';
            } else {
                $signature = $parts[0];
                $debugInfo['signature_format'] = 'v=signature';
            }
        } else {
            $debugInfo['signature_format'] = 'plain';
        }
        
        $body = $request->getContent();
        $verified = false;
        
        // Try different verification methods
        $methods = [
            'timestamp_dot_body' => $timestamp ? "{$timestamp}.{$body}" : null,
            'body_only' => $body,
            'timestamp_space_body' => $timestamp ? "{$timestamp} {$body}" : null,
            'timestamp_colon_body' => $timestamp ? "{$timestamp}:{$body}" : null,
        ];
        
        foreach ($methods as $method => $payload) {
            if ($payload === null) continue;
            
            $expected = hash_hmac('sha256', $payload, $webhookSecret);
            $debugInfo['signature_attempts'][$method] = [
                'expected' => substr($expected, 0, 20) . '...',
                'matches' => hash_equals($expected, $signature)
            ];
            
            if (hash_equals($expected, $signature)) {
                $verified = true;
                $debugInfo['verified_method'] = $method;
                break;
            }
            
            // Also try base64 encoded
            $expectedBase64 = base64_encode(hash_hmac('sha256', $payload, $webhookSecret, true));
            $debugInfo['signature_attempts'][$method . '_base64'] = [
                'expected' => substr($expectedBase64, 0, 20) . '...',
                'matches' => ($expectedBase64 === $signature)
            ];
            
            if ($expectedBase64 === $signature) {
                $verified = true;
                $debugInfo['verified_method'] = $method . '_base64';
                break;
            }
        }
        
        $debugInfo['received_signature'] = substr($signature, 0, 20) . '...';
        $debugInfo['timestamp'] = $timestamp;
        $debugInfo['verified'] = $verified;
        
        // Log the debug info
        Log::info('[Retell Debug] Webhook verification attempt', $debugInfo);
        
        if (!$verified) {
            abort(401, 'Invalid signature');
        }
        
        // Mark request as validated
        $request->merge(['webhook_validated' => true]);
        
        return $next($request);
    }
}