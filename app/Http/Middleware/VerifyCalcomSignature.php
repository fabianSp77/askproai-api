<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class VerifyCalcomSignature
{
    /**
     * Handle an incoming Cal.com webhook request.
     * Verifies HMAC-SHA256 signature to ensure authenticity.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Rate limiting for webhook endpoints (prevent abuse)
        $rateLimitKey = 'calcom-webhook:' . $request->ip();
        if (RateLimiter::tooManyAttempts($rateLimitKey, 30)) {
            Log::warning('[Cal.com] Webhook rate limit exceeded', [
                'ip' => $request->ip(),
                'attempts' => RateLimiter::attempts($rateLimitKey)
            ]);
            return response('Too many webhook requests', 429);
        }
        RateLimiter::hit($rateLimitKey, 60); // 30 requests per minute

        // 1) Get webhook secret from configuration
        $secret = config('services.calcom.webhook_secret');

        if (blank($secret)) {
            Log::error('[Cal.com] Webhook secret not configured in services.calcom.webhook_secret');
            return response('Webhook configuration error', 500);
        }

        // 2) Get request payload
        $payload = $request->getContent();
        
        // 3) Calculate expected signatures (handle different payload variations)
        $expectedSignatures = [
            hash_hmac('sha256', $payload, $secret),
            hash_hmac('sha256', rtrim($payload, "\r\n"), $secret),
            'sha256=' . hash_hmac('sha256', $payload, $secret),
            'sha256=' . hash_hmac('sha256', rtrim($payload, "\r\n"), $secret),
        ];

        // 4) Get signature from request headers (check multiple header variants)
        $providedSignature = $request->header('X-Cal-Signature-256')
            ?? $request->header('Cal-Signature-256')
            ?? $request->header('X-Cal-Signature')
            ?? $request->header('Cal-Signature');

        if (!$providedSignature) {
            Log::warning('[Cal.com] Webhook request without signature', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'headers' => array_keys($request->headers->all())
            ]);
            return response('Missing webhook signature', 401);
        }

        // 5) Verify signature using timing-safe comparison
        $signatureValid = false;
        foreach ($expectedSignatures as $expectedSignature) {
            if (hash_equals($expectedSignature, $providedSignature)) {
                $signatureValid = true;
                break;
            }
        }

        if (!$signatureValid) {
            Log::warning('[Cal.com] Invalid webhook signature', [
                'ip' => $request->ip(),
                'provided_signature' => substr($providedSignature, 0, 10) . '...',
                'path' => $request->path()
            ]);
            return response('Invalid webhook signature', 401);
        }

        // 6) Optional: Check timestamp to prevent replay attacks
        if ($request->has('timestamp')) {
            $timestamp = $request->input('timestamp');
            $currentTime = now()->timestamp;
            
            // Reject webhooks older than 5 minutes
            if (abs($currentTime - $timestamp) > 300) {
                Log::warning('[Cal.com] Webhook timestamp too old', [
                    'webhook_time' => $timestamp,
                    'current_time' => $currentTime,
                    'difference' => abs($currentTime - $timestamp)
                ]);
                return response('Webhook timestamp expired', 401);
            }
        }

        // Signature verified successfully
        Log::info('[Cal.com] Webhook signature verified', [
            'ip' => $request->ip(),
            'event' => $request->input('triggerEvent'),
            'path' => $request->path()
        ]);

        // Clear rate limit on successful verification
        RateLimiter::clear($rateLimitKey);

        return $next($request);
    }
}