<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyRetellFunctionSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.retellai.function_secret');

        if (blank($secret)) {
            Log::error('Retell function secret not configured');
            return response()->json([
                'error' => 'Retell function authentication not configured',
            ], 500);
        }

        if ($this->hasValidBearerToken($request, $secret) || $this->hasValidSignature($request, $secret)) {
            return $next($request);
        }

        // Temporary debug logging to understand what Retell is sending
        Log::warning('Retell function authentication failed - DEBUG', [
            'path' => $request->path(),
            'ip' => $request->ip(),
            'headers' => $request->headers->all(),
            'has_authorization' => $request->hasHeader('Authorization'),
            'has_signature' => $request->hasHeader('X-Retell-Function-Signature'),
            'auth_header' => substr($request->header('Authorization') ?? 'none', 0, 20),
        ]);

        return response()->json([
            'error' => 'Unauthorized',
        ], 401);
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
