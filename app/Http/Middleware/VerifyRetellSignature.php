<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyRetellSignature
{
    public function handle(Request $r, Closure $next): Response
    {
        $secret = config('services.retell.token');
        $sig    = $r->header('x-retell-signature');

        Log::info('Retell-Webhook RAW', ['body' => $r->getContent(), 'sig' => $sig]);

        if (!$secret || !$sig) {
            Log::warning('Retell-Webhook: Signature missing');
            return response('Signature missing', 401);
        }

        $expected = hash_hmac('sha256', $r->getContent(), $secret);   // hex-digest
        Log::info('Retell-Webhook EXPECTED', ['expected' => $expected]);

        if (!hash_equals($expected, $sig)) {
            Log::warning('Retell-Webhook: Signature mismatch');
            return response('Invalid signature', 401);
        }

        Log::info('Retell-Webhook: Signature OK');
        return $next($r);
    }
}
