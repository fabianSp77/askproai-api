<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to verify Retell webhook signatures
 *
 * Retell uses a signature format: t=timestamp,v1=hmac_signature
 * where the HMAC is computed over: timestamp.payload using the webhook secret.
 *
 * This prevents:
 * - Unauthorized webhook submissions (requires secret)
 * - Replay attacks (5-minute TTL on signatures)
 * - Payload tampering (HMAC verification)
 *
 * @see https://docs.retellai.com/features/secure-webhook
 */
class VerifyRetellWebhookSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $webhookSecret = config('services.retellai.webhook_secret');

        // Fail-secure: reject if secret is not configured
        if (empty($webhookSecret)) {
            Log::error('Retell webhook secret not configured');
            return response()->json(['error' => 'Webhook secret not configured'], 500);
        }

        // Get signature from header (case-insensitive)
        $signature = $request->header('x-retell-signature');

        // Reject if no signature provided
        if (empty($signature)) {
            Log::warning('Retell webhook: Missing signature header', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);
            return response()->json(['error' => 'Missing signature'], 401);
        }

        // Trim whitespace from signature
        $signature = trim($signature);

        // Verify the signature
        $payload = $request->getContent();
        if (!$this->verifySignature($payload, $webhookSecret, $signature)) {
            Log::warning('Retell webhook: Invalid signature', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'signature_prefix' => substr($signature, 0, 20) . '...',
            ]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // Signature valid - proceed with request
        Log::debug('Retell webhook signature verified successfully', [
            'path' => $request->path(),
        ]);

        return $next($request);
    }

    /**
     * Verify Retell webhook signature
     *
     * @param string $payload Raw request body
     * @param string $apiKey Retell API key
     * @param string $signature x-retell-signature header value
     * @return bool
     */
    private function verifySignature(string $payload, string $apiKey, string $signature): bool
    {
        try {
            // Retell signature format: t=timestamp,v1=hmac_signature
            $parts = [];
            foreach (explode(',', $signature) as $part) {
                $splitResult = explode('=', $part, 2);
                if (count($splitResult) !== 2) {
                    continue;
                }
                [$key, $value] = $splitResult;
                $parts[$key] = $value;
            }

            // Support BOTH Retell signature formats:
            // Old format: t=timestamp,v1=hmac  → signed payload = "timestamp.payload"
            // New format: v=timestamp,d=digest → signed payload = "payload+timestamp" (no separator!)

            $isNewFormat = isset($parts['v']) && isset($parts['d']);
            $isOldFormat = isset($parts['t']) && isset($parts['v1']);

            if ($isNewFormat) {
                $timestamp = $parts['v'];
                $receivedSignature = $parts['d'];
            } elseif ($isOldFormat) {
                $timestamp = $parts['t'];
                $receivedSignature = $parts['v1'];
            } else {
                Log::warning('Retell signature missing required parts', [
                    'signature' => $signature,
                    'parts' => array_keys($parts),
                    'supported_formats' => ['t=,v1= (old)', 'v=,d= (new)'],
                ]);
                return false;
            }

            // Prevent replay attacks: reject signatures older than 5 minutes
            // Note: New format uses milliseconds, old format uses seconds
            $timestampSeconds = $isNewFormat ? (int)($timestamp / 1000) : (int)$timestamp;
            $currentTime = time();
            $signatureAge = $currentTime - $timestampSeconds;

            if ($signatureAge > 300) { // 5 minutes
                Log::warning('Retell signature too old (replay attack protection)', [
                    'timestamp' => $timestamp,
                    'timestamp_seconds' => $timestampSeconds,
                    'age_seconds' => $signatureAge,
                    'format' => $isNewFormat ? 'new (v=,d=)' : 'old (t=,v1=)',
                ]);
                return false;
            }

            // Compute expected signature based on format:
            // Old format: HMAC-SHA256("timestamp.payload", apiKey)
            // New format: HMAC-SHA256("payload+timestamp", apiKey) - NO separator!
            if ($isNewFormat) {
                // New format: payload concatenated with timestamp (no separator)
                $signedPayload = $payload . $timestamp;
            } else {
                // Old format: timestamp.payload (with dot separator)
                $signedPayload = $timestamp . '.' . $payload;
            }
            $expectedSignature = hash_hmac('sha256', $signedPayload, $apiKey);

            // Constant-time comparison to prevent timing attacks
            $isValid = hash_equals($expectedSignature, $receivedSignature);

            if (!$isValid) {
                Log::warning('Retell signature mismatch', [
                    'format' => $isNewFormat ? 'new (v=,d=)' : 'old (t=,v1=)',
                    'timestamp' => $timestamp,
                    'timestamp_seconds' => $timestampSeconds,
                    'payload_length' => strlen($payload),
                    'signed_payload_preview' => substr($signedPayload, 0, 50) . '...',
                    'expected_signature_prefix' => substr($expectedSignature, 0, 10),
                    'received_signature_prefix' => substr($receivedSignature, 0, 10),
                ]);
            }

            return $isValid;

        } catch (\Exception $e) {
            Log::error('Retell signature verification error', [
                'error' => $e->getMessage(),
                'signature' => $signature,
            ]);
            return false;
        }
    }
}
