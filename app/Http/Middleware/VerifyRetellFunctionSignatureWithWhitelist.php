<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyRetellFunctionSignatureWithWhitelist
{
    /**
     * Whitelisted IPs that don't require authentication
     * These are known Retell AI server IPs
     */
    private const WHITELISTED_IPS = [
        '100.20.5.228',     // Retell AI production
        '100.20.0.0/16',    // AWS us-east-1 range (broader whitelist)
        '127.0.0.1',        // Localhost for testing
        '::1',              // IPv6 localhost
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Check if IP is whitelisted - if so, bypass authentication
        if ($this->isWhitelistedIP($request->ip())) {
            Log::info('Retell function call from whitelisted IP', [
                'ip' => $request->ip(),
                'path' => $request->path()
            ]);
            return $next($request);
        }

        $secret = config('services.retellai.function_secret');

        if (blank($secret)) {
            Log::error('Retell function secret not configured');
            return response()->json([
                'error' => 'Retell function authentication not configured',
            ], 500);
        }

        // Check authentication: Bearer token or HMAC signature required
        if ($this->hasValidBearerToken($request, $secret) || $this->hasValidSignature($request, $secret)) {
            return $next($request);
        }

        // Authentication failed - log details for debugging
        Log::warning('Retell function authentication failed', [
            'path' => $request->path(),
            'ip' => $request->ip(),
            'has_authorization' => $request->hasHeader('Authorization'),
            'has_signature' => $request->hasHeader('X-Retell-Function-Signature'),
        ]);

        return response()->json([
            'error' => 'Unauthorized',
        ], 401);
    }

    private function isWhitelistedIP(string $ip): bool
    {
        foreach (self::WHITELISTED_IPS as $whitelisted) {
            // Check for CIDR notation
            if (str_contains($whitelisted, '/')) {
                if ($this->ipInRange($ip, $whitelisted)) {
                    return true;
                }
            } else {
                // Exact IP match
                if ($ip === $whitelisted) {
                    return true;
                }
            }
        }
        return false;
    }

    private function ipInRange(string $ip, string $range): bool
    {
        list($subnet, $bits) = explode('/', $range);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet &= $mask;
        return ($ip & $mask) == $subnet;
    }

    private function hasValidBearerToken(Request $request, string $secret): bool
    {
        $authorization = $request->header('Authorization');
        if (!is_string($authorization) || !str_starts_with($authorization, 'Bearer ')) {
            return false;
        }

        $token = substr($authorization, 7);

        return hash_equals($secret, trim($token));
    }

    private function hasValidSignature(Request $request, string $secret): bool
    {
        $provided = $request->header('X-Retell-Function-Signature');
        if (!is_string($provided)) {
            return false;
        }

        $payload = $request->getContent();
        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, trim($provided));
    }
}