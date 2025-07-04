<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * DEBUG VERSION: Logs all incoming webhook attempts for troubleshooting
 * DO NOT USE IN PRODUCTION!
 */
class VerifyRetellSignatureDebug
{
    public function handle(Request $request, Closure $next): Response
    {
        // Log ALL headers and body for debugging
        Log::warning('[RETELL DEBUG] Incoming webhook attempt', [
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'headers' => $request->headers->all(),
            'body' => $request->getContent(),
            'method' => $request->method(),
        ]);
        
        // Check what headers Retell is actually sending
        $headers = [];
        foreach ($request->headers->all() as $key => $value) {
            if (stripos($key, 'retell') !== false || stripos($key, 'signature') !== false) {
                $headers[$key] = $value;
            }
        }
        
        Log::warning('[RETELL DEBUG] Signature-related headers', $headers);
        
        // For now, let it through to see what happens
        return $next($request);
    }
}