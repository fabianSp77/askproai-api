<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\Horizon;
use App\Http\Middleware\RestrictToInternalNetwork;
use Symfony\Component\HttpFoundation\IpUtils;

class HorizonServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // SECURITY: Horizon access restricted to internal networks only
        Horizon::auth(function ($request) {
            // First check: Internal network restriction
            if (!$this->isInternalNetwork($request)) {
                return false;
            }
            
            // Second check: Authenticated admin user
            $user = $request->user();
            if (!$user) {
                return false;
            }
            
            // Third check: Authorized admin emails
            return in_array($user->email, [
                'fabian@askproai.de',    // Primary admin
                // Add additional admin emails here
            ]);
        });
        
        // Additional security: Hide Horizon from production unless explicitly enabled
        if (app()->environment('production') && !config('horizon.dashboard_enabled', false)) {
            Horizon::auth(fn() => false);
        }
    }
    
    /**
     * Check if request comes from internal network
     */
    private function isInternalNetwork($request): bool
    {
        $allowedRanges = [
            '10.0.0.0/8',
            '172.16.0.0/12', 
            '192.168.0.0/16',
            '127.0.0.0/8',
            '::1/128',
        ];
        
        $clientIp = $request->ip();
        return IpUtils::checkIp($clientIp, $allowedRanges);
    }
}
