# Retell.ai Webhook Setup Guide

## =¨ **WICHTIG: Webhook URL für automatische Updates**

Damit Anrufe automatisch ohne manuellen Import angezeigt werden, müssen Sie in Retell.ai die richtige Webhook URL konfigurieren.

### 1. **Neue Real-time Webhook URL** (Empfohlen)
```
https://api.askproai.de/api/retell/realtime/webhook
```

Diese URL bietet:
-  Sofortige Verarbeitung ohne Queue-Verzögerung
-  Live-Updates während laufender Anrufe
-  Automatische Company-Zuordnung
-  Bessere Fehlerbehandlung

### 2. **Legacy Webhook URLs** (Falls Real-time nicht funktioniert)
```
https://api.askproai.de/api/retell/webhook
https://api.askproai.de/api/webhook
```

### =Ë **Schritt-für-Schritt Anleitung**

1. **Login bei Retell.ai**
   - Gehen Sie zu https://retell.ai/dashboard
   - Navigieren Sie zu "Settings" ’ "Webhooks"

2. **Webhook URL eintragen**
   ```
   Webhook URL: https://api.askproai.de/api/retell/realtime/webhook
   ```

3. **Events aktivieren**
   Aktivieren Sie ALLE folgenden Events:
   -  `call_started` - Für Live-Anzeige
   -  `call_ended` - Für Appointment-Erstellung
   -  `call_analyzed` - Für Nachbearbeitung
   -  `call_failed` - Für Fehlerbehandlung

4. **Webhook Secret kopieren**
   - Kopieren Sie das Webhook Secret
   - Fügen Sie es in `.env` ein:
   ```
   RETELL_WEBHOOK_SECRET=ihr_webhook_secret_hier
   ```

5. **Testen**
   - Klicken Sie auf "Test Webhook" in Retell
   - Prüfen Sie die Logs: `tail -f storage/logs/laravel.log`

### =' **Troubleshooting**

#### Problem: Anrufe werden nicht angezeigt
1. **Prüfen Sie Horizon Status**
   ```bash
   php artisan horizon:status
   ```
   Falls nicht läuft:
   ```bash
   php artisan horizon
   ```

2. **Prüfen Sie fehlgeschlagene Jobs**
   ```bash
   php artisan queue:failed
   ```

3. **Webhook Logs prüfen**
   ```bash
   tail -f storage/logs/laravel.log | grep -i retell
   ```

#### Problem: Webhook Signature Error
- Aktuell ist die Signatur-Verifizierung deaktiviert
- Falls Sie sie aktivieren möchten, stellen Sie sicher, dass das Secret korrekt ist

### =Ê **Live Monitoring**

1. **Live Calls Widget**
   - Zeigt aktive Anrufe in Echtzeit
   - 2-Sekunden Auto-Refresh
   - Sync-Button für manuellen Import

2. **Server-Sent Events Stream**
   ```
   GET https://api.askproai.de/api/retell/realtime/stream
   ```
   - Für eigene Integrationen
   - Liefert Echtzeit-Updates

3. **Active Calls API**
   ```
   GET https://api.askproai.de/api/retell/realtime/active-calls
   ```
   - JSON Response mit allen aktiven Anrufen

### =€ **Best Practices**

1. **Verwenden Sie immer die Real-time URL** für beste Performance
2. **Aktivieren Sie alle Events** für vollständige Datenerfassung
3. **Monitoren Sie regelmäßig** Failed Jobs in Horizon
4. **Testen Sie nach Änderungen** mit einem Testanruf

### =Þ **Test-Anruf durchführen**

1. Rufen Sie Ihre Retell-Nummer an
2. Warten Sie 2-5 Sekunden
3. Der Anruf sollte im Live Calls Widget erscheinen
4. Nach Beendigung sollte er in der Anrufliste auftauchen

## Support

Bei Problemen prüfen Sie:
- `/admin/horizon` - Queue Status
- `/admin/calls` - Anrufliste
- `storage/logs/laravel.log` - System Logs