<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\IpUtils;

class RestrictToInternalNetwork
{
    /**
     * Internal IP ranges that are allowed to access admin panels
     */
    private array $allowedRanges = [
        // Private IPv4 ranges (RFC 1918)
        '10.0.0.0/8',           // Class A private networks
        '172.16.0.0/12',        // Class B private networks  
        '192.168.0.0/16',       // Class C private networks
        
        // Loopback
        '127.0.0.0/8',          // IPv4 loopback
        '::1/128',              // IPv6 loopback
        
        // Link-local
        '169.254.0.0/16',       // IPv4 link-local
        'fe80::/10',            // IPv6 link-local
        
        // VPN and office networks (customize these)
        '10.0.0.0/24',          // Office network example
        '192.168.1.0/24',       // Home office example
    ];

    /**
     * Specific allowed IPs (for external admin access)
     */
    private array $allowedIps = [
        // Add specific external IPs if needed
        // '203.0.113.42',      // Example external admin IP
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $clientIp = $this->getClientIp($request);
        
        // Check if IP is in allowed ranges or specific IPs
        if ($this->isAllowedIp($clientIp)) {
            Log::info('Admin access allowed', [
                'ip' => $clientIp,
                'user_agent' => $request->userAgent(),
                'path' => $request->path(),
                'method' => $request->method()
            ]);
            
            return $next($request);
        }

        // Log blocked access attempt
        Log::warning('Admin access blocked - external IP', [
            'ip' => $clientIp,
            'user_agent' => $request->userAgent(),
            'path' => $request->path(),
            'method' => $request->method(),
            'referer' => $request->header('Referer')
        ]);

        // Return 404 to avoid revealing admin panel existence
        return response()->view('errors.404', [
            'message' => 'The requested resource could not be found.'
        ], 404);
    }

    /**
     * Get the real client IP address
     */
    private function getClientIp(Request $request): string
    {
        // Check common proxy headers in order of preference
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_REAL_IP',           // Nginx proxy
            'HTTP_X_FORWARDED_FOR',     // Standard proxy header
            'HTTP_X_FORWARDED',         // Microsoft proxy
            'HTTP_X_CLUSTER_CLIENT_IP', // Cluster environments
            'HTTP_FORWARDED_FOR',       // Alternative
            'HTTP_FORWARDED',           // RFC 7239
            'REMOTE_ADDR'               // Direct connection
        ];

        foreach ($headers as $header) {
            $value = $request->server($header);
            if (!empty($value)) {
                // Handle comma-separated list of IPs (take first one)
                $ip = trim(explode(',', $value)[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $request->ip();
    }

    /**
     * Check if IP is allowed to access admin panel
     */
    private function isAllowedIp(string $ip): bool
    {
        // Check specific allowed IPs first
        if (in_array($ip, $this->allowedIps)) {
            return true;
        }

        // Check if IP is in allowed ranges
        return IpUtils::checkIp($ip, $this->allowedRanges);
    }

    /**
     * Check if request is from development environment
     */
    private function isDevelopmentEnvironment(): bool
    {
        return app()->environment(['local', 'development', 'testing']);
    }

    /**
     * Emergency override for critical access (use with extreme caution)
     */
    private function hasEmergencyOverride(Request $request): bool
    {
        $emergencyToken = config('app.emergency_admin_token');
        
        if (empty($emergencyToken)) {
            return false;
        }

        $providedToken = $request->header('X-Emergency-Override') ?? 
                        $request->query('emergency_override');

        if ($providedToken === $emergencyToken) {
            Log::critical('Emergency admin override used', [
                'ip' => $this->getClientIp($request),
                'user_agent' => $request->userAgent(),
                'timestamp' => now()
            ]);
            
            return true;
        }

        return false;
    }
}