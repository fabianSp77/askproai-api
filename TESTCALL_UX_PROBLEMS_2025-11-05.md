# Testanruf Analyse: UX-Probleme Identifiziert

**Call ID**: `call_3aa2c23a5f45c874a674b59106c`
**Zeit**: 16:01:04 Uhr
**Dauer**: 66 Sekunden
**Status**: Abgebrochen (Kunde frustriert)

---

## Executive Summary

Der Testanruf hat **2 kritische UX-Probleme** aufgedeckt, die zu Kundenfrustration und Abbruch führten:

1. **P1-CRITICAL: Fehlende Service-Disambiguierung**
   - Agent fragt nicht nach Herren- vs. Damenhaarschnitt
   - Kunde sagt nur "Haarschnitt" → Agent akzeptiert ohne Klärung

2. **P0-CRITICAL: Keine Terminvorschläge bei offener Anfrage**
   - Kunde fragt 3x: "Was haben Sie heute noch frei?"
   - Agent antwortet 3x: "Um wie viel Uhr möchten Sie?"
   - Agent ruft NICHT check_availability auf
   - Agent macht KEINE Terminvorschläge

**Resultat**: Kunde bricht frustriert ab, kein Termin gebucht

---

## Vollständiges Transkript mit Analyse

### Turn 1: Begrüßung
```
Agent: "Willkommen bei Friseur 1! Wie kann ich Ihnen helfen?"
User: "Ja, guten Tag. Ich hätte gern einen Termin für für heute noch.
       Haben Sie da heute noch was was frei?"
```

**Analyse**:
- ✅ Kunde äußert klare Absicht: Termin heute
- ✅ Kunde fragt nach Verfügbarkeit: "was noch frei?"
- ❌ Kunde nennt KEINE Uhrzeit (offene Anfrage)

**Erwartetes Verhalten**:
Agent sollte check_availability aufrufen und verfügbare Zeiten anbieten:
```
"Gerne! Heute haben wir noch freie Termine um 14:00, 16:30 und 18:00 Uhr.
 Welche Zeit passt Ihnen am besten?"
```

**Tatsächliches Verhalten**:
```
Agent: "Ich kann Ihnen gerne einen Termin buchen. Für welchen Service
        interessieren Sie sich? Haarschnitt oder Färben?"
```

⚠️ Agent ignoriert die Verfügbarkeitsfrage komplett!

---

### Turn 2: Service-Auswahl

```
User: "Ja, genau, Haarschnitt."
Agent: "Wie ist Ihr Name? Dann kann ich alles für den Haarschnitt heute noch vorbereiten."
```

**Analyse**:
- ❌ **Problem 1 identifiziert**: Agent fragt NICHT nach Herren vs. Damen
- Es gibt im System:
  - Herrenhaarschnitt (32€, 55 Min)
  - Damenhaarschnitt (45€, 45 Min)
- Agent akzeptiert "Haarschnitt" ohne Disambiguierung

**Erwartetes Verhalten**:
```
Agent: "Gerne, einen Haarschnitt! Ist das ein Herrenhaarschnitt oder
        Damenhaarschnitt?"
```

**Tatsächliches Verhalten**:
Agent springt direkt zur Namensabfrage ohne zu klären welcher Service.

---

### Turn 3: Namensabfrage
```
User: "Hans Schuster."
Agent: "Perfekt, Herr Schuster! Jetzt brauche ich nur noch die Uhrzeit für
        Ihren Haarschnitt heute. Um wie viel Uhr möchten Sie kommen?"
```

**Analyse**:
- Agent sammelt Name
- Agent fragt nach Uhrzeit
- ⚠️ Kunde hatte BEREITS nach Verfügbarkeit gefragt (ignoriert)

---

### Turn 4: Kunde wiederholt Verfügbarkeitsfrage

```
User: "Ja, haben Sie heute, was haben Sie denn heut noch frei?"
Agent: "Ich habe jetzt Ihren Namen und den Service. Jetzt brauche ich nur
        noch die Uhrzeit für Ihren Haarschnitt. Um wie viel Uhr möchten
        Sie heute kommen?"
```

**Analyse**:
- ❌ **Problem 2 verschärft**: Kunde fragt ZUM ZWEITEN MAL nach Verfügbarkeit
- Agent IGNORIERT die Frage erneut
- Agent besteht auf Uhrzeit vom Kunden
- Keine check_availability aufgerufen

**Erwartetes Verhalten**:
```
Agent: [ruft check_availability auf]
Agent: "Heute haben wir noch Termine frei um 14:00, 15:30, 17:00 und
        18:30 Uhr. Welche Zeit würde Ihnen passen?"
```

---

### Turn 5: Kunde wird frustriert

```
User: "Ja, können Sie mir nicht sagen, wann Sie heut noch einen Termin frei haben?"
Agent: "Ich verstehe, dass Sie gerne einen Termin hätten. Um Ihnen zu helfen,
        benötige ich noch die Uhrzeit für Ihren Haarschnitt heute.
        Um wie viel Uhr möchten Sie kommen?"
```

**Analyse**:
- ❌ **Problem 2 kritisch**: Kunde fragt ZUM DRITTEN MAL nach Verfügbarkeit
- Agent verwendet sogar Empathie-Phrase ("Ich verstehe")
- Aber Agent macht IMMER NOCH keine Terminvorschläge
- Agent fragt wieder nach Uhrzeit
- **Call endet frustriert ohne Buchung**

---

## Function Call Analyse

**Erwartete Function Calls**:
1. `check_availability_v17` - nach "heute noch frei?"
2. `book_appointment_v17` - nach Bestätigung der Zeit

**Tatsächliche Function Calls**:
```
KEINE Function Calls gefunden im Log!
```

**Kritischer Befund**:
- Agent hat NIEMALS check_availability aufgerufen
- Obwohl Kunde 3x explizit nach Verfügbarkeit fragt
- Agent bleibt in "Datensammlung-Modus" stecken
- Kunde erhält nie die angeforderte Information

---

## Root Cause Analysis

### Problem 1: Fehlende Service-Disambiguierung

**Ursache**:
Conversation Flow Node "Buchungsdaten sammeln" fehlt Logik zur Service-Disambiguierung.

**Aktuelles Verhalten**:
```
User: "Haarschnitt"
→ Agent akzeptiert direkt
→ Keine Nachfrage Herren/Damen
→ Falltback zu Standard-Service (vermutlich Damenhaarschnitt alphabetisch)
```

**Benötigtes Verhalten**:
```
User: "Haarschnitt"
→ Agent erkennt: Ambiguität (Herren vs. Damen existieren beide)
→ Agent fragt: "Herrenhaarschnitt oder Damenhaarschnitt?"
→ User wählt spezifischen Service
→ Agent verwendet korrekten Service für Verfügbarkeit/Buchung
```

**Services, die Disambiguierung brauchen**:
- "Haarschnitt" → Herrenhaarschnitt vs. Damenhaarschnitt
- "Föhnen" → Föhnen & Styling Damen vs. Föhnen & Styling Herren
- "Schnitt" → Herrenhaarschnitt vs. Damenhaarschnitt vs. Trockenschnitt

---

### Problem 2: Keine Terminvorschläge bei offener Verfügbarkeitsanfrage

**Ursache 1: Conversation Flow Design**

Der aktuelle Flow verlangt:
1. Service sammeln ✅
2. Datum sammeln ✅
3. **Uhrzeit sammeln** ← HIER steckt er fest
4. DANN erst check_availability

**Problem**:
Flow geht davon aus, dass Kunde immer eine Wunschuhrzeit nennt.
Aber viele Kunden fragen: "Was ist noch frei?"

**Ursache 2: Fehlende "Availability-First" Route**

Es gibt KEINE Node/Route für:
```
User: "Was ist heute noch frei?"
→ Agent: check_availability(datum=heute, ohne Uhrzeit)
→ Agent: Zeige alle verfügbaren Slots
→ User wählt Zeit
→ Agent: Buche gewählte Zeit
```

**Ursache 3: Prompt-Instruktion fehlt**

Global Prompt sagt NICHT explizit:
```
"Wenn Kunde nach Verfügbarkeit fragt OHNE Uhrzeit zu nennen:
1. Rufe check_availability auf (mit nur Datum, ohne Uhrzeit)
2. Liste verfügbare Zeiten
3. Lass Kunde wählen"
```

---

## Lösungsvorschläge

### Lösung Problem 1: Service-Disambiguierung

#### Option A: Prompt-Enhancement (Schnell, empfohlen)

**Ergänze Global Prompt mit**:

```markdown
## WICHTIG: Service-Disambiguierung

**Bei mehrdeutigen Service-Anfragen IMMER nachfragen:**

### Services die Klärung brauchen:
- "Haarschnitt" → Frage: "Herrenhaarschnitt oder Damenhaarschnitt?"
- "Föhnen" oder "Styling" → Frage: "Föhnen für Damen oder Herren?"
- "Schnitt" → Frage: "Meinen Sie Herrenhaarschnitt, Damenhaarschnitt, oder Trockenschnitt?"

**Beispiel-Dialog:**
User: "Ich möchte einen Haarschnitt buchen"
Agent: "Gerne! Möchten Sie einen Herrenhaarschnitt (32€, 55 Min) oder
        Damenhaarschnitt (45€, 45 Min)?"
User: "Herrenhaarschnitt"
Agent: ✅ Verwendet Herrenhaarschnitt für Buchung

**NIEMALS:**
❌ Einfach annehmen welchen Service der Kunde meint
❌ Default zum ersten alphabetischen Service
❌ Ohne Nachfrage fortfahren bei Ambiguität
```

**Vorteil**: Einfach, schnell implementierbar
**Nachteil**: Verlässt sich auf LLM-Verständnis

---

#### Option B: Conversation Flow Node (Strukturiert, robust)

**Erstelle neue Node**: "Service Disambiguation"

**Flow**:
```
User sagt Service-Keyword
  ↓
Check: Ist Service eindeutig?
  ├─ JA → Weiter zu "Datum sammeln"
  └─ NEIN → Gehe zu "Service Disambiguation Node"
        ↓
        Agent: "Meinen Sie [Option A] oder [Option B]?"
        ↓
        User wählt
        ↓
        Setze spezifischen Service
        ↓
        Weiter zu "Datum sammeln"
```

**Implementierung**:
1. Add Node "service_disambiguation"
2. Add dynamic variables:
   - {{ambiguous_services}} - Liste der Optionen
   - {{selected_service}} - Finaler Service
3. Node Prompt: "Bitte wählen Sie: {{ambiguous_services}}"
4. Transition zu "collect_booking_data" mit selected_service

**Vorteil**: Strukturiert, zuverlässig, testbar
**Nachteil**: Mehr Arbeit, Conversation Flow muss geändert werden

---

### Lösung Problem 2: Terminvorschläge bei offener Verfügbarkeitsanfrage

#### Option A: Prompt-Enhancement (Schnell)

**Ergänze Global Prompt mit**:

```markdown
## WICHTIG: Proaktive Terminvorschläge

**Wenn Kunde nach Verfügbarkeit fragt OHNE Uhrzeit zu nennen:**

### Trigger-Phrases:
- "Was haben Sie heute noch frei?"
- "Wann haben Sie noch Termine?"
- "Welche Zeiten sind verfügbar?"
- "Haben Sie heute noch was frei?"
- "Wann kann ich kommen?"

### Richtiges Verhalten:
1. ✅ Rufe SOFORT check_availability auf (mit Datum, OHNE Uhrzeit)
2. ✅ Zeige dem Kunden 3-5 verfügbare Zeiten
3. ✅ Lass Kunden wählen
4. ✅ Buche gewählte Zeit

**Beispiel-Dialog:**
User: "Haben Sie heute noch was frei?"
Agent: [ruft check_availability(datum=heute) auf]
Agent: "Ja, heute habe ich noch Termine frei um 14:00, 16:30 und 18:00 Uhr.
        Welche Zeit würde Ihnen passen?"
User: "16:30 passt"
Agent: [bucht 16:30]

**NIEMALS:**
❌ "Um wie viel Uhr möchten Sie kommen?" wenn Kunde nach Verfügbarkeit fragt
❌ Den Kunden zwingen eine Zeit zu nennen bevor du Verfügbarkeit checkst
❌ Die Verfügbarkeitsfrage ignorieren
```

**Vorteil**: Einfach, schnell
**Nachteil**: LLM könnte Trigger-Phrases übersehen

---

#### Option B: Neue "Availability-First" Node (Strukturiert)

**Erstelle neue Route im Flow**:

```
Intent Router
  ├─ Existing: "Termin buchen" (mit Uhrzeit)
  └─ NEW: "Verfügbarkeit prüfen" (ohne Uhrzeit)
        ↓
    "Availability Check Node"
        ├─ Collect: Service, Datum
        ├─ Call: check_availability(service, datum, keine Uhrzeit)
        ├─ Show: Liste verfügbarer Zeiten
        └─ User wählt Zeit
              ↓
          "Confirm Booking Node"
              └─ Book selected time
```

**Node Prompt** ("Availability Check Node"):
```markdown
Du hast diese verfügbaren Zeiten für {{service}} am {{datum}}:
{{available_times}}

Sage dem Kunden:
"Heute haben wir noch Termine frei um {{time_1}}, {{time_2}} und {{time_3}}.
 Welche Zeit würde Ihnen am besten passen?"

Warte auf Auswahl, dann buche die gewählte Zeit.
```

**Vorteil**: Strukturiert, expliziter Workflow, besser testbar
**Nachteil**: Flow-Änderungen erforderlich

---

#### Option C: Backend Intelligence (Robust)

**Erweitere check_availability_v17 Backend**:

**Neue Funktion**: `suggest_available_times()`

```php
/**
 * Smart availability suggestions
 *
 * Wenn check_availability aufgerufen wird OHNE Uhrzeit:
 * - Finde alle verfügbaren Slots für das Datum
 * - Filtere nach Arbeitszeiten (9-19 Uhr)
 * - Returniere Top 3-5 Optionen
 * - Formatiert für natürliche Sprache
 */
public function suggestAvailableTimes($params, $callId)
{
    $datum = $params['datum'];
    $service = $params['service'];

    // No time specified → Find all available slots
    if (empty($params['uhrzeit'])) {
        $slots = $this->findAllAvailableSlots($datum, $service);

        $top5 = array_slice($slots, 0, 5);

        return $this->responseFormatter->success([
            'available' => true,
            'suggestions' => $top5,
            'message' => sprintf(
                'Heute haben wir noch Termine frei um %s. Welche Zeit passt Ihnen?',
                $this->formatTimeList($top5)
            )
        ]);
    }

    // Time specified → Check specific slot (existing logic)
    return $this->checkSpecificSlot($datum, $service, $params['uhrzeit']);
}
```

**Vorteil**:
- Backend macht die intelligente Arbeit
- LLM muss nur check_availability aufrufen
- Funktioniert auch wenn LLM Prompt nicht perfekt folgt

**Nachteil**: Backend-Änderung erforderlich

---

## Empfohlene Implementierung (Priorität)

### Phase 1: Quick Wins (Prompt-Enhancement) - 30 Minuten

**Schritt 1**: Ergänze Global Prompt mit beiden Sections:
1. Service-Disambiguierung Rules
2. Proaktive Terminvorschläge Rules

**Schritt 2**: Test Call
- "Ich möchte einen Haarschnitt" → Prüfe: Fragt nach Herren/Damen?
- "Was ist heute noch frei?" → Prüfe: Macht Terminvorschläge?

**Erwartete Verbesserung**: 70-80% der Fälle behoben

---

### Phase 2: Backend Intelligence (Robust) - 2 Stunden

**Schritt 1**: Erweitere `check_availability_v17`:
- Wenn keine Uhrzeit → `suggestAvailableTimes()`
- Returniere formatierte Liste

**Schritt 2**: Update Response Formatter
- Natürliche Sprache: "um 14:00, 16:30 und 18:00 Uhr"
- Strukturierte Daten für LLM

**Erwartete Verbesserung**: 95% der Fälle behoben

---

### Phase 3: Flow Optimization (Strukturiert) - 1 Tag

**Schritt 1**: Neue Nodes im Conversation Flow
- "Service Disambiguation Node"
- "Availability-First Node"

**Schritt 2**: Update Intent Router
- Erkenne "Verfügbarkeitsanfrage" vs. "Direkte Buchung"

**Erwartete Verbesserung**: 99% der Fälle, perfekter Workflow

---

## Testing Scenarios

### Test 1: Service Disambiguierung

**Input**: "Ich möchte einen Haarschnitt buchen"
**Erwartung**:
```
Agent: "Gerne! Möchten Sie einen Herrenhaarschnitt oder Damenhaarschnitt?"
User: "Herrenhaarschnitt"
Agent: ✅ Verwendet Herrenhaarschnitt
```

---

### Test 2: Offene Verfügbarkeitsanfrage

**Input**: "Haben Sie heute noch was frei?"
**Erwartung**:
```
Agent: [ruft check_availability auf]
Agent: "Ja, heute haben wir noch Termine um 14:00, 16:30 und 18:00.
        Welche Zeit passt Ihnen?"
User: "16:30"
Agent: ✅ Bucht 16:30
```

---

### Test 3: Verfügbarkeit mit Service

**Input**: "Was haben Sie heute noch frei für Damenhaarschnitt?"
**Erwartung**:
```
Agent: [ruft check_availability(service=Damenhaarschnitt, datum=heute) auf]
Agent: "Für Damenhaarschnitt haben wir heute noch um 15:00, 17:00 und 19:00.
        Was würde Ihnen passen?"
```

---

### Test 4: Kombiniert (Beide Probleme)

**Input**: "Haarschnitt heute noch möglich?"
**Erwartung**:
```
Agent: "Gerne! Herrenhaarschnitt oder Damenhaarschnitt?"
User: "Herrenhaarschnitt"
Agent: [ruft check_availability auf]
Agent: "Für Herrenhaarschnitt haben wir heute noch um 14:00, 16:00 und 18:00.
        Welche Zeit?"
User: "16:00"
Agent: ✅ Bucht Herrenhaarschnitt um 16:00
```

---

## Zusammenfassung

**2 Kritische UX-Probleme identifiziert**:

1. **Service-Disambiguierung fehlt** (P1)
   - Agent fragt nicht nach Herren vs. Damen bei "Haarschnitt"
   - Kunde könnte falschen Service bekommen
   - Fix: Prompt-Enhancement oder Disambiguation Node

2. **Keine Terminvorschläge** (P0)
   - Agent ignoriert Verfügbarkeitsanfragen 3x
   - Agent besteht auf Uhrzeit vom Kunden
   - Kunde bricht frustriert ab
   - Fix: Prompt-Enhancement + Backend Intelligence

**Empfohlener Ansatz**:
1. Start mit Prompt-Enhancement (30 Min) → 70-80% Fix
2. Add Backend Intelligence (2 Std) → 95% Fix
3. Optional: Flow Restructuring → 99% Perfekt

**Nächster Schritt**:
Soll ich die Prompt-Enhancements jetzt implementieren?

---

**Analysiert von**: Claude AI
**Datum**: 2025-11-05 16:15
**Call ID**: call_3aa2c23a5f45c874a674b59106c
