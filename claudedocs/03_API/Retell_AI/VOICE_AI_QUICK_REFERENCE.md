# Voice AI Quick Reference Card
**Version**: 1.0 | **Date**: 2025-10-23 | **Purpose**: Schnellreferenz für Voice AI Best Practices

---

## Timing Rules

| Situation | Max Pause | Action |
|-----------|-----------|--------|
| Normal Talk | 1-2s | Sofort antworten |
| API Call (fast) | 3s | "Einen Moment..." |
| API Call (slow) | 5s | Zwischenmeldung nach 3s |
| Never | >5s | IMMER Update geben |

**Implementation**: `speak_during_execution: true` in allen Function Nodes

---

## Name Policy

| Kontext | Format | Beispiel |
|---------|--------|----------|
| Begrüßung | [Vorname] [Nachname] | "Willkommen zurück, Hans Schuster!" |
| Während Warten | KEIN Name | "Einen Moment..." (NICHT "Hans!") |
| Verabschiedung | [Vorname] [Nachname] | "Vielen Dank, Hans Schuster!" |
| Formell | Herr/Frau [Nachname] | "Guten Tag, Herr Schuster!" |

❌ **NIEMALS**: "Ich bin noch hier, Hans!" (nur Vorname)

---

## Date/Time Sammlung

**IMMER in 2 Schritten**:
```
1. Datum: "Für welchen Tag?"
2. Zeit:  "Zu welcher Uhrzeit?"
```

**Wenn User NUR Zeit nennt**:
```
User: "gegen dreizehn Uhr"
Agent: "Für heute oder morgen?"  ← PFLICHT!
```

**Smart Inference** (Backend):
```php
IF requested_time < current_time:
    → Assume TOMORROW
ELSE:
    → Assume TODAY
```

---

## Error Messages

| Error Type | User Message | Action |
|------------|-------------|--------|
| `past_time` | "Dieser Zeitpunkt ist leider schon vorbei." | Offer alternatives |
| `no_availability` | "Um [ZEIT] ist leider kein Termin frei." | Offer alternatives |
| `policy_violation` | "Termine können nur bis [FRIST] gebucht werden." | Explain + offer new |
| `technical_error` | "Es gab ein technisches Problem." | Human handoff |

**Template**:
```
"[PROBLEM]. Ich habe aber [ALTERNATIVE1] oder [ALTERNATIVE2]. Passt Ihnen eine dieser Zeiten?"
```

---

## Sprache: Do's & Don'ts

✅ **DO**:
- 1-2 Sätze pro Antwort
- "Einen Moment bitte..."
- "Für welchen Tag?"
- "Ist das korrekt?"

❌ **DON'T**:
- Monologe (>3 Sätze)
- "ähm", "sozusagen", "irgendwie"
- Wiederholungen nach "Ja"
- "Bitte warten Sie" (Befehlston)

---

## Dialog Struktur (Template)

```
1. Begrüßung:  "Guten Tag bei Ask Pro AI. Wie kann ich helfen?"
2. Service:    "Welche Dienstleistung?" (wenn nicht genannt)
3. Datum:      "Für welchen Tag?"
4. Zeit:       "Zu welcher Uhrzeit?"
5. Prüfung:    "Einen Moment..." [API]
6a. Verfügbar: "[DATUM] um [ZEIT] ist frei. Soll ich das buchen?"
6b. Nicht frei: "Um [ZEIT] ist leider nicht frei. Ich habe [ALT]."
7. Buchung:    "Einen Moment..." [API]
8. Ende:       "Gebucht. Sie erhalten eine Bestätigung. Auf Wiederhören!"
```

**Optimale Dauer**: 30-60 Sekunden

---

## Global Prompt Checklist

```markdown
✅ Rolle & Kontext (oben)
✅ KRITISCHE Regeln (oben, nicht unten!)
✅ Name Policy (POLICY Section)
✅ Datum/Zeit Strategie (2 Schritte!)
✅ Fehlerbehandlung (Empathie + Alternativen)
✅ Kurze Antworten (1-2 Sätze)
✅ Turn-Taking (0.5-1s Response Time)
✅ Beispiele mit ✅/❌
```

---

## Node Instructions Template

```json
{
  "instruction": {
    "type": "prompt",
    "text": "[ACTION DESCRIPTION]\n\n**SCHRITT 1**: [First action]\n- Bullet point\n\n**WICHTIG**:\n- Constraint 1\n- Constraint 2\n\n**TRANSITION**: When [condition] → [next_node]"
  }
}
```

**Best Practices**:
- Spezifisch (nicht vage)
- Formatiert (Markdown)
- Beispiele (wo sinnvoll)
- Constraints (WICHTIG Section)

---

## Testing Quick Checks

**Manual Tests** (5 Szenarien):
1. ✅ Standard Booking (User weiß alles)
2. ✅ Implicit Time (nur Zeit genannt)
3. ✅ Past Time Recovery (13:00 um 15:42)
4. ✅ No Availability (Slot belegt)
5. ✅ Name Policy (bekannter Kunde)

**Automated Tests**:
```php
test_infers_tomorrow_when_time_passed()
test_selects_herrenhaarschnitt_not_beratung()
test_offers_alternatives_instead_of_terminating()
test_uses_full_name_in_greeting()
```

---

## Common Pitfalls

| Problem | Symptom | Fix |
|---------|---------|-----|
| Lange Stille | User denkt Call abgebrochen | `speak_during_execution: true` |
| Nur Vorname | "Hans!" statt "Hans Schuster!" | Global Prompt: Name Policy |
| Datum-Fehler | "13:00" → HEUTE (vorbei) | Date Inference Logic |
| Halluzinierte Alternativen | Agent bietet ungeprüfte Zeiten | V17 explicit Function Nodes |
| Abruptes Ende | "Technisches Problem" bei User-Fehler | Error Recovery Flow |

---

## Performance Targets

| Metric | Target | Current | Status |
|--------|--------|---------|--------|
| Call Completion Rate | >85% | ~50% | 🔴 Fix needed |
| Service Match Accuracy | 100% | ~60% | 🔴 Fix needed |
| Date Inference Accuracy | >90% | 0% | 🔴 Fix needed |
| Name Policy Compliance | 100% | ~20% | 🔴 Fix needed |
| Avg Call Duration | 30-60s | ~45s | 🟢 OK |

---

## Deployment Checklist

**Before Deploy**:
- [ ] Global Prompt updated (Name Policy, Date/Time)
- [ ] Node Instructions optimized (Alternativen, Fehler)
- [ ] V17 Flow prepared (explicit Function Nodes)
- [ ] Backend fixes deployed (Service Selection, Date Inference)
- [ ] Tests passing (Unit + E2E)

**Deploy Steps**:
```bash
1. php publish_agent_v17.php
2. Verify via Retell API (check conversation_flow_id)
3. Test Call (manual scenarios 1-5)
4. Monitor logs (30 min)
5. Rollback plan ready (previous version ID)
```

**After Deploy**:
- [ ] Manual test all 5 scenarios
- [ ] Check metrics dashboard
- [ ] Monitor error logs (24h)
- [ ] User feedback survey
- [ ] Document learnings

---

**Related Docs**:
- Full Guide: `VOICE_AI_CONVERSATION_DESIGN_GUIDE_2025.md`
- RCA: `ROOT_CAUSE_ANALYSIS_2025-10-23_CALL_1541.md`
- Deployment: `DEPLOYMENT_PROZESS_RETELL_FLOW.md`
