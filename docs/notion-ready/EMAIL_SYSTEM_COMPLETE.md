# E-Mail System Dokumentation

## Übersicht
Das AskProAI System verwendet Resend.com für den E-Mail-Versand. Die E-Mails haben ein professionelles HTML-Design und können optional eine CSV-Datei mit allen Anrufdaten enthalten.

## E-Mail Design

### 1. **Professionelles HTML-Template**
- Responsive Design (Mobile-optimiert)
- Modernes Layout mit Farbverlauf im Header (Lila)
- Strukturierte Informationsboxen
- Action Items mit Icons
- Call-to-Action Button zum Dashboard

### 2. **E-Mail Inhalte**

#### Header
- Firmenname mit Gradient-Hintergrund
- "Neue Anrufzusammenfassung" Untertitel

#### Hauptinhalte
- **Anrufer-Informationen**: Name und Telefonnummer
- **Zeitinformationen**: Datum, Uhrzeit und Dauer
- **Dringlichkeit**: Farbcodierte Badges (Dringend/Hoch/Normal)
- **Zusammenfassung**: KI-generierte Zusammenfassung des Gesprächs
- **Erforderliche Maßnahmen**: Action Items mit Icons
- **Erfasste Informationen**: Strukturierte Tabelle mit allen erfassten Daten
- **Transkript**: Vollständiges Gesprächstranskript (optional)
- **Kundeninformationen**: E-Mail, Adresse, letzter Kontakt

## CSV-Export Funktionalität

### CSV-Inhalt
Die CSV-Datei enthält folgende Spalten:
- ID, Datum, Uhrzeit
- Dauer (Sekunden und formatiert MM:SS)
- Telefonnummer
- Kundenname und E-Mail
- Filiale
- Status und Dringlichkeit
- Zusammenfassung
- Termin-Informationen
- Vollständiges Transkript
- Erfasste Daten (gefiltert)
- Agent Name
- Anrufkosten
- Zeitstempel (Erstellt/Aktualisiert)

### Datenschutz-Filter
Folgende technische Felder werden NICHT exportiert:
- `caller_id`
- `twilio_call_sid`
- `direction`
- `to_number`, `from_number` (interne Felder)
- Andere interne API-Identifikatoren

## Verwendung im Business Portal

### E-Mail versenden
1. Auf der Call-Detail Seite auf "Zusammenfassung senden" klicken
2. E-Mail-Adressen eingeben (kommagetrennt für mehrere Empfänger)
3. Optionen wählen:
   - ✅ **Transkript einschließen**: Fügt das vollständige Gesprächstranskript hinzu
   - ✅ **CSV-Datei anhängen**: Hängt eine CSV-Datei mit allen Anrufdaten an
4. Auf "Senden" klicken

### API-Endpunkt
```
POST /business/api/calls/{id}/send-summary
```

Parameter:
```json
{
    "recipients": ["email1@example.com", "email2@example.com"],
    "include_transcript": true,
    "include_csv": true,
    "message": "Optionale Nachricht",
    "subject": "Optionaler Betreff"
}
```

## Sicherheitsaspekte

### Empfängertypen
- **internal**: Für interne Mitarbeiter (vollständige Informationen)
- **external**: Für externe Empfänger (mit Vertraulichkeitshinweis)

### Duplikatschutz
- E-Mails an denselben Empfänger werden innerhalb von 5 Minuten blockiert
- Verhindert versehentliches mehrfaches Versenden

### Vertraulichkeitshinweise
- Footer enthält automatisch generierten Hinweis
- Bei externen Empfängern zusätzlicher Datenschutzhinweis

## Konfiguration

### Resend API
```env
MAIL_MAILER=resend
RESEND_API_KEY=re_Nt1PhZEM_AAuXr5So799ySny9ja5DbgP1
MAIL_FROM_ADDRESS="info@askproai.de"
MAIL_FROM_NAME="AskProAI"
```

### SPF-Record
Der DNS SPF-Record ist bereits für Resend konfiguriert:
```
v=spf1 include:spf.resend.com -all
```

## Vorteile von Resend
- ✅ Optimiert für transaktionale E-Mails
- ✅ Bessere Zustellraten als normale SMTP-Server
- ✅ Detaillierte Delivery-Reports im Resend Dashboard
- ✅ Schnellere Zustellung
- ✅ Bereits im SPF-Record konfiguriert