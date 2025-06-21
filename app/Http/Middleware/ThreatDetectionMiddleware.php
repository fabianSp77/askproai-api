<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Security\ThreatDetector;
use Symfony\Component\HttpFoundation\Response;

class ThreatDetectionMiddleware
{
    private ThreatDetector $detector;

    public function __construct(ThreatDetector $detector)
    {
        $this->detector = $detector;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Analyze request for threats
        $threats = $this->detector->analyze($request);

        // Block if critical threats detected
        if (!empty($threats) && $this->containsCriticalThreat($threats)) {
            return $this->blockRequest($request);
        }

        // Continue with request
        $response = $next($request);

        // Add security headers to response
        return $this->addSecurityHeaders($response);
    }

    /**
     * Check if threats contain critical ones
     */
    private function containsCriticalThreat(array $threats): bool
    {
        $criticalTypes = ['sql_injection', 'command_injection', 'path_traversal'];
        
        foreach ($threats as $threat) {
            if (isset($threat['threats'])) {
                foreach ($threat['threats'] as $type) {
                    if (in_array($type, $criticalTypes)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Block suspicious request
     */
    private function blockRequest(Request $request): Response
    {
        // Log blocked request
        \Log::channel('security')->error('Request blocked due to security threat', [
            'ip' => $request->ip(),
            'url' => $request->fullUrl(),
            'method' => $request->method()
        ]);

        // Return generic error to avoid information disclosure
        return response()->json([
            'error' => 'Bad Request',
            'message' => 'Your request contains invalid data.'
        ], 400);
    }

    /**
     * Add security headers to response
     */
    private function addSecurityHeaders($response)
    {
        // Only add headers if response supports it
        if ($response instanceof \Illuminate\Http\Response || $response instanceof \Symfony\Component\HttpFoundation\Response) {
            $headers = [
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options' => 'DENY',
                'X-XSS-Protection' => '1; mode=block',
                'Referrer-Policy' => 'strict-origin-when-cross-origin',
                'Content-Security-Policy' => "default-src 'self' http: https:; script-src 'self' 'unsafe-inline' 'unsafe-eval' http: https:; style-src 'self' 'unsafe-inline' http: https:; connect-src 'self' http: https: ws: wss:;",
            ];

            foreach ($headers as $key => $value) {
                $response->headers->set($key, $value);
            }
        }

        return $response;
    }
}