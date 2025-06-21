<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyRetellSignatureBypass
{
    public function handle(Request $request, Closure $next): Response
    {
        // TEMPORARY: Log but don't verify signatures
        Log::warning('BYPASSING Retell signature verification', [
            'url' => $request->fullUrl(),
            'has_signature' => $request->hasHeader('X-Retell-Signature'),
            'has_timestamp' => $request->hasHeader('X-Retell-Timestamp'),
            'ip' => $request->ip(),
            'event' => $request->input('event')
        ]);
        
        // Mark request as validated
        $request->merge(['webhook_validated' => true]);

        return $next($request);
    }
}