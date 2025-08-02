<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Filament Locale Configuration
    |--------------------------------------------------------------------------
    |
    | Diese Konfiguration definiert die Lokalisierungseinstellungen für
    | das Filament Admin Panel, insbesondere für Datumsformate.
    |
    */

    'locale' => 'de',
    
    'timezone' => 'Europe/Berlin',
    
    'date_formats' => [
        'date' => 'd.m.Y',
        'datetime' => 'd.m.Y H:i',
        'time' => 'H:i',
        'datetime_with_seconds' => 'd.m.Y H:i:s',
    ],
    
    'date_display_formats' => [
        'date' => 'DD.MM.YYYY',
        'datetime' => 'DD.MM.YYYY HH:mm',
        'time' => 'HH:mm',
        'datetime_with_seconds' => 'DD.MM.YYYY HH:mm:ss',
    ],
    
    // Relative Zeitangaben
    'relative_dates' => [
        'today' => 'Heute',
        'yesterday' => 'Gestern',
        'tomorrow' => 'Morgen',
        'past' => 'Vergangen',
        'future' => 'Zukünftig',
        'now' => 'Jetzt',
        'just_now' => 'Gerade eben',
        'minutes_ago' => 'vor :count Minuten|vor :count Minute',
        'hours_ago' => 'vor :count Stunden|vor :count Stunde',
        'days_ago' => 'vor :count Tagen|vor :count Tag',
        'weeks_ago' => 'vor :count Wochen|vor :count Woche',
        'months_ago' => 'vor :count Monaten|vor :count Monat',
        'years_ago' => 'vor :count Jahren|vor :count Jahr',
        'in_minutes' => 'in :count Minuten|in :count Minute',
        'in_hours' => 'in :count Stunden|in :count Stunde',
        'in_days' => 'in :count Tagen|in :count Tag',
        'in_weeks' => 'in :count Wochen|in :count Woche',
        'in_months' => 'in :count Monaten|in :count Monat',
        'in_years' => 'in :count Jahren|in :count Jahr',
    ],
];