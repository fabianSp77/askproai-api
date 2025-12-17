# Phase 4 UI Testing Guide - Super Admin ✅
**Datum**: 2025-11-14
**Rolle**: Super Admin
**Browser**: Chrome/Firefox empfohlen
**URL**: `/admin`

---

## 🎯 Ihr Menü - Bestätigung

Sie sollten folgende Menüpunkte sehen (✅ = bereits sichtbar):

### ✅ CRM → Rückrufanfragen
**Resource**: `CallbackRequestResource` (Phase 4 Update)
**Änderung**: Email-Feld hinzugefügt

### ✅ Termine & Richtlinien → Stornierung & Umbuchung
**Resource**: `PolicyConfigurationResource` (Phase 4 Update)
**Änderung**: 8 neue Policy-Typen hinzugefügt (11 gesamt)

### ✅ Einstellungen → Anrufweiterleitung
**Resource**: `CallForwardingConfigurationResource` (Phase 4 NEU)
**Änderung**: Komplett neu erstellt

**Status**: ✅ Alle 3 Resources sind in Ihrem Menü sichtbar!

---

## 📋 Detaillierter Test-Plan

### Test 1️⃣: PolicyConfigurationResource (Stornierung & Umbuchung)

**Navigation**: Termine & Richtlinien → Stornierung & Umbuchung

#### 1.1 Liste anzeigen ✅
- [ ] Öffnen Sie "Stornierung & Umbuchung"
- [ ] Erwartung: Sie sehen existierende Policies
- [ ] Prüfen: Sind neue Policy-Typen in der Badge-Spalte sichtbar?

#### 1.2 Neue Policy erstellen - Operational Type
**Schritt 1**: Klicken Sie auf "+ Neue Richtlinie" (oben rechts)

**Schritt 2**: Füllen Sie das Formular aus:
- **Zugeordnete Entität**: Wählen Sie eine Filiale (z.B. "Filiale München")
- **Richtlinientyp**: Wählen Sie "📅 Terminbuchung" (NEU!)

  **Erwartete Änderung im Formular**:
  - ✅ Sektion "Richtliniendetails" erscheint
  - ✅ Toggle "Policy aktiviert" (Standard: AN)
  - ✅ Textarea "Nachricht bei Deaktivierung" (nur sichtbar wenn Toggle AUS)
  - ✅ KeyValue "Erlaubte Zeiten (Optional)"

**Schritt 3**: Konfigurieren Sie die Policy:
```
✅ Policy aktiviert: AN (grün)
Erlaubte Zeiten: Lassen Sie leer (24/7 erlaubt)
```

**Schritt 4**: Speichern Sie die Policy

**Erwartete Ergebnisse**:
- [ ] Policy wird gespeichert (grüne Erfolgsmeldung)
- [ ] Sie werden zur Detail-Ansicht weitergeleitet
- [ ] Policy-Typ zeigt "Terminbuchung" mit blauem Badge
- [ ] Icon zeigt Kalender-Symbol

#### 1.3 Neue Policy erstellen - Access Control Type
**Schritt 1**: Erstellen Sie eine neue Policy

**Schritt 2**: Füllen Sie das Formular aus:
- **Zugeordnete Entität**: Wählen Sie eine Filiale
- **Richtlinientyp**: Wählen Sie "🔒 Anonyme Anrufer" (NEU!)

**Erwartete Änderung im Formular**:
- ✅ **READ-ONLY Sicherheitshinweis** erscheint:
  ```
  ⚠️ Diese Regeln sind fest im System verankert und können nicht geändert werden.

  Erlaubt für anonyme Anrufer:
  - ✅ Terminbuchung
  - ✅ Verfügbarkeit prüfen
  - ✅ Service-Informationen
  - ✅ Öffnungszeiten
  - ✅ Rückruf anfordern

  NICHT erlaubt für anonyme Anrufer:
  - ❌ Termin verschieben
  - ❌ Termin stornieren
  - ❌ Termin abfragen
  ```

**Erwartete Ergebnisse**:
- [ ] Sicherheitshinweis wird angezeigt
- [ ] Keine konfigurierbaren Felder sichtbar (nur Hinweis)
- [ ] Policy kann gespeichert werden

#### 1.4 Neue Policy erstellen - Info Disclosure Type
**Schritt 1**: Erstellen Sie eine neue Policy

**Schritt 2**: Füllen Sie das Formular aus:
- **Zugeordnete Entität**: Wählen Sie eine Filiale
- **Richtlinientyp**: Wählen Sie "👁️ Info-Offenlegung" (NEU!)

**Erwartete Änderung im Formular**:
- ✅ **CheckboxList "Standard-Felder"** erscheint:
  - [ ] Datum (vorausgewählt)
  - [ ] Uhrzeit (vorausgewählt)
  - [ ] Service (vorausgewählt)

- ✅ **CheckboxList "Felder auf Nachfrage"** erscheint:
  - [ ] Mitarbeiter-Name (vorausgewählt)
  - [ ] Preis
  - [ ] Notizen

**Schritt 3**: Ändern Sie die Auswahl:
```
Standard-Felder: Nur "Datum" und "Uhrzeit" auswählen
Felder auf Nachfrage: "Mitarbeiter-Name" und "Preis" auswählen
```

**Erwartete Ergebnisse**:
- [ ] Checkboxen sind interaktiv
- [ ] Auswahl wird gespeichert
- [ ] Detail-Ansicht zeigt korrekte Config

#### 1.5 Filter und Suche testen
**Schritt 1**: Gehen Sie zurück zur Liste

**Schritt 2**: Öffnen Sie die Filter (oben)

**Erwartete Filter**:
- [ ] **Richtlinientyp** (Mehrfachauswahl):
  - Sollte ALLE 11 Typen anzeigen (3 Legacy + 8 Neue)
  - Mit Emoji-Icons vor jedem Typ

**Schritt 3**: Filtern Sie nach "📅 Terminbuchung"

**Erwartete Ergebnisse**:
- [ ] Nur "Terminbuchung"-Policies werden angezeigt
- [ ] Badge ist blau
- [ ] Icon ist Kalender

#### 1.6 Detail-Ansicht testen
**Schritt 1**: Klicken Sie auf eine Policy-Zeile

**Erwartete Anzeige**:
- [ ] Policy-Typ zeigt korrekten Namen mit Badge
- [ ] Badge-Farbe: Blau (Operational) oder Lila (Access Control)
- [ ] Icon entspricht dem Typ
- [ ] Konfiguration wird korrekt angezeigt

---

### Test 2️⃣: CallbackRequestResource (Rückrufanfragen)

**Navigation**: CRM → Rückrufanfragen

#### 2.1 Liste anzeigen ✅
- [ ] Öffnen Sie "Rückrufanfragen"
- [ ] Erwartung: Sie sehen existierende Callback-Requests

#### 2.2 Email-Spalte anzeigen
**Schritt 1**: Klicken Sie auf das Spalten-Symbol (oben rechts)

**Schritt 2**: Aktivieren Sie die Spalte "E-Mail"

**Erwartete Ergebnisse**:
- [ ] "E-Mail" Spalte wird sichtbar
- [ ] Envelope-Icon (✉️) vor Email-Adressen
- [ ] Emails sind kopierbar (Klick → Clipboard)
- [ ] "—" wird angezeigt wenn keine Email

#### 2.3 Neue Callback-Anfrage mit Email erstellen
**Schritt 1**: Klicken Sie auf "+ Neue Rückrufanfrage"

**Schritt 2**: Füllen Sie das Formular aus:
```
Tab: Kontaktdaten
  Filiale: Wählen Sie eine Filiale
  Telefonnummer: +4915112345678
  Kundenname: Max Mustermann
  E-Mail: max.mustermann@example.com ← ✅ NEU!
```

**Erwartete Anzeige**:
- [ ] **3-spaltiges Grid** (Telefon | Name | Email)
- [ ] Email-Feld hat Envelope-Icon
- [ ] Helper-Text: "Optional: Für Terminbestätigungen per E-Mail"
- [ ] Email-Validierung (nur gültige Emails akzeptiert)

**Schritt 3**: Füllen Sie restliche Felder aus:
```
Tab: Details
  Priorität: Normal

Tab: Zuweisung
  Status: Ausstehend
```

**Schritt 4**: Speichern Sie die Anfrage

**Erwartete Ergebnisse**:
- [ ] Anfrage wird gespeichert
- [ ] Detail-Ansicht zeigt Email-Feld
- [ ] Email ist kopierbar

#### 2.4 Email-Filter testen
**Schritt 1**: Gehen Sie zurück zur Liste

**Schritt 2**: Öffnen Sie die Filter

**Erwartete Filter**:
- [ ] **Mit E-Mail** (NEU!):
  - "Alle anzeigen" (Standard)
  - "Nur mit E-Mail"
  - "Ohne E-Mail"

**Schritt 3**: Filtern Sie nach "Nur mit E-Mail"

**Erwartete Ergebnisse**:
- [ ] Nur Anfragen mit Email werden angezeigt
- [ ] Email-Spalte zeigt Werte (keine "—")

#### 2.5 Detail-Ansicht - Email anzeigen
**Schritt 1**: Klicken Sie auf eine Anfrage MIT Email

**Erwartete Anzeige**:
- [ ] Sektion "Hauptinformationen" zeigt:
  - **E-Mail (Callback)**: max.mustermann@example.com
  - Helper-Text: "Für Terminbestätigungen"
  - Email ist kopierbar

- [ ] Wenn Kunde verknüpft ist, zeigt auch:
  - **E-Mail (Kunde)**: kunde@example.com
  - Helper-Text: "Aus Kundenprofil"

**Unterschied verstehen**:
- **E-Mail (Callback)**: Direkt in Callback-Request gespeichert (NEU!)
- **E-Mail (Kunde)**: Aus Customer-Model (alt)

---

### Test 3️⃣: CallForwardingConfigurationResource (Anrufweiterleitung)

**Navigation**: Einstellungen → Anrufweiterleitung

**Status**: ✅ Komplett neue Resource (Phase 4)

#### 3.1 Liste anzeigen ✅
- [ ] Öffnen Sie "Anrufweiterleitung"
- [ ] Erwartung: Liste ist leer ODER zeigt existierende Configs

**Badge-Anzeige**:
- [ ] Navigation-Badge zeigt Anzahl aktiver Weiterleitungen
- [ ] Badge ist grün (success)

#### 3.2 Neue Weiterleitungs-Konfiguration erstellen

**Schritt 1**: Klicken Sie auf "+ Neue Weiterleitung"

**Erwartetes Formular mit 4 Sektionen**:

---

**Sektion 1: Basis-Einstellungen**
```
✅ Filiale: Wählen Sie eine Filiale (Dropdown, required, unique!)
✅ Aktiviert: AN (Toggle, grün)
✅ Zeitzone: Europe/Berlin (Dropdown)
```

**Wichtig**: Jede Filiale kann nur EINE Weiterleitungs-Konfiguration haben!

---

**Sektion 2: Weiterleitungsregeln** (REPEATER!)

**Schritt A**: Klicken Sie auf "+ Weiterleitungsregel hinzufügen"

**Erwartete Felder im Repeater-Item**:
```
Auslöser: Dropdown mit 5 Optionen
  - 📅 Keine Verfügbarkeit
  - 🕐 Außerhalb Öffnungszeiten
  - ❌ Buchung fehlgeschlagen
  - 📞 Hohe Anruflast
  - ✋ Manuell

Ziel-Nummer: Textfeld (Tel-Input)
  - Placeholder: +49151123456789
  - Helper: "E.164 Format (z.B. +4915112345678)"
  - Validierung: Regex /^\+[1-9]\d{1,14}$/

Priorität: Zahl (min: 1, default: 1)
  - Helper: "Niedrigere Zahl = höhere Priorität"

Zusätzliche Bedingungen: KeyValue (Optional)
  - Beispiel: day: monday, time_after: 18:00
```

**Schritt B**: Füllen Sie die erste Regel aus:
```
Auslöser: 📅 Keine Verfügbarkeit
Ziel-Nummer: +4915112345678
Priorität: 1
```

**Schritt C**: Klicken Sie erneut auf "+ Weiterleitungsregel hinzufügen"

**Schritt D**: Füllen Sie die zweite Regel aus:
```
Auslöser: 🕐 Außerhalb Öffnungszeiten
Ziel-Nummer: +4915198765432
Priorität: 2
```

**Erwartete Repeater-Funktionen**:
- [ ] Items sind **reorderbar** (Drag & Drop)
- [ ] Items sind **collapsible** (einklappbar)
- [ ] **Item-Label** zeigt: "📅 Keine Verfügbarkeit → +4915112345678"
- [ ] Min 1 Item, Max 10 Items
- [ ] Delete-Button pro Item

---

**Sektion 3: Fallback-Nummern** (COLLAPSED - aufklappen!)

**Schritt**: Klappen Sie die Sektion auf

**Erwartete Felder**:
```
Standard-Weiterleitungsnummer: (optional)
  - Tel-Input mit E.164 Validierung
  - Helper: "Fallback wenn keine Regel greift"
  - Icon: Phone

Notfall-Weiterleitungsnummer: (optional)
  - Tel-Input mit E.164 Validierung
  - Helper: "Bei kritischen Fehlern"
  - Icon: Phone-X-Mark
```

**Schritt**: Füllen Sie aus:
```
Standard-Weiterleitungsnummer: +4989123456
Notfall-Weiterleitungsnummer: +4989654321
```

---

**Sektion 4: Aktive Zeiten** (COLLAPSED - aufklappen!)

**Schritt**: Klappen Sie die Sektion auf

**Erwartete Anzeige**:
- [ ] **Info-Platzhalter** mit Beispiel-JSON:
  ```json
  {
    "monday": ["09:00-17:00"],
    "tuesday": ["09:00-17:00"],
    "friday": ["09:00-15:00"]
  }
  ```

- [ ] **Textarea** für JSON-Eingabe
  - Validierung: JSON-Format
  - Helper: "JSON-Format: {\"weekday\": [\"HH:MM-HH:MM\"]}"

**Schritt**: Lassen Sie das Feld leer (= 24/7 aktiv)

---

**Schritt 5**: Speichern Sie die Konfiguration

**Erwartete Ergebnisse**:
- [ ] Konfiguration wird gespeichert
- [ ] Erfolgsmeldung: "Anrufweiterleitung erfolgreich erstellt"
- [ ] Weiterleitung zur Detail-Ansicht

#### 3.3 Telefonnummer-Validierung testen (WICHTIG!)

**Schritt 1**: Erstellen Sie eine neue Weiterleitungs-Konfiguration

**Schritt 2**: Fügen Sie eine Regel hinzu mit **UNGÜLTIGER** Nummer:
```
Ziel-Nummer: 0151123456 (ohne +, falsch!)
```

**Schritt 3**: Versuchen Sie zu speichern

**Erwartete Fehlermeldung**:
- [ ] ❌ Rote Fehler-Box unter dem Feld
- [ ] Text: "Bitte geben Sie eine gültige Telefonnummer im E.164 Format ein (z.B. +4915112345678)."

**Schritt 4**: Korrigieren Sie die Nummer:
```
Ziel-Nummer: +4915112345678 (mit +, korrekt!)
```

**Erwartete Ergebnisse**:
- [ ] ✅ Fehler verschwindet
- [ ] Speichern erfolgreich

#### 3.4 Unique Branch Constraint testen

**Schritt 1**: Erstellen Sie eine Weiterleitungs-Konfiguration für "Filiale München"

**Schritt 2**: Versuchen Sie, eine ZWEITE Konfiguration für "Filiale München" zu erstellen

**Erwartete Fehlermeldung**:
- [ ] ❌ "Für diese Filiale existiert bereits eine Weiterleitungs-Konfiguration."
- [ ] Speichern wird blockiert

#### 3.5 Detail-Ansicht testen

**Schritt 1**: Klicken Sie auf eine Weiterleitungs-Konfiguration in der Liste

**Erwartete Anzeige** (5 Sektionen):

**Sektion 1: Hauptinformationen**
- [ ] ID (Badge)
- [ ] Filiale: "München" (fett, mit Icon)
- [ ] Status: "Aktiv" (grünes Badge mit Häkchen)
- [ ] Zeitzone: "Europe/Berlin" (Badge)

**Sektion 2: Weiterleitungsregeln**
- [ ] Repeater-Anzeige mit allen Regeln:
  ```
  Regel 1:
    Auslöser: "📅 Keine Verfügbarkeit" (blaues Badge)
    Ziel-Nummer: "+4915112345678" (kopierbar)
    Priorität: "1" (gelbes Badge)
    Bedingungen: "day: monday" oder "—"
  ```

**Sektion 3: Fallback-Nummern** (nur sichtbar wenn gesetzt)
- [ ] Standard: "+4989123456" (kopierbar)
- [ ] Notfall: "+4989654321" (kopierbar)

**Sektion 4: Aktive Zeiten**
- [ ] Wenn leer: "24/7 aktiv"
- [ ] Wenn gesetzt: Formatiert angezeigt (Markdown)
  ```
  **monday**: 09:00-17:00
  **tuesday**: 09:00-17:00
  ```

**Sektion 5: Zeitstempel**
- [ ] Erstellt am: "14.11.2025 10:00" (+ "vor 2 Stunden")
- [ ] Aktualisiert am: "14.11.2025 10:00" (+ "vor 2 Stunden")

#### 3.6 Tabelle testen

**Schritt 1**: Gehen Sie zurück zur Liste

**Erwartete Spalten**:
- [ ] **ID**: Badge
- [ ] **Filiale**: Name (fett, Icon, suchbar)
- [ ] **Regeln**: Badge mit Anzahl (z.B. "2", blaues Badge)
- [ ] **Standard-Nummer**: "+4989123456" (kopierbar) oder "—"
- [ ] **Aktiv**: Grünes Häkchen oder rotes X
- [ ] **Zeitzone**: Badge (toggleable, hidden by default)
- [ ] **Erstellt am**: Datum (toggleable, hidden by default)

**Schritt 2**: Sortieren Sie nach "Regeln" (Spalten-Header klicken)

**Erwartete Ergebnisse**:
- [ ] Sortierung nach Anzahl der Regeln funktioniert

#### 3.7 Filter testen

**Schritt 1**: Öffnen Sie die Filter

**Erwartete Filter (4 Filter)**:
- [ ] **Filiale**: Mehrfachauswahl, suchbar
- [ ] **Status**:
  - "Alle anzeigen"
  - "Nur aktive"
  - "Nur inaktive"
- [ ] **Regeln**:
  - "Alle anzeigen"
  - "Mit Regeln"
  - "Ohne Regeln"
- [ ] **Fallback**:
  - "Alle anzeigen"
  - "Mit Fallback-Nummer"
  - "Ohne Fallback-Nummer"

**Schritt 2**: Filtern Sie nach "Nur aktive"

**Erwartete Ergebnisse**:
- [ ] Nur Konfigurationen mit grünem Häkchen werden angezeigt

#### 3.8 Actions testen

**Schritt 1**: Klicken Sie auf die 3-Punkte-Action-Gruppe einer Zeile

**Erwartete Actions**:
- [ ] **"Deaktivieren"** (wenn aktiv) oder **"Aktivieren"** (wenn inaktiv)
  - Icon ändert sich (X-Circle vs. Check-Circle)
  - Farbe ändert sich (danger vs. success)

- [ ] **"Zu anderer Filiale kopieren"**
  - Öffnet Modal
  - Zeigt nur Filialen OHNE existierende Konfiguration
  - Kopiert alle Regeln

- [ ] **"Ansehen"**: Öffnet Detail-Ansicht
- [ ] **"Bearbeiten"**: Öffnet Edit-Formular
- [ ] **"Löschen"**: Soft Delete mit Bestätigung

**Schritt 2**: Klicken Sie auf "Deaktivieren"

**Erwartete Ergebnisse**:
- [ ] Bestätigungsdialog erscheint
- [ ] Nach Bestätigung: Status ändert sich auf inaktiv (rotes X)
- [ ] Erfolgsmeldung: "Weiterleitung deaktiviert"

**Schritt 3**: Klicken Sie auf "Zu anderer Filiale kopieren"

**Erwartete Anzeige**:
- [ ] Modal öffnet sich
- [ ] Dropdown "Ziel-Filiale" zeigt nur verfügbare Filialen
- [ ] Nach Auswahl und Speichern: Neue Konfiguration wird erstellt

#### 3.9 Bulk Actions testen

**Schritt 1**: Wählen Sie 2-3 Konfigurationen aus (Checkboxen)

**Schritt 2**: Klicken Sie auf "Massenaktionen" (oben)

**Erwartete Bulk Actions**:
- [ ] **Aktivieren**: Alle ausgewählten auf aktiv setzen
- [ ] **Deaktivieren**: Alle ausgewählten auf inaktiv setzen
- [ ] **Löschen (Soft Delete)**: Mit Bestätigung
- [ ] **Endgültig löschen**: Nur für Super Admin, mit Bestätigung
- [ ] **Wiederherstellen**: Für gelöschte Einträge

**Schritt 3**: Klicken Sie auf "Aktivieren"

**Erwartete Ergebnisse**:
- [ ] Bestätigungsdialog
- [ ] Nach Bestätigung: Alle ausgewählten auf aktiv (grünes Häkchen)
- [ ] Erfolgsmeldung: "Weiterleitungen aktiviert"
- [ ] Checkboxen werden deselektiert

#### 3.10 Edit testen

**Schritt 1**: Bearbeiten Sie eine existierende Konfiguration

**Schritt 2**: Fügen Sie eine dritte Regel hinzu im Repeater

**Schritt 3**: Speichern Sie

**Erwartete Ergebnisse**:
- [ ] Änderungen werden gespeichert
- [ ] Erfolgsmeldung: "Anrufweiterleitung erfolgreich aktualisiert"
- [ ] Weiterleitung zur Detail-Ansicht
- [ ] Neue Regel wird angezeigt

---

## 🎓 Berechtigungs-Tests (Super Admin)

### Als Super Admin sollten Sie ALLE Actions sehen:

#### PolicyConfigurationResource
- [x] ✅ Liste anzeigen (viewAny)
- [x] ✅ Details anzeigen (view)
- [x] ✅ Erstellen (create)
- [x] ✅ Bearbeiten (update)
- [x] ✅ Löschen (delete)
- [x] ✅ Wiederherstellen (restore)
- [x] ✅ Endgültig löschen (forceDelete)

#### CallbackRequestResource
- [x] ✅ Liste anzeigen (viewAny)
- [x] ✅ Details anzeigen (view)
- [x] ✅ Erstellen (create)
- [x] ✅ Bearbeiten (update)
- [x] ✅ Löschen (delete)

#### CallForwardingConfigurationResource
- [x] ✅ Liste anzeigen (viewAny)
- [x] ✅ Details anzeigen (view)
- [x] ✅ Erstellen (create)
- [x] ✅ Bearbeiten (update)
- [x] ✅ Löschen (delete)
- [x] ✅ Wiederherstellen (restore)
- [x] ✅ Endgültig löschen (forceDelete)

**Berechtigung-Logik**:
```php
// Super Admin bekommt IMMER Zugriff (before() Methode in allen Policies)
if ($user->hasRole('super_admin')) {
    return true; // ✅ Alle Operationen erlaubt
}
```

---

## 🐛 Bekannte Issues / Workarounds

### Issue 1: Repeater-Items nicht reorderbar
**Symptom**: Drag & Drop funktioniert nicht
**Workaround**: Delete + neu hinzufügen in gewünschter Reihenfolge
**Status**: Filament 3.x Standardverhalten (sollte funktionieren)

### Issue 2: Email-Validierung zu streng
**Symptom**: Gültige Emails werden abgelehnt
**Workaround**: Temporär deaktivieren und später testen
**Status**: Sollte nicht auftreten (Standard email() Regel)

### Issue 3: Unique Branch Constraint nicht greift
**Symptom**: Zweite Config für gleiche Filiale kann erstellt werden
**Workaround**: Manuelle Prüfung vor dem Erstellen
**Status**: Sollte nicht auftreten (DB-Level Constraint)

---

## ✅ Erwartete Test-Ergebnisse

### Minimale Anforderungen (MUSS funktionieren):
- [ ] Alle 3 Resources im Menü sichtbar
- [ ] PolicyConfigurationResource: Alle 11 Typen in Dropdown
- [ ] PolicyConfigurationResource: Form-Felder ändern sich je nach Typ
- [ ] CallbackRequestResource: Email-Feld sichtbar und speicherbar
- [ ] CallForwardingConfigurationResource: Repeater funktioniert
- [ ] CallForwardingConfigurationResource: E.164 Validierung funktioniert

### Erweiterte Funktionen (SOLLTE funktionieren):
- [ ] Filter funktionieren für alle Resources
- [ ] Sortierung funktioniert
- [ ] Bulk Actions funktionieren
- [ ] Kopierfunktion funktioniert (Email, Telefonnummer)
- [ ] Clone to Branch funktioniert
- [ ] Toggle Active/Inactive funktioniert

### Nice-to-Have (KANN funktionieren):
- [ ] Repeater Drag & Drop Reordering
- [ ] Conditional Field Visibility (sollte funktionieren)
- [ ] Dynamic Item Labels im Repeater (sollte funktionieren)

---

## 📊 Bug-Report-Template

Falls Sie einen Fehler finden, bitte so dokumentieren:

```markdown
## Bug: [Kurze Beschreibung]

**Resource**: PolicyConfigurationResource / CallbackRequestResource / CallForwardingConfigurationResource
**Schritt**: [Welcher Test-Schritt]
**Erwartung**: [Was sollte passieren]
**Tatsächlich**: [Was passiert tatsächlich]
**Screenshot**: [Falls möglich]
**Browser**: Chrome / Firefox / Safari
**Console Errors**: [F12 → Console → Copy Error]

**Reproduzierbar**: Ja / Nein
**Schritte zum Reproduzieren**:
1. ...
2. ...
3. ...
```

---

## 🎯 Kritische Test-Punkte

**Priorität 1 (KRITISCH)**:
1. ✅ E.164 Telefonnummer-Validierung (Sicherheit!)
2. ✅ Unique Branch Constraint (Datenintegrität!)
3. ✅ Super Admin kann ALLES sehen/bearbeiten
4. ✅ Email-Feld speichert korrekt in DB

**Priorität 2 (WICHTIG)**:
5. ✅ Repeater funktioniert (min 1, max 10)
6. ✅ Conditional Fields erscheinen korrekt
7. ✅ Filter und Suche funktionieren

**Priorität 3 (NICE-TO-HAVE)**:
8. Drag & Drop Reordering
9. Clone to Branch
10. Bulk Actions

---

**Viel Erfolg beim Testen! 🚀**

Bei Fragen oder Problemen: Dokumentieren Sie den Bug wie oben beschrieben.
