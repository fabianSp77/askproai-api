# Visionary Data Integration: Specification Checklist

**Datum**: 20. Dezember 2025
**Status**: Offene Fragen an Thomas (Visionary Data)
**Zweck**: Klaerung der Schnittstellen-Spezifikation vor Implementierung

---

## Uebersicht

Diese Checkliste enthaelt 15 offene Fragen, die vor der vollstaendigen Integration mit Visionary Data geklaert werden muessen. Die Fragen sind in 4 Bloecke gruppiert.

---

## Block A: Lookup (ANI → Company/Branch Context)

Visionary soll anhand der Anrufernummer (ANI) den Kontext liefern.

### A1. Lookup API Endpoint
**Frage**: Wie lautet die URL fuer den ANI-Lookup?
- Format: `POST /api/v1/lookup` oder anders?
- Erwartete Latenz: <100ms, <500ms, oder flexibel?

### A2. Request Payload
**Frage**: Welche Felder werden im Lookup-Request erwartet?
```json
{
  "phone_number": "+4915123456789",  // Pflicht?
  "call_id": "call_abc123",          // Pflicht?
  "timestamp": "2025-12-20T14:30:00Z"  // Pflicht?
}
```

### A3. Response Payload
**Frage**: Welche Felder liefert die Lookup-Response?
```json
{
  "tenant_id": "???",
  "site_id": "???",
  "person_id": "???",
  "policies": [],
  "context": {}
}
```
- Welche Felder sind garantiert, welche optional?
- Was bedeuten `tenant_id`, `site_id`, `person_id` semantisch?

### A4. Authentifizierung
**Frage**: Wie authentifizieren wir uns?
- [ ] API Key im Header (`X-API-Key: xxx`)
- [ ] HMAC-SHA256 Signatur
- [ ] OAuth2 Bearer Token
- [ ] Anderes: ___________

---

## Block B: Ticket Intake Webhook

Wir senden neue Tickets an Visionary.

### B1. Webhook Endpoint
**Frage**: Wohin senden wir neue Tickets?
- Direkt an Visionary Middleware?
- Oder direkt an PSA (ManageEngine, Autotask, etc.)?

**Empfehlung von uns**: Visionary Middleware als Ziel, damit ihr PSA-spezifisch adaptieren koennt.

### B2. Payload Format
**Frage**: Akzeptiert ihr unser Schema v1.0?
- Siehe: `docs/service-gateway/SCHEMA_V1.0_TICKET_CREATED.md`
- Oder braucht ihr Jira-kompatibles Format?
- Welche Custom Fields sind noetig?

### B3. Response Semantik
**Frage**: Was liefert ihr in der Response zurueck?
```json
{
  "success": true,
  "ticket_id": "???",        // Eure Ticket-ID?
  "external_reference": "???" // PSA Ticket-ID?
}
```

### B4. Retry Policy
**Frage**: Wie sollen wir bei Fehlern reagieren?
- Bei 5xx: Retry nach X Sekunden?
- Bei 4xx: Kein Retry?
- Rate Limits vorhanden?
- Idempotenz via `event_id` oder `idempotency_key`?

---

## Block C: Transcript / Consent / GDPR

### C1. Transcript-Uebertragung
**Frage**: Wie soll das Transkript uebertragen werden?
- [ ] Inline im JSON Payload (aktuell implementiert)
- [ ] Als Attachment (Base64 encoded)
- [ ] Als Link/Reference zum Abruf via API
- [ ] Gar nicht (nur Summary)

### C2. Transkript-Limit
**Frage**: Gibt es ein Limit fuer Transkript-Laenge?
- Max Zeichen: ___________
- Max Segmente: ___________

### C3. GDPR / Datenschutz
**Frage**: Wie sollen personenbezogene Daten behandelt werden?
- [ ] Plaintext (Kunde hat zugestimmt)
- [ ] Pseudonymisiert (Name → Hashwert)
- [ ] Consent-Flag im Payload erforderlich?

### C4. Kunden-Benachrichtigung
**Frage**: Wer sendet die Bestaetigung an den Endkunden?
- [ ] Wir (AskPro) senden E-Mail nach Ticket-Erstellung
- [ ] Ihr (Visionary) sendet nach PSA-Import
- [ ] Beides
- [ ] Keiner (nur internes Ticket)

---

## Block D: Retries / Idempotenz / Response Semantik

### D1. Timeout
**Frage**: Welchen Timeout sollen wir konfigurieren?
- Lookup API: ___ Sekunden
- Ticket Intake: ___ Sekunden

### D2. Retry Backoff
**Frage**: Welche Retry-Strategie ist gewuenscht?
- [ ] Exponential Backoff (1s, 2s, 4s, 8s, ...)
- [ ] Fixed Interval (z.B. alle 60s)
- [ ] Max Attempts: ___

### D3. Status Sync (Callback)
**Frage**: Wird es einen Callback geben, wenn sich der Ticket-Status aendert?
- Ihr ruft unseren Webhook bei Status-Aenderung?
- Welche Events: created, updated, resolved, closed?

### D4. Error Handling
**Frage**: Welche Fehlercodes sollen wir erwarten?
```
400 - Bad Request (Validation Error)
401 - Unauthorized
403 - Forbidden (Rate Limit?)
404 - ???
409 - Conflict (Duplikat?)
429 - Too Many Requests
500 - Internal Server Error
503 - Service Unavailable
```

---

## Naechste Schritte

1. **Thomas** beantwortet diese Fragen
2. **Fabian** sendet dieses Dokument an Thomas
3. **Wir** implementieren basierend auf den Antworten
4. **Smoke Test** mit Dummy-Endpoint (RequestBin)
5. **Production Integration** nach Freigabe

---

## Technische Voraussetzungen unsererseits

| Komponente | Status | Anmerkung |
|------------|--------|-----------|
| Schema v1.0 | ✅ Fertig | 10 Pflichtfelder definiert |
| Email Backup | ✅ Fertig | Inkl. Transkript + JSON Block |
| Exchange Logging | ✅ Fertig | No-Leak Guarantee, Admin UI |
| Webhook Handler | ⏳ Bereit | Wartet auf Endpoint-URL |
| HMAC Signatur | ⏳ Bereit | Wartet auf Secret |
| Lookup Integration | ⏳ Bereit | Wartet auf Spec |

---

## Kontakt

**AskPro Seite**: Fabian ([email])
**Visionary Seite**: Thomas ([email])
