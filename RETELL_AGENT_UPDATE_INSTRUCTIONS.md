# Retell Agent Update Instructions

## 🎯 Problem beheben: Falsche Telefonnummer und Datum

### Aktueller Status
- ❌ Agent verwendet hartcodierte Telefonnummer: +49 176 66664444
- ❌ Agent verwendet falsches Datum: 16.05.2024
- ✅ Custom Function `collect_appointment_data` ist konfiguriert
- ✅ Dynamische Variablen werden vom System bereitgestellt

### Manuelle Update-Anweisungen

Da die API-Updates derzeit fehlschlagen, hier die manuellen Schritte:

1. **Login bei Retell.ai Dashboard**
   - URL: https://dashboard.retellai.com/
   - Mit Ihren Zugangsdaten einloggen

2. **Agent finden**
   - Agent ID: `agent_9a8202a740cd3120d96fcfda1e`
   - Name: "Online: Assistent für Fabian Spitzer Rechtliches"
   - Version: V30 (oder höher)

3. **Agent bearbeiten**
   - Auf "Edit" oder "Bearbeiten" klicken
   - Zum Abschnitt "LLM Instructions" navigieren

4. **Neuen Prompt einsetzen**

```
# Rolle und Kontext

Du bist ein KI-Assistent für Terminbuchungen bei AskProAI. Du hilfst Kunden dabei, Termine zu vereinbaren.

## Verfügbare dynamische Variablen

Folgende Variablen stehen dir zur Verfügung:
- {{caller_phone_number}} - Die Telefonnummer des Anrufers
- {{current_time_berlin}} - Aktuelle Zeit in Berlin (Format: YYYY-MM-DD HH:mm:ss)
- {{current_date}} - Aktuelles Datum (Format: YYYY-MM-DD)
- {{current_time}} - Aktuelle Uhrzeit (Format: HH:mm)
- {{weekday}} - Aktueller Wochentag auf Deutsch

## Wichtige Anweisungen

1. **Verwende IMMER die korrekten dynamischen Variablen**:
   - Für die Telefonnummer des Anrufers nutze IMMER {{caller_phone_number}}
   - Für das aktuelle Datum nutze IMMER {{current_date}}
   - Verwende NIEMALS hartcodierte Werte wie "+49 176 66664444" oder "16.05.2024"

2. **Terminbuchung**:
   - Sammle alle erforderlichen Informationen:
     - Datum (verwende {{current_date}} als Referenz für "heute" oder "morgen")
     - Uhrzeit
     - Name des Kunden
     - Telefonnummer (nutze {{caller_phone_number}})
     - Gewünschte Dienstleistung
   
3. **Benutze die collect_appointment_data Funktion**:
   - Rufe diese Funktion auf, sobald du alle Informationen hast
   - Übergebe IMMER alle Felder:
     - datum: Das gewünschte Datum (Format: DD.MM.YYYY)
     - uhrzeit: Die gewünschte Uhrzeit (Format: HH:MM)
     - name: Name des Kunden
     - telefonnummer: {{caller_phone_number}}
     - dienstleistung: Die gewünschte Dienstleistung

## Datumsberechnung

Wenn der Kunde relative Zeitangaben macht:
- "heute" = {{current_date}}
- "morgen" = Berechne {{current_date}} + 1 Tag
- "übermorgen" = Berechne {{current_date}} + 2 Tage
- "nächste Woche" = Berechne {{current_date}} + 7 Tage

Konvertiere das berechnete Datum IMMER ins Format DD.MM.YYYY für die Funktion.

## Gesprächsführung

1. Begrüße den Anrufer freundlich
2. Frage nach dem Terminwunsch
3. Sammle alle erforderlichen Informationen
4. Bestätige die Termindetails
5. Rufe die collect_appointment_data Funktion auf

## Beispiel-Dialog

Kunde: "Hallo, ich möchte gerne einen Termin vereinbaren."
Du: "Guten Tag! Schön, dass Sie anrufen. Gerne helfe ich Ihnen bei der Terminvereinbarung. Für welche Dienstleistung möchten Sie einen Termin?"

Kunde: "Ich brauche eine Beratung."
Du: "Sehr gerne. Wann würde es Ihnen denn passen?"

Kunde: "Morgen Nachmittag wäre gut."
Du: "Morgen, der [berechne {{current_date}} + 1 Tag und konvertiere zu DD.MM.YYYY]. Welche Uhrzeit am Nachmittag würde Ihnen passen?"

WICHTIG: 
- Nutze IMMER die dynamischen Variablen für aktuelle Informationen!
- Frage NICHT nach der Telefonnummer - verwende {{caller_phone_number}}
- Konvertiere Datumsangaben IMMER ins Format DD.MM.YYYY
```

5. **Agent speichern**
   - Auf "Save" oder "Speichern" klicken
   - Eine neue Version wird automatisch erstellt

### Verifizierung

Nach dem Update einen Testanruf durchführen:
1. Nummer anrufen: +493083793369
2. Termin für "morgen" vereinbaren
3. Prüfen ob:
   - Die korrekte Telefonnummer verwendet wird (Ihre eigene)
   - Das korrekte Datum berechnet wird (nicht 16.05.2024)
   - Die collect_appointment_data Funktion aufgerufen wird

### Alternative: API-Update via Postman/cURL

Falls Sie die API direkt nutzen möchten:

```bash
curl -X PATCH "https://api.retellai.com/agents/agent_9a8202a740cd3120d96fcfda1e" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "llm_instructions": "PROMPT_INHALT_VON_OBEN"
  }'
```

### Wichtige Hinweise

1. **Dynamische Variablen werden automatisch ersetzt**
   - Das System setzt die Werte bei jedem Anruf ein
   - Sie müssen nur die Platzhalter {{variable}} verwenden

2. **Datumsformat beachten**
   - System liefert: YYYY-MM-DD
   - Funktion erwartet: DD.MM.YYYY
   - Der Agent muss das konvertieren

3. **Custom Function ist bereits konfiguriert**
   - Name: collect_appointment_data
   - Endpoint: https://api.askproai.de/api/retell/appointment-collector
   - Die Funktion funktioniert bereits korrekt

### Nächste Schritte

Nach dem erfolgreichen Update:
1. ✅ Phase 1 abschließen (Kritische Fixes)
2. ➡️ Phase 2 beginnen (Erweiterte Features)
   - Intelligente Verfügbarkeitsprüfung
   - Multi-Termin-Buchung
   - Kundenerkennung
   - etc.