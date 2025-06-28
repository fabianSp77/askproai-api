# Retell.ai Webhook Setup Guide

## =� **WICHTIG: Webhook URL f�r automatische Updates**

Damit Anrufe automatisch ohne manuellen Import angezeigt werden, m�ssen Sie in Retell.ai die richtige Webhook URL konfigurieren.

### 1. **Neue Real-time Webhook URL** (Empfohlen)
```
https://api.askproai.de/api/retell/realtime/webhook
```

Diese URL bietet:
-  Sofortige Verarbeitung ohne Queue-Verz�gerung
-  Live-Updates w�hrend laufender Anrufe
-  Automatische Company-Zuordnung
-  Bessere Fehlerbehandlung

### 2. **Legacy Webhook URLs** (Falls Real-time nicht funktioniert)
```
https://api.askproai.de/api/retell/webhook
https://api.askproai.de/api/webhook
```

### =� **Schritt-f�r-Schritt Anleitung**

1. **Login bei Retell.ai**
   - Gehen Sie zu https://retell.ai/dashboard
   - Navigieren Sie zu "Settings" � "Webhooks"

2. **Webhook URL eintragen**
   ```
   Webhook URL: https://api.askproai.de/api/retell/realtime/webhook
   ```

3. **Events aktivieren**
   Aktivieren Sie ALLE folgenden Events:
   -  `call_started` - F�r Live-Anzeige
   -  `call_ended` - F�r Appointment-Erstellung
   -  `call_analyzed` - F�r Nachbearbeitung
   -  `call_failed` - F�r Fehlerbehandlung

4. **Webhook Secret kopieren**
   - Kopieren Sie das Webhook Secret
   - F�gen Sie es in `.env` ein:
   ```
   RETELL_WEBHOOK_SECRET=ihr_webhook_secret_hier
   ```

5. **Testen**
   - Klicken Sie auf "Test Webhook" in Retell
   - Pr�fen Sie die Logs: `tail -f storage/logs/laravel.log`

### =' **Troubleshooting**

#### Problem: Anrufe werden nicht angezeigt
1. **Pr�fen Sie Horizon Status**
   ```bash
   php artisan horizon:status
   ```
   Falls nicht l�uft:
   ```bash
   php artisan horizon
   ```

2. **Pr�fen Sie fehlgeschlagene Jobs**
   ```bash
   php artisan queue:failed
   ```

3. **Webhook Logs pr�fen**
   ```bash
   tail -f storage/logs/laravel.log | grep -i retell
   ```

#### Problem: Webhook Signature Error
- Aktuell ist die Signatur-Verifizierung deaktiviert
- Falls Sie sie aktivieren m�chten, stellen Sie sicher, dass das Secret korrekt ist

### =� **Live Monitoring**

1. **Live Calls Widget**
   - Zeigt aktive Anrufe in Echtzeit
   - 2-Sekunden Auto-Refresh
   - Sync-Button f�r manuellen Import

2. **Server-Sent Events Stream**
   ```
   GET https://api.askproai.de/api/retell/realtime/stream
   ```
   - F�r eigene Integrationen
   - Liefert Echtzeit-Updates

3. **Active Calls API**
   ```
   GET https://api.askproai.de/api/retell/realtime/active-calls
   ```
   - JSON Response mit allen aktiven Anrufen

### =� **Best Practices**

1. **Verwenden Sie immer die Real-time URL** f�r beste Performance
2. **Aktivieren Sie alle Events** f�r vollst�ndige Datenerfassung
3. **Monitoren Sie regelm��ig** Failed Jobs in Horizon
4. **Testen Sie nach �nderungen** mit einem Testanruf

### =� **Test-Anruf durchf�hren**

1. Rufen Sie Ihre Retell-Nummer an
2. Warten Sie 2-5 Sekunden
3. Der Anruf sollte im Live Calls Widget erscheinen
4. Nach Beendigung sollte er in der Anrufliste auftauchen

## Support

Bei Problemen pr�fen Sie:
- `/admin/horizon` - Queue Status
- `/admin/calls` - Anrufliste
- `storage/logs/laravel.log` - System Logs