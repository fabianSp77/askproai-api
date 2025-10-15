# Admin-Handbuch - AskProAI Verwaltungspanel
**Version**: 1.0
**Datum**: 2025-10-03
**Zielgruppe**: Administratoren, Branch Manager, System-Betreuer

---

## Inhaltsverzeichnis

1. [Erste Schritte](#erste-schritte)
2. [Dashboard Übersicht](#dashboard-übersicht)
3. [Geschäftsregeln konfigurieren](#geschäftsregeln-konfigurieren)
4. [Benachrichtigungen einrichten](#benachrichtigungen-einrichten)
5. [Rückrufanfragen bearbeiten](#rückrufanfragen-bearbeiten)
6. [Terminänderungen nachvollziehen](#terminänderungen-nachvollziehen)
7. [Kunden-Risiko-Management](#kunden-risiko-management)
8. [FAQs & Troubleshooting](#faqs--troubleshooting)

---

## Erste Schritte

### Anmeldung

1. Öffnen Sie Ihren Browser und navigieren Sie zu: `https://api.askproai.de/admin`
2. Geben Sie Ihre E-Mail-Adresse und Ihr Passwort ein
3. Klicken Sie auf "Anmelden"

**Standardzugangsdaten** (bitte nach erster Anmeldung ändern):
- E-Mail: `admin@askproai.de`
- Passwort: `admin123`

### Passwort ändern

1. Klicken Sie oben rechts auf Ihren Namen
2. Wählen Sie "Profil bearbeiten"
3. Geben Sie ein neues sicheres Passwort ein
4. Klicken Sie auf "Speichern"

**Passwort-Anforderungen**:
- Mindestens 8 Zeichen
- Großbuchstaben, Kleinbuchstaben, Zahlen
- Sonderzeichen empfohlen

---

## Dashboard Übersicht

Nach der Anmeldung sehen Sie das Dashboard mit wichtigen Kennzahlen und Widgets.

### Dashboard-Komponenten

#### 1. Persönliche Begrüßung
```
Guten Morgen, Max Mustermann! 👋
Heute ist Mittwoch, 3. Oktober 2025
```
- Zeigt personalisierte Begrüßung basierend auf Tageszeit
- Aktuelles Datum auf Deutsch

#### 2. Dashboard-Statistiken Widget
```
┌─────────────────────────────────────────────────┐
│ Gesamtkunden: 52                                │
│ ↗️ +12% diesen Monat                            │
│ 5 VIP-Kunden                                    │
└─────────────────────────────────────────────────┘
```

**Was bedeuten die Zahlen?**
- **Gesamtkunden**: Alle registrierten Kunden
- **Prozent-Änderung**: Wachstum/Rückgang vs. letztem Monat
- **Pfeil-Symbol**: ↗️ = Wachstum, ↘️ = Rückgang
- **VIP-Kunden**: Kunden mit hohem Lifetime-Value

#### 3. Schnellaktionen Widget
```
┌─────────────────────────────────────────────────┐
│ Schnellaktionen                                 │
│                                                 │
│ 📞 Neuer Termin      👤 Neuer Kunde            │
│ 💬 Rückrufanfrage    📧 Benachrichtigung       │
└─────────────────────────────────────────────────┘
```

**Verfügbare Schnellaktionen**:
- **Neuer Termin**: Direkt einen Termin anlegen
- **Neuer Kunde**: Kundenprofil erstellen
- **Rückrufanfrage**: Callback für Kunden einplanen
- **Benachrichtigung**: Manuelle Benachrichtigung senden

#### 4. Neueste Termine Widget
Zeigt die letzten 5 Termine mit Status-Badges:
- 🟢 **Bestätigt**: Termin ist fix
- 🟡 **Ausstehend**: Warte auf Bestätigung
- 🔴 **Storniert**: Termin wurde abgesagt

#### 5. Neueste Anrufe Widget
Zeigt die letzten 5 Anrufe mit:
- Anruftyp (eingehend/ausgehend/verpasst)
- Kunde/Nummer
- Zeitstempel
- Dauer

### Dashboard-Aktualisierung

Das Dashboard aktualisiert sich automatisch alle 5 Minuten. Sie können auch manuell aktualisieren:
- **Browser**: F5-Taste drücken
- **Seite neu laden**: Klick auf Browser-Refresh-Button

---

## Geschäftsregeln konfigurieren

> ⚠️ **Hinweis**: Dieses Feature ist aktuell in Entwicklung (siehe IMPROVEMENT_ROADMAP.md Sprint 1 Task 1.2).
> Diese Anleitung beschreibt die Funktionalität, die nach Implementierung verfügbar sein wird.

### Was sind Geschäftsregeln?

Geschäftsregeln definieren, wie Ihr Unternehmen mit Stornierungen, Umbuchungen und Serientermine umgeht.

**Beispiel-Szenarien**:
- "Stornierung 24h vorher ist kostenlos, danach 50% Gebühr"
- "Umbuchung max. 2x möglich, danach 10€ Gebühr"
- "Serientermine jeden Montag für 8 Wochen"

### Hierarchie-Ebenen

Geschäftsregeln können auf 4 Ebenen konfiguriert werden:

```
🏢 Unternehmen (Standard für alle)
   ↓
   🏪 Filiale (Überschreibt Unternehmensregel)
      ↓
      ⚙️ Service (Überschreibt Filialregel)
         ↓
         👤 Mitarbeiter (Individuelle Präferenz)
```

**Wichtig**: Eine Regel auf niedrigerer Ebene überschreibt immer die Regel auf höherer Ebene!

### Stornierungsregeln erstellen

#### Schritt 1: Navigation
1. Klicken Sie in der linken Navigation auf **"Konfiguration"**
2. Wählen Sie **"Geschäftsregeln"**
3. Klicken Sie oben rechts auf **"Neue Regel"**

#### Schritt 2: Geltungsbereich wählen
1. **Tab "Geltungsbereich"** öffnet sich automatisch
2. Klicken Sie auf **"Gilt für"**
3. Wählen Sie die Ebene:
   - **Unternehmen**: Regel gilt für alle Filialen/Services/Mitarbeiter
   - **Filiale**: Nur für ausgewählte Filiale
   - **Service**: Nur für ausgewählten Service (z.B. "Beratung")
   - **Mitarbeiter**: Nur für ausgewählten Mitarbeiter

4. Wählen Sie **Policy-Typ**: `Stornierungsregeln`

#### Schritt 3: Regelkonfiguration
1. Wechseln Sie zum **Tab "Regelkonfiguration"**
2. Klicken Sie auf **"Parameter hinzufügen"**
3. Füllen Sie die Felder aus:

**Beispiel: 24h-Stornierungsregel mit 50% Gebühr**
```
Parameter                    | Wert
-----------------------------|--------
hours_before                 | 24
fee_percentage              | 50
max_cancellations_per_month | 3
grace_period_days           | 1
```

**Was bedeuten die Parameter?**
- `hours_before`: Mindestfrist in Stunden vor Termin (z.B. 24 = ein Tag vorher)
- `fee_percentage`: Gebühr in Prozent (z.B. 50 = 50% des Termininwerts)
- `fee_fixed`: Alternativ feste Gebühr in € (z.B. 10.00)
- `max_cancellations_per_month`: Maximale Anzahl Stornierungen pro Monat
- `grace_period_days`: Kulanzfrist in Tagen (z.B. 1 = innerhalb 24h nach Termin noch stornierbar)

#### Schritt 4: Hierarchie prüfen
1. Wechseln Sie zum **Tab "Hierarchie"**
2. Sehen Sie die Hierarchie-Kette:
   ```
   Unternehmen: AskProAI GmbH (cancellation)
      → Filiale: München Zentrum (override)
         → Service: Beratung (override)
   ```
3. Prüfen Sie die **"Effektive Konfiguration"** (zeigt finale Regel nach allen Overrides)

#### Schritt 5: Speichern
1. Klicken Sie unten rechts auf **"Erstellen"**
2. Erfolgsmeldung erscheint: "Geschäftsregel erfolgreich erstellt"

### Umbuchungsregeln erstellen

Ähnlich wie Stornierungsregeln, aber mit anderen Parametern:

**Wichtige Parameter**:
- `hours_before`: Mindestfrist für Umbuchung (z.B. 6 Stunden)
- `max_reschedules`: Maximale Anzahl Umbuchungen (z.B. 2)
- `fee_after_count`: Gebühr ab welcher Umbuchung (z.B. ab der 2. Umbuchung)
- `fee_amount`: Gebühr in € (z.B. 5.00)

**Beispiel-Konfiguration**:
```
hours_before = 6       → Umbuchung mind. 6h vorher
max_reschedules = 2    → Max. 2 Umbuchungen erlaubt
fee_after_count = 2    → Ab 2. Umbuchung Gebühr
fee_amount = 5.00      → Gebühr: 5€
```

### Serientermine konfigurieren

**Wichtige Parameter**:
- `frequency`: Intervall (`daily`, `weekly`, `monthly`)
- `interval`: Anzahl (z.B. 2 = alle 2 Wochen bei `weekly`)
- `max_occurrences`: Maximale Anzahl Termine (z.B. 10)
- `end_date`: Enddatum (Format: YYYY-MM-DD, z.B. 2025-12-31)

**Beispiel: Wöchentlicher Termin für 8 Wochen**
```
frequency = weekly
interval = 1
max_occurrences = 8
```

**Beispiel: Alle 2 Wochen bis Jahresende**
```
frequency = weekly
interval = 2
end_date = 2025-12-31
```

### Bestehende Regeln bearbeiten

1. Öffnen Sie **"Konfiguration" → "Geschäftsregeln"**
2. Finden Sie die Regel in der Tabelle
3. Klicken Sie auf **Stift-Symbol** (Bearbeiten)
4. Nehmen Sie Änderungen vor
5. Klicken Sie **"Speichern"**

**Tipp**: Nutzen Sie die Filter, um Regeln schnell zu finden:
- Nach **Policy-Typ** (Stornierung, Umbuchung, Serientermine)
- Nach **Ebene** (Unternehmen, Filiale, Service, Mitarbeiter)
- Nach **Override-Status** (Nur Overrides anzeigen)

---

## Benachrichtigungen einrichten

### Überblick Benachrichtigungssystem

Das System kann automatisch Benachrichtigungen über verschiedene Kanäle senden:
- 📧 **E-Mail**: Für ausführliche Informationen
- 💬 **SMS**: Für dringende Kurznachrichten
- 💚 **WhatsApp**: Für bequeme Kommunikation
- 🔔 **Push**: Für App-Benachrichtigungen

### Event-Types (13 verfügbare Events)

**Buchungs-Events**:
- `appointment_created`: Neuer Termin erstellt
- `appointment_updated`: Termin geändert
- `appointment_cancelled`: Termin storniert

**Erinnerungs-Events**:
- `appointment_reminder_24h`: 24 Stunden vor Termin
- `appointment_reminder_2h`: 2 Stunden vor Termin
- `appointment_reminder_1week`: 1 Woche vor Termin

**Änderungs-Events**:
- `cancellation`: Stornierung durchgeführt
- `reschedule_confirmed`: Umbuchung bestätigt
- `appointment_modified`: Termin modifiziert

**Callback-Events**:
- `callback_request_received`: Rückrufanfrage eingegangen
- `callback_scheduled`: Rückruf eingeplant

**Abschluss-Events**:
- `no_show`: Kunde nicht erschienen
- `appointment_completed`: Termin erfolgreich abgeschlossen
- `payment_received`: Zahlung eingegangen

### Benachrichtigungs-Konfiguration erstellen

> ⚠️ **Hinweis**: Dieses Feature ist aktuell in Entwicklung (siehe IMPROVEMENT_ROADMAP.md Sprint 1 Task 1.3).

#### Schritt 1: Navigation
1. **"Benachrichtigungen"** → **"Konfiguration"** → **"Neue Konfiguration"**

#### Schritt 2: Geltungsbereich
Wählen Sie, für wen die Konfiguration gilt:
- **Unternehmen**: Standard für alle
- **Filiale**: Nur bestimmte Filiale
- **Service**: Nur bestimmter Service
- **Mitarbeiter**: Persönliche Präferenz

**Wichtig**: Mitarbeiter können nur ihre eigenen Präferenzen ändern, keine Geschäftsregeln!

#### Schritt 3: Event & Kanal
1. **Event-Typ wählen**: z.B. `appointment_reminder_24h` (📅 Erinnerung 24h)
2. **Primärer Kanal**: z.B. `WhatsApp` (💚)
3. **Fallback-Kanal**: z.B. `E-Mail` (📧)
   - Wird verwendet, wenn WhatsApp fehlschlägt
4. **Aktiviert**: Ja/Nein Toggle

**Beispiel-Konfiguration**:
```
Event: Termineinnerung 24h vorher
Primärer Kanal: WhatsApp
Fallback: E-Mail
Aktiviert: Ja
```
→ System versucht zuerst WhatsApp, bei Fehler automatisch E-Mail

#### Schritt 4: Wiederholungen (Retry-Logik)
1. **Anzahl Wiederholungen**: z.B. `3` (= 3x wiederholen bei Fehler)
2. **Verzögerung (Minuten)**: z.B. `5` (= 5 Min. warten zwischen Versuchen)

**Beispiel**:
```
Wiederholungen: 3
Verzögerung: 5 Minuten

→ Bei Fehler wird 3 Mal wiederholt mit je 5 Min. Pause
```

#### Schritt 5: Erweiterte Optionen (Optional)
1. **Template-Override**: Eigene Benachrichtigungsvorlage wählen
2. **Metadaten** (KeyValue):
   ```
   priority       | high
   rate_limit     | 100
   quiet_hours_start | 22:00
   quiet_hours_end   | 08:00
   ```

**Was bedeuten die Metadaten?**
- `priority`: Priorität (high/normal/low)
- `rate_limit`: Max. Benachrichtigungen pro Stunde
- `quiet_hours_start/end`: Ruhezeitfenster (keine Benachrichtigungen)

#### Schritt 6: Speichern & Testen
1. Klicken Sie **"Erstellen"**
2. Klicken Sie auf **"Test senden"** (Papierflieger-Symbol)
3. System sendet Test-Benachrichtigung an Ihre Kontaktdaten

### Benachrichtigungsvorlagen anpassen

#### Vorlage bearbeiten
1. **"Benachrichtigungen"** → **"Vorlagen"**
2. Finden Sie die Vorlage (z.B. "Termin-Erinnerung 24h")
3. Klicken Sie auf **Stift-Symbol**

#### Verfügbare Variablen

**Kundendaten**:
- `{name}`: Kundenname (z.B. "Max Mustermann")
- `{email}`: E-Mail-Adresse
- `{phone}`: Telefonnummer

**Termindaten**:
- `{date}`: Termindatum (z.B. "15.10.2025")
- `{time}`: Terminzeit (z.B. "14:30")
- `{location}`: Filiale/Standort (z.B. "München Zentrum")
- `{service}`: Service-Name (z.B. "Beratungsgespräch")
- `{employee}`: Mitarbeiter-Name (z.B. "Anna Schmidt")

**Finanzdaten**:
- `{amount:currency}`: Betrag formatiert (z.B. "€50,00")

**Beispiel-Template (WhatsApp)**:
```
Hallo {name}! 👋

Erinnerung: Ihr Termin "{service}" ist morgen um {time} bei uns in {location}.

Ihr Berater: {employee}

Falls Sie nicht kommen können, sagen Sie bitte rechtzeitig ab.

Bis morgen!
Ihr AskProAI Team
```

#### Vorschau anzeigen
1. Klicken Sie auf **"Vorschau"** (Augen-Symbol)
2. System zeigt Template mit Beispieldaten gerendert:
   ```
   Hallo Max Mustermann! 👋

   Erinnerung: Ihr Termin "Beratungsgespräch" ist morgen um 14:30
   bei uns in München Zentrum.

   Ihr Berater: Anna Schmidt

   Falls Sie nicht kommen können, sagen Sie bitte rechtzeitig ab.

   Bis morgen!
   Ihr AskProAI Team
   ```

### Benachrichtigungs-Warteschlange überwachen

#### Queue-Übersicht
1. **"Benachrichtigungen"** → **"Warteschlange"**
2. Sehen Sie alle ausstehenden/versendeten Benachrichtigungen

#### Status-Bedeutung
- 🟡 **Pending** (Ausstehend): Warte auf Versand
- 🔵 **Processing** (In Bearbeitung): Wird gerade versendet
- 🟢 **Sent** (Gesendet): Erfolgreich versendet
- 🔴 **Failed** (Fehlgeschlagen): Fehler beim Versand
- ⚫ **Cancelled** (Abgebrochen): Manuell abgebrochen

#### Fehlgeschlagene Benachrichtigungen wiederholen
1. Filter: **Status** → `Failed` (Fehlgeschlagen)
2. Wählen Sie die Benachrichtigungen aus (Checkbox)
3. Klicken Sie **"Wiederholen"** (Bulk-Aktion)
4. System versucht erneut zu senden

#### Priorität ändern
Benachrichtigungen mit hoher Priorität werden zuerst versendet:
- 🔴 **High**: Sofort versenden
- 🟡 **Normal**: Reguläre Warteschlange
- 🟢 **Low**: Verzögert versenden

**Priorität ändern**:
1. Öffnen Sie die Benachrichtigung
2. Ändern Sie **"Priorität"**
3. Klicken Sie **"Speichern"**

---

## Rückrufanfragen bearbeiten

### Überblick

Rückrufanfragen sind Kundenanfragen, die einen Rückruf vom Team erfordern. Das System zeigt:
- 📞 Offene Rückrufanfragen
- ⏰ Überfällige Anfragen (SLA-Verstoß)
- ✅ Erledigte Anfragen

### Rückrufanfragen-Liste anzeigen

1. **"CRM"** → **"Rückrufanfragen"**
2. Sie sehen eine Tabelle mit allen Anfragen

#### Spalten-Bedeutung
- **ID**: Eindeutige Nummer der Anfrage
- **Kunde**: Name und Kontaktdaten
- **Status-Badge**:
  - 🟡 **Pending** (Ausstehend): Noch nicht bearbeitet
  - 🔵 **Assigned** (Zugewiesen): Einem Mitarbeiter zugewiesen
  - 🟢 **Contacted** (Kontaktiert): Kunde wurde kontaktiert
  - ✅ **Completed** (Erledigt): Anfrage abgeschlossen
  - 🔴 **Escalated** (Eskaliert): An Vorgesetzten weitergeleitet

- **Priorität**:
  - 🔴 **High** (Hoch): Dringend, sofort bearbeiten
  - 🟡 **Normal**: Reguläre Bearbeitung
  - 🟢 **Low** (Niedrig): Kann warten

- **Überfällig?**:
  - ⏰ **Ja**: SLA überschritten, sofort handeln!
  - ✅ **Nein**: Innerhalb SLA

### Workflow: Rückrufanfrage bearbeiten

#### Schritt 1: Anfrage zuweisen
1. Finden Sie die Anfrage (Filter: Status = Pending)
2. Klicken Sie auf **"Zuweisen"** (Aktionen-Spalte)
3. Wählen Sie den Mitarbeiter aus
4. Optional: Notiz hinzufügen (z.B. "Spezialist für Versicherung")
5. Klicken Sie **"Zuweisen"**

**Oder: Schnellzuweisung**
- Klicken Sie **"Mir zuweisen"** → Sofort Ihnen zugewiesen

#### Schritt 2: Kunde kontaktieren
1. Öffnen Sie die zugewiesene Anfrage
2. Sehen Sie Kontaktdaten:
   - Telefon: 📞 (klickbar zum Kopieren)
   - E-Mail: 📧 (klickbar zum Kopieren)
   - Bevorzugte Zeiten: z.B. "Montag: 09:00-12:00"

3. Kontaktieren Sie den Kunden
4. Klicken Sie **"Kontaktiert markieren"**
5. Füllen Sie das Formular aus:
   ```
   Kontaktmethode: [Telefon / SMS / E-Mail]
   Ergebnis: Kunde erreicht
   Notiz: "Termin vereinbart für 15.10."
   ```
6. Klicken Sie **"Bestätigen"**

#### Schritt 3: Anfrage abschließen
1. Nach erfolgreicher Kontaktaufnahme
2. Klicken Sie **"Erledigt markieren"**
3. Füllen Sie das Formular:
   ```
   Ergebnis: [Erfolg / Kunde nicht erreicht / Kein Interesse]
   Folgeaktion: [Termin vereinbart / Angebot gesendet / Keine]
   Abschließende Notiz: "Beratungsgespräch am 15.10. um 14:00"
   ```
4. Klicken Sie **"Abschließen"**

Status wechselt zu ✅ **Completed**

### Eskalation bei Problemen

#### Wann eskalieren?
- Kunde mehrfach nicht erreichbar
- Besondere Anforderung außerhalb Ihrer Kompetenz
- SLA überschritten und Sie benötigen Hilfe

#### Eskalations-Prozess
1. Öffnen Sie die Rückrufanfrage
2. Klicken Sie **"Eskalieren"**
3. Füllen Sie das Formular:
   ```
   Eskaliert an: [Vorgesetzter auswählen]
   Grund: Kunde 3x nicht erreicht, bitte alternative Kontaktmethode versuchen
   Priorität: Hoch
   ```
4. Klicken Sie **"Eskalieren"**

**Was passiert?**
- Anfrage erhält Status 🔴 **Escalated**
- Vorgesetzter wird benachrichtigt
- Eskalations-Eintrag in Historie sichtbar

### Filter & Suche

**Häufige Filter**:
- **Status**: Zeige nur `Pending` (zum Bearbeiten)
- **Priorität**: Zeige nur `High` (dringende Anfragen)
- **Überfällig**: Zeige nur überfällige Anfragen
- **Filiale**: Nur Anfragen Ihrer Filiale
- **Zugewiesen an**: Nur Ihre Anfragen

**Erweiterte Filter**:
1. Klicken Sie **"Filter"** (oben rechts)
2. Wählen Sie mehrere Filter:
   ```
   Status: Pending
   Priorität: High
   Überfällig: Ja
   ```
3. Ergebnis: Nur dringende, überfällige, offene Anfragen

**Suche**:
- Suchfeld oben: Nach Kundennamen, Telefon, E-Mail suchen

### Bulk-Aktionen (Mehrere Anfragen gleichzeitig)

1. Wählen Sie mehrere Anfragen aus (Checkbox links)
2. Klicken Sie **"Bulk-Aktionen"** (oben)
3. Verfügbare Aktionen:
   - **Bulk-Zuweisung**: Alle an einen Mitarbeiter zuweisen
   - **Bulk-Abschluss**: Mehrere auf einmal abschließen (bei Massenaktionen)
   - **Exportieren**: Als CSV/Excel für Reporting

---

## Terminänderungen nachvollziehen

> ⚠️ **Hinweis**: Dieses Feature ist aktuell in Entwicklung (siehe IMPROVEMENT_ROADMAP.md Sprint 1 Task 1.4).

### Überblick

Das System protokolliert alle Terminänderungen:
- Stornierungen
- Umbuchungen
- Wer hat geändert (User/Staff/Customer/System)
- Policy-Konformität (innerhalb oder außerhalb Geschäftsregeln)
- Berechnete Gebühren

### Änderungs-Historie anzeigen

#### Navigation
1. **"Berichte"** → **"Terminänderungen"**
2. Sie sehen eine Tabelle mit allen Änderungen

#### Spalten-Bedeutung
- **ID**: Änderungs-ID
- **Termin**: Link zum ursprünglichen Termin
- **Kunde**: Link zum Kundenprofil
- **Typ-Badge**:
  - 🔴 **Stornierung**: Termin abgesagt
  - 🟡 **Umbuchung**: Termin verschoben

- **Policy-Konform?**:
  - ✅ **Ja** (Grün): Innerhalb Geschäftsregeln
  - ❌ **Nein** (Rot): Verstoß gegen Policy (z.B. zu kurzfristig)

- **Gebühr**: Berechnete Gebühr in € (z.B. "€25,00")
- **Grund**: Begründung für Änderung
- **Geändert von**: Person/System, das Änderung vorgenommen hat
- **Zeitpunkt**: Datum & Uhrzeit der Änderung

### Filter & Analyse

#### Häufige Filter
1. **Typ**: Nur Stornierungen oder nur Umbuchungen
2. **Policy-Konform**: Nur Policy-Verstöße anzeigen
3. **Mit Gebühr**: Nur Änderungen mit berechneter Gebühr
4. **Datumsbereich**: z.B. "Letzte 30 Tage"
5. **Kunde**: Änderungen eines bestimmten Kunden

**Beispiel: Finde alle Policy-Verstöße mit Gebühr**
```
Filter:
- Policy-Konform: Nein
- Mit Gebühr: Ja
- Datumsbereich: 01.09.2025 - 30.09.2025

Ergebnis: Alle kostenpflichtigen Verstöße im September
```

#### Statistiken anzeigen
1. Klicken Sie **"Statistiken"** (oben rechts)
2. Modal zeigt:
   ```
   📊 Änderungsstatistiken

   Änderungen heute: 15
   - Stornierungen: 12
   - Umbuchungen: 3

   Policy-Verstöße heute: 5
   Gebühren heute: €125,00

   Trend:
   - Letzte Woche: 82 Änderungen
   - Diese Woche: 95 Änderungen (+16%)
   ```

### Details einer Änderung anzeigen

#### Änderungs-Details öffnen
1. Klicken Sie auf eine Zeile in der Tabelle
2. Detail-Ansicht öffnet sich mit Abschnitten:

**Abschnitt: Änderungsdetails**
```
ID: #12345
Typ: Stornierung 🔴
Policy-Konform: Nein ❌
Gebühr: €25,00
Zeitpunkt: 03.10.2025 14:30 (vor 2 Stunden)

Termin: #67890 (Link)
Kunde: Max Mustermann (Link)
```

**Abschnitt: Akteur**
```
Geändert von: Anna Schmidt (Mitarbeiter)
Akteur-Typ: Staff
```

**Abschnitt: Begründung**
```
"Kunde hat kurzfristig erkrankt. Keine Kulanz da bereits 3. Stornierung diesen Monat."
```

**Abschnitt: Zusätzliche Informationen** (Metadaten)
```
{
  "previous_date": "2025-10-05 10:00",
  "cancellation_reason_category": "illness",
  "notification_sent": true,
  "fee_waived": false
}
```

### Export für Compliance-Audits

#### CSV/Excel Export
1. Wählen Sie Änderungen aus (Checkbox) oder "Alle auswählen"
2. Klicken Sie **"Bulk-Aktionen"** → **"Exportieren"**
3. Wählen Sie Format: `CSV` oder `Excel`
4. Datei wird heruntergeladen

**Export-Inhalt**:
```csv
ID,Datum,Kunde,Typ,Policy-Konform,Gebühr,Grund,Geändert von
12345,03.10.2025,Max Mustermann,Stornierung,Nein,25.00,"Erkrankt",Anna Schmidt
12346,03.10.2025,Lisa Müller,Umbuchung,Ja,0.00,"Terminkollision",System
...
```

#### Verwendung des Exports
- **Finanz-Reporting**: Gebühren-Einnahmen analysieren
- **Compliance-Audits**: Policy-Konformität nachweisen
- **Kunden-Analyse**: Häufige Stornierer identifizieren
- **Mitarbeiter-Performance**: Wer verarbeitet Änderungen

### Dashboard-Widget: Änderungs-Statistiken

> ⚠️ **Nach Implementierung** (Sprint 2 Task 2.2)

Das Dashboard zeigt tagesaktuelle Änderungs-Statistiken:

```
┌─────────────────────────────────────────────────┐
│ Änderungen heute: 15                            │
│ ↗️ 12 Stornierungen                             │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│ Policy-Verstöße: 5                              │
│ ⚠️ Heute                                         │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│ Gebühren: €125,00                               │
│ 💰 Heute eingenommen                            │
└─────────────────────────────────────────────────┘
```

Klicken Sie auf ein Widget → Direkt zum vollständigen Bericht

---

## Kunden-Risiko-Management

### Überblick

Das System identifiziert automatisch Kunden mit Abwanderungsrisiko (Churn Risk) basierend auf:
- ⏰ **Lange Inaktivität**: Kein Termin seit >90 Tagen
- ❌ **Häufige Absagen**: >2 Stornierungen
- 📉 **Niedriges Engagement**: Engagement-Score <30
- ⚠️ **Manuell markiert**: Als gefährdet markiert

### Risiko-Kunden anzeigen

#### Widget auf Kunden-Liste
1. **"CRM"** → **"Kunden"**
2. Oben sehen Sie **"Kunden mit Risiko-Alarm"** Widget
3. Zeigt Kunden mit Risiko-Level:

**Risiko-Level**:
- 🔴 **Kritisch**: >120 Tage inaktiv ODER "Verloren"-Status
- 🟡 **Hoch**: 90-120 Tage inaktiv ODER "At Risk"-Status
- 🟠 **Mittel**: 60-90 Tage inaktiv
- 🟢 **Niedrig**: <60 Tage inaktiv

#### Widget auf Kunden-Detail-Seite
1. Öffnen Sie einen Kunden (Klick auf Name)
2. **Header-Widget**: Kunden-Übersicht
   ```
   ┌─────────────────────────────────────────────────┐
   │ Gesamtkunden: 52                                │
   │ ↗️ +12% diesen Monat                            │
   │ 5 VIP-Kunden                                    │
   │                                                 │
   │ Gefährdete Kunden: 8                            │
   │ 2 verloren                                      │
   │                                                 │
   │ Retention Rate: 85%                             │
   │ 44 von 52 aktiv                                 │
   └─────────────────────────────────────────────────┘
   ```

3. **Footer-Widget**: Risiko-Alarm-Tabelle
   ```
   Kunde              | Risiko  | Gründe
   -------------------|---------|-----------------------------------
   Max Mustermann     | Hoch 🟡 | ⏰ Lange inaktiv | ❌ Häufige Absagen
   Lisa Müller        | Kritisch🔴| ⏰ Lange inaktiv | 📉 Niedriges Engagement
   ...
   ```

### Risiko-Faktoren verstehen

**Symbole-Bedeutung**:
- ⏰ **Lange inaktiv**: Letzter Termin vor >90 Tagen
- ❌ **Häufige Absagen**: >2 Stornierungen
- 📉 **Niedriges Engagement**: Engagement-Score <30 (0-100 Skala)
- ⚠️ **Als gefährdet markiert**: Manuell als "At Risk" gesetzt

**Engagement-Score Berechnung**:
```
Score = 100 Punkte (Maximum)
- Jede Stornierung: -10 Punkte
- 30 Tage ohne Termin: -5 Punkte
- Keine Öffnungen von E-Mails: -5 Punkte
- Positive Bewertung: +10 Punkte
```

### Proaktive Maßnahmen

#### Maßnahme 1: Kunde kontaktieren
1. Klicken Sie in der Risiko-Tabelle auf **"Kontaktieren"** (Telefon-Symbol)
2. Wählen Sie Kontaktmethode:
   - 📞 **Anrufen**: Direkte Telefonkontaktaufnahme
   - 💬 **SMS senden**: Kurze Nachricht
   - 📧 **E-Mail senden**: Ausführliche Nachricht
   - 🎁 **Sonderangebot**: Spezielle Aktion senden

3. Füllen Sie Notiz aus:
   ```
   Kontaktmethode: Anrufen
   Notiz: "Kunde angerufen wegen langer Inaktivität.
          Plant Rückkehr im November."
   ```

4. Klicken Sie **"Bestätigen"**

**Was passiert?**
- Kontakt wird in Kunden-Notizen protokolliert
- `last_contact_at` Timestamp aktualisiert
- Erfolgsbenachrichtigung: "Kunde Max Mustermann kontaktiert"

#### Maßnahme 2: Rückgewinnungskampagne starten
1. Klicken Sie **"Rückgewinnung"** (Geschenk-Symbol)
2. System führt automatisch aus:
   - Status → `prospect` (Interessent)
   - Engagement-Score +20 Punkte
   - Kunde wird in Rückgewinnungs-Funnel aufgenommen

3. Erfolgsbenachrichtigung:
   ```
   ✅ Rückgewinnungskampagne gestartet
   Kunde wurde für Rückgewinnung markiert.
   ```

4. **Nächste Schritte** (manuell):
   - Senden Sie personalisiertes Angebot
   - Vereinbaren Sie Beratungstermin
   - Bieten Sie Rabatt/Sonderkonditionen an

### Best Practices

**Priorität setzen**:
1. **Kritisch 🔴**: Sofort kontaktieren (heute)
2. **Hoch 🟡**: Diese Woche kontaktieren
3. **Mittel 🟠**: Nächste 2 Wochen
4. **Niedrig 🟢**: Beobachten, kein Action nötig

**Kontakt-Strategie**:
- **Telefon**: Für VIP-Kunden und kritische Fälle
- **E-Mail**: Für reguläre Rückgewinnung
- **SMS**: Für kurze Erinnerungen
- **Sonderangebot**: Für Preis-sensible Kunden

**Dokumentation**:
- Notieren Sie IMMER das Ergebnis des Kontakts
- Vermerken Sie geplante Folgetermine
- Aktualisieren Sie Kunden-Status manuell wenn nötig

### Leeres Risiko-Widget (Erfolgsmeldung)

Wenn keine Risiko-Kunden vorhanden:
```
┌─────────────────────────────────────────────────┐
│ 🎉 Keine Risiko-Kunden                          │
│                                                 │
│ Alle Kunden sind aktiv und engagiert!          │
│ Großartige Arbeit! 🏆                           │
│                                                 │
│ [Alle Kunden anzeigen]                          │
└─────────────────────────────────────────────────┘
```

---

## FAQs & Troubleshooting

### Häufig gestellte Fragen

#### 1. Ich kann mich nicht anmelden
**Problem**: "Diese Kombination aus Zugangsdaten wurde nicht in unserer Datenbank gefunden"

**Lösung**:
- Prüfen Sie E-Mail-Adresse auf Tippfehler
- Caps Lock ausgeschaltet?
- Passwort vergessen? → Klicken Sie "Passwort zurücksetzen"
- Kontaktieren Sie Ihren Admin

#### 2. Dashboard zeigt keine Daten / leere Widgets
**Problem**: Widgets sind leer oder zeigen "—"

**Mögliche Ursachen**:
1. **Neu angelegter Account**: Noch keine Daten vorhanden
   - Lösung: Warten Sie, bis erste Kunden/Termine angelegt sind

2. **Cache-Problem**: Browser-Cache veraltet
   - Lösung: Drücken Sie `Ctrl + F5` (Hard Refresh)

3. **Berechtigungsproblem**: Sie haben keine Berechtigung
   - Lösung: Admin um Zugriff bitten

4. **System-Fehler**: Widget-Fehler
   - Lösung: Fehlermeldung im Widget sollte erscheinen
   - Kontaktieren Sie Support mit Screenshot

#### 3. KeyValue-Feld: Was soll ich eingeben?
**Problem**: Feld "Metadaten" oder "Konfiguration" ohne Erklärung

**Lösung**:
- ✅ **Nach Update** (Sprint 1 Task 1.5): Alle KeyValue-Felder haben Helper-Text
- Schauen Sie auf **Helper-Text** unter dem Feld
- Beispiele werden angezeigt (z.B. "hours_before: 24")
- Bei Unsicherheit: Fragen Sie Ihren Admin

#### 4. Geschäftsregel greift nicht
**Problem**: Stornierung sollte Gebühr haben, aber keine Gebühr berechnet

**Prüfen Sie**:
1. **Hierarchie**: Wurde die Regel auf richtiger Ebene erstellt?
   - Filialregel überschreibt Unternehmensregel
   - Mitarbeiterregel überschreibt Service-Regel

2. **Parameter korrekt**: `hours_before = 24` statt `24h`

3. **Policy-Typ**: Stornierungsregel für Stornierung, nicht Umbuchungsregel

4. **Effektive Config prüfen**:
   - Öffnen Sie Geschäftsregel → Tab "Hierarchie"
   - Sehen Sie "Effektive Konfiguration"
   - Stimmt diese mit Erwartung überein?

#### 5. Benachrichtigung wird nicht versendet
**Problem**: Kunde erhält keine Erinnerungs-E-Mail

**Prüfen Sie**:
1. **Event-Konfiguration existiert**:
   - "Benachrichtigungen" → "Konfiguration"
   - Suchen Sie nach `appointment_reminder_24h`
   - Ist **"Aktiviert"** auf Ja?

2. **Kanal verfügbar**:
   - Kunde hat E-Mail-Adresse hinterlegt?
   - Fallback-Kanal konfiguriert?

3. **Warteschlange prüfen**:
   - "Benachrichtigungen" → "Warteschlange"
   - Status = `Failed`?
   - Fehlermeldung lesen

4. **Retry-Count aufgebraucht**:
   - Wurde bereits 3x wiederholt?
   - Benachrichtigung manuell wiederholen

#### 6. Rückrufanfrage überfällig - was tun?
**Problem**: Anfrage hat ⏰ Überfällig-Badge

**Sofortmaßnahmen**:
1. **Priorisieren**: Überfällige zuerst bearbeiten
2. **Schnellzuweisung**: "Mir zuweisen" klicken
3. **Sofort kontaktieren**: Kunde anrufen
4. **Bei Nicht-Erreichbarkeit**: Eskalieren

**SLA-Zeiten** (Standard):
- **High Priority**: 2 Stunden
- **Normal Priority**: 4 Stunden
- **Low Priority**: 8 Stunden

#### 7. Kunde in Risiko-Liste - was bedeutet das?
**Problem**: Kunde hat 🔴 Kritisch-Badge

**Bedeutung**:
- >120 Tage keine Aktivität
- ODER Journey-Status = "Churned" (Verloren)

**Maßnahmen**:
1. **Heute kontaktieren**: Nicht aufschieben!
2. **Sonderangebot senden**: Rabatt/Aktion anbieten
3. **Rückgewinnungskampagne**: Button "Rückgewinnung" klicken
4. **Eskalation bei VIP**: Vorgesetzten informieren

#### 8. "Permission Denied" Fehler
**Problem**: "Sie haben keine Berechtigung für diese Aktion"

**Ursachen**:
1. **Rollen-Problem**: Ihre Rolle hat nicht die nötige Berechtigung
   - Lösung: Admin um Berechtigung bitten

2. **Multi-Tenant**: Sie versuchen, Daten anderer Firma zu sehen
   - Lösung: Prüfen Sie Company-Filter oben rechts

3. **Read-Only Resource**: z.B. Terminänderungen können nicht bearbeitet werden
   - Das ist gewollt: Audit-Trail ist schreibgeschützt

### Technische Probleme

#### Dashboard lädt langsam (>5 Sekunden)
**Ursache**: Zu viele Daten, Cache nicht aktiv

**Lösung** (für Admins):
- Prüfen Sie, ob Caching aktiv ist
- Dashboard-Widget-Caching: 5 Min. TTL
- Navigation-Badge-Caching: 5 Min. TTL

**Workaround für User**:
- Schließen Sie nicht benötigte Browser-Tabs
- Löschen Sie Browser-Cache
- Nutzen Sie Chrome/Firefox (nicht IE)

#### "500 Internal Server Error"
**Bedeutung**: Server-Fehler

**Sofortmaßnahmen**:
1. **Seite neu laden**: F5 drücken
2. **Warten**: Server könnte überlastet sein (1-2 Min. warten)
3. **Support kontaktieren**: Wenn Fehler bleibt

**Info für Support**:
- Screenshot des Fehlers
- Was haben Sie getan, bevor Fehler auftrat?
- URL der Seite
- Uhrzeit des Fehlers

#### Mobile-Ansicht: Felder nicht sichtbar
**Problem**: Auf Smartphone fehlen Formularfelder

**Lösung**:
- **Landscape-Modus**: Drehen Sie Smartphone horizontal
- **Desktop-Ansicht erzwingen**: Browser-Einstellungen → "Desktop-Ansicht anfordern"
- **Tablet nutzen**: Für bessere Übersicht
- **Desktop-PC**: Für komplexe Formulare (empfohlen)

### Fehlermeldungen verstehen

#### "Validation Error: hours_before must be numeric"
**Bedeutung**: Parameter `hours_before` ist keine Zahl

**Falsch**: `hours_before = "24 Stunden"`
**Richtig**: `hours_before = 24`

#### "Policy conflict: Cannot override company policy as staff"
**Bedeutung**: Mitarbeiter können keine Unternehmensregeln überschreiben

**Lösung**:
- Mitarbeiter können nur persönliche Präferenzen setzen
- Geschäftsregeln müssen vom Admin/Manager erstellt werden

#### "Notification queue full: Rate limit exceeded"
**Bedeutung**: Zu viele Benachrichtigungen in kurzer Zeit

**Ursache**: `rate_limit` in Metadaten überschritten (z.B. Max. 100/Stunde)

**Lösung**:
- Warten Sie 1 Stunde
- Oder: Admin kann `rate_limit` erhöhen

#### "Circular hierarchy detected"
**Bedeutung**: Policy-Override-Kette hat Schleife

**Beispiel**: Policy A überschreibt B, B überschreibt C, C überschreibt A (Schleife!)

**Lösung**:
- Prüfen Sie Hierarchie-Tab
- Entfernen Sie zirkuläre Overrides
- Struktur sollte linear sein (A → B → C)

### Performance-Tipps

**Schnellere Workflows**:
1. **Keyboard-Shortcuts nutzen** (ab Sprint 3):
   - `Ctrl + A`: Mir zuweisen (in Rückrufanfragen)
   - `Ctrl + C`: Kontaktiert markieren
   - `Ctrl + E`: Eskalieren

2. **Quick-Actions verwenden**:
   - "Mir zuweisen" statt "Zuweisen" → Dialog → Auswählen
   - "Schnellaktionen" Widget auf Dashboard

3. **Filter speichern** (falls verfügbar):
   - Häufig genutzte Filter als "Gespeicherte Ansicht"
   - Spart Zeit beim täglichen Filtern

4. **Bulk-Aktionen für Massen-Updates**:
   - 10 Rückrufanfragen zuweisen? → Alle auswählen → Bulk-Zuweisung
   - Nicht einzeln!

### Kontakt & Support

**Bei technischen Problemen**:
1. **Support-E-Mail**: support@askproai.de
2. **Telefon-Hotline**: +49 XXX XXXXXXX (Mo-Fr 9-17 Uhr)
3. **Ticket-System**: https://support.askproai.de

**Informationen für Support bereitstellen**:
- Browser & Version (z.B. Chrome 118)
- Screenshot des Problems
- Fehlermeldung (vollständiger Text)
- Was Sie getan haben (Schritt-für-Schritt)
- Uhrzeit des Problems

**Schulung & Onboarding**:
- **Admin-Schulung**: Jeden ersten Montag im Monat
- **Video-Tutorials**: https://docs.askproai.de/videos
- **Handbuch-PDF**: Downloadbar im Admin-Panel (oben rechts → Hilfe)

---

## Anhang

### Glossar

**Begriffe**:
- **SLA** (Service Level Agreement): Vereinbarte Reaktionszeit
- **TTL** (Time To Live): Cache-Gültigkeitsdauer
- **Override**: Überschreiben einer übergeordneten Regel
- **Polymorphic**: Beziehung zu verschiedenen Entitätstypen (Company/Branch/Service/Staff)
- **Fallback**: Ausweich-Option wenn primäre Option fehlschlägt
- **Retry**: Wiederholung bei Fehler
- **Bulk-Aktion**: Massen-Aktion für mehrere Einträge gleichzeitig
- **Badge**: Farbige Kennzeichnung (z.B. Status-Badge)
- **Widget**: Dashboard-Element (z.B. Statistik-Karte)
- **Churn**: Kundenabwanderung
- **Engagement-Score**: Aktivitäts-Bewertung (0-100)
- **Journey-Status**: Kunden-Lifecycle-Phase (Prospect → Active → At Risk → Churned)

### Versionshinweise

**Version 1.0** (2025-10-03):
- Initiale Version des Admin-Handbuchs
- Basis: FEATURE_AUDIT.md, UX_ANALYSIS.md, IMPROVEMENT_ROADMAP.md
- Features dokumentiert: Dashboard, Rückrufanfragen, Kunden-Risiko
- ⚠️ **Hinweis**: 3 Features noch in Entwicklung:
  - Geschäftsregeln konfigurieren (Sprint 1 Task 1.2)
  - Benachrichtigungen einrichten (Sprint 1 Task 1.3)
  - Terminänderungen nachvollziehen (Sprint 1 Task 1.4)

**Nächste Updates**:
- Version 1.1 (nach Sprint 1): Vollständige Geschäftsregeln-Anleitung
- Version 1.2 (nach Sprint 2): Hierarchie-Visualisierung, Dashboard-Erweiterungen
- Version 1.3 (nach Sprint 3): Keyboard-Shortcuts, Erweiterte Features

---

**Viel Erfolg bei der Nutzung des Admin-Panels!** 🚀

Bei Fragen oder Problemen: support@askproai.de
