# WICHTIG: Retell.ai Webhook URL Konfiguration

## 🚨 KRITISCH: Falsche Webhook URL konfiguriert!

### Problem:
- Retell.ai sendet Webhooks an: `/api/retell/webhook-simple`
- Diese URL ist ein **temporärer Debug-Endpunkt** ohne Signatur-Verifizierung
- Echte Retell-Webhooks bekommen 500 Fehler

### Lösung:

1. **Logge dich in Retell.ai Dashboard ein**
2. **Gehe zu Settings → Webhooks**
3. **Ändere die Webhook URL zu:**
   ```
   https://api.askproai.de/api/retell/webhook
   ```

### Warum funktioniert webhook-simple nicht?
- Es ist nur für lokale Tests gedacht
- Hat keine Signatur-Verifizierung
- Kann mit echten Retell-Daten nicht umgehen

### Alternativen (falls die Hauptroute Probleme macht):

1. **Option 1: Webhook ohne Signatur** (NICHT EMPFOHLEN)
   ```
   https://api.askproai.de/api/retell/webhook-bypass
   ```

2. **Option 2: MCP Webhook** (Moderne Variante)
   ```
   https://api.askproai.de/api/webhooks/retell
   ```

### Test nach Änderung:
1. Mache einen Testanruf
2. Prüfe ob der Anruf in `/admin/calls` erscheint
3. Live-Widget sollte den Anruf anzeigen

### Weitere Debugging-Befehle:
```bash
# Prüfe eingehende Webhooks
tail -f /var/log/nginx/access.log | grep retell

# Prüfe Laravel Logs
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep -i retell
```