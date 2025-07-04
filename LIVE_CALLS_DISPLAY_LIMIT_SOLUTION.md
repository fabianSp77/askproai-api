# Live Calls Display Limit - Elegante Lösung für viele Anrufe

## Problem
Bei vielen gleichzeitigen Anrufen würde das LiveCallsWidget zu groß und unübersichtlich werden.

## Implementierte Lösung

### 1. Standard-Limit von 5 Anrufen
```php
public $displayLimit = 5;
public $showAll = false;
```

### 2. "Show More/Less" Button
- Zeigt standardmäßig nur 5 Anrufe
- Button zeigt Anzahl der verbleibenden Anrufe: "X weitere Anrufe anzeigen"
- Klick erweitert/reduziert die Anzeige
- Smooth Scroll zurück nach oben beim Reduzieren

### 3. Implementierung im Widget

#### PHP (LiveCallsWidget.php):
```php
public function getDisplayedCalls(): array
{
    if ($this->showAll || count($this->activeCalls) <= $this->displayLimit) {
        return $this->activeCalls;
    }
    
    return array_slice($this->activeCalls, 0, $this->displayLimit);
}

public function getRemainingCallsCount(): int
{
    return max(0, count($this->activeCalls) - $this->displayLimit);
}

public function toggleShowAll(): void
{
    $this->showAll = !$this->showAll;
    
    if (!$this->showAll) {
        // Scroll back to top when collapsing
        $this->dispatch('scroll-to-top');
    }
}
```

#### Blade Template:
```blade
@if(count($activeCalls) > $displayLimit)
    <div class="mt-4 text-center">
        <x-filament::button
            wire:click="toggleShowAll"
            color="gray"
            size="sm"
        >
            @if($showAll)
                <x-heroicon-m-chevron-up class="h-4 w-4 mr-1" />
                Weniger anzeigen
            @else
                <x-heroicon-m-chevron-down class="h-4 w-4 mr-1" />
                {{ $this->getRemainingCallsCount() }} weitere Anrufe anzeigen
            @endif
        </x-filament::button>
    </div>
@endif
```

## Alternative Lösung: CompactLiveCallsWidget

Für Szenarien mit sehr vielen Anrufen (>10) wurde ein alternatives Table-Widget erstellt:

### Features:
- Kompakte Tabellenansicht
- Pagination (5, 10, 25 Einträge)
- Sortier- und Suchfunktionen
- Live-Updates alle 5 Sekunden
- Wird nur angezeigt bei >10 aktiven Anrufen

### Verwendung:
```php
// In ListCalls.php hinzufügen:
protected function getHeaderWidgets(): array
{
    return [
        \App\Filament\Admin\Widgets\LiveCallsWidget::class,
        \App\Filament\Admin\Widgets\CompactLiveCallsWidget::class, // Nur bei >10 Anrufen
        // ...
    ];
}
```

## Vorteile der Lösung

1. **Performance**: Nur 5 DOM-Elemente werden initial gerendert
2. **Übersichtlichkeit**: Wichtigste Anrufe sofort sichtbar
3. **Flexibilität**: User kann bei Bedarf alle sehen
4. **Responsive**: Funktioniert auf allen Bildschirmgrößen
5. **Fallback**: Kompakte Tabelle für extreme Fälle

## Konfiguration

Das Limit kann einfach angepasst werden:
```php
// In LiveCallsWidget.php
public $displayLimit = 5; // Ändern auf gewünschten Wert
```

## Weitere Optionen

1. **User-Preference**: Limit pro User speichern
2. **Gruppierung**: Nach Agent oder Status gruppieren
3. **Filter**: Nur bestimmte Anrufe anzeigen
4. **Priorisierung**: Wichtige Anrufe zuerst

Die implementierte Lösung ist elegant, performant und benutzerfreundlich!