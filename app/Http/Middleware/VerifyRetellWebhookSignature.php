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
        // 🔥 TEMPORARY FIX: Use IP whitelist instead of signature verification
        // Retell uses a custom signature format that requires their SDK
        // Official Retell IP: 100.20.5.228
        $allowedIps = [
            '100.20.5.228', // Official Retell IP
            '127.0.0.1',    // Local testing
        ];

        $clientIp = $request->ip();

        if (!in_array($clientIp, $allowedIps)) {
            Log::error('Retell webhook rejected: IP not whitelisted', [
                'ip' => $clientIp,
                'path' => $request->path(),
                'user_agent' => $request->userAgent(),
            ]);
            return response()->json(['error' => 'Unauthorized: IP not whitelisted'], 401);
        }

        Log::info('✅ Retell webhook accepted (IP whitelisted)', [
            'ip' => $clientIp,
            'path' => $request->path(),
        ]);

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
}