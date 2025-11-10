# Services UI - Verbesserungsvorschläge

**Datum**: 2025-11-04
**Status**: 💡 Ideensammlung
**Ziel**: Composite-Segmente in Tabelle sichtbar machen

---

## 🎯 Deine Anforderung

> "Bei Ansatzfärbung (Composite-Service) sollen die einzelnen Segmente auch in der Tabelle aufklappbar/zuklappbar sein, sodass man sieht, welche Dienstleistungen dahinter liegen."

---

## 💡 Vorgeschlagene Lösungen

### Option 1: **Mouseover-Popup mit Segmenten** ⭐ EMPFOHLEN

**Beschreibung**: Beim Mouseover auf Composite-Badge wird ein großes Tooltip mit allen Segmenten angezeigt.

**Vorteile**:
- ✅ Keine zusätzliche Spalte nötig
- ✅ Funktioniert perfekt mit bestehendem Design
- ✅ Schnell implementierbar (5 Minuten)
- ✅ Keine komplexe Logik
- ✅ Mobile-freundlich (Tooltip on tap)

**Darstellung**:
```
Herrenhaarschnitt                    [🎨 Composite]
                                            ↓
                                    (Mouseover Tooltip)
┌────────────────────────────────────────────────────────┐
│  🔢 Behandlungsablauf (4 Schritte)                     │
│                                                        │
│  Ⓐ Ansatzfärbung auftragen     30 min + 30 min Pause │
│     [██████████                ]                      │
│                                                        │
│  Ⓑ Auswaschen                  15 min                 │
│     [████                      ]                      │
│                                                        │
│  Ⓒ Formschnitt                 30 min                 │
│     [██████████                ]                      │
│                                                        │
│  Ⓓ Föhnen & Styling            30 min                 │
│     [██████████                ]                      │
│                                                        │
│  ⏱️ Gesamt: 135 min (105 aktiv + 30 Pause)            │
└────────────────────────────────────────────────────────┘
```

**Implementation**: Erweitere Composite-Badge Tooltip mit Segment-Details.

---

### Option 2: **Expandable Rows** (Aufklappbare Zeilen)

**Beschreibung**: Zeilen mit Composite-Services können ausgeklappt werden, um Segmente darunter zu zeigen.

**Vorteile**:
- ✅ Intuitive UX (wie Datei-Explorer)
- ✅ Viel Platz für Details
- ✅ Kann offen bleiben während Scroll

**Nachteile**:
- ⚠️ Filament 3 API komplex
- ⚠️ Mehr vertikaler Platz benötigt
- ⚠️ Mobile-Ansicht schwieriger

**Darstellung**:
```
┌────────────────────────────────────────────────────────┐
│ ▶ Ansatzfärbung  │135min│ 58€ │...│ ⋮                 │ ← Collapsed
├────────────────────────────────────────────────────────┤
│ ▼ Ansatzfärbung  │135min│ 58€ │...│ ⋮                 │ ← Expanded
│ ┌──────────────────────────────────────────────────┐  │
│ │  Ⓐ Ansatzfärbung auftragen  30min + 30min Pause │  │
│ │  Ⓑ Auswaschen               15min                │  │
│ │  Ⓒ Formschnitt              30min                │  │
│ │  Ⓓ Föhnen & Styling         30min                │  │
│ └──────────────────────────────────────────────────┘  │
├────────────────────────────────────────────────────────┤
│ ▶ Dauerwelle     │135min│ 78€ │...│ ⋮                 │
└────────────────────────────────────────────────────────┘
```

**Implementation**: Filament `->expandable()` mit Custom View.

---

### Option 3: **Modal beim Klick auf Composite-Badge**

**Beschreibung**: Klick auf Composite-Badge öffnet Modal mit detaillierter Segment-Ansicht.

**Vorteile**:
- ✅ Volle Kontrolle über Layout
- ✅ Kann interaktiv sein (z.B. Edit-Buttons)
- ✅ Viel Platz für Visualisierungen

**Nachteile**:
- ⚠️ Erfordert zusätzlichen Klick
- ⚠️ Verlässt Tabellen-Kontext
- ⚠️ Nicht so schnell wie Tooltip

**Darstellung**:
```
Klick auf Badge → Modal öffnet sich

┌──────────────────────────────────────────────────────┐
│  Ansatzfärbung - Behandlungsablauf                  ×│
├──────────────────────────────────────────────────────┤
│                                                      │
│  [3 Summary Cards: Gesamt, Aktiv, Pausen]          │
│                                                      │
│  Ⓐ Ansatzfärbung auftragen    30 min + 30 min Pause│
│     [Progress Bar]                                   │
│           ↓                                          │
│  Ⓑ Auswaschen                 15 min                │
│     [Progress Bar]                                   │
│           ↓                                          │
│  ... (wie Detail-Seite)                             │
│                                                      │
│  [Schließen]                                        │
└──────────────────────────────────────────────────────┘
```

**Implementation**: Badge als Action-Button mit Modal-Anzeige.

---

### Option 4: **Dedizierte "Segmente" Spalte**

**Beschreibung**: Neue Spalte nur für Composite-Services, zeigt Segment-Count und Quick-Info.

**Vorteile**:
- ✅ Immer sichtbar (kein Hover nötig)
- ✅ Filterable/Sortierbar nach Segment-Anzahl
- ✅ Klare Trennung

**Nachteile**:
- ⚠️ Zusätzliche Spalte (mehr Platz)
- ⚠️ Leer bei Standard-Services

**Darstellung**:
```
┌──────────────────────────────────────────────────────────────┐
│ Dienstleistung    │ Dauer │ Preis │ Segmente      │ ⋮        │
├───────────────────┼───────┼───────┼───────────────┼──────────┤
│ Herrenhaarschnitt │55 min │ 32 €  │ —             │ ⋮        │
│ Ansatzfärbung     │135min │ 58 €  │ 🔢 4 Schritte │ ⋮        │ ← Klickbar
└───────────────────┴───────┴───────┴───────────────┴──────────┘
```

**Implementation**: Neue TextColumn mit Action zum Öffnen Details.

---

### Option 5: **Inline Mini-Cards**

**Beschreibung**: Composite-Services zeigen Mini-Segment-Cards direkt unter dem Namen (als Description).

**Vorteile**:
- ✅ Immer sichtbar
- ✅ Kein zusätzlicher Klick
- ✅ Kompakt

**Nachteile**:
- ⚠️ Nimmt vertikalen Platz
- ⚠️ Nur wenig Info pro Segment möglich

**Darstellung**:
```
Ansatzfärbung
✓ Aktiv | Cal.com: ... | Behandlungen
Ⓐ 30min  Ⓑ 15min  Ⓒ 30min  Ⓓ 30min  [+30min Pausen]
```

**Implementation**: Erweitere Description mit Segment-Tags.

---

## 📊 Vergleichstabelle

| Feature | Option 1<br>Mouseover | Option 2<br>Expandable | Option 3<br>Modal | Option 4<br>Spalte | Option 5<br>Inline |
|---------|-------------------|-------------------|---------------|----------------|---------------|
| **Geschwindigkeit** | ⭐⭐⭐ | ⭐⭐ | ⭐ | ⭐⭐⭐ | ⭐⭐⭐ |
| **Detailtiefe** | ⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐ | ⭐ |
| **Platzsparend** | ⭐⭐⭐⭐⭐ | ⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐ |
| **Mobile** | ⭐⭐⭐⭐ | ⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐ |
| **Implementation** | ⭐⭐⭐⭐⭐ | ⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐ |

---

## 🎯 Meine Empfehlung

**Option 1: Mouseover-Popup mit Segmenten** ⭐

**Warum?**
1. **Schnellste Implementation** (5 Minuten) - nur Tooltip erweitern
2. **Kein zusätzlicher Platz** - bestehendes Design bleibt
3. **Beste UX** - Info sofort verfügbar ohne Klick
4. **Mobile-friendly** - Tooltip on tap funktioniert
5. **Konsistent** - Passt zum bestehenden Tooltip-System

**Alternative bei komplexeren Anforderungen**: Option 3 (Modal) wenn du später Segment-Editing direkt aus der Tabelle willst.

---

## 🚀 Weitere Verbesserungsideen

### 1. **Online-Buchung Indikator**
- Derzeit nur im Tooltip sichtbar
- **Vorschlag**: Kleines Badge oder Icon direkt beim Service-Namen
- Symbol: 🌐 (online) oder 🏢 (vor Ort)

### 2. **Preis-Visualisierung**
- **Vorschlag**: Farbliche Kategorisierung
  - Grün: 20-40€ (Budget)
  - Blau: 41-80€ (Standard)
  - Lila: 81+€ (Premium)

### 3. **Schnell-Aktionen**
- **Vorschlag**: Häufige Aktionen direkt in Zeile (ohne Dropdown)
  - ⚡ Quick-View (Eye-Icon)
  - 🔄 Cal.com Sync (Sync-Icon)
  - ✏️ Quick-Edit (Pencil-Icon)

### 4. **Kategorie-Icons**
- **Vorschlag**: Visuelle Icons für Kategorien
  - 💇 Haarschnitt
  - 🎨 Färbung
  - 💆 Behandlung
  - ✂️ Styling

### 5. **Verfügbarkeits-Indikator**
- **Vorschlag**: Zeige nächsten verfügbaren Termin-Slot
- Format: "Nächster Slot: Morgen 14:00"
- Farbe: Grün (heute), Gelb (diese Woche), Grau (>1 Woche)

---

## 🧪 Was möchtest du als Nächstes?

**A) Option 1 implementieren** (Mouseover-Popup) ⭐ SCHNELL
**B) Option 2 versuchen** (Expandable Rows) - Experimentell
**C) Option 3 implementieren** (Modal beim Klick)
**D) Option 4 + 5 kombinieren** (Spalte + Inline)
**E) Andere Verbesserungen aus Liste oben**

Oder etwas ganz anderes? Sag mir, welche Richtung am besten passt!

---

**Hinweis**: Option 1 kann ich in 5 Minuten umsetzen. Die anderen Optionen benötigen 15-30 Minuten je nach Komplexität.
