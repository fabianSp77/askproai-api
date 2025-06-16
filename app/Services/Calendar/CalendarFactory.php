<?php

namespace App\Services\Calendar;

use InvalidArgumentException;

class CalendarFactory
{
    private static array $providers = [
        'calcom' => CalcomCalendarService::class,
        'google' => GoogleCalendarService::class,
        // Weitere Provider hier hinzufügen
    ];
    
    public static function create(string $provider, array $config = []): CalendarInterface
    {
        if (!isset(self::$providers[$provider])) {
            throw new InvalidArgumentException("Calendar provider '{$provider}' is not supported.");
        }
        
        $providerClass = self::$providers[$provider];
        return new $providerClass($config);
    }
    
    public static function getSupportedProviders(): array
    {
        return array_keys(self::$providers);
    }
    
    public static function getProviderDisplayName(string $provider): string
    {
        return match($provider) {
            'calcom' => 'Cal.com',
            'google' => 'Google Calendar',
            'outlook' => 'Microsoft Outlook',
            'calendly' => 'Calendly',
            default => ucfirst($provider)
        };
    }
}
