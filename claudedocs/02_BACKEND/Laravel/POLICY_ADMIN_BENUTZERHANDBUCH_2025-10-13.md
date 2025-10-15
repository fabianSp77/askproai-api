# Policy Administration - Benutzerhandbuch
**Version:** 1.0
**Datum:** 2025-10-13
**Für:** AskProAI Team (alle Benutzer)

---

## 🎯 ZIEL DIESES HANDBUCHS

**Sie lernen:**
- ✅ Wie Sie Policy-Einstellungen anzeigen und ändern
- ✅ Wie Sie neue Policies für verschiedene Bereiche erstellen
- ✅ Wie die Hierarchie funktioniert (Unternehmen → Filiale → Service → Mitarbeiter)
- ✅ Praktische Beispiele für häufige Szenarien

**Was sind Policies?**
Policies sind **Regeln** für Termine:
- 🚫 **Stornierung:** Wann dürfen Kunden absagen? Gibt es Gebühren?
- 🔄 **Umbuchung:** Wann dürfen Kunden verschieben? Gibt es Gebühren?
- 🔁 **Wiederkehrend:** Regeln für Serien-Termine (noch nicht aktiv)

---

## 📍 WO FINDE ICH DIE POLICY-VERWALTUNG?

### Schritt 1: Admin-Panel öffnen
```
URL: https://api.askproai.de/admin
```

### Schritt 2: Anmelden
- Benutzername und Passwort eingeben
- Auf "Anmelden" klicken

### Schritt 3: Zur Policy-Verwaltung navigieren
1. Im **linken Menü** nach unten scrollen
2. Sektion **"Richtlinien"** finden
3. Klick auf **"Richtlinienkonfigurationen"**

**Oder direkt:**
```
https://api.askproai.de/admin/policy-configurations
```

---

## 📊 DIE POLICY-ÜBERSICHT

### Was Sie sehen

Eine **Tabelle** mit allen Policies:

```
┌─────┬──────────────┬──────────┬───────────────┬──────────────┐
│ ID  │ Entitätstyp  │ Entität  │ Richtlinien-  │ Überschrei-  │
│     │              │          │ typ           │ bung         │
├─────┼──────────────┼──────────┼───────────────┼──────────────┤
│ 15  │ 🏢 Unternehmen│ AskProAI │ 🚫 Stornierung│ Nein         │
│ 16  │ 🏢 Unternehmen│ AskProAI │ 🔄 Umbuchung  │ Nein         │
└─────┴──────────────┴──────────┴───────────────┴──────────────┘
```

### Spalten-Erklärung

| Spalte | Bedeutung |
|--------|-----------|
| **ID** | Eindeutige Policy-Nummer (zum Referenzieren) |
| **Entitätstyp** | Für wen gilt diese Policy? (Unternehmen/Filiale/Service/Mitarbeiter) |
| **Entität** | Konkreter Name (z.B. "AskProAI", "VIP-Beratung") |
| **Richtlinientyp** | Stornierung, Umbuchung oder Wiederkehrend |
| **Überschreibung** | Ob diese Policy eine andere ergänzt |

### Buttons in der Tabelle

- **👁️ Anzeigen:** Details der Policy ansehen
- **✏️ Bearbeiten:** Policy ändern
- **🗑️ Löschen:** Policy entfernen

---

## 🔍 POLICY DETAILS ANZEIGEN

### Schritt 1: Policy anklicken
- Auf die **Zeile** der Policy klicken ODER
- Auf die **3 Punkte** (⋮) rechts → **"Anzeigen"**

### Was Sie sehen

**📋 Hauptinformationen:**
- ID, Richtlinientyp, Überschreibung-Status

**📝 Rohe Konfiguration:**
```
hours_before: 24
max_cancellations_per_month: 5
fee_percentage: 0
```

**✅ Effektive Konfiguration:**
```
✓ hours_before: 24
✓ max_cancellations_per_month: 5
✓ fee_percentage: 0
```

**Was ist der Unterschied?**
- **Rohe Config:** Was direkt in DIESER Policy definiert ist
- **Effektive Config:** Was nach Hierarchie und Vererbung tatsächlich gilt

---

## ✏️ POLICY ÄNDERN (BEARBEITEN)

### Beispiel: Stornogebühr ändern

**Ziel:** Stornogebühr von 0% auf 50% ändern

#### Schritt 1: Policy öffnen
- Policy #15 (Stornierung) finden
- Auf **3 Punkte** (⋮) → **"Bearbeiten"** klicken

#### Schritt 2: Zur Konfiguration scrollen
- Sektion **"Richtliniendetails"** finden
- Feld **"Konfiguration"** anschauen

#### Schritt 3: Wert ändern
Sie sehen eine Liste:
```
┌─────────────────────────────┬───────┐
│ Einstellung                 │ Wert  │
├─────────────────────────────┼───────┤
│ hours_before                │ 24    │
│ max_cancellations_per_month │ 5     │
│ fee_percentage              │ 0     │ ← DIESEN ÄNDERN
└─────────────────────────────┴───────┘
```

**Ändern:**
1. In der Zeile `fee_percentage` den Wert von `0` auf `50` ändern
2. ⚠️ **NUR Zahl eingeben, KEINE Anführungszeichen!**

#### Schritt 4: Speichern
- Unten rechts auf **"Speichern"** klicken
- ✅ Fertig! Änderung ist sofort aktiv

**Ergebnis:**
Ab sofort wird bei Stornierungen <24h eine Gebühr von 50% des Terminpreises erhoben!

---

## ➕ NEUE POLICY ERSTELLEN

### Häufige Szenarien

#### SZENARIO A: Standard-Regeln für ALLE Termine ändern

**Ziel:** Company-weite Policy ändern

**Vorgehen:**
1. Bestehende Company-Policy bearbeiten (siehe oben)
2. KEINE neue Policy erstellen (sonst haben Sie 2 Company-Policies!)

---

#### SZENARIO B: Service mit strengeren Regeln

**Ziel:** "VIP-Beratung" soll 48h Vorlauf statt 24h haben

##### Schritt 1: "Erstellen" klicken
- Oben rechts auf **"Erstellen"** Button

##### Schritt 2: Zuordnung ausfüllen
**Sektion: "Zuordnung"**

```
┌──────────────────────────────────────────────────────┐
│ Zugeordnete Entität                                  │
│ [ Service ▼ ]                                        │
│   ↓ (Dropdown öffnet sich)                           │
│   - Unternehmen                                      │
│   - Filiale                                          │
│   → Service ← AUSWÄHLEN                              │
│   - Mitarbeiter                                      │
└──────────────────────────────────────────────────────┘

Jetzt erscheint ein weiteres Dropdown:
┌──────────────────────────────────────────────────────┐
│ Service auswählen                                    │
│ [ VIP-Beratung ▼ ] ← SERVICE AUSWÄHLEN              │
└──────────────────────────────────────────────────────┘
```

##### Schritt 3: Richtlinientyp wählen
**Sektion: "Richtliniendetails"**

```
┌──────────────────────────────────────────────────────┐
│ Richtlinientyp                                       │
│ [ 🚫 Stornierung - Regelt wann Kunden absagen ▼ ]   │
└──────────────────────────────────────────────────────┘
```

##### Schritt 4: Verfügbare Einstellungen lesen
**Automatisch erscheint:**
```
┌──────────────────────────────────────────────────────┐
│ 📚 Verfügbare Einstellungen                          │
│                                                      │
│ **hours_before** = Mindestvorlauf in Stunden        │
│   (z.B. 24 = Kunde muss 24h vorher absagen)         │
│                                                      │
│ **max_cancellations_per_month** = Max. Stornos      │
│   (z.B. 5 = max 5x/Monat)                           │
│                                                      │
│ **fee_percentage** = Stornogebühr in %              │
│   (z.B. 50 = 50% Gebühr)                            │
│                                                      │
│ **fee** = Fixe Stornogebühr in Euro                 │
│   (z.B. 15 = 15€ Gebühr)                            │
└──────────────────────────────────────────────────────┘
```

##### Schritt 5: Konfiguration eingeben
**Feld: "Konfiguration"**

1. Klick auf **"➕ Einstellung hinzufügen"**
2. Erste Zeile erscheint:
   ```
   Einstellung: hours_before
   Wert: 48
   ```
3. Klick auf **"➕ Einstellung hinzufügen"**
4. Zweite Zeile erscheint:
   ```
   Einstellung: fee_percentage
   Wert: 50
   ```

##### Schritt 6: Hierarchie (OPTIONAL - oft nicht nötig!)
**Sektion: "Hierarchie & Überschreibung"**

- Diese Sektion ist **zugeklappt** (collapsed)
- ⚠️ **Meistens NICHT ausfüllen!**
- Nur wenn Sie Parent-Werte ERGÄNZEN (nicht ersetzen) möchten

**In diesem Fall:** NICHTS ausfüllen (lassen Sie es zugeklappt)

##### Schritt 7: Erstellen
- Unten rechts auf **"Erstellen"** klicken
- ✅ Fertig!

**Ergebnis:**
- **VIP-Beratung Termine:** 48h Vorlauf, 50% Gebühr
- **Alle anderen Termine:** 24h Vorlauf, 0% Gebühr

---

#### SZENARIO C: Filiale mit anderen Regeln

**Ziel:** München-Filiale soll 12h Vorlauf statt 24h haben

**Vorgehen:**
1. **"Erstellen"** klicken
2. **Zuordnung:**
   - Zugeordnete Entität: **Filiale**
   - Filiale auswählen: **AskProAI Hauptsitz München**
3. **Richtlinientyp:** Stornierung
4. **Konfiguration:**
   ```
   hours_before: 12
   ```
5. **Hierarchie:** NICHT ausfüllen (lassen zugeklappt)
6. **"Erstellen"** klicken

**Ergebnis:**
- **München-Filiale:** 12h Vorlauf
- **Alle anderen Filialen:** 24h Vorlauf (Company-Default)

---

#### SZENARIO D: Mitarbeiter mit individueller Policy

**Ziel:** Fabian Spitzer Termine sollen keine Umbuchungsgebühr haben

**Vorgehen:**
1. **"Erstellen"** klicken
2. **Zuordnung:**
   - Zugeordnete Entität: **Mitarbeiter**
   - Mitarbeiter auswählen: **Fabian Spitzer**
3. **Richtlinientyp:** Umbuchung
4. **Konfiguration:**
   ```
   fee_percentage: 0
   hours_before: 1
   ```
5. **Hierarchie:** NICHT ausfüllen
6. **"Erstellen"** klicken

**Ergebnis:**
- **Fabian Spitzer Termine:** 1h Vorlauf, 0% Gebühr
- **Alle anderen Mitarbeiter:** Company-Default

---

## 🏗️ HIERARCHIE VERSTEHEN

### Die 4 Ebenen

```
                        ┌────────────────┐
                        │   Unternehmen  │ ← Niedrigste Priorität
                        │   (AskProAI)   │
                        └────────┬───────┘
                                 │
                        ┌────────▼───────┐
                        │    Filiale     │
                        │   (München)    │
                        └────────┬───────┘
                                 │
                        ┌────────▼───────┐
                        │    Service     │
                        │ (VIP-Beratung) │
                        └────────┬───────┘
                                 │
                        ┌────────▼───────┐
                        │  Mitarbeiter   │ ← Höchste Priorität
                        │    (Fabian)    │
                        └────────────────┘
```

### Wie das System entscheidet

**Beispiel: Termin für "VIP-Beratung" mit Mitarbeiter "Fabian Spitzer"**

System prüft in dieser Reihenfolge:

1. Hat **Mitarbeiter "Fabian Spitzer"** eine eigene Policy?
   - ✅ JA → **Diese wird verwendet!**
   - ❌ NEIN → Weiter zu 2.

2. Hat **Service "VIP-Beratung"** eine eigene Policy?
   - ✅ JA → Diese wird verwendet!
   - ❌ NEIN → Weiter zu 3.

3. Hat **Filiale "München"** eine eigene Policy?
   - ✅ JA → Diese wird verwendet!
   - ❌ NEIN → Weiter zu 4.

4. Hat **Unternehmen "AskProAI"** eine Policy?
   - ✅ JA → Diese wird verwendet!
   - ❌ NEIN → Standard-Werte (keine Einschränkungen)

**💡 Die SPEZIFISCHSTE Policy gewinnt immer!**

---

## ⚠️ HÄUFIGE FEHLER VERMEIDEN

### ❌ FEHLER #1: Anführungszeichen bei Zahlen

**FALSCH:**
```
hours_before: "24"  ← ANFÜHRUNGSZEICHEN!
```

**RICHTIG:**
```
hours_before: 24    ← NUR ZAHL!
```

**Warum?**
Das System erwartet Zahlen (Integer), keine Strings!

---

### ❌ FEHLER #2: Mehrere Company-Policies gleichen Typs

**Problem:**
Sie haben bereits eine Company-Policy für Stornierung, erstellen aber eine neue statt die alte zu bearbeiten.

**Folge:**
- Datenbank-Constraint verhindert Speicherung
- Fehlermeldung: "Unique constraint violation"

**Lösung:**
- Existierende Company-Policy **bearbeiten** statt neue erstellen
- ODER: Alte Policy erst löschen, dann neue erstellen

---

### ❌ FEHLER #3: Hierarchie unnötig aktivieren

**Problem:**
Sie aktivieren "Ist Überschreibung" obwohl Sie die Parent-Policy komplett ersetzen möchten.

**Wann NICHT aktivieren:**
- Standard-Fall: Service hat KOMPLETT andere Regeln als Company
- Sie wollen KEINE Werte von Parent erben

**Wann aktivieren:**
- Sie wollen nur EINZELNE Werte ändern
- Andere Werte sollen von Parent geerbt werden

**Beispiel wo es Sinn macht:**
```
Company-Policy: {hours_before: 24, fee: 0, max_cancellations: 5}
Service-Policy (mit Override): {fee: 10}  ← Nur fee ändern

Effektiv: {hours_before: 24, fee: 10, max_cancellations: 5}
         ← hours_before und max_cancellations geerbt!
```

---

### ❌ FEHLER #4: Falsche Einstellungs-Keys

**FALSCH:**
```
hours: 24           ← Heißt "hours_before"!
max_cancels: 5      ← Heißt "max_cancellations_per_month"!
fee_percent: 50     ← Heißt "fee_percentage"!
```

**RICHTIG:**
```
hours_before: 24
max_cancellations_per_month: 5
fee_percentage: 50
```

**Tipp:**
Schauen Sie immer auf die **"📚 Verfügbare Einstellungen"** Box im Formular!

---

## 📋 CHECKLISTE: NEUE POLICY ERSTELLEN

### Vor dem Erstellen

- [ ] **Szenario klar?** Weiß ich, was ich erreichen möchte?
- [ ] **Existiert schon eine Policy?** Falls ja, bearbeiten statt neu erstellen!
- [ ] **Richtige Ebene?** Company, Branch, Service oder Staff?

### Beim Erstellen

- [ ] **Zuordnung:** Richtige Entität ausgewählt?
- [ ] **Richtlinientyp:** Stornierung oder Umbuchung?
- [ ] **Verfügbare Einstellungen** gelesen?
- [ ] **Konfiguration:** Richtige Keys verwendet? (siehe Box)
- [ ] **Werte:** NUR Zahlen, KEINE Anführungszeichen?
- [ ] **Hierarchie:** Sektion zugeklappt lassen (Standard-Fall)?

### Nach dem Erstellen

- [ ] **Test:** Funktioniert die Policy wie erwartet?
- [ ] **Retell Agent:** Sagt der Agent die richtigen Infos an?
- [ ] **Effektive Config:** In Details-Ansicht prüfen

---

## 🎓 QUICK-START VORLAGEN

### Vorlage 1: Standard Company-Stornierung

**Ziel:** Grundlegende Stornierungsregeln für ALLE Termine

```
Zuordnung:
  Entität: Unternehmen → AskProAI

Richtlinientyp: Stornierung

Konfiguration:
  hours_before: 24
  max_cancellations_per_month: 5
  fee_percentage: 0

Hierarchie: NICHT ausfüllen
```

---

### Vorlage 2: Standard Company-Umbuchung

**Ziel:** Grundlegende Umbuchungsregeln für ALLE Termine

```
Zuordnung:
  Entität: Unternehmen → AskProAI

Richtlinientyp: Umbuchung

Konfiguration:
  hours_before: 1
  max_reschedules_per_appointment: 3
  fee_percentage: 0

Hierarchie: NICHT ausfüllen
```

---

### Vorlage 3: VIP-Service mit Gebühren

**Ziel:** VIP-Services haben strengere Regeln und Gebühren

```
Zuordnung:
  Entität: Service → [VIP-Service Name]

Richtlinientyp: Stornierung

Konfiguration:
  hours_before: 48
  fee_percentage: 50
  max_cancellations_per_month: 2

Hierarchie: NICHT ausfüllen
```

---

### Vorlage 4: Flexibler Service ohne Gebühren

**Ziel:** Schnellberatung sehr flexibel (kurzer Vorlauf, keine Gebühren)

```
Zuordnung:
  Entität: Service → 15 Minuten Schnellberatung

Richtlinientyp: Stornierung

Konfiguration:
  hours_before: 2
  max_cancellations_per_month: 10
  fee_percentage: 0

Hierarchie: NICHT ausfüllen
```

---

## 🔧 TROUBLESHOOTING

### Problem: "Unique constraint violation"

**Fehlermeldung:**
```
SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry
```

**Ursache:**
Sie versuchen eine zweite Policy gleichen Typs für die gleiche Entität zu erstellen.

**Lösung:**
1. Überprüfen Sie die existierenden Policies
2. Bearbeiten Sie die existierende Policy ODER
3. Löschen Sie die alte Policy zuerst

---

### Problem: Policy wirkt nicht

**Symptome:**
- Policy erstellt, aber Agent sagt andere Werte an
- System verhält sich nicht wie erwartet

**Mögliche Ursachen:**

**1. Cache noch nicht geleert**
- **Lösung:** Warten Sie 5 Minuten (Cache TTL)
- **Oder:** Cache manuell leeren (Admin-Befehl)

**2. Spezifischere Policy existiert**
- **Beispiel:** Sie haben Company-Policy geändert, aber Service hat eigene Policy
- **Lösung:** Prüfen Sie in Details-Ansicht die "Effektive Konfiguration"

**3. Retell Prompt hat hardcoded Werte**
- **Problem:** Prompt sagt "10€ Gebühr" aber Policy sagt 0€
- **Lösung:** Retell Prompt muss aktualisiert werden (siehe Technisches Team)

---

### Problem: Werte werden als Text gespeichert

**Symptom:**
In Details-Ansicht sehen Sie:
```
hours_before: "24"  ← MIT ANFÜHRUNGSZEICHEN
```

**Ursache:**
Sie haben Anführungszeichen mit eingegeben.

**Lösung:**
1. Policy bearbeiten
2. Wert ohne Anführungszeichen neu eingeben: `24`
3. Speichern

---

## 📞 SUPPORT

### Bei Fragen oder Problemen

**Option 1: Dokumentation prüfen**
- Dieses Handbuch
- `/claudedocs/POLICY_CONFIGURATION_GUIDE_2025-10-13.md` (Technische Details)

**Option 2: Admin kontaktieren**
- Für technische Probleme
- Bei Fehlermeldungen
- Für komplexe Hierarchie-Szenarien

**Option 3: Tests durchführen**
- Testanruf mit Retell Agent
- Stornierung/Umbuchung durchspielen
- Verhalten beobachten

---

## 📚 WEITERFÜHRENDE THEMEN

### Fortgeschrittene Hierarchie

**Wann die "Überschreibung" aktivieren?**

**Szenario:**
Sie haben eine Company-Policy mit 5 Einstellungen, aber für einen Service möchten Sie nur 1 Einstellung ändern.

**Ohne Override (Standard):**
```
Service-Policy: {hours_before: 48}
→ Nur hours_before gilt, alle anderen Werte fehlen!
```

**Mit Override (aktiviert):**
```
Company: {hours_before: 24, fee: 0, max: 5, ...}
Service (Override): {hours_before: 48}
→ Effektiv: {hours_before: 48, fee: 0, max: 5, ...}
   ← Andere Werte geerbt!
```

---

### Gestaffelte Gebühren (Fee Tiers)

**Ziel:** Gebühr abhängig vom Vorlauf

**Nicht über UI möglich!**
- Erfordert JSON-Struktur
- Muss in Datenbank direkt eingegeben werden
- Kontaktieren Sie Technical Support

**Beispiel-Struktur:**
```json
{
  "fee_tiers": [
    {"min_hours": 48, "fee": 0},
    {"min_hours": 24, "fee": 10},
    {"min_hours": 12, "fee": 20},
    {"min_hours": 0, "fee": 30}
  ]
}
```

---

## ✅ ZUSAMMENFASSUNG

### Was Sie gelernt haben

- ✅ Wie Sie zur Policy-Verwaltung navigieren
- ✅ Wie Sie existierende Policies anzeigen und ändern
- ✅ Wie Sie neue Policies für verschiedene Ebenen erstellen
- ✅ Wie die Hierarchie funktioniert (spezifisch gewinnt)
- ✅ Häufige Fehler und wie man sie vermeidet
- ✅ Quick-Start Vorlagen für typische Szenarien

### Wichtigste Regeln

1. **Nur Zahlen** als Werte eingeben, keine Anführungszeichen
2. **Company-Policies bearbeiten**, nicht neue erstellen (bei gleicher Entität)
3. **Hierarchie-Sektion** meist zugeklappt lassen (Standard-Fall)
4. **Verfügbare Einstellungen** Box beachten (richtige Keys verwenden)
5. **Test durchführen** nach jeder Änderung

### Nächste Schritte

1. **Machen Sie sich mit der UI vertraut:** Schauen Sie sich existierende Policies an
2. **Testen Sie Änderungen:** Bearbeiten Sie einen Wert und beobachten Sie das Verhalten
3. **Erstellen Sie Ihre erste Policy:** Nutzen Sie eine Quick-Start Vorlage
4. **Dokumentieren Sie Ihre Policies:** Halten Sie fest, welche Regeln Sie haben

---

**Viel Erfolg mit der Policy-Verwaltung!** 🎉

**Bei Fragen:** Siehe Support-Sektion oben
**Technische Details:** Siehe POLICY_CONFIGURATION_GUIDE_2025-10-13.md

---

**Handbuch erstellt:** 2025-10-13
**Version:** 1.0
**Autor:** Claude Code für AskProAI Team
