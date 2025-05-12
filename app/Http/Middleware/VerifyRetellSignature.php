<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyRetellSignature
{
    public function handle(Request $request, Closure $next)
    {
        $signature = $request->header('X-Retell-Signature');
        $body      = $request->getContent();

        if (! hash_equals($signature, hash_hmac('sha256', $body, config('services.retell.secret')))) {
            abort(401, 'Invalid Retell signature');
        }

        return $next($request);
    }
}
