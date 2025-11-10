# ADR-005: Non-blocking Cancellation mit Reschedule-first

**Status:** Akzeptiert
**Datum:** 2025-11-03
**Autor:** Platform Team

## Kontext

Bisherige Policy: 24h-Cutoff für Cancel & Reschedule blockierte kurzfristige Stornos und Umbuchungen.

**Problem:**
- Schlechtes Kundenerlebnis durch starre Zeitfenster
- Zahlung erfolgt vor Ort beim Friseur (keine Vorauszahlung)
- Keine finanziellen Risiken durch Online-Stornierungen
- Kunden frustriert bei legitimen Umbuchungswünschen

**Bisheriges Verhalten:**
- Cancel <24h vor Termin → Blockiert mit Fehlermeldung
- Reschedule <24h vor Termin → Blockiert mit Fehlermeldung
- Keine Flexibilität für Kunden

## Entscheidung

**Policy-Änderungen:**

1. **Non-blocking Cancellation:**
   - Cancel jederzeit erlaubt (0 Min Cutoff)
   - Keine zeitlichen Einschränkungen mehr

2. **Non-blocking Reschedule:**
   - Reschedule jederzeit erlaubt (0 Min Cutoff)
   - Keine zeitlichen Einschränkungen mehr

3. **Reschedule-first Flow:**
   - Agent bietet bei jeder Storno-Absicht automatisch Umbuchung an
   - "Möchten Sie den Termin lieber verschieben statt stornieren?"
   - Erst nach Ablehnung wird storniert

4. **Branch Notification:**
   - Filiale wird bei JEDER Stornierung informiert
   - Dual-Channel: Email + Filament UI Notification
   - Enthält Info: "Direkt neu gebucht" oder "Ohne Umbuchung storniert"

5. **Keine Limits:**
   - Max-Reschedule-Policy entfernt
   - Kunden können beliebig oft umbuchen
   - Missbrauchsschutz über Branch-Monitoring

## Konsequenzen

### Positiv
- ✅ Besseres Kundenerlebnis (keine frustrierenden Blockierungen)
- ✅ Filiale behält Kontrolle durch Notifications
- ✅ Flexible Gebühren-Option später hinzufügbar (bei Bedarf)
- ✅ Reschedule-first reduziert tatsächliche Stornos
- ✅ Passt zur Zahlungsstruktur (Vor-Ort-Zahlung)

### Negativ
- ⚠️ Potentieller Missbrauch möglich (keine Limits mehr)
- ⚠️ Filiale muss Notifications regelmäßig prüfen
- ⚠️ Last-Minute-Stornos können Leerlauf verursachen

### Mitigation
- Branch erhält sofortige Benachrichtigung (Email + UI)
- Telemetrie erlaubt Missbrauchserkennung
- Gebühren-System kann später aktiviert werden

## Implementierung

### Backend (PolicyEngine)
```
PolicyEngine.canCancel(appointment)
  → always return true (kein Cutoff-Check mehr)

PolicyEngine.canReschedule(appointment)
  → always return true (kein Cutoff-Check mehr)
```

### Retell Agent Flow
```
User: "Ich möchte meinen Termin stornieren"
Agent: "Kein Problem. Möchten Sie den Termin lieber auf einen anderen Tag verschieben?"

Fall A: User sagt Ja
  → check_availability()
  → reschedule_appointment()
  → Branch-Notification: "Direkt neu gebucht auf [Datum]"

Fall B: User sagt Nein
  → cancel_appointment()
  → Branch-Notification: "Storniert ohne Umbuchung"
```

### Notifications
**Email-Template:**
```
Betreff: Stornierung für [Termin]

Kunde [Name] hat Termin am [Datum] um [Zeit] storniert.
Status: [Direkt neu gebucht am X | Ohne Umbuchung storniert]

Dienstleistung: [Service]
Mitarbeiter: [Staff]
```

**Filament UI Notification:**
- Erscheint in Glocken-Icon
- Kategorie: "appointment_cancelled"
- Aktionen: "Details anzeigen", "Als gelesen markieren"

### Telemetrie (neu)
- `reschedule_offered`: Wie oft Agent Umbuchung angeboten hat
- `reschedule_accepted`: Wie oft Kunde angenommen hat
- `reschedule_declined`: Wie oft Kunde abgelehnt hat
- `branch_notified`: Notifications erfolgreich versendet

## Alternativen (verworfen)

**Alternative 1: Gebühren für Last-Minute-Stornos**
- Verworfen: Zahlung vor Ort, keine Online-Payment-Integration

**Alternative 2: Gestaffelter Cutoff (6h/12h statt 24h)**
- Verworfen: Immer noch zu restriktiv, schlechtes UX

**Alternative 3: Warn-Transfer zu Filiale bei Storno-Absicht**
- Verworfen: Zu komplex, verlängert Call-Dauer

## Referenzen

- E2E-Dokumentation: `/docs/e2e/index.html` (Section B, C)
- E2E-Spezifikation: `/docs/e2e/e2e.md` (FR-2, FR-3, Matrix 4, Test 3)
- GATE 0 Documentation: `/docs/e2e/GATE0_SUMMARY_100.md`
- Changelog: `/docs/e2e/CHANGELOG.md`

## Review & Approval

**Reviewed by:** Platform Team
**Approved by:** Product Owner
**Datum:** 2025-11-03
**Status:** ✅ Akzeptiert und implementiert in Dokumentation

---

**Letzte Aktualisierung:** 2025-11-03
**Nächster Review:** Bei Änderungen am Gebühren-/Payment-System
