# Policy Configuration Guide - AskProAI
**Datum:** 2025-10-13
**Company:** AskProAI (ID: 15)
**Zweck:** Vollständige Übersicht aller Policy-Einstellungen und wie man sie verwaltet

---

## 📋 INHALTSVERZEICHNIS

1. [Aktuelle Policy-Einstellungen](#aktuelle-policy-einstellungen)
2. [Wo Policies anzeigen/ändern](#wo-policies-anzeigenändern)
3. [Policy-Hierarchie verstehen](#policy-hierarchie-verstehen)
4. [Verfügbare Policy-Typen](#verfügbare-policy-typen)
5. [Konfigurationsoptionen](#konfigurationsoptionen)
6. [Praktische Beispiele](#praktische-beispiele)

---

## 🎯 AKTUELLE POLICY-EINSTELLUNGEN

### Unternehmen: **AskProAI** (Company ID: 15)

#### 1️⃣ Stornierungsrichtlinie (Cancellation Policy)
**Policy ID:** 15
**Gültig für:** Gesamtes Unternehmen (alle Filialen, Services, Mitarbeiter)

```json
{
  "hours_before": 24,
  "max_cancellations_per_month": 5,
  "fee_percentage": 0
}
```

**Bedeutung:**
- ✅ **24 Stunden Vorlauf:** Kunden müssen mindestens 24 Stunden vor dem Termin stornieren
- ✅ **Maximum 5 Stornos pro Monat:** Ein Kunde kann maximal 5 Termine pro Monat stornieren
- ✅ **Keine Gebühr:** Es wird keine Stornogebühr erhoben (0%)

**Verhalten wenn Policy verletzt:**
- Stornierung <24h: **ABGELEHNT** (Kunde kann nicht stornieren)
- Mehr als 5 Stornos/Monat: **ABGELEHNT** mit Fehlermeldung

---

#### 2️⃣ Umbuchungsrichtlinie (Reschedule Policy)
**Policy ID:** 16
**Gültig für:** Gesamtes Unternehmen (alle Filialen, Services, Mitarbeiter)

```json
{
  "hours_before": 1,
  "max_reschedules_per_appointment": 3,
  "fee_percentage": 0
}
```

**Bedeutung:**
- ✅ **1 Stunde Vorlauf:** Kunden müssen mindestens 1 Stunde vor dem Termin umbuchen
- ✅ **Maximum 3 Umbuchungen pro Termin:** Ein einzelner Termin kann maximal 3x verschoben werden
- ✅ **Keine Gebühr:** Es wird keine Umbuchungsgebühr erhoben (0%)

**Verhalten wenn Policy verletzt:**
- Umbuchung <1h: **ABGELEHNT** (Kunde kann nicht umbuchen)
- Mehr als 3 Umbuchungen: **ABGELEHNT** mit Fehlermeldung

---

### Filiale: **AskProAI Hauptsitz München**
**Branch ID:** 9f4d5e2a-46f7-41b6-b81d-1532725381d4

**Status:** ⚠️ **KEINE BRANCH-SPEZIFISCHEN POLICIES**
→ Es werden die Company-Policies verwendet (siehe oben)

---

### Services (Auswahl)
**Anzahl Services:** 14 Services im System

**Status:** ⚠️ **KEINE SERVICE-SPEZIFISCHEN POLICIES**
→ Alle Services verwenden die Company-Policies

**Beispiel Services:**
- 30 Minuten Beratung (ID: 45)
- 15 Minuten Schnellberatung (ID: 46)
- 30 Minuten Termin mit Fabian Spitzer (ID: 38)
- AskProAI + aus Berlin + Beratung (ID: 47)

---

## 💻 WO POLICIES ANZEIGEN/ÄNDERN

### Admin-Interface: Filament Dashboard

#### 1. **Anmelden im Admin-Panel**
```
URL: https://api.askproai.de/admin
```

#### 2. **Navigation zur Policy-Verwaltung**
1. Im linken Menü: **"Richtlinien"** Sektion
2. Klick auf: **"Richtlinienkonfigurationen"**

**Direkter Link:**
```
https://api.askproai.de/admin/policy-configurations
```

---

#### 3. **Übersicht der Policies**

Sie sehen eine Tabelle mit folgenden Spalten:

| Spalte | Beschreibung |
|--------|--------------|
| **ID** | Eindeutige Policy-ID |
| **Entitätstyp** | Company / Branch / Service / Staff |
| **Entität** | Name der Entität (z.B. "AskProAI") |
| **Richtlinientyp** | Stornierung / Umbuchung / Wiederkehrend |
| **Überschreibung** | Zeigt an, ob diese Policy eine andere überschreibt |

**Aktuelle Ansicht (vereinfacht):**
```
┌────┬─────────────┬──────────┬──────────────┬───────────────┐
│ ID │ Entitätstyp │ Entität  │ Richtlinien  │ Überschreibung│
│    │             │          │ typ          │               │
├────┼─────────────┼──────────┼──────────────┼───────────────┤
│ 15 │ Unternehmen │ AskProAI │ Stornierung  │ Nein          │
│ 16 │ Unternehmen │ AskProAI │ Umbuchung    │ Nein          │
└────┴─────────────┴──────────┴──────────────┴───────────────┘
```

---

#### 4. **Policy ANZEIGEN (Details)**

**Schritte:**
1. Auf eine Policy-Zeile klicken
2. Oder auf die 3 Punkte (⋮) rechts klicken → **"Anzeigen"**

**Sie sehen dann:**

**Hauptinformationen:**
- ID, Richtlinientyp, Überschreibung-Status

**Rohe Konfiguration:**
```
hours_before: 24
max_cancellations_per_month: 5
fee_percentage: 0
```

**Effektive Konfiguration:**
```
✓ hours_before: 24
✓ max_cancellations_per_month: 5
✓ fee_percentage: 0
```

*(Effektive Config zeigt die tatsächlich angewendete Konfiguration nach Berücksichtigung aller Überschreibungen)*

---

#### 5. **Policy ÄNDERN**

**Schritte:**
1. Auf die 3 Punkte (⋮) rechts klicken → **"Bearbeiten"**
2. Im Formular **"Konfiguration"** Sektion finden
3. Werte ändern (siehe [Konfigurationsoptionen](#konfigurationsoptionen))
4. **"Speichern"** klicken

**Beispiel: Stornogebühr von 0% auf 50% ändern**
1. Policy #15 öffnen
2. **"Bearbeiten"** klicken
3. In der Konfiguration:
   - Suche `fee_percentage` Zeile
   - Ändere Wert von `0` auf `50`
4. **"Speichern"**

**Ergebnis:**
```json
{
  "hours_before": 24,
  "max_cancellations_per_month": 5,
  "fee_percentage": 50   ← GEÄNDERT
}
```

→ Ab sofort wird bei Stornierungen <24h eine Gebühr von 50% des Terminpreises erhoben!

---

#### 6. **NEUE Policy erstellen**

**Schritte:**
1. Oben rechts auf **"Erstellen"** klicken
2. Formular ausfüllen:

**Sektion: Zuordnung**
- **Zugeordnete Entität:** Wählen Sie eine aus
  - **Unternehmen** (gilt für alles)
  - **Filiale** (nur diese Filiale)
  - **Service** (nur dieser Service)
  - **Mitarbeiter** (nur dieser Mitarbeiter)

**Sektion: Richtliniendetails**
- **Richtlinientyp:** Wählen Sie einen aus
  - Stornierung
  - Umbuchung
  - Wiederkehrend

- **Konfiguration:** Key-Value Paare hinzufügen
  - Klick auf **"Einstellung hinzufügen"**
  - Geben Sie Key und Value ein (siehe [Konfigurationsoptionen](#konfigurationsoptionen))

**Sektion: Hierarchie & Überschreibung**
- **Ist Überschreibung:** Aktivieren Sie diese Option, wenn diese Policy eine übergeordnete überschreibt
- **Überschreibt Richtlinie:** Wählen Sie die Parent-Policy aus (falls Überschreibung)

3. **"Erstellen"** klicken

---

## 🏗️ POLICY-HIERARCHIE VERSTEHEN

### 4-Level Hierarchie (spezifisch → allgemein)

```
1. Staff (Mitarbeiter)         ← Höchste Priorität
   ↓ (falls nicht definiert)
2. Service (Dienstleistung)
   ↓ (falls nicht definiert)
3. Branch (Filiale)
   ↓ (falls nicht definiert)
4. Company (Unternehmen)       ← Niedrigste Priorität
```

**Regel:** Die SPEZIFISCHSTE Policy gewinnt!

---

### Beispiel: Hierarchie in Aktion

**Szenario:**
- Company Policy: 24h Vorlauf, 0% Gebühr
- Service "VIP-Beratung" Policy: 48h Vorlauf, 25% Gebühr
- Termin für "VIP-Beratung"

**Welche Policy wird angewendet?**
→ **Service Policy** (48h Vorlauf, 25% Gebühr)

**Warum?**
→ Service ist spezifischer als Company, daher hat Service-Policy Vorrang

---

### Überschreibungsmechanismus

**Zwei Modi:**

#### 1. **Komplette Überschreibung** (is_override = false)
→ Diese Policy ERSETZT alle Parent-Policies komplett

**Beispiel:**
```json
// Company Policy
{"hours_before": 24, "fee_percentage": 0, "max_cancellations_per_month": 5}

// Service Policy (is_override = false)
{"hours_before": 48}

// Effektive Policy für Service:
{"hours_before": 48}  ← NUR Service-Werte, keine Vererbung!
```

#### 2. **Merge mit Override** (is_override = true, overrides_id = 15)
→ Diese Policy ERGÄNZT Parent-Policy (Child-Werte überschreiben Parent bei Konflikt)

**Beispiel:**
```json
// Company Policy (ID: 15)
{"hours_before": 24, "fee_percentage": 0, "max_cancellations_per_month": 5}

// Service Policy (is_override = true, overrides_id = 15)
{"hours_before": 48, "fee_percentage": 25}

// Effektive Policy für Service:
{
  "hours_before": 48,              ← Von Service (überschrieben)
  "fee_percentage": 25,            ← Von Service (überschrieben)
  "max_cancellations_per_month": 5 ← Von Company (geerbt)
}
```

---

## 📝 VERFÜGBARE POLICY-TYPEN

### 1. **Cancellation Policy** (Stornierung)

**Zweck:** Regelt, wann und wie Kunden Termine stornieren können

**Anwendungsbeispiele:**
- Kunde ruft an: "Ich möchte meinen Termin morgen absagen"
- Retell Agent prüft Policy
- Entscheidet: Erlaubt oder Abgelehnt

---

### 2. **Reschedule Policy** (Umbuchung)

**Zweck:** Regelt, wann und wie Kunden Termine verschieben können

**Anwendungsbeispiele:**
- Kunde ruft an: "Ich möchte meinen Termin auf einen anderen Tag verschieben"
- Retell Agent prüft Policy
- Entscheidet: Erlaubt oder Abgelehnt

---

### 3. **Recurring Policy** (Wiederkehrend)

**Zweck:** Regelt Serien-Termine und wiederkehrende Buchungen

**Status:** ⚠️ **AKTUELL NICHT KONFIGURIERT**

**Mögliche Anwendung:**
- Wöchentliche Beratungstermine
- Monatliche Check-ins
- Regelmäßige Wartungstermine

---

## ⚙️ KONFIGURATIONSOPTIONEN

### Cancellation Policy - Verfügbare Einstellungen

| Key | Typ | Beschreibung | Beispiel | Standard |
|-----|-----|--------------|----------|----------|
| `hours_before` | Zahl | Mindest-Vorlauf in Stunden | `24` | 0 (jederzeit) |
| `max_cancellations_per_month` | Zahl | Maximale Stornos pro Monat pro Kunde | `3` | Unbegrenzt |
| `fee_percentage` | Zahl | Stornogebühr in % des Terminpreises | `50` | 0 (keine Gebühr) |
| `fee` | Zahl | Fixe Stornogebühr in € | `15` | 0 |
| `fee_tiers` | Array | Gestaffelte Gebühren nach Vorlauf | Siehe unten | Standard-Tiers |

---

### Reschedule Policy - Verfügbare Einstellungen

| Key | Typ | Beschreibung | Beispiel | Standard |
|-----|-----|--------------|----------|----------|
| `hours_before` | Zahl | Mindest-Vorlauf in Stunden | `12` | 0 (jederzeit) |
| `max_reschedules_per_appointment` | Zahl | Maximale Umbuchungen pro Termin | `2` | Unbegrenzt |
| `fee_percentage` | Zahl | Umbuchungsgebühr in % | `25` | 0 (keine Gebühr) |
| `fee` | Zahl | Fixe Umbuchungsgebühr in € | `10` | 0 |
| `fee_tiers` | Array | Gestaffelte Gebühren nach Vorlauf | Siehe unten | Standard-Tiers |

---

### Standard-Gebühren-Struktur (wenn keine Policy definiert)

**Wenn KEINE Policy existiert oder keine Gebühren-Einstellung:**

```
>48h Vorlauf  → 0€ Gebühr
24-48h        → 10€ Gebühr
<24h          → 15€ Gebühr
```

**Code-Referenz:** `AppointmentPolicyEngine.php:194-201`

---

### Fee Tiers - Gestaffelte Gebühren

**Format:**
```json
{
  "fee_tiers": [
    {"min_hours": 48, "fee": 0},
    {"min_hours": 24, "fee": 10},
    {"min_hours": 0, "fee": 15}
  ]
}
```

**Bedeutung:**
- ≥48h Vorlauf: 0€
- ≥24h Vorlauf: 10€
- <24h Vorlauf: 15€

**Alternative mit Prozent:**
```json
{
  "fee_tiers": [
    {"min_hours": 48, "fee_percentage": 0},
    {"min_hours": 24, "fee_percentage": 25},
    {"min_hours": 0, "fee_percentage": 50}
  ]
}
```

---

## 💡 PRAKTISCHE BEISPIELE

### Beispiel 1: Service mit strengeren Regeln

**Szenario:** "VIP-Beratung" soll strengere Stornierungsregeln haben

**Schritte:**
1. **Erstellen** klicken
2. **Zuordnung:**
   - Zugeordnete Entität: **Service** → "VIP-Beratung" wählen
3. **Richtliniendetails:**
   - Richtlinientyp: **Stornierung**
   - Konfiguration:
     ```
     hours_before: 48
     fee_percentage: 50
     max_cancellations_per_month: 2
     ```
4. **Hierarchie & Überschreibung:**
   - Ist Überschreibung: **JA** (aktivieren)
   - Überschreibt Richtlinie: **#15** (Company Cancellation Policy)
5. **Erstellen**

**Ergebnis:**
- **VIP-Beratung Termine:**
  - 48h Vorlauf erforderlich
  - 50% Gebühr bei Stornierung
  - Max 2 Stornos/Monat
- **Alle anderen Termine:**
  - 24h Vorlauf erforderlich
  - 0% Gebühr
  - Max 5 Stornos/Monat

---

### Beispiel 2: Filiale mit kürzeren Vorlaufzeiten

**Szenario:** München-Filiale soll flexiblere Stornierung erlauben (12h statt 24h)

**Schritte:**
1. **Erstellen** klicken
2. **Zuordnung:**
   - Zugeordnete Entität: **Filiale** → "AskProAI Hauptsitz München"
3. **Richtliniendetails:**
   - Richtlinientyp: **Stornierung**
   - Konfiguration:
     ```
     hours_before: 12
     ```
4. **Hierarchie & Überschreibung:**
   - Ist Überschreibung: **JA** (aktivieren)
   - Überschreibt Richtlinie: **#15** (Company Cancellation Policy)
5. **Erstellen**

**Ergebnis:**
- **München-Filiale:**
  - 12h Vorlauf erforderlich ← GEÄNDERT
  - 0% Gebühr ← GEERBT von Company
  - Max 5 Stornos/Monat ← GEERBT von Company
- **Alle anderen Filialen (zukünftig):**
  - 24h Vorlauf erforderlich
  - 0% Gebühr
  - Max 5 Stornos/Monat

---

### Beispiel 3: Mitarbeiter mit individueller Policy

**Szenario:** "Fabian Spitzer" Termine sollen KEINE Umbuchungsgebühr haben

**Schritte:**
1. **Erstellen** klicken
2. **Zuordnung:**
   - Zugeordnete Entität: **Mitarbeiter** → "Fabian Spitzer" wählen
3. **Richtliniendetails:**
   - Richtlinientyp: **Umbuchung**
   - Konfiguration:
     ```
     fee_percentage: 0
     hours_before: 1
     ```
4. **Hierarchie & Überschreibung:**
   - Ist Überschreibung: **JA** (aktivieren)
   - Überschreibt Richtlinie: **#16** (Company Reschedule Policy)
5. **Erstellen**

**Ergebnis:**
- **Fabian Spitzer Termine:**
  - 1h Vorlauf ← GEERBT
  - 0% Gebühr ← GESETZT
  - Max 3 Umbuchungen ← GEERBT
- **Alle anderen Mitarbeiter:**
  - Verwenden Company Policy

---

### Beispiel 4: Gestaffelte Gebühren einrichten

**Szenario:** Stornogebühr soll nach Vorlauf gestaffelt sein

**Aktuelle Config (Flat Fee):**
```json
{
  "hours_before": 24,
  "fee_percentage": 0
}
```

**Neue Config (Gestaffelt):**
```json
{
  "hours_before": 24,
  "fee_tiers": [
    {"min_hours": 48, "fee": 0},
    {"min_hours": 24, "fee": 10},
    {"min_hours": 12, "fee": 20},
    {"min_hours": 0, "fee": 30}
  ]
}
```

**Schritte:**
1. Policy #15 öffnen → **Bearbeiten**
2. Konfiguration ändern:
   - **Löschen:** `fee_percentage` Zeile
   - **Hinzufügen:**
     - Key: `fee_tiers`
     - Value: `[{"min_hours": 48, "fee": 0}, {"min_hours": 24, "fee": 10}, {"min_hours": 12, "fee": 20}, {"min_hours": 0, "fee": 30}]`
3. **Speichern**

**Ergebnis:**
- Stornierung >48h vor Termin: 0€
- Stornierung 24-48h vor Termin: 10€
- Stornierung 12-24h vor Termin: 20€
- Stornierung <12h vor Termin: 30€

---

## 🔍 WIE POLICIES IM CODE FUNKTIONIEREN

### 1. Policy-Evaluation Flow

**Wenn ein Kunde einen Termin stornieren möchte:**

```
1. Retell Agent empfängt Stornierungsanfrage
   ↓
2. AppointmentPolicyEngine::canCancel() wird aufgerufen
   ↓
3. resolvePolicy() sucht passende Policy (Hierarchie: Staff → Service → Branch → Company)
   ↓
4. Policy wird gefunden (z.B. Company Policy #15)
   ↓
5. Prüfungen:
   ✓ Check 1: hours_before erfüllt? (24h Vorlauf?)
   ✓ Check 2: max_cancellations_per_month erfüllt? (<5 Stornos/Monat?)
   ↓
6. Ergebnis:
   - ALLOW mit Fee 0€ ODER
   - DENY mit Fehlermeldung
   ↓
7. Retell Agent kommuniziert Ergebnis an Kunde
```

**Code-Referenz:** `AppointmentPolicyEngine.php:29-88`

---

### 2. Aktuelle Verwendung im Retell Prompt

**Datei:** `RETELL_PROMPT_V78_FINAL.txt:214-244`

```
🔄 FUNCTION: reschedule_appointment

GEBÜHREN (Du berechnest!):
>48h → Kostenlos
24-48h → 10€
<24h → 15€

❌ FUNCTION: cancel_appointment

24-STUNDEN-REGEL (Du prüfst!):
Wenn >=24h: Storniere
Wenn <24h: Ablehnen, Verschiebung anbieten
```

**⚠️ WICHTIG:**
Der Retell Prompt enthält **HARDCODED** Gebühren-Regeln!
Diese stimmen NICHT mit den aktuellen Policies überein!

**Aktuelle Policies:**
- Cancellation: 24h Vorlauf, 0% Gebühr
- Reschedule: 1h Vorlauf, 0% Gebühr

**Retell Prompt sagt:**
- Reschedule: >48h kostenlos, 24-48h 10€, <24h 15€
- Cancel: >=24h erlaubt, <24h ablehnen

**→ Der Code validiert korrekt, aber der Agent KOMMUNIZIERT falsche Gebühren!**

---

## ⚠️ ERKANNTE PROBLEME

### Problem #1: Retell Prompt nicht synchronisiert

**Beschreibung:**
Der Retell Agent Prompt enthält hardcoded Gebühren, die nicht mit der Policy-Datenbank übereinstimmen.

**Impact:**
- Agent sagt Kunden falsche Gebühren
- Verwirrung wenn Agent "10€ Gebühr" ankündigt, aber System 0€ berechnet

**Lösung:**
Retell Prompt muss aktualisiert werden, um Policy-Engine zu respektieren:

```
GEBÜHREN:
→ Frage System nach Gebühr (via API)
→ NIEMALS hardcoded Gebühren kommunizieren
```

---

### Problem #2: Keine Branch-spezifischen Policies

**Beschreibung:**
Aktuell existieren nur Company-Level Policies. Branch-spezifische Policies fehlen.

**Impact:**
- Alle Filialen haben identische Regeln
- Keine Flexibilität für unterschiedliche Standorte

**Lösung:**
Falls gewünscht: Branch-Policies erstellen (siehe [Beispiel 2](#beispiel-2-filiale-mit-kürzeren-vorlaufzeiten))

---

### Problem #3: Keine Service-spezifischen Policies

**Beschreibung:**
Alle Services verwenden die gleichen Policies.

**Impact:**
- "VIP-Beratung" hat gleiche Regeln wie "Schnellberatung"
- Keine Differenzierung nach Service-Wichtigkeit

**Lösung:**
Falls gewünscht: Service-Policies erstellen (siehe [Beispiel 1](#beispiel-1-service-mit-strengeren-regeln))

---

## 📊 ZUSAMMENFASSUNG

### ✅ Was funktioniert

1. **Policy-System:**
   - ✅ Vollständig implementiert
   - ✅ 4-Level Hierarchie funktioniert
   - ✅ Überschreibungsmechanismus arbeitet korrekt
   - ✅ Admin-Interface vorhanden und funktional

2. **Aktuelle Company-Policies:**
   - ✅ Cancellation: 24h Vorlauf, max 5/Monat, 0% Gebühr
   - ✅ Reschedule: 1h Vorlauf, max 3/Termin, 0% Gebühr

3. **Code:**
   - ✅ AppointmentPolicyEngine validiert korrekt
   - ✅ Gebühren-Berechnung implementiert
   - ✅ Quota-Tracking implementiert

---

### ⚠️ Was fehlt/nicht synchron

1. **Retell Prompt:**
   - ❌ Enthält hardcoded Gebühren (10€, 15€)
   - ❌ Stimmt nicht mit Policy-Datenbank überein (0€)
   - ❌ Agent kommuniziert falsche Informationen

2. **Policy-Granularität:**
   - ⚠️ Keine Branch-Policies (alle Filialen gleich)
   - ⚠️ Keine Service-Policies (alle Services gleich)
   - ⚠️ Keine Staff-Policies (alle Mitarbeiter gleich)

3. **Recurring Policy:**
   - ⚠️ Nicht konfiguriert
   - ⚠️ Keine Verwendung im System

---

## 🎯 EMPFEHLUNGEN

### 1. **DRINGEND: Retell Prompt aktualisieren**

**Priorität:** 🔴 **HOCH**

**Aktion:**
Entferne hardcoded Gebühren aus `RETELL_PROMPT_V78_FINAL.txt` und lass den Agent die Policy-Engine befragen.

**Änderung in Zeilen 214-244:**

**VORHER:**
```
GEBÜHREN (Du berechnest!):
>48h → Kostenlos
24-48h → 10€
<24h → 15€
```

**NACHHER:**
```
GEBÜHREN (System berechnet automatisch):
→ System prüft Policy und berechnet korrekte Gebühr
→ Kommuniziere nur was System zurückgibt
→ NIEMALS eigene Gebühren erfinden!
```

---

### 2. **Optional: Service-spezifische Policies**

**Priorität:** 🟡 **MITTEL**

**Wenn gewünscht:**
- "VIP-Services" strengere Regeln (48h, Gebühren)
- "Schnellberatung" flexiblere Regeln (2h, keine Gebühren)

**Aktion:** Siehe [Beispiel 1](#beispiel-1-service-mit-strengeren-regeln)

---

### 3. **Optional: Branch-spezifische Policies**

**Priorität:** 🟢 **NIEDRIG**

**Nur relevant wenn:**
- Mehrere Filialen mit unterschiedlichen Regeln
- Unterschiedliche Märkte/Standorte

**Aktion:** Siehe [Beispiel 2](#beispiel-2-filiale-mit-kürzeren-vorlaufzeiten)

---

## 📚 WEITERFÜHRENDE INFORMATIONEN

### Code-Referenzen

| Datei | Zweck |
|-------|-------|
| `app/Models/PolicyConfiguration.php` | Policy Model |
| `app/Services/Policies/AppointmentPolicyEngine.php` | Policy Validation Logic |
| `app/Services/Policies/PolicyConfigurationService.php` | Policy Resolution Service |
| `app/Filament/Resources/PolicyConfigurationResource.php` | Admin Interface |
| `database/migrations/2025_10_01_060201_create_policy_configurations_table.php` | Database Schema |

---

### Admin-URLs

| Funktion | URL |
|----------|-----|
| Policy-Liste | https://api.askproai.de/admin/policy-configurations |
| Policy erstellen | https://api.askproai.de/admin/policy-configurations/create |
| Policy #15 (Cancellation) | https://api.askproai.de/admin/policy-configurations/15 |
| Policy #16 (Reschedule) | https://api.askproai.de/admin/policy-configurations/16 |

---

### Datenbank-Zugriff

**Alle Policies anzeigen:**
```bash
php artisan tinker --execute="
\App\Models\PolicyConfiguration::with('configurable')->get()->each(function(\$p) {
    echo \$p->id . ': ' . \$p->policy_type . ' - ' . \$p->configurable->name . PHP_EOL;
    echo json_encode(\$p->config, JSON_PRETTY_PRINT) . PHP_EOL;
});
"
```

**Policy für bestimmten Service finden:**
```bash
php artisan tinker --execute="
\$service = \App\Models\Service::find(45);
\$policyService = app(\App\Services\Policies\PolicyConfigurationService::class);
\$policy = \$policyService->resolvePolicy(\$service, 'cancellation');
print_r(\$policy);
"
```

---

**Dokumentation erstellt:** 2025-10-13
**Autor:** Claude Code
**Version:** 1.0
**Status:** Vollständig
