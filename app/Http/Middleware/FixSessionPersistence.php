<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FixSessionPersistence
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Ensure session is started
        if (! $request->hasSession() || ! $request->session()->isStarted()) {
            $request->session()->start();
        }

        $response = $next($request);

        // Force session to save
        if ($request->hasSession() && $request->session()->isStarted()) {
            $request->session()->save();
        }

        return $response;
    }
}
