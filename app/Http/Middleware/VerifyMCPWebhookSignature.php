<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyMCPWebhookSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip validation in test mode
        if (config('retell-mcp.testing.test_mode')) {
            return $next($request);
        }
        
        // Skip if webhook validation is disabled
        if (!config('retell-mcp.features.webhook_validation', true)) {
            Log::warning('MCP webhook validation is disabled', [
                'url' => $request->url(),
                'ip' => $request->ip(),
            ]);
            return $next($request);
        }
        
        // Verify IP whitelist if configured
        if ($this->shouldVerifyIp() && !$this->isIpAllowed($request)) {
            Log::error('MCP webhook from unauthorized IP', [
                'ip' => $request->ip(),
                'allowed_ips' => config('retell-mcp.security.allowed_ips'),
            ]);
            
            return response()->json([
                'error' => 'Unauthorized IP address',
            ], 403);
        }
        
        // Verify signature
        if (!$this->verifySignature($request)) {
            Log::error('Invalid MCP webhook signature', [
                'url' => $request->url(),
                'headers' => $request->headers->all(),
            ]);
            
            return response()->json([
                'error' => 'Invalid signature',
            ], 401);
        }
        
        // Verify timestamp to prevent replay attacks
        if (!$this->verifyTimestamp($request)) {
            Log::error('MCP webhook timestamp validation failed', [
                'url' => $request->url(),
            ]);
            
            return response()->json([
                'error' => 'Request expired',
            ], 401);
        }
        
        // Add webhook metadata to request
        $request->merge([
            'webhook_metadata' => [
                'verified_at' => now()->toISOString(),
                'source_ip' => $request->ip(),
                'signature_verified' => true,
            ],
        ]);
        
        return $next($request);
    }
    
    /**
     * Verify the webhook signature
     *
     * @param Request $request
     * @return bool
     */
    protected function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-MCP-Signature');
        $timestamp = $request->header('X-MCP-Timestamp');
        $body = $request->getContent();
        
        if (!$signature || !$timestamp) {
            return false;
        }
        
        $secret = config('retell-mcp.security.webhook_secret');
        if (!$secret) {
            Log::error('MCP webhook secret not configured');
            return false;
        }
        
        // Calculate expected signature
        $payload = $timestamp . '.' . $body;
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        
        // Use timing-safe comparison
        return hash_equals($expectedSignature, $signature);
    }
    
    /**
     * Verify the request timestamp
     *
     * @param Request $request
     * @return bool
     */
    protected function verifyTimestamp(Request $request): bool
    {
        $timestamp = $request->header('X-MCP-Timestamp');
        
        if (!$timestamp || !is_numeric($timestamp)) {
            return false;
        }
        
        $currentTime = time();
        $requestTime = (int) $timestamp;
        
        // Allow 5 minute window
        $allowedWindow = 300;
        
        return abs($currentTime - $requestTime) <= $allowedWindow;
    }
    
    /**
     * Check if IP verification should be performed
     *
     * @return bool
     */
    protected function shouldVerifyIp(): bool
    {
        $allowedIps = config('retell-mcp.security.allowed_ips', []);
        return !empty($allowedIps);
    }
    
    /**
     * Check if the request IP is allowed
     *
     * @param Request $request
     * @return bool
     */
    protected function isIpAllowed(Request $request): bool
    {
        $clientIp = $request->ip();
        $allowedIps = config('retell-mcp.security.allowed_ips', []);
        
        // Check for exact match
        if (in_array($clientIp, $allowedIps)) {
            return true;
        }
        
        // Check for CIDR notation
        foreach ($allowedIps as $allowedIp) {
            if (str_contains($allowedIp, '/')) {
                if ($this->ipInCidr($clientIp, $allowedIp)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Check if IP is in CIDR range
     *
     * @param string $ip
     * @param string $cidr
     * @return bool
     */
    protected function ipInCidr(string $ip, string $cidr): bool
    {
        list($subnet, $bits) = explode('/', $cidr);
        if ($bits === null) {
            $bits = 32;
        }
        
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet &= $mask;
        
        return ($ip & $mask) == $subnet;
    }
}