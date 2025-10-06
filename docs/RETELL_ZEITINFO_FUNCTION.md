# Retell AI - Current Time Berlin Function

## Übersicht

Der `zeitinfo` API-Endpunkt liefert das aktuelle Datum, die Uhrzeit und den Wochentag in deutscher Zeit (Europe/Berlin Zeitzone). Dieser Endpunkt ist speziell für den Retell AI Agent entwickelt, damit dieser das aktuelle Datum und die Zeit kennt.

## API-Endpunkt

**URL:** `https://api.askproai.de/api/zeitinfo`
**Methode:** GET
**Query Parameter:** `locale=de` (optional, hat keinen Effekt, da immer deutsche Zeit geliefert wird)

## Response Format

```json
{
  "timestamp": 1758909412,
  "datetime": "2025-09-26 19:56:52",
  "timezone": "Europe/Berlin",
  "weekday": "Friday",
  "weekday_de": "Freitag",
  "month": "September",
  "month_de": "September",
  "date_de": "26.09.2025",
  "time_de": "19:56",
  "formatted": "Freitag, 26. September 2025, 19:56 Uhr",
  "formatted_short": "Freitag, 26.09.2025, 19:56 Uhr"
}
```

## Konfiguration in Retell Dashboard

### Custom Function einrichten

1. **Öffnen Sie das Retell Dashboard**
2. **Navigieren Sie zu Ihrem Agent**
3. **Gehen Sie zu "Custom Functions"**
4. **Erstellen Sie eine neue Function mit folgenden Einstellungen:**

```
Name: current_time_berlin
Description: Liefert aktuelles Datum, Uhrzeit und Wochentag in deutscher Zeit
API Endpoint: GET https://api.askproai.de/api/zeitinfo?locale=de
Timeout (ms): 120000
Headers: (keine erforderlich)
Query Parameters: (bereits in URL enthalten)
Response Variables: (optional, siehe unten)
Speak During Execution: false
Speak After Execution: true
```

### Optional: Response Variables extrahieren

Sie können spezifische Werte aus der Response als Variablen extrahieren:

- `date_de` → Das aktuelle Datum im Format TT.MM.JJJJ
- `time_de` → Die aktuelle Uhrzeit im Format HH:MM
- `weekday_de` → Der aktuelle Wochentag auf Deutsch
- `formatted_short` → Komplette formatierte Zeit und Datum

### Agent Prompt anpassen

Fügen Sie folgende Instruktion zum Agent Prompt hinzu:

```
Du kannst jederzeit die aktuelle Zeit und das Datum abrufen, indem du die "current_time_berlin" Function aufrufst.
Dies gibt dir die aktuelle Zeit in Deutschland (Berlin Zeitzone).

Verwende diese Information, um:
- Termine in Relation zum aktuellen Datum zu setzen
- Die aktuelle Uhrzeit zu nennen, wenn danach gefragt wird
- Den aktuellen Wochentag zu kennen
- Zeitliche Bezüge korrekt zu verwenden (heute, morgen, nächste Woche, etc.)

Beispiel-Antwort wenn nach der Zeit gefragt wird:
"Es ist jetzt [time_de] Uhr am [weekday_de], den [date_de]."
```

## Test der Function

### Via curl:
```bash
curl -s "https://api.askproai.de/api/zeitinfo" | jq .
```

### Via Retell Test-Konsole:
1. Öffnen Sie die Test-Konsole in Retell
2. Führen Sie die Function aus: `current_time_berlin()`
3. Überprüfen Sie die Response

## Anwendungsfälle

1. **Zeitansage:** "Wie spät ist es gerade?"
2. **Datumsinformation:** "Welcher Tag ist heute?"
3. **Terminbezug:** "Ist der Termin morgen noch verfügbar?" (Agent weiß was "morgen" bedeutet)
4. **Zeitliche Orientierung:** Agent kann zwischen Vergangenheit und Zukunft unterscheiden

## Fehlerbehandlung

Falls der Endpunkt nicht erreichbar ist, sollte der Agent sagen:
"Entschuldigung, ich kann gerade die aktuelle Zeit nicht abrufen. Bitte nennen Sie mir das gewünschte Datum direkt."

## Monitoring

Der Endpunkt hat ein Rate Limit von 100 Anfragen pro Minute. Dies sollte für normale Nutzung ausreichend sein.

## Support

Bei Problemen überprüfen Sie:
1. Ist der Endpunkt erreichbar: `curl https://api.askproai.de/api/zeitinfo`
2. Sind die Laravel Caches geleert: `php artisan cache:clear`
3. Logs prüfen: `tail -f /var/www/api-gateway/storage/logs/laravel.log`