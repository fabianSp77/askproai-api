# 🎯 AskProAI Business Model Visualisierung

## Übersicht der drei Hauptmodelle

### 📱 Modell 1: Simple Business (z.B. Einzelpraxis)

```
┌─────────────────────────────────────┐
│        Dr. Schmidt Praxis           │
│                                     │
│  📞 Eine Telefonnummer              │
│  🏢 Ein Standort                    │
│  👥 3 Mitarbeiter                   │
│  📋 5 Services                      │
└─────────────────────────────────────┘
                 ↓
         [Einfache Buchung]
                 ↓
┌─────────────────────────────────────┐
│  Kunde → AI → Termin → Bestätigung  │
└─────────────────────────────────────┘
```

**Beispiel-Dialog:**
```
AI: "Guten Tag, Praxis Dr. Schmidt. Wie kann ich Ihnen helfen?"
Kunde: "Ich hätte gerne einen Termin."
AI: "Gerne. Für welche Behandlung?"
Kunde: "Vorsorgeuntersuchung"
AI: "Wann hätten Sie Zeit? Ich habe morgen um 10:00 oder 14:00 Uhr frei."
```

### 🏢 Modell 2: Multi-Branch Hotline (z.B. Fitnessstudio-Kette)

```
┌─────────────────────────────────────────────┐
│              FitXpert GmbH                  │
│         📞 Zentrale: 0800-FITNESS          │
└─────────────────────────────────────────────┘
                    ↓
    ┌───────────────┴───────────────┐
    ↓               ↓               ↓
┌─────────┐    ┌─────────┐    ┌─────────┐
│ Berlin  │    │ Hamburg │    │ München │
│ Mitte   │    │ Altona  │    │ Zentrum │
├─────────┤    ├─────────┤    ├─────────┤
│ ✓ Probe │    │ ✓ Probe │    │ ✓ Probe │
│ ✓ Yoga  │    │ ✓ PT    │    │ ✓ Sauna │
└─────────┘    └─────────┘    └─────────┘
```

**Beispiel-Dialog:**
```
AI: "Willkommen bei FitXpert! Möchten Sie ein Probetraining vereinbaren?"
Kunde: "Ja, gerne."
AI: "Perfekt! In welcher Stadt möchten Sie trainieren?"
Kunde: "In Berlin"
AI: "Wir haben Studios in Berlin Mitte, Charlottenburg und Prenzlauer Berg. Welches liegt für Sie am günstigsten?"
Kunde: "Mitte wäre super"
AI: "Ausgezeichnet! Wann hätten Sie Zeit? Morgen um 10:00 oder 17:00 Uhr?"
```

### 💇 Modell 3: Complex Service Matrix (z.B. Friseur-Kette)

```
┌───────────────────────────────────────────────────┐
│              Hair & Beauty Group                  │
│                                                   │
│  Zentrale Verwaltung + Individuelle Standorte    │
└───────────────────────────────────────────────────┘
                        ↓
        ┌───────────────┴───────────────┐
        ↓                               ↓
┌─────────────────────┐        ┌─────────────────────┐
│   Berlin Premium    │        │   Berlin Express    │
├─────────────────────┤        ├─────────────────────┤
│ Services:           │        │ Services:           │
│ • Schnitt    ⭐⭐⭐  │        │ • Schnitt    ⭐⭐    │
│ • Färben     ⭐⭐⭐  │        │ • Waschen    ⭐     │
│ • Extensions ⭐⭐⭐  │        │ • Föhnen     ⭐     │
│ • Hochzeit   ⭐⭐⭐  │        │ (Kein Färben)       │
├─────────────────────┤        ├─────────────────────┤
│ Team:               │        │ Team:               │
│ Anna  [E][F][X][H]  │        │ Marie [S][W]        │
│ Klaus [E][F]        │        │ Tom   [S][W]        │
│ Lisa  [F][X][H]     │        │                     │
└─────────────────────┘        └─────────────────────┘

Legende: [S]=Schnitt [F]=Färben [X]=Extensions [H]=Hochzeit [W]=Waschen
         ⭐=Junior ⭐⭐=Senior ⭐⭐⭐=Expert/Premium
```

**Beispiel-Dialog:**
```
AI: "Willkommen bei Hair & Beauty! Was darf ich für Sie tun?"
Kunde: "Ich möchte meine Haare färben lassen und Extensions."
AI: "Gerne! Diese Services bieten wir in unserem Premium-Salon in Berlin Mitte an. Haben Sie einen Lieblings-Stylisten?"
Kunde: "Nein, aber ich hätte gerne jemand Erfahrenes."
AI: "Perfekt! Anna ist unsere Expert-Stylistin für Färben und Extensions. Sie hat morgen um 14:00 Uhr Zeit. Die Behandlung dauert etwa 3 Stunden. Passt das?"
```

## 🔄 Buchungsflow-Unterschiede

### Simple Business Flow
```
Start → Service → Datum → Zeit → Bestätigung
        (1 Option) (Einfach)
```

### Multi-Branch Flow
```
Start → Service → FILIALE → Datum → Zeit → Bestätigung
                  (Auswahl)
```

### Complex Matrix Flow
```
Start → Service → Filiale → MITARBEITER → Datum → Zeit → Bestätigung
        (Prüfung: Wer kann das?)  (Nach Skill)
```

## 📊 Komplexitäts-Vergleich

| Feature | Simple | Multi-Branch | Complex Matrix |
|---------|--------|--------------|----------------|
| Filialen | 1 | 3-10 | 3-50 |
| Mitarbeiter/Filiale | 1-5 | 5-15 | 10-30 |
| Services | 3-10 | 10-20 | 20-100 |
| Service-Varianten | Keine | Wenige | Viele |
| Skill-Level | Nein | Optional | Pflicht |
| Routing-Logik | Keine | Einfach | Komplex |
| Setup-Zeit | 5 Min | 15 Min | 30-60 Min |

## 🎨 UI-Konzept für Service-Matrix-Verwaltung

### Admin-Ansicht: Mitarbeiter-Kompetenz-Matrix

```
┌─────────────────────────────────────────────────────┐
│ 🏢 Filiale: Berlin Premium                    [▼]   │
├─────────────────────────────────────────────────────┤
│                                                     │
│ Mitarbeiter-Service-Matrix            [+ Service]  │
│                                                     │
│ ┌─────────┬────────┬────────┬──────┬────────────┐ │
│ │    👤    │   ✂️   │   🎨   │  💇  │     👰     │ │
│ │         │Schnitt │ Färben │ Ext. │ Hochzeit   │ │
│ ├─────────┼────────┼────────┼──────┼────────────┤ │
│ │ Anna B. │   ⭐⭐⭐ │   ⭐⭐⭐ │  ⭐⭐⭐│    ⭐⭐⭐    │ │
│ │ Klaus M.│   ⭐⭐⭐ │   ⭐⭐  │   -  │     -      │ │
│ │ Lisa S. │    -   │   ⭐⭐⭐ │  ⭐⭐⭐│    ⭐⭐⭐    │ │
│ │ [+ Neu] │        │        │      │            │ │
│ └─────────┴────────┴────────┴──────┴────────────┘ │
│                                                     │
│ Klicken Sie auf einen Stern um das Level zu ändern │
│                                                     │
│ ⭐ Junior (Ausbildung)                              │
│ ⭐⭐ Senior (2+ Jahre)                               │
│ ⭐⭐⭐ Expert (5+ Jahre + Zertifikate)               │
└─────────────────────────────────────────────────────┘
```

### Kunden-Ansicht: Verfügbarkeits-Kalender

```
┌─────────────────────────────────────────────────────┐
│ 📅 Verfügbare Termine für: Färben + Extensions      │
├─────────────────────────────────────────────────────┤
│                                                     │
│ Filiale: Berlin Premium ✓                           │
│ Service-Dauer: ca. 3 Stunden                        │
│                                                     │
│ ┌─── Diese Woche ───┐  ┌─── Nächste Woche ───┐    │
│ │                   │  │                      │    │
│ │ Mi 19.06          │  │ Mo 24.06             │    │
│ │ ⚪ 10:00 - Anna   │  │ ⚪ 09:00 - Anna      │    │
│ │ ⚪ 14:00 - Lisa   │  │ ⚪ 11:00 - Lisa      │    │
│ │                   │  │ ⚪ 15:00 - Anna      │    │
│ │ Do 20.06          │  │                      │    │
│ │ ⚪ 11:00 - Anna   │  │ Di 25.06             │    │
│ │                   │  │ ⚪ 10:00 - Lisa      │    │
│ └───────────────────┘  └──────────────────────┘    │
│                                                     │
│         💡 Anna und Lisa sind Expert-Stylisten      │
└─────────────────────────────────────────────────────┘
```

## 🚀 Skalierbarkeits-Konzept

### Hierarchie-Ebenen

```
AskProAI Platform
    │
    ├── 🏢 Tenant Level (Unternehmen)
    │   ├── Master Services
    │   ├── Globale Einstellungen
    │   └── Zentrale Telefonnummer
    │
    ├── 🏬 Branch Level (Filiale)
    │   ├── Service Overrides
    │   ├── Lokale Öffnungszeiten
    │   └── Filial-Telefonnummer
    │
    └── 👤 Staff Level (Mitarbeiter)
        ├── Persönliche Skills
        ├── Verfügbarkeiten
        └── Zertifizierungen
```

### Onboarding-Wizard für verschiedene Modelle

```
Start: "Welches Geschäftsmodell passt zu Ihnen?"

┌─────────────┐  ┌─────────────┐  ┌─────────────┐
│   Simple    │  │Multi-Branch │  │  Complex    │
│             │  │             │  │   Matrix    │
│ [Auswählen] │  │ [Auswählen] │  │ [Auswählen] │
└─────────────┘  └─────────────┘  └─────────────┘
       ↓                ↓                 ↓
   5 Min Setup     15 Min Setup     Guided Setup
                                    mit Experten
```

## 💡 Innovations-Potenzial

### 1. **AI-gestützte Optimierung**
- Automatische Mitarbeiter-Zuteilung basierend auf Kundenpräferenzen
- Vorhersage von No-Shows
- Dynamische Preisgestaltung

### 2. **Visual Analytics**
```
Auslastungs-Heatmap (Beispiel Friseur-Kette)
       Mo  Di  Mi  Do  Fr  Sa  So
09:00  🟡  🟢  🟢  🟡  🔴  🔴  ⚫
10:00  🟡  🟡  🟢  🟡  🔴  🔴  ⚫
11:00  🟠  🟡  🟡  🟠  🔴  🔴  ⚫
...
🟢 <50% 🟡 50-70% 🟠 70-85% 🔴 >85% ⚫ Geschlossen
```

### 3. **Smart Routing Features**
- Kunden-Historie: "Sie waren zuletzt bei Anna"
- Präferenz-Learning: "Kunden wie Sie buchen oft..."
- Geo-Routing: "Nächste Filiale zu Ihrer Adresse"

Diese Visualisierung zeigt die Flexibilität und Skalierbarkeit des AskProAI-Systems für verschiedene Geschäftsmodelle.