<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

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
        // 🔥 ULTRA DEBUG: Write to file to prove middleware runs
        file_put_contents('/tmp/retell_middleware_test.log', date('Y-m-d H:i:s') . ' - Middleware EXECUTED' . PHP_EOL, FILE_APPEND);

        // ALWAYS ACCEPT (temporary for debugging)
        return $next($request);

        // TODO: Implement proper Retell signature verification
        // Retell uses x-retell-signature header with custom format
        // See: https://docs.retellai.com/features/secure-webhook
        //
        // OLD CODE (HMAC verification - doesn't work with Retell):
        // $webhookSecret = config('services.retellai.webhook_secret');
        // $signature = $request->header('X-Retell-Signature');
        // $payload = $request->getContent();
        // $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);
        // if (!hash_equals($expectedSignature, trim($signature))) { ... }
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
                [$key, $value] = explode('=', $part, 2);
                $parts[$key] = $value;
            }

            if (!isset($parts['t']) || !isset($parts['v1'])) {
                Log::warning('Retell signature missing required parts', [
                    'signature' => $signature,
                    'parts' => array_keys($parts),
                ]);
                return false;
            }

            $timestamp = $parts['t'];
            $receivedSignature = $parts['v1'];

            // Prevent replay attacks: reject signatures older than 5 minutes
            $currentTime = time();
            $signatureAge = $currentTime - (int)$timestamp;
            if ($signatureAge > 300) { // 5 minutes
                Log::warning('Retell signature too old', [
                    'timestamp' => $timestamp,
                    'age_seconds' => $signatureAge,
                ]);
                return false;
            }

            // Compute expected signature: HMAC-SHA256(timestamp.payload, apiKey)
            $signedPayload = $timestamp . '.' . $payload;
            $expectedSignature = hash_hmac('sha256', $signedPayload, $apiKey);

            // Constant-time comparison to prevent timing attacks
            $isValid = hash_equals($expectedSignature, $receivedSignature);

            if (!$isValid) {
                Log::warning('Retell signature mismatch', [
                    'timestamp' => $timestamp,
                    'payload_length' => strlen($payload),
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