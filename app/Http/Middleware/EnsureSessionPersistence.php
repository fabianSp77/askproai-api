<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class EnsureSessionPersistence
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Ensure session is started
        if (!Session::isStarted()) {
            Session::start();
        }

        // Process the request
        $response = $next($request);

        // Force session save after response
        Session::save();

        // Add session cookie headers if not present
        if (!$response->headers->has('Set-Cookie')) {
            $config = config('session');
            $cookie = cookie(
                $config['cookie'],
                Session::getId(),
                $config['lifetime'],
                $config['path'],
                $config['domain'],
                $config['secure'],
                $config['http_only'],
                false,
                $config['same_site'] ?? null
            );
            $response->withCookie($cookie);
        }

        return $response;
    }
}