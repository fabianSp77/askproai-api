# Live Call Display Solution - Laufende Anrufe in Echtzeit anzeigen

## Problem
Laufende Anrufe wurden nicht sofort in der Anrufliste angezeigt, sondern erst nachdem sie beendet waren.

## Gelöste Probleme

### 1. Fehlender Job für call_started Events
**Problem**: `ProcessRetellCallStartedJob` war referenziert aber existierte nicht
**Lösung**: Job erstellt, der sofort einen Call-Record anlegt wenn ein Anruf startet

### 2. LiveCallsWidget nicht sichtbar
**Problem**: Widget war nicht in der Header-Widget-Liste
**Lösung**: Widget zur CallResource hinzugefügt

## Implementierte Lösung

### ProcessRetellCallStartedJob
```php
// app/Jobs/ProcessRetellCallStartedJob.php
- Erstellt Call-Record mit status 'in_progress'
- Setzt end_timestamp auf null für laufende Anrufe
- Broadcasted CallCreated Event für Echtzeit-Updates
```

### Widget-Integration
```php
// app/Filament/Admin/Resources/CallResource/Pages/ListCalls.php
protected function getHeaderWidgets(): array
{
    return [
        \App\Filament\Admin\Widgets\LiveCallsWidget::class,  // NEU
        \App\Filament\Admin\Widgets\CallLiveStatusWidget::class,
        // ...
    ];
}
```

## Features der Live-Anzeige

### LiveCallsWidget
- Zeigt alle laufenden Anrufe in Echtzeit
- Live-Timer zeigt aktuelle Gesprächsdauer
- Aktualisiert sich alle 5 Sekunden
- Pusher/WebSocket Integration für sofortige Updates
- Zeigt Anrufer-Nummer und Agent-Info

### CallLiveStatusWidget
- Kompakte Übersicht mit Anzahl aktiver Anrufe
- Zeigt letzte Anrufe der letzten Stunde
- Status-Indikatoren (grün = aktiv)

## Wie es funktioniert

1. **Anruf startet** → Retell sendet `call_started` Webhook
2. **Webhook verarbeitet** → `ProcessRetellCallStartedJob` erstellt Call-Record
3. **Event broadcast** → `CallCreated` Event wird an Frontend gesendet
4. **Live-Update** → Widget zeigt neuen Anruf sofort an
5. **Automatische Aktualisierung** → Timer wird alle 5 Sekunden aktualisiert
6. **Anruf endet** → `call_ended` Webhook aktualisiert den Record

## Test

1. Testanruf simulieren:
```bash
php test-live-call-display.php
```

2. Oder echten Anruf machen und sofort prüfen:
- https://api.askproai.de/admin/calls
- LiveCallsWidget zeigt laufende Anrufe
- Timer zählt die Sekunden

## Status-Übersicht

✅ **ProcessRetellCallStartedJob** erstellt
✅ **LiveCallsWidget** zur Calls-Seite hinzugefügt
✅ **Echtzeit-Updates** via Pusher/WebSocket
✅ **Automatische Aktualisierung** alle 5 Sekunden
✅ **In-Progress Status** wird korrekt angezeigt

## Monitoring

- Laufende Anrufe: Calls mit `end_timestamp = NULL`
- Live-Status: `call_status = 'in_progress'`
- Widget zeigt automatisch alle aktiven Anrufe
- Pusher-Events für sofortige Updates