<?php

namespace App\Filament\Traits;

trait DisablesPollingOnMobile
{
    /**
     * Get the polling interval, disabled on mobile devices
     */
    protected function getPollingInterval(): ?string
    {
        // Check if user agent is mobile
        if ($this->isMobileDevice()) {
            return null; // Disable polling on mobile
        }

        // Return the default polling interval for desktop
        return static::$pollingInterval ?? null;
    }

    /**
     * Detect if the current request is from a mobile device
     */
    protected function isMobileDevice(): bool
    {
        $userAgent = request()->header('User-Agent', '');

        // Common mobile device patterns
        $mobilePatterns = [
            'Android',
            'iPhone',
            'iPad',
            'iPod',
            'Windows Phone',
            'BlackBerry',
            'Mobile',
            'Opera Mini',
            'IEMobile',
            'WPDesktop'
        ];

        foreach ($mobilePatterns as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Override the table method to conditionally set polling
     */
    protected function configureTablePolling($table)
    {
        $pollingInterval = $this->getPollingInterval();

        if ($pollingInterval) {
            return $table->poll($pollingInterval);
        }

        return $table;
    }
}