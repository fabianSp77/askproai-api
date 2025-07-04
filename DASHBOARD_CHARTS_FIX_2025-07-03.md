# Dashboard Charts Fix - GitHub Issue #265

## Date: 2025-07-03

## Issue
Die Charts in den Dashboard-Widgets zeigten statische/falsche Daten statt der echten historischen Anrufdaten.

## Root Cause
Die Stat-Widgets verwendeten hartcodierte Beispieldaten für die Charts:
```php
->chart([7, 4, 9, 5, 12, 8, $callsToday])
```

## Solution

### 1. BranchStatsWidget - Dynamische Chart-Daten
**Datei**: `/app/Filament/Admin/Resources/BranchResource/Widgets/BranchStatsWidget.php`

#### "Anrufe heute" Chart:
- Zeigt die letzten 7 Tage (6 Tage zurück + heute)
- Iteriert über jeden Tag und zählt Anrufe
- Berücksichtigt nur Telefonnummern der Filiale

#### "Anrufe diese Woche" Chart:
- Neue Methode `getWeeklyCallChart()` hinzugefügt
- Zeigt Montag bis Sonntag der aktuellen Woche
- Zeigt 0 für zukünftige Tage

### 2. CompanyStatsOverview - Chart für Anrufe
**Datei**: `/app/Filament/Admin/Resources/CompanyResource/Widgets/CompanyStatsOverview.php`

- "Anrufe heute" Statistik hat jetzt einen Chart
- Zeigt ebenfalls die letzten 7 Tage
- Basiert auf company_id statt Telefonnummern

## Code-Beispiel

```php
// Chart-Daten für die letzten 7 Tage
$chartData = [];
if (!empty($phoneNumbers)) {
    for ($i = 6; $i >= 0; $i--) {
        $date = now()->subDays($i);
        $dayCount = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->whereIn('to_number', $phoneNumbers)
            ->whereDate('created_at', $date)
            ->count();
        $chartData[] = $dayCount;
    }
} else {
    $chartData = [0, 0, 0, 0, 0, 0, 0];
}
```

## Getestete Ergebnisse

### Krückeberg Servicegruppe:
- Chart zeigt: [0, 0, 0, 0, 0, 0, 1]
- Korrekt: 1 Anruf heute, keine in den letzten 6 Tagen

### AskProAI:
- Chart zeigt: [0, 49, 61, 3, 0, 29, 2]
- Korrekt: Historische Daten der letzten 7 Tage

## Result
- Charts zeigen jetzt echte Daten statt Beispielwerte
- Daten werden dynamisch aus der Datenbank geladen
- Leere Arrays werden mit Nullen gefüllt wenn keine Daten vorhanden
- Performance optimiert durch einzelne Queries pro Tag