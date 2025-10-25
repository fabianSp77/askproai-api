# Admin-Guide: Policy-Konfiguration

**Datum:** 2025-10-25
**System:** AskPro AI Gateway - Stornierung & Verschiebung
**Zielgruppe:** Administratoren

---

## 📋 ÜBERSICHT

Dieser Guide zeigt Schritt-für-Schritt, wie Sie Stornierung- und Verschiebungsregeln im Admin-Panel konfigurieren.

**Admin-URL:** https://api.askproai.de/admin/policy-configurations

---

## 🎯 WAS KANN KONFIGURIERT WERDEN?

### Stornierung-Richtlinien (Cancellation)

| Parameter | Beschreibung | Werte |
|-----------|--------------|-------|
| **Mindestvorlauf** | Wie viele Stunden vor Termin muss storniert werden | 1h bis 168h (1 Woche) |
| **Max. Stornierungen pro Monat** | Wie oft darf ein Kunde pro Monat stornieren | 1 bis unbegrenzt |
| **Prozentuale Gebühr** | % vom Terminpreis als Gebühr | 0% bis 100% |
| **Fixe Gebühr** | Fester Betrag in Euro | 0€ bis beliebig |

### Verschiebung-Richtlinien (Reschedule)

| Parameter | Beschreibung | Werte |
|-----------|--------------|-------|
| **Mindestvorlauf** | Wie viele Stunden vor Termin muss verschoben werden | 1h bis 72h (3 Tage) |
| **Max. Verschiebungen pro Termin** | Wie oft darf ein Termin verschoben werden | 1x bis unbegrenzt |
| **Prozentuale Gebühr** | % vom Terminpreis als Gebühr | 0% bis 50% |
| **Fixe Gebühr** | Fester Betrag in Euro | 0€ bis beliebig |

---

## 🏢 POLICY-HIERARCHIE

Policies werden in dieser Reihenfolge geprüft (spezifischste gewinnt):

```
1. Mitarbeiter (Staff)      ← Höchste Priorität
   ↓
2. Service (z.B. Herrenhaarschnitt)
   ↓
3. Filiale (Branch)
   ↓
4. Unternehmen (Company)    ← Niedrigste Priorität (Standard)
```

**Beispiel:**
- Company-Policy: Stornierung 24h vorher
- Service-Policy für "Dauerwelle": Stornierung 48h vorher
- **Ergebnis:** Bei "Dauerwelle" gilt 48h, bei allen anderen Services 24h

---

## 📝 SCHRITT-FÜR-SCHRITT: POLICY ERSTELLEN

### Vorbereitung

1. **Einloggen:**
   ```
   URL: https://api.askproai.de/admin
   ```

2. **Navigation:**
   ```
   Seitenleiste → "Termine & Richtlinien" → "Stornierung & Umbuchung"
   ```

3. **Neue Policy erstellen:**
   ```
   Klick auf: "+ Neue Richtlinie"
   ```

---

### Schritt 1: Entität auswählen

**Frage:** Für wen soll die Policy gelten?

**Optionen:**

```
[Dropdown: "Entität"]
├─ 🏢 Unternehmen (Company)     → Gilt für ALLE Filialen/Services
├─ 🏪 Filiale (Branch)          → Gilt für EINE Filiale
├─ ✂️ Service                   → Gilt für EINEN Service (z.B. Herrenhaarschnitt)
└─ 👤 Mitarbeiter (Staff)       → Gilt für EINEN Mitarbeiter
```

**Empfehlung für Start:**
- **Unternehmen** wählen (gilt als Standard für alles)
- Später spezifischere Policies für Services/Filialen erstellen

---

### Schritt 2: Richtlinientyp wählen

**Frage:** Was soll geregelt werden?

**Optionen:**

```
[Dropdown: "Richtlinientyp"]
├─ 🚫 Stornierung (Cancellation)     → Wann dürfen Kunden absagen?
├─ 🔄 Umbuchung (Reschedule)         → Wann dürfen Kunden verschieben?
└─ 🔁 Wiederkehrend (Recurring)      → Serien-Termine (optional)
```

**Empfehlung:**
- Starten Sie mit **Stornierung**
- Dann **Umbuchung** separat konfigurieren

---

### Schritt 3: Stornierung-Regeln konfigurieren

**Wenn "Stornierung" gewählt:**

#### 3.1 Mindestvorlauf (Required)

```
[Zahlfeld: "Mindestvorlauf (Stunden)"]
Eingabe: 24

✅ Bedeutung: Kunde muss mindestens 24 Stunden vor Termin stornieren
❌ Weniger als 24h vorher → Stornierung NICHT möglich
```

**Gängige Werte:**
- **12h**: Kurzfristige Termine (z.B. Haarschnitt)
- **24h**: Standard (empfohlen)
- **48h**: Bei langer Vorbereitung (z.B. Dauerwelle, Färbung)
- **72h**: Bei teuren Services

#### 3.2 Max. Stornierungen pro Monat (Optional)

```
[Zahlfeld: "Max. Stornierungen pro Monat"]
Eingabe: 3

✅ Bedeutung: Kunde darf max. 3x pro Monat stornieren
❌ Bei 4. Versuch → Stornierung NICHT möglich
```

**Gängige Werte:**
- **Leer lassen**: Unbegrenzt (empfohlen für Start)
- **3**: Verhindert Missbrauch
- **1**: Sehr streng (nur für problematische Kunden)

#### 3.3 Gebühren (Optional)

**Prozentuale Gebühr:**
```
[Zahlfeld: "Stornierungsgebühr (%)"]
Eingabe: 0

✅ 0% = Kostenlos
✅ 50% = Halber Preis
✅ 100% = Voller Preis
```

**Fixe Gebühr:**
```
[Zahlfeld: "Fixe Stornierungsgebühr (€)"]
Eingabe: 10.00

✅ Bedeutung: Immer 10€ Gebühr (unabhängig vom Service-Preis)
```

**Empfehlung für Start:**
- **Prozentual: 0%** (keine Gebühr)
- **Fix: leer** (keine Gebühr)
- Später bei Bedarf anpassen

---

### Schritt 4: Verschiebung-Regeln konfigurieren

**Wenn "Umbuchung" gewählt:**

#### 4.1 Mindestvorlauf (Required)

```
[Zahlfeld: "Mindestvorlauf (Stunden)"]
Eingabe: 12

✅ Bedeutung: Kunde muss mindestens 12 Stunden vor Termin verschieben
❌ Weniger als 12h vorher → Verschiebung NICHT möglich
```

**Gängige Werte:**
- **6h**: Sehr flexibel
- **12h**: Standard (empfohlen)
- **24h**: Konservativ

#### 4.2 Max. Verschiebungen pro Termin (Optional)

```
[Zahlfeld: "Max. Verschiebungen pro Termin"]
Eingabe: 2

✅ Bedeutung: Ein Termin darf max. 2x verschoben werden
❌ Bei 3. Versuch → Verschiebung NICHT möglich
```

**Gängige Werte:**
- **Leer lassen**: Unbegrenzt (empfohlen für Start)
- **2**: Verhindert endloses Verschieben
- **1**: Streng (nur 1x verschieben erlaubt)

#### 4.3 Gebühren (Optional)

**Prozentuale Gebühr:**
```
[Zahlfeld: "Verschiebungsgebühr (%)"]
Eingabe: 0

✅ 0% = Kostenlos
✅ 25% = Viertel Preis
✅ 50% = Halber Preis
```

**Fixe Gebühr:**
```
[Zahlfeld: "Fixe Verschiebungsgebühr (€)"]
Eingabe: 5.00

✅ Bedeutung: Immer 5€ Gebühr
```

**Empfehlung für Start:**
- **Prozentual: 0%** (keine Gebühr)
- **Fix: leer** (keine Gebühr)

---

### Schritt 5: Speichern & Aktivieren

```
[Button: "Speichern"]

✅ Policy ist SOFORT aktiv
✅ Gilt für alle neuen Anfragen
✅ Bestandstermine: Nicht rückwirkend geändert
```

---

## 🎯 BEISPIEL-KONFIGURATIONEN

### Beispiel 1: Friseur1 - Standard Policy (Company-Wide)

**Ziel:** Faire Regelung für alle Filialen und Services

```yaml
Entität: Unternehmen (Friseur1)
Typ: Stornierung

Regeln:
  Mindestvorlauf: 24 Stunden
  Max. Stornierungen/Monat: 3
  Prozentuale Gebühr: 0%
  Fixe Gebühr: (leer)

Ergebnis:
  ✅ Kunde kann bis 24h vorher kostenlos stornieren
  ✅ Max. 3x pro Monat
  ❌ Bei kurzfristigerem Versuch: "Stornierung nicht möglich"
```

```yaml
Entität: Unternehmen (Friseur1)
Typ: Umbuchung

Regeln:
  Mindestvorlauf: 12 Stunden
  Max. Verschiebungen/Termin: 2
  Prozentuale Gebühr: 0%
  Fixe Gebühr: (leer)

Ergebnis:
  ✅ Kunde kann bis 12h vorher kostenlos verschieben
  ✅ Max. 2x pro Termin
  ❌ Bei 3. Versuch: "Verschiebung nicht möglich"
```

---

### Beispiel 2: Service-spezifisch - Dauerwelle (höhere Vorlaufzeit)

**Ziel:** Bei aufwändigen Services mehr Vorlaufzeit verlangen

```yaml
Entität: Service (Dauerwelle)
Typ: Stornierung

Regeln:
  Mindestvorlauf: 48 Stunden
  Max. Stornierungen/Monat: (leer - unbegrenzt)
  Prozentuale Gebühr: 50%
  Fixe Gebühr: (leer)

Hierarchie:
  Service-Policy (48h) ÜBERSCHREIBT Company-Policy (24h)

Ergebnis:
  ✅ Dauerwelle: 48h Vorlauf erforderlich, 50% Gebühr bei Stornierung
  ✅ Herrenhaarschnitt: 24h Vorlauf (Company-Policy greift)
```

---

### Beispiel 3: Filial-spezifisch - Flagship Store (strenger)

**Ziel:** In Hauptfiliale strengere Regeln

```yaml
Entität: Filiale (Berlin Mitte)
Typ: Stornierung

Regeln:
  Mindestvorlauf: 48 Stunden
  Max. Stornierungen/Monat: 2
  Prozentuale Gebühr: 0%
  Fixe Gebühr: 15.00€

Hierarchie:
  Branch-Policy (48h) ÜBERSCHREIBT Company-Policy (24h)

Ergebnis:
  ✅ Berlin Mitte: 48h Vorlauf, max 2x/Monat, 15€ Gebühr
  ✅ Alle anderen Filialen: 24h Vorlauf, 3x/Monat (Company-Policy)
```

---

## 🔍 POLICIES PRÜFEN & BEARBEITEN

### Alle Policies anzeigen

```
Navigation: Admin → Stornierung & Umbuchung

Tabelle zeigt:
├─ Entität (z.B. "Friseur1" oder "Herrenhaarschnitt")
├─ Typ (Stornierung / Umbuchung)
├─ Mindestvorlauf
├─ Max. Limit
└─ Gebühren
```

### Policy bearbeiten

```
1. Klick auf Policy-Zeile
2. Werte ändern
3. [Button: "Speichern"]

✅ Änderungen SOFORT aktiv
```

### Policy löschen

```
1. Klick auf Policy-Zeile
2. [Button: "Löschen" (oben rechts)]
3. Bestätigung: "Ja, löschen"

✅ Policy entfernt
⚠️ Hierarchie greift jetzt auf nächst-höhere Policy zurück
```

---

## ⚠️ WICHTIGE HINWEISE

### Was passiert OHNE Policy?

**Wenn KEINE Policy konfiguriert ist:**

```
Stornierung:   ✅ Unbegrenzt erlaubt
Verschiebung:  ✅ Unbegrenzt erlaubt
Vorlaufzeit:   ⚠️ Nur: "Termin darf nicht in Vergangenheit liegen"
Gebühren:      ✅ Keine
```

**ABER:** Anonyme Anrufer können trotzdem NICHT stornieren/verschieben (Sicherheitsregel).

---

### Anonyme Anrufer vs. Bestandskunden

**Diese Regeln gelten UNABHÄNGIG von Policies:**

| Anrufer-Typ | Termin buchen | Termin stornieren | Termin verschieben |
|-------------|---------------|-------------------|--------------------|
| **Anonym** (keine Telefonnummer) | ✅ JA | ❌ NEIN | ❌ NEIN |
| **Bestandskunde** (mit Telefonnummer) | ✅ JA | ✅ JA (mit Policy) | ✅ JA (mit Policy) |

**Warum?**
- Anonyme Anrufer können nicht verifiziert werden
- Verhindert Missbrauch (fremde Termine stornieren)
- Bestandskunden werden via Telefonnummer + Filiale erkannt

---

### Rückwirkende Änderungen?

**NEIN:** Policies gelten NICHT rückwirkend.

```
Beispiel:
  1. Termin gebucht: 2025-10-25 (Policy: 24h Vorlauf)
  2. Policy geändert: 2025-10-26 (NEU: 48h Vorlauf)

  Frage: Welche Policy gilt für Termin von 2025-10-25?
  Antwort: 24h (Policy zum Buchungszeitpunkt)

  ⚠️ ABER: Bei Stornierungsversuch HEUTE wird NEUE Policy geprüft (48h)
```

**Best Practice:**
- Policies mit Bedacht ändern
- Kunden bei Änderungen informieren
- Übergangszeit einplanen

---

## 🧪 TESTEN DER KONFIGURATION

### Test 1: Policy erstellen & prüfen

```bash
1. Policy erstellen:
   - Entität: Friseur1
   - Typ: Stornierung
   - Vorlauf: 24h

2. Überprüfen in DB:
   cd /var/www/api-gateway
   php artisan tinker --execute="echo \App\Models\PolicyConfiguration::count();"

   ✅ Erwartung: Anzahl gestiegen (z.B. 4 → 5)
```

### Test 2: Hierarchie testen

```bash
1. Company-Policy: 24h Vorlauf
2. Service-Policy: 48h Vorlauf (für "Dauerwelle")

3. Testanruf:
   - Service: "Dauerwelle"
   - Versuche Stornierung 36h vorher

   ✅ Erwartung: "Stornierung nicht möglich" (Service-Policy greift)

4. Testanruf:
   - Service: "Herrenhaarschnitt"
   - Versuche Stornierung 20h vorher

   ✅ Erwartung: "Stornierung nicht möglich" (Company-Policy greift)
```

### Test 3: Anonyme Blockierung testen

```bash
1. Testanruf von UNTERDRÜCKTER Nummer
2. Termin buchen: ✅ Sollte funktionieren
3. Termin stornieren: ❌ Sollte NICHT funktionieren

   ✅ Erwartung: "Bitte rufen Sie uns zurück" (Redirect zu Callback)
```

---

## 📊 MONITORING

### Log-Einträge prüfen

**Erfolgreiche Policy-Prüfung:**
```bash
tail -f storage/logs/laravel.log | grep "Policy check"

# Erwartete Ausgabe:
# [2025-10-25] Policy check: can_cancel=true, hours_notice=36, required=24
```

**Policy-Verletzung:**
```bash
tail -f storage/logs/laravel.log | grep "Policy violation"

# Erwartete Ausgabe:
# [2025-10-25] Policy violation: Cancellation requires 24h notice, only 12h remain
```

**Anonyme Blockierung:**
```bash
tail -f storage/logs/laravel.log | grep "Anonymous caller tried"

# Erwartete Ausgabe:
# [2025-10-25] Anonymous caller tried to cancel - redirecting to callback
```

---

## 🔧 TROUBLESHOOTING

### Problem: Policy wird nicht angewendet

**Symptome:** Stornierung funktioniert trotz Policy

**Lösungen:**

```bash
1. Cache leeren:
   php artisan config:clear
   php artisan cache:clear

2. Policy-Konfiguration prüfen:
   php artisan tinker --execute="
     \$policy = \App\Models\PolicyConfiguration::latest()->first();
     dd(\$policy->toArray());
   "

3. Hierarchie prüfen:
   - Gibt es eine spezifischere Policy?
   - Service-Policy überschreibt Company-Policy
```

---

### Problem: UI zeigt Policy nicht an

**Symptome:** Policy in DB, aber nicht in Admin-UI

**Lösungen:**

```bash
1. Filament Cache leeren:
   php artisan filament:cache-clear

2. Browser-Cache leeren:
   Strg+Shift+R (Hard Reload)

3. Company-Filter prüfen:
   - Sind Sie im richtigen Unternehmen eingeloggt?
   - Policies sind company-specific
```

---

### Problem: Gebühren werden nicht berechnet

**Symptome:** Stornierung ohne Gebühr trotz Konfiguration

**Hinweis:**
```
⚠️ AKTUELL: Gebühren-Berechnung ist INFORMATIV
✅ Policy-Engine prüft und gibt Gebühr zurück
❌ Automatische Abrechnung: Noch nicht implementiert

Nächste Schritte:
  1. Manuelle Gebühren-Erfassung in Filament
  2. Integration mit Zahlungsanbieter (geplant)
  3. Automatische Rechnungserstellung (geplant)
```

---

## 📋 CHECKLISTE: ERSTE KONFIGURATION

Für Friseur1 (oder andere Company):

- [ ] **Schritt 1:** Admin-Panel öffnen (https://api.askproai.de/admin)
- [ ] **Schritt 2:** Navigation → "Stornierung & Umbuchung"
- [ ] **Schritt 3:** Company-wide Stornierung-Policy erstellen
  - [ ] Entität: Friseur1
  - [ ] Typ: Stornierung
  - [ ] Vorlauf: 24 Stunden
  - [ ] Max/Monat: 3
  - [ ] Gebühren: 0% (vorerst)
- [ ] **Schritt 4:** Company-wide Verschiebung-Policy erstellen
  - [ ] Entität: Friseur1
  - [ ] Typ: Umbuchung
  - [ ] Vorlauf: 12 Stunden
  - [ ] Max/Termin: 2
  - [ ] Gebühren: 0% (vorerst)
- [ ] **Schritt 5:** Testen (siehe oben)
- [ ] **Schritt 6:** Logs monitoring (erste 24h)
- [ ] **Schritt 7:** Bei Bedarf: Service-spezifische Policies (Dauerwelle etc.)

---

## 📞 SUPPORT

**Bei Fragen oder Problemen:**

1. **Log-Analyse:** `tail -f storage/logs/laravel.log`
2. **Dokumentation:** Siehe `STORNIERUNG_VERSCHIEBUNG_STATUS_2025-10-25.md`
3. **Troubleshooting:** Siehe `TROUBLESHOOTING_POLICIES.md`
4. **Quick Reference:** Siehe `QUICK_REFERENCE_POLICIES.md`

---

**Erstellt:** 2025-10-25
**Version:** 1.0
**Autor:** Claude Code (Sonnet 4.5)
