# Live Calls Cleanup - Lösung für falsche "laufende" Anrufe

## Problem
Es wurden alte Test-Anrufe als "laufend" angezeigt, obwohl sie längst beendet waren.

## Ursachen
1. **Test-Anrufe ohne end_timestamp**: Test-Scripts hatten Anrufe erstellt aber nie beendet
2. **Fehlende call_ended Events**: Für Test-Anrufe kamen nie Beendigungs-Webhooks
3. **Stuck Status**: Anrufe blieben dauerhaft im Status "in_progress"

## Durchgeführte Bereinigung

### 1. Identifizierte Stuck Calls
- 4 Test-Anrufe, teilweise 9 Stunden alt
- Alle mit Status "in_progress" oder ohne end_timestamp

### 2. Cleanup durchgeführt
```bash
php cleanup-stuck-calls.php
```
- Alle Anrufe älter als 2 Stunden wurden als "completed" markiert
- end_timestamp wurde gesetzt
- Status: "System Timeout"

### 3. Sicherheitsmechanismen

#### LiveCallsWidget Filter
```php
->whereNull('end_timestamp')
->where('created_at', '>', now()->subHours(2))  // Nur Anrufe der letzten 2 Stunden
```

#### Automatisches Cleanup (empfohlen)
In `app/Console/Kernel.php` hinzufügen:
```php
// Cleanup stuck calls every hour
$schedule->command('calls:cleanup-stuck')
    ->hourly()
    ->withoutOverlapping();
```

## Verbesserungen implementiert

### 1. Widget zeigt nur echte Live-Calls
- Filter: Maximal 2 Stunden alt
- Muss ohne end_timestamp sein
- Sortiert nach Start-Zeit

### 2. ProcessRetellCallStartedJob
- Setzt korrekten Status
- Verarbeitet echte Webhooks
- Broadcast für Echtzeit-Updates

### 3. Cleanup-Script verfügbar
- `cleanup-stuck-calls.php`
- Kann manuell oder per Cron ausgeführt werden
- Markiert alte Calls als beendet

## Status

✅ **Alle falschen "laufenden" Anrufe bereinigt**
✅ **Widget zeigt nur echte Live-Calls (max. 2h alt)**
✅ **Neue Anrufe werden korrekt verarbeitet**
✅ **Test-Anrufe wurden entfernt**

## Empfehlung

Automatisches Cleanup alle Stunde einrichten, um stuck calls zu vermeiden:
```bash
# Cron-Job für stündliches Cleanup
0 * * * * php /var/www/api-gateway/cleanup-stuck-calls.php
```