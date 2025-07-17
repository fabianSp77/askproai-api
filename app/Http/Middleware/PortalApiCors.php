<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PortalApiCors
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
        $response = $next($request);

        // Add CORS headers for API endpoints
        if ($request->is('business/api/*') || $request->is('business/api-optional/*')) {
            $headers = [
                'Access-Control-Allow-Origin' => $request->headers->get('Origin') ?: '*',
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
                'Access-Control-Allow-Headers' => 'X-Requested-With, Content-Type, X-CSRF-TOKEN, Authorization, Accept, Origin',
                'Access-Control-Allow-Credentials' => 'true',
                'Access-Control-Max-Age' => '86400',
            ];

            // Only set Access-Control-Allow-Origin to specific origin if credentials are used
            if ($request->headers->get('Origin')) {
                $allowedOrigins = [
                    'https://api.askproai.de',
                    'https://business.askproai.de',
                    'https://askproai.de',
                    'http://localhost:3000', // For development
                    'http://localhost:5173', // For Vite development
                ];

                $origin = $request->headers->get('Origin');
                if (in_array($origin, $allowedOrigins)) {
                    $headers['Access-Control-Allow-Origin'] = $origin;
                }
            }

            foreach ($headers as $key => $value) {
                $response->headers->set($key, $value);
            }
        }

        return $response;
    }
}