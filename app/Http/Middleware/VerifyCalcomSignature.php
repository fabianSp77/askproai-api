<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyCalcomSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        /* 1) Secret jetzt aus der Config ziehen */
        $secret = config('services.calcom.webhook_secret');

        if (blank($secret)) {
            Log::warning('[Cal.com] Secret missing (config)');

            return response('Cal.com secret missing', 500);
        }

        /* 2) Digests – roh & ohne NL */
        $raw = $request->getContent();
        $trimmed = rtrim($raw, "\r\n");

        $valid = [
            hash_hmac('sha256', $raw, $secret),
            hash_hmac('sha256', $trimmed, $secret),
            'sha256='.hash_hmac('sha256', $raw, $secret),
            'sha256='.hash_hmac('sha256', $trimmed, $secret),
        ];

        /* 3) Header lesen (4 Varianten) */
        $provided = $request->header('X-Cal-Signature-256')
            ?? $request->header('Cal-Signature-256')
            ?? $request->header('X-Cal-Signature')
            ?? $request->header('Cal-Signature')
            ?? 'no-secret-provided';

        if (! in_array($provided, $valid, true)) {
            Log::debug('[Cal.com] Signature failed', compact('provided'));

            return response('Invalid Cal.com signature', 401);
        }

        return $next($request);
    }
}
