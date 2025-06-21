<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyRetellSignatureTemporary
{
    public function handle(Request $request, Closure $next): Response
    {
        // Log webhook details for debugging
        Log::warning('RETELL WEBHOOK - SIGNATURE BYPASS ACTIVE', [
            'url' => $request->fullUrl(),
            'has_signature' => $request->hasHeader('X-Retell-Signature'),
            'has_timestamp' => $request->hasHeader('X-Retell-Timestamp'),
            'signature' => $request->header('X-Retell-Signature'),
            'timestamp' => $request->header('X-Retell-Timestamp'),
            'ip' => $request->ip(),
            'event' => $request->input('event'),
            'call_id' => $request->input('call.call_id')
        ]);
        
        // Mark request as validated to bypass further checks
        $request->merge(['webhook_validated' => true]);

        return $next($request);
    }
}