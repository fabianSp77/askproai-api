<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class IpWhitelist
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get whitelisted IPs from config or environment
        $whitelist = config('webhook.ip_whitelist', []);
        
        // Add Retell.ai known IPs
        $retellIps = [
            // Retell.ai production IPs (these are examples - get actual IPs from Retell)
            '35.160.120.126',
            '44.233.151.27',
            '34.211.200.85',
            // Add more Retell IPs as needed
        ];
        
        // Merge all whitelisted IPs
        $allowedIps = array_merge($whitelist, $retellIps);
        
        // Always allow localhost in development
        if (app()->environment('local')) {
            $allowedIps[] = '127.0.0.1';
            $allowedIps[] = '::1';
        }
        
        // Get the client's IP address
        $clientIp = $request->ip();
        
        // Check if request is coming through a proxy
        if ($request->server('HTTP_X_FORWARDED_FOR')) {
            $ips = explode(',', $request->server('HTTP_X_FORWARDED_FOR'));
            $clientIp = trim($ips[0]);
        }
        
        // Log the incoming request
        Log::info('IP Whitelist Check', [
            'client_ip' => $clientIp,
            'forwarded_for' => $request->server('HTTP_X_FORWARDED_FOR'),
            'real_ip' => $request->server('HTTP_X_REAL_IP'),
            'allowed_ips' => $allowedIps,
            'path' => $request->path()
        ]);
        
        // Check if IP is whitelisted
        if (!in_array($clientIp, $allowedIps)) {
            Log::warning('Blocked request from non-whitelisted IP', [
                'ip' => $clientIp,
                'path' => $request->path(),
                'user_agent' => $request->userAgent()
            ]);
            
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Your IP address is not whitelisted'
            ], 403);
        }
        
        return $next($request);
    }
}