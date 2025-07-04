<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Nur für HTTP-Responses (nicht für BinaryFileResponse etc.)
        if (method_exists($response, 'header')) {
            // Prevent clickjacking attacks
            $response->header('X-Frame-Options', 'SAMEORIGIN');
            
            // Prevent MIME type sniffing
            $response->header('X-Content-Type-Options', 'nosniff');
            
            // Enable XSS protection
            $response->header('X-XSS-Protection', '1; mode=block');
            
            // Referrer policy
            $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');
            
            // Permissions policy (formerly Feature-Policy)
            $response->header('Permissions-Policy', 'geolocation=(self), microphone=(), camera=()');
            
            // Content Security Policy (adjust as needed)
            if (!app()->environment('local')) {
                $response->header('Content-Security-Policy', 
                    "default-src 'self'; " .
                    "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://unpkg.com; " .
                    "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; " .
                    "font-src 'self' https://fonts.gstatic.com; " .
                    "img-src 'self' data: https:; " .
                    "connect-src 'self' wss: https://api.askproai.de; " .
                    "frame-ancestors 'self';"
                );
            }
            
            // Strict Transport Security (only for HTTPS)
            if ($request->secure()) {
                $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
            }
        }

        return $response;
    }
}