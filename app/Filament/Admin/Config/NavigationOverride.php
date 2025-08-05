<?php

namespace App\Filament\Admin\Config;

class NavigationOverride
{
    /**
     * Temporarily override navigation visibility checks
     */
    public static function shouldShowAllNavigation(): bool
    {
        // Check environment variable
        return env('SHOW_ALL_NAVIGATION', true);
    }
}
