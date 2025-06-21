<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class WebhookIpWhitelist
{
    /**
     * IP ranges for webhook providers
     */
    protected array $whitelist = [
        'stripe' => [
            // Stripe webhook IPs (as of 2025)
            '3.18.12.63/32',
            '3.130.192.231/32',
            '13.235.14.237/32',
            '13.235.122.149/32',
            '18.211.135.69/32',
            '35.154.171.200/32',
            '52.15.183.38/32',
            '54.88.130.119/32',
            '54.88.130.237/32',
            '54.187.174.169/32',
            '54.187.205.235/32',
            '54.187.216.72/32',
        ],
        'retell' => [
            // Retell.ai webhook IPs - these should be obtained from Retell
            // Using example ranges for now
            '34.95.0.0/16',
            '35.245.0.0/16',
        ],
        'calcom' => [
            // Cal.com webhook IPs - these should be obtained from Cal.com
            // Using Vercel ranges as Cal.com uses Vercel
            '76.76.21.0/24',
            '76.223.0.0/16',
        ],
    ];

    /**
     * Handle an incoming request
     */
    public function handle(Request $request, Closure $next, string $provider = null): Response
    {
        // Skip in local development
        if (app()->environment('local')) {
            return $next($request);
        }

        $clientIp = $this->getClientIp($request);
        
        // Determine provider from route
        if (!$provider) {
            $provider = $this->detectProvider($request);
        }

        // Check if IP is whitelisted
        if (!$this->isIpWhitelisted($clientIp, $provider)) {
            Log::warning('Webhook request from non-whitelisted IP', [
                'ip' => $clientIp,
                'provider' => $provider,
                'url' => $request->fullUrl(),
                'headers' => $request->headers->all(),
            ]);

            // Log potential security incident
            $this->logSecurityIncident($request, $clientIp, $provider);

            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Request not allowed from this IP address',
            ], 403);
        }

        // Add provider context to request
        $request->attributes->set('webhook_provider', $provider);
        $request->attributes->set('webhook_ip', $clientIp);

        return $next($request);
    }

    /**
     * Get the real client IP address
     */
    protected function getClientIp(Request $request): string
    {
        // Check for forwarded IPs (load balancer, proxy)
        $forwardedFor = $request->header('X-Forwarded-For');
        if ($forwardedFor) {
            $ips = explode(',', $forwardedFor);
            return trim($ips[0]);
        }

        // Check for real IP header
        $realIp = $request->header('X-Real-IP');
        if ($realIp) {
            return $realIp;
        }

        // Fallback to request IP
        return $request->ip();
    }

    /**
     * Detect webhook provider from request
     */
    protected function detectProvider(Request $request): ?string
    {
        $path = $request->path();
        
        if (str_contains($path, 'stripe')) {
            return 'stripe';
        } elseif (str_contains($path, 'retell')) {
            return 'retell';
        } elseif (str_contains($path, 'calcom') || str_contains($path, 'cal.com')) {
            return 'calcom';
        }

        return null;
    }

    /**
     * Check if IP is whitelisted for provider
     */
    protected function isIpWhitelisted(string $ip, ?string $provider): bool
    {
        if (!$provider || !isset($this->whitelist[$provider])) {
            return false;
        }

        foreach ($this->whitelist[$provider] as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP is in CIDR range
     */
    protected function ipInRange(string $ip, string $range): bool
    {
        if (strpos($range, '/') === false) {
            $range .= '/32';
        }

        list($subnet, $bits) = explode('/', $range);
        
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && 
            filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            
            $ip = ip2long($ip);
            $subnet = ip2long($subnet);
            $mask = -1 << (32 - $bits);
            $subnet &= $mask;
            
            return ($ip & $mask) == $subnet;
        }
        
        // IPv6 support
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && 
            filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            
            $ip_bin = inet_pton($ip);
            $subnet_bin = inet_pton($subnet);
            
            $bytes_to_check = intval($bits / 8);
            $bits_to_check = $bits % 8;
            
            for ($i = 0; $i < $bytes_to_check; $i++) {
                if ($ip_bin[$i] !== $subnet_bin[$i]) {
                    return false;
                }
            }
            
            if ($bits_to_check > 0) {
                $mask = 0xFF << (8 - $bits_to_check);
                return (ord($ip_bin[$bytes_to_check]) & $mask) === (ord($subnet_bin[$bytes_to_check]) & $mask);
            }
            
            return true;
        }
        
        return false;
    }

    /**
     * Log security incident
     */
    protected function logSecurityIncident(Request $request, string $ip, ?string $provider): void
    {
        try {
            \DB::table('security_incidents')->insert([
                'type' => 'webhook_ip_blocked',
                'severity' => 'medium',
                'ip_address' => $ip,
                'provider' => $provider,
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'headers' => json_encode($request->headers->all()),
                'body' => substr($request->getContent(), 0, 1000), // First 1KB only
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log security incident', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update whitelist from external source (for dynamic IPs)
     */
    public function updateWhitelist(string $provider): void
    {
        switch ($provider) {
            case 'stripe':
                // Stripe publishes their IPs at: https://stripe.com/files/ips/ips_webhooks.txt
                try {
                    $ips = file_get_contents('https://stripe.com/files/ips/ips_webhooks.txt');
                    $this->whitelist['stripe'] = array_filter(array_map('trim', explode("\n", $ips)));
                    
                    // Cache the updated list
                    \Cache::put('webhook_whitelist_stripe', $this->whitelist['stripe'], now()->addDays(1));
                } catch (\Exception $e) {
                    Log::error('Failed to update Stripe webhook IPs', ['error' => $e->getMessage()]);
                }
                break;
                
            // Add other providers as needed
        }
    }
}