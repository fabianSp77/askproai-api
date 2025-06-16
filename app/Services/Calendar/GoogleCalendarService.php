<?php

namespace App\Services\Calendar;

class GoogleCalendarService extends BaseCalendarService
{
    public function getProviderName(): string
    {
        return 'Google Calendar';
    }
    
    public function getEventTypes(): array
    {
        // Google Calendar hat keine "Event Types" im klassischen Sinne
        // Wir simulieren diese durch Kalender + Dauer-Kombinationen
        return [
            [
                'external_id' => 'google_30min',
                'name' => '30 Minuten Termin',
                'duration_minutes' => 30,
                'provider_data' => ['calendar_id' => $this->config['calendar_id'] ?? 'primary']
            ],
            [
                'external_id' => 'google_60min',
                'name' => '60 Minuten Termin',
                'duration_minutes' => 60,
                'provider_data' => ['calendar_id' => $this->config['calendar_id'] ?? 'primary']
            ]
        ];
    }
    
    public function validateConnection(): bool
    {
        // TODO: Implement Google OAuth validation
        return false;
    }
    
    // Weitere Methoden folgen...
}
