<?php

namespace App\Filament\Admin\Resources\CallResource\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

class CallTabInfoWidget extends Widget
{
    protected static string $view = 'filament.admin.resources.call-resource.widgets.call-tab-info';
    
    protected int | string | array $columnSpan = 'full';
    
    public static function canView(): bool
    {
        return true;
    }
    
    public function getCachedTabDescriptions(): array
    {
        return Cache::remember('call-tab-descriptions', 3600, function () {
            return $this->getTabDescriptions();
        });
    }
    
    public function getTabDescriptions(): array
    {
        return [
            'all' => [
                'title' => 'Alle Anrufe',
                'description' => 'Zeigt alle eingegangenen Anrufe unabhängig vom Status oder Datum an.',
                'hint' => 'Nutzen Sie diese Ansicht für eine vollständige Übersicht aller Kundeninteraktionen.',
                'color' => 'gray'
            ],
            'today' => [
                'title' => 'Heutige Anrufe',
                'description' => 'Zeigt nur Anrufe, die heute eingegangen sind.',
                'hint' => 'Ideal für die tägliche Nachbearbeitung und um keinen aktuellen Anruf zu verpassen.',
                'color' => 'primary'
            ],
            'high_conversion' => [
                'title' => 'Verkaufschancen',
                'description' => 'Anrufe mit positiver Stimmung, über 2 Minuten Dauer und ohne gebuchten Termin.',
                'hint' => 'Diese Anrufer zeigen hohes Interesse und sollten prioritär kontaktiert werden!',
                'color' => 'success'
            ],
            'needs_followup' => [
                'title' => 'Dringende Anrufe',
                'description' => 'Anrufe mit negativer Stimmung, hoher Dringlichkeit oder explizitem Terminwunsch.',
                'hint' => 'Diese Anrufer benötigen sofortige Aufmerksamkeit, um Unzufriedenheit zu vermeiden!',
                'color' => 'danger'
            ],
            'with_appointment' => [
                'title' => 'Erledigte Anrufe',
                'description' => 'Erfolgreich abgeschlossene Anrufe mit bereits gebuchtem Termin.',
                'hint' => 'Keine weitere Aktion erforderlich - diese Anrufer sind bereits versorgt.',
                'color' => 'success'
            ],
            'without_customer' => [
                'title' => 'Unbekannte Anrufer',
                'description' => 'Anrufe von unbekannten Nummern ohne Kundenzuordnung.',
                'hint' => 'Prüfen Sie, ob es sich um neue Interessenten oder Spam-Anrufe handelt.',
                'color' => 'warning'
            ],
        ];
    }
}