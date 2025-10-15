# Admin-Seiten Erklärung - Policy vs Assignment
**Datum:** 2025-10-13
**Thema:** Unterschied zwischen 3 ähnlich benannten Seiten

---

## 🔍 DIE 3 SEITEN IM ÜBERBLICK

### 1️⃣ **Policy Configurations** (Hauptseite)
📍 https://api.askproai.de/admin/policy-configurations

**Was ist das?**
→ Vollständige Verwaltung von **Termin-Richtlinien** (Stornierung, Umbuchung, Recurring)

**Wofür?**
- ✅ Stornierungsregeln: Wann dürfen Kunden absagen? Mit Gebühr?
- ✅ Umbuchungsregeln: Wann dürfen Kunden verschieben? Wie oft?
- ✅ Wiederkehrende Termine: Serien-Termine automatisch erstellen

**Für wen?**
→ **Fortgeschrittene User** die alle Optionen brauchen

**Features:**
- Alle Policy-Typen (cancellation, reschedule, recurring)
- Hierarchie (Company → Branch → Service → Staff)
- Überschreibungen und Vererbung
- Dashboard mit Statistiken und Widgets
- **NEU:** Dropdowns statt JSON-Editor

---

### 2️⃣ **Policy Onboarding** (Setup Wizard)
📍 https://api.askproai.de/admin/policy-onboarding

**Was ist das?**
→ Geführter **Wizard/Assistent** um die ERSTE Policy zu erstellen

**Wofür?**
- ✅ Schritt-für-Schritt Anleitung für Anfänger
- ✅ Erklärt was Policies sind
- ✅ Zeigt wie Hierarchie funktioniert
- ✅ Vereinfachtes Formular

**Für wen?**
→ **Neue User** die zum ersten Mal eine Policy erstellen

**Features:**
- 4 Schritte: Willkommen → Entität → Regeln → Abschluss
- Hilfe-Texte und Erklärungen eingebaut
- Erstellt dann eine Policy in "Policy Configurations"
- Nur für Stornierung & Umbuchung (kein Recurring)

**⚠️ Wichtig:**
Diese Seite ERSTELLT letztlich eine Policy in der Hauptseite (#1).
Es ist nur eine **vereinfachte Einstiegsseite** für Anfänger.

---

### 3️⃣ **Company Assignment Configs** (Mitarbeiter-Zuordnung)
📍 https://api.askproai.de/admin/company-assignment-configs

**Was ist das?**
→ Konfiguration wie **Mitarbeiter zu Terminen zugeordnet** werden

**Wofür?**
- ✅ Bestimmt: Darf JEDER Mitarbeiter jeden Termin machen?
- ✅ Oder: Nur qualifizierte Mitarbeiter für bestimmte Services?

**Für wen?**
→ **Admins** die Mitarbeiter-Workflow konfigurieren

**2 Modelle:**

**🎯 Egal wer (any_staff):**
- Erster verfügbarer Mitarbeiter bekommt den Termin
- Für: Call-Center, allgemeine Beratung
- Jeder kann alles

**🎓 Nur Qualifizierte (service_staff):**
- Nur Mitarbeiter die für diesen Service qualifiziert sind
- Für: Friseure, Werkstätten, Praxen
- Nicht jeder kann alles (z.B. nur Meister darf bestimmte Reparaturen machen)

**⚠️ KOMPLETT ANDERES THEMA!**
Hat mit Policies (Stornierung/Umbuchung) NICHTS zu tun!

---

## 📊 ZUSAMMENHANG & UNTERSCHIEDE

### Policy Configurations vs Policy Onboarding:

```
┌─────────────────────────────────────────┐
│   Policy Onboarding (Wizard)            │
│   "Erste Policy erstellen"              │
│                                         │
│   [Schritt 1] [Schritt 2] [Schritt 3] │
│                                         │
│            ↓ (erstellt)                 │
│                                         │
│   Policy Configurations (Verwaltung)    │
│   "Alle Policies verwalten"             │
│                                         │
│   📋 Policy #15 - AskProAI Stornierung  │
│   📋 Policy #16 - AskProAI Umbuchung    │
│   📋 Policy #14 - Krückeberg Storno     │
└─────────────────────────────────────────┘
```

**Gleich:**
- Beide arbeiten mit `PolicyConfiguration` Model
- Beide erstellen Termin-Richtlinien
- Beide speichern in gleicher Datenbank-Tabelle

**Unterschied:**
- **Onboarding** = Wizard für Anfänger (vereinfacht, 4 Schritte)
- **Configurations** = Vollständige Verwaltung (alle Features, komplex)

---

### Company Assignment Configs = KEIN POLICY!

```
┌───────────────────────────────┐     ┌──────────────────────────────┐
│  Policy Configurations        │     │  Company Assignment Configs  │
│  (Termin-REGELN)              │     │  (Mitarbeiter-ZUORDNUNG)     │
│                               │     │                              │
│  Thema:                       │     │  Thema:                      │
│  • WANN stornieren?           │     │  • WER macht Termin?         │
│  • WANN umbuchen?             │     │  • Qualifikation nötig?      │
│  • WIE OFT erlaubt?           │     │  • Egal wer vs Experte?      │
│  • GEBÜHREN?                  │     │                              │
│                               │     │                              │
│  Beispiel:                    │     │  Beispiel:                   │
│  "24h vorher stornieren,      │     │  "Nur Friseur-Meister darf   │
│   max 5x pro Monat,           │     │   Dauerwellen machen"        │
│   kostenlos"                  │     │                              │
└───────────────────────────────┘     └──────────────────────────────┘
         POLICIES                              STAFF ASSIGNMENT
```

---

## 💡 EMPFEHLUNG

### Welche Seite sollten Sie nutzen?

**Für neue User:**
1. ✅ Starten Sie mit **Policy Onboarding** (Wizard)
2. ➡️ Lassen Sie sich durchführen
3. ✅ Danach können Sie in **Policy Configurations** weitere Policies erstellen

**Für erfahrene User:**
- ✅ Gehen Sie direkt zu **Policy Configurations**
- ✅ Nutzen Sie die verbesserten Dropdowns
- ✅ Erstellen/Bearbeiten Sie alle Policies dort

**Für Staff-Zuordnung:**
- ✅ Gehen Sie zu **Company Assignment Configs**
- ⚠️ Das ist ein ANDERES Thema (nicht Policies!)
- ✅ Wählen Sie "any_staff" oder "service_staff"

---

## 🔧 VERBESSERUNGSVORSCHLAG

### Problem: Verwirrende Namen

**Aktuell:**
- "Policy Configurations" ← unklar
- "Policy Onboarding" ← unklar was der Unterschied ist
- "Company Assignment Configs" ← klingt ähnlich wie "Configurations"

**Besser wäre:**
- "📋 Termin-Richtlinien" (statt Policy Configurations)
- "🎓 Richtlinien-Assistent" (statt Policy Onboarding)
- "👥 Mitarbeiter-Zuordnung" (statt Company Assignment Configs)

**Navigation-Gruppen:**
```
Richtlinien
  └─ 📋 Termin-Richtlinien
  └─ 🎓 Richtlinien-Assistent

Mitarbeiter
  └─ 👥 Mitarbeiter-Zuordnung
  └─ 👤 Mitarbeiter-Verwaltung
```

So wäre sofort klar:
- Alles unter "Richtlinien" = Termin-Regeln (Stornierung/Umbuchung)
- Alles unter "Mitarbeiter" = Personal-Verwaltung

---

## 📋 AKTUELLE NAVIGATION

**Aktuell im Admin:**

```
Richtlinien
  └─ 📋 Richtlinienkonfigurationen  ← Policy Configurations

Help & Setup
  └─ 🎓 Policy Setup Wizard         ← Policy Onboarding

Mitarbeiter-Zuordnung
  └─ ⚙️ Firmen-Konfiguration        ← Company Assignment Configs
```

→ Die Gruppierung ist schon gut, aber die Namen könnten klarer sein!

---

## ✅ CHECKLISTE: WELCHE SEITE FÜR WELCHEN ZWECK?

| Ich möchte... | Richtige Seite |
|--------------|----------------|
| Erste Policy erstellen | 🎓 Policy Onboarding |
| Policy bearbeiten/löschen | 📋 Policy Configurations |
| Policy-Statistiken sehen | 📋 Policy Configurations (Widgets) |
| Verstehen wie Policies funktionieren | 🎓 Policy Onboarding (Erklärungen) |
| Stornierungsregeln ändern | 📋 Policy Configurations |
| Umbuchungsregeln ändern | 📋 Policy Configurations |
| Mitarbeiter-Qualifikationen festlegen | ⚙️ Company Assignment Configs |
| Bestimmen wer welchen Service macht | ⚙️ Company Assignment Configs |

---

**Erstellt:** 2025-10-13 15:30 UTC
**Für:** User-Dokumentation
**Status:** Erklärungs-Dokument
