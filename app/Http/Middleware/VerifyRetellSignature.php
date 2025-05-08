<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyRetellSignature
{
    public function handle(Request $r, Closure $next): Response
    {
        $secret = config('services.retell.token');   // dein API-Key
        $sig    = $r->header('x-retell-signature');  // kommt Base64-kodiert

        if (!$secret || !$sig) {
            return response('Signature missing', 401);
        }

        $expected = base64_encode(hash_hmac('sha256', $r->getContent(), $secret, true));

        if (!hash_equals($expected, $sig)) {
            return response('Invalid signature', 401);
        }

        return $next($r);
    }
}
