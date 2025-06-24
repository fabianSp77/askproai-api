# Retell.ai Zeitinfo Setup

## Übersicht
Die `current_time_berlin` Custom Function für Retell.ai Agents wurde auf den eigenen Server umgestellt.

## Änderungen

### 1. API Route hinzugefügt
- **Datei**: `/var/www/api-gateway/routes/api.php`
- **Endpoint**: `/api/zeitinfo`
- **Controller**: `ZeitinfoController@jetzt`

### 2. Neue URL für Retell.ai
**ALT**: `http://152.53.228.178/api/zeitinfo?locale=de`  
**NEU**: `https://api.askproai.de/api/zeitinfo?locale=de`

## JSON Response Format
```json
{
  "date": "22.06.2025",    // Format: DD.MM.YYYY
  "time": "14:30",         // Format: HH:MM
  "weekday": "Sonntag"     // Deutscher Wochentag
}
```

## Retell.ai Konfiguration

### Custom Function Schema (bleibt unverändert)
```json
{
  "type": "object",
  "properties": {
    "date": {
      "type": "string",
      "description": "Aktuelles Datum im Format TT.MM.JJJJ"
    },
    "weekday": {
      "type": "string",
      "description": "Aktueller Wochentag ausgeschrieben"
    },
    "time": {
      "type": "string",
      "description": "Aktuelle Uhrzeit im Format HH:MM"
    }
  },
  "required": ["date", "time", "weekday"]
}
```

### Was in Retell.ai geändert werden muss:
1. **Agent bearbeiten**
2. **Custom Function `current_time_berlin`**
3. **URL ändern** zu: `https://api.askproai.de/api/zeitinfo?locale=de`
4. **Webhook Method**: GET
5. **Speichern**

## Vorteile
- ✅ Keine externe Abhängigkeit mehr
- ✅ Bessere Performance (eigener Server)
- ✅ Zuverlässigkeit erhöht
- ✅ Logging integriert
- ✅ Korrekte Zeitzone (Europe/Berlin)

## Test
```bash
curl https://api.askproai.de/api/zeitinfo?locale=de
```

## Troubleshooting
Falls der Agent die Zeit nicht erhält:
1. Prüfen Sie die URL in Retell.ai
2. Stellen Sie sicher, dass HTTPS verwendet wird
3. Überprüfen Sie die Logs: `tail -f /var/www/api-gateway/storage/logs/laravel.log`