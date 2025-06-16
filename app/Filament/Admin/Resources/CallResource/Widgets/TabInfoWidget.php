<?php

namespace App\Filament\Admin\Resources\CallResource\Widgets;

use Filament\Widgets\Widget;
use Livewire\Attributes\On;

class TabInfoWidget extends Widget
{
    protected static string $view = 'filament.admin.resources.call-resource.widgets.tab-info';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 1; // Nach den Tabs anzeigen
    
    public string $activeTab = 'today';
    
    protected array $tabInfos = [
        'all' => [
            'title' => 'Alle Anrufe',
            'description' => 'Zeigt alle eingegangenen Anrufe unabhängig vom Status oder Datum an. Nutzen Sie diese Ansicht für eine vollständige Übersicht aller Kundeninteraktionen.',
        ],
        'today' => [
            'title' => 'Heutige Anrufe',
            'description' => 'Zeigt nur Anrufe, die heute eingegangen sind. Ideal für die tägliche Nachbearbeitung und um keinen aktuellen Anruf zu verpassen.',
        ],
        'high_conversion' => [
            'title' => 'Verkaufschancen',
            'description' => 'Anrufe mit positiver Stimmung, über 2 Minuten Dauer und ohne gebuchten Termin. Diese Anrufer zeigen hohes Interesse und sollten prioritär kontaktiert werden!',
        ],
        'needs_followup' => [
            'title' => 'Dringende Anrufe',
            'description' => 'Anrufe mit negativer Stimmung, hoher Dringlichkeit oder explizitem Terminwunsch. Diese Anrufer benötigen sofortige Aufmerksamkeit!',
        ],
        'with_appointment' => [
            'title' => 'Erledigte Anrufe',
            'description' => 'Erfolgreich abgeschlossene Anrufe mit bereits gebuchtem Termin. Keine weitere Aktion erforderlich.',
        ],
        'without_customer' => [
            'title' => 'Unbekannte Anrufer',
            'description' => 'Anrufe von unbekannten Nummern ohne Kundenzuordnung. Prüfen Sie, ob es sich um neue Interessenten handelt.',
        ]
    ];
    
    public function mount(): void
    {
        $this->activeTab = request()->query('activeTab', 'today');
    }
    
    public function getCurrentInfo(): array
    {
        return $this->tabInfos[$this->activeTab] ?? $this->tabInfos['today'];
    }
    
    #[On('activeTabChanged')]
    public function updateActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }
    
    public static function canView(): bool
    {
        return true;
    }
}