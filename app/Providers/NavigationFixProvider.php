<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class NavigationFixProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Override navigation checks for admin users
        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            // List of navigation-related permissions
            $navigationPermissions = [
                'manage_company',
                'manage_settings',
                'manage_billing',
                'view_reports',
                'view_system_health',
                'manage_compliance',
            ];
            
            // If checking navigation permissions and user has admin role, allow
            if (in_array($ability, $navigationPermissions) && $user->hasRole('admin')) {
                return true;
            }
        });
    }
}
