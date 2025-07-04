# Retell Automatic Call Import Solution

## Problem
Anrufe werden nicht automatisch in die Datenbank importiert und erscheinen nicht auf der Calls-Seite: https://api.askproai.de/admin/calls

## Ursachen
1. **Keine automatische Import-Routine**: Es gab keinen Cron-Job zum regelmäßigen Import von Anrufen
2. **Webhook-Probleme**: Webhooks kommen an, aber scheitern bei der Verarbeitung
3. **Datenbank-Fehler**: Fehlende Spalten und falsche Datentypen verhindern die Speicherung

## Implementierte Lösung

### 1. Automatischer Import (NEU)
```php
// app/Console/Kernel.php - Zeile 147-152
$schedule->command('retell:fetch-calls --limit=50')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/retell-call-import.log'));
```

**Ergebnis**: Alle 15 Minuten werden automatisch die letzten 50 Anrufe von Retell importiert

### 2. Webhook-Verarbeitung repariert
- Pending Webhooks verarbeitet
- Fehlerhafte Webhooks korrigiert
- Queue-Verarbeitung sichergestellt

### 3. Datenbank-Probleme behoben
- Negative Dauern korrigiert
- Fehlende call_id Felder gefüllt
- Company-Zuordnung sichergestellt

## Sofortige Maßnahmen

### Manueller Import
```bash
# Importiere die letzten 50 Anrufe sofort
php artisan retell:fetch-calls --limit=50

# Überprüfe den Status
php monitor-retell-webhooks.php
```

### Webhook-Konfiguration prüfen
1. Gehe zu https://dashboard.retellai.com/
2. Navigate zu Settings > Webhooks
3. Stelle sicher, dass diese URL eingetragen ist:
   ```
   https://api.askproai.de/api/retell/webhook
   ```
4. Aktiviere diese Events:
   - call_started
   - call_ended
   - call_analyzed

## Monitoring

### Logs prüfen
```bash
# Import-Log
tail -f storage/logs/retell-call-import.log

# Laravel Log
tail -f storage/logs/laravel.log

# Queue Status
php artisan horizon
```

### Automatische Überwachung
- Calls werden alle 15 Minuten importiert
- Fehlgeschlagene Imports werden geloggt
- Webhook-Fehler werden in webhook_events Tabelle gespeichert

## Test

1. Mache einen Testanruf
2. Warte maximal 15 Minuten
3. Prüfe https://api.askproai.de/admin/calls
4. Der Anruf sollte automatisch erscheinen

## Backup-Lösung

Falls Webhooks nicht funktionieren, greift der automatische Import alle 15 Minuten und holt fehlende Anrufe nach.

## Scripts für Troubleshooting

- `fix-retell-call-import.php` - Importiert Anrufe und prüft Konfiguration
- `monitor-retell-webhooks.php` - Überwacht Webhook-Status
- `fix-retell-webhook-processing.php` - Repariert fehlerhafte Webhooks

## Status
✅ Automatischer Import aktiviert
✅ Webhook-Verarbeitung repariert  
✅ Datenbank-Fehler behoben
✅ Monitoring eingerichtet