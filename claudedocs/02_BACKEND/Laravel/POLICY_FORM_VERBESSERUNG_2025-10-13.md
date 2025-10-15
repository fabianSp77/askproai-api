# Policy-Formular Verbesserung - Benutzerfreundliche Dropdowns
**Datum:** 2025-10-13
**Priorität:** 🟢 Verbesserung
**Status:** ✅ **FERTIG**

---

## 🎯 ZIEL

User-Anfrage:
> "Kannst du mir in den Konfiguration da, wo man die einzelnen Richtlinien einstellen kann die Möglichkeiten als Dropdown oder Auswahl zur Verfügung stellen mit einer kurzen Erklärung, so dass ich es leichter habe, diese einzustellen."

**Vorher:** Kompliziertes KeyValue-Feld mit JSON-Eingabe
**Nachher:** Benutzerfreundliche Dropdowns mit klaren Erklärungen und Empfehlungen

---

## ✅ IMPLEMENTIERTE VERBESSERUNGEN

### 1. **Dynamische Felder basierend auf Policy-Typ**

Wenn Sie einen **Richtlinientyp** auswählen, erscheinen automatisch die passenden Felder:

#### 🚫 **Stornierung (Cancellation)**

**4 Felder mit Dropdowns:**

1. **⏰ Mindestvorlauf für Stornierung**
   - Dropdown mit Optionen: 1h, 2h, 4h, 8h, 12h, 24h, 48h, 72h, 1 Woche
   - Standard: 24 Stunden
   - Empfehlung: "Wie früh muss der Kunde absagen? **Empfehlung: 24 Stunden**"

2. **🔢 Maximale Stornierungen pro Monat**
   - Dropdown: 1, 2, 3, 5, 10, Unbegrenzt
   - Standard: 5
   - Empfehlung: "Wie oft darf ein Kunde pro Monat stornieren? **Empfehlung: 3-5 Stornierungen**"

3. **💰 Stornogebühr (Prozent vom Terminpreis)**
   - Dropdown: 0%, 10%, 25%, 50%, 75%, 100%
   - Standard: 0% (kostenlos)
   - Empfehlung: "**Empfehlung: 0% (kostenlos) oder 50%**"

4. **💵 Fixe Stornogebühr (in Euro)** *(Optional)*
   - Textfeld mit €-Symbol
   - Platzhalter: "z.B. 15"
   - Hinweis: "Feste Gebühr in Euro (zusätzlich oder statt Prozent). Leer lassen = keine fixe Gebühr"

---

#### 🔄 **Umbuchung (Reschedule)**

**4 Felder mit Dropdowns:**

1. **⏰ Mindestvorlauf für Umbuchung**
   - Dropdown: 1h, 2h, 4h, 8h, 12h, 24h, 48h, 72h
   - Standard: 12 Stunden
   - Empfehlung: "**Empfehlung: 12-24 Stunden**"

2. **🔄 Maximale Umbuchungen pro Termin**
   - Dropdown: 1x, 2x, 3x, 5x, Unbegrenzt
   - Standard: 3
   - Empfehlung: "**Empfehlung: 2-3 Umbuchungen**"

3. **💰 Umbuchungsgebühr (Prozent vom Terminpreis)**
   - Dropdown: 0%, 10%, 25%, 50%
   - Standard: 0% (kostenlos)
   - Empfehlung: "**Empfehlung: 0% (kostenlos) oder 10-25%**"

4. **💵 Fixe Umbuchungsgebühr (in Euro)** *(Optional)*
   - Textfeld mit €-Symbol
   - Platzhalter: "z.B. 10"
   - Hinweis: "Feste Gebühr in Euro (zusätzlich oder statt Prozent). Leer lassen = keine fixe Gebühr"

---

#### 🔁 **Wiederkehrend (Recurring)**

**2 Felder mit Dropdowns:**

1. **🔁 Wiederholungsfrequenz**
   - Dropdown: Täglich, Wöchentlich, Alle 2 Wochen, Monatlich
   - Standard: Wöchentlich
   - Hinweis: "Wie oft soll der Termin wiederholt werden?"

2. **🔢 Maximale Wiederholungen**
   - Dropdown: 5, 10, 20, 52 (1 Jahr wöchentlich), Unbegrenzt
   - Standard: 10
   - Hinweis: "Wie viele Termine maximal erstellen?"

---

## 🎨 BENUTZERFREUNDLICHKEIT

### **Vorher (Kompliziert):**
```
Konfiguration:
┌─────────────────────────────────┐
│ Einstellung          │ Wert     │
├──────────────────────┼──────────┤
│ hours_before         │ 24       │  ← User musste Namen kennen
│ max_cancellations... │ 5        │  ← Tippfehler möglich
│ fee_percentage       │ 0        │  ← Keine Erklärung
└─────────────────────────────────┘
```

### **Nachher (Benutzerfreundlich):**
```
⏰ Mindestvorlauf für Stornierung:
┌─────────────────────────────────────┐
│ ▼ 24 Stunden (1 Tag) vorher         │  ← Dropdown mit klaren Optionen
└─────────────────────────────────────┘
💡 Wie früh muss der Kunde absagen? Empfehlung: 24 Stunden

🔢 Maximale Stornierungen pro Monat:
┌─────────────────────────────────────┐
│ ▼ 5 Stornierungen pro Monat         │  ← Lesbare Optionen
└─────────────────────────────────────┘
💡 Wie oft darf ein Kunde pro Monat stornieren? Empfehlung: 3-5 Stornierungen
```

---

## ⚙️ TECHNISCHE DETAILS

### Änderungen in `PolicyConfigurationResource.php`:

**Zeilen 95-283:**
- KeyValue-Feld entfernt
- Policy-Type Select auf `->live()` gesetzt (Echtzeit-Updates)
- 3 separate Grid-Gruppen erstellt (Cancellation, Reschedule, Recurring)
- Jede Gruppe mit `->visible()` basierend auf `policy_type`
- Alle Felder mit `config.` Präfix (z.B. `config.hours_before`)
- Filament speichert automatisch als JSON in `config` Spalte

### Validierung:
- Alle wichtigen Felder als `->required()` markiert
- Numerische Felder mit `->numeric()`
- Dropdown-Felder mit `->native(false)` (moderne UI)
- Default-Werte gesetzt für schnellere Eingabe

---

## 📊 VERFÜGBARE OPTIONEN

### Vorlaufzeiten (hours_before):
- 1, 2, 4, 8, 12, 24, 48, 72, 168 Stunden

### Limits:
- Stornierungen/Monat: 1, 2, 3, 5, 10, 999 (unbegrenzt)
- Umbuchungen/Termin: 1, 2, 3, 5, 999 (unbegrenzt)
- Wiederholungen: 5, 10, 20, 52, 999 (unbegrenzt)

### Gebühren:
- Prozentual: 0%, 10%, 25%, 50%, 75%, 100%
- Fix: Beliebiger Euro-Betrag (optional)

### Frequenzen (Recurring):
- Täglich, Wöchentlich, Alle 2 Wochen, Monatlich

---

## 🧪 TESTEN

### So testen Sie die neue Oberfläche:

1. **Neue Policy erstellen:**
   - Gehen Sie zu: https://api.askproai.de/admin/policy-configurations/create

2. **Stornierungsrichtlinie testen:**
   - Wählen Sie "Unternehmen" → AskProAI
   - Wählen Sie "🚫 Stornierung"
   - → Formular zeigt automatisch 4 Felder mit Dropdowns

3. **Umbuchungsrichtlinie testen:**
   - Wählen Sie "🔄 Umbuchung"
   - → Formular zeigt automatisch andere 4 Felder

4. **Policy bearbeiten:**
   - Klicken Sie auf bestehende Policy #15 oder #16
   - → Felder werden automatisch mit gespeicherten Werten befüllt
   - → Dropdown zeigt aktuell ausgewählten Wert

---

## ✅ VORTEILE

### Für den User:
- ✅ **Keine Tippfehler** mehr bei Feldnamen
- ✅ **Klare Optionen** statt freie Zahleneingabe
- ✅ **Empfehlungen** direkt bei jedem Feld
- ✅ **Visuell übersichtlich** mit Emojis und Gruppierung
- ✅ **Schneller** durch Default-Werte
- ✅ **Sicher** durch Validierung

### Für das System:
- ✅ **Konsistente Daten** (nur erlaubte Werte)
- ✅ **Validierte Eingaben** (keine Strings wo Zahlen erwartet)
- ✅ **Gleiche Datenstruktur** wie vorher (config JSON)
- ✅ **Abwärtskompatibel** zu bestehenden Policies

---

## 🎯 BEISPIEL-WORKFLOWS

### **Workflow 1: Standard-Stornierungsrichtlinie erstellen**
```
1. Wähle: Unternehmen → AskProAI
2. Wähle: 🚫 Stornierung
3. Wähle: ⏰ 24 Stunden vorher
4. Wähle: 🔢 5 Stornierungen pro Monat
5. Wähle: 💰 Kostenlos (0%)
6. Lasse: 💵 Fixe Gebühr leer
7. Klicke: Speichern

→ Gespeichert als: {hours_before: 24, max_cancellations_per_month: 5, fee_percentage: 0}
```

### **Workflow 2: Premium-Service mit Gebühren**
```
1. Wähle: Service → VIP-Beratung
2. Wähle: 🚫 Stornierung
3. Wähle: ⏰ 48 Stunden vorher
4. Wähle: 🔢 2 Stornierungen pro Monat
5. Wähle: 💰 50% Gebühr
6. Gib ein: 💵 25 € (fixe Mindestgebühr)
7. Klicke: Speichern

→ Gespeichert als: {hours_before: 48, max_cancellations_per_month: 2, fee_percentage: 50, fee: 25}
```

---

## 🔗 RELATED

**Admin-Interface:** https://api.askproai.de/admin/policy-configurations

**Dokumentation:**
- Policy-Guide: `/claudedocs/POLICY_CONFIGURATION_GUIDE_2025-10-13.md`
- Benutzerhandbuch: `/claudedocs/POLICY_ADMIN_BENUTZERHANDBUCH_2025-10-13.md`
- Widget-Fixes: `/claudedocs/POLICY_WIDGET_ERRORS_FIXED_2025-10-13.md`

**Code:**
- Resource: `app/Filament/Resources/PolicyConfigurationResource.php` (Zeilen 95-283)
- Model: `app/Models/PolicyConfiguration.php`

---

**Erstellt:** 2025-10-13 15:15 UTC
**Status:** ✅ EINSATZBEREIT
**User-Feedback:** Ausstehend
