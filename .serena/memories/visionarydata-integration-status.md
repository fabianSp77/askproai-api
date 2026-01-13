# VisionaryData Integration Status

**Letzte Aktualisierung:** 2026-01-13

---

## Firmendaten

**Visionary Data GmbH**
- **Adresse:** Mozartstr. 2, 85659 Forstern
- **Handelsregister:** HRB 272172
- **Registergericht:** Amtsgericht München
- **USt-IdNr:** DE449916795
- **W-IdNr:** 114/139/00281

---
**Ansprechpartner VisionaryData:**
- Sebastian Sager (CTO) - sebastian.sager@visionarydata.de
- Thomas Stanner (GF) - thomas.stanner@visionarydata.de
- Sebastian Gesellensetter - sebastian.gesellensetter@visionarydata.de

---

## Technische Konfiguration

### Webhook
- **URL:** `https://agents-test.ascadi.ai/webhook/eb15a330-b983-4697-b4b4-d3bd61ff41f4-ticketsystem`
- **Auth:** HMAC-SHA256 Signatur
- **Status:** ✅ Funktioniert (alle Deliveries HTTP 200)

### E-Mail Backup
- **Empfänger:** `ticket-support@visionarydata.de` (seit 2026-01-13)
- **Vorher:** fabian@askproai.de + 3x VisionaryData-Adressen

### ServiceOutputConfigurations (Company 1658 - IT-Systemhaus Test GmbH)
- ID 28: Security Incident - Critical Alert
- ID 29: Infrastructure Support - High Priority
- ID 30: Application Support - Standard

---

## Status nach Sebastian-Antwort (13.01.2026)

### Bestätigt von Sebastian
- ✅ **70 parallele Anrufe** reichen aus (nur 2 Telefonkräfte bei VisionaryData)
- ✅ **Error-Handling** ist OK ("fein für mich")
- ✅ **Ticket-ID Feldname:** `ticket_id` (Sebastian baut um)

### Unsere Code-Verbesserungen (13.01.2026)
- ✅ `extractExternalId()` gehärtet (Type-Safety, Längenvalidierung, Sanitization)
- ✅ Tests hinzugefügt (`tests/Unit/ServiceGateway/ExtractExternalIdTest.php`)
- ✅ Filament UI: external_reference in Liste sichtbar + suchbar + Filter

## Offene Punkte

### Warten auf VisionaryData
1. **Thomas** → Feedback zu Gesprächsverlauf/Anpassungen am Voice Agent
2. **Ticket-ID Umbau** → Sebastian baut auf `ticket_id` um (in Arbeit)

### Unsere Kapazität
- **Aktuell:** mindestens 70 parallele Telefonate
- **Erweiterbar:** Jederzeit (Voice-API-Plan upgraden)

---

## E-Mail-Verlauf

### Antwort von Sebastian (13.01.2026)
```
Hallo Fabian,

danke für die schnelle Rückmeldung.

Ich glaube 70 parallele Anrufe sind mehr als genug, nachdem es mMn aktuell 
nur zwei Telefonkräfte geht. Wir werden damit also nicht in Engpässe geraten.

Das Error-Handling ist fein für mich. Bzgl. der Ticket-ID werden wir mit 
ticket_id gehen – ich stell das mal um.

Liebe Grüße
Sebastian
```

### Antwort-Entwurf (noch zu senden)
```
Hallo Sebastian,

perfekt, dann sind wir uns einig!

ticket_id ist auf meiner Seite bereits vorbereitet – sobald ihr
den Umbau abgeschlossen habt, können wir einen kurzen Test machen.

Bzgl. Thomas: Gib Bescheid, wenn er sich meldet.

Beste Grüße,
Fabian
```



---

## Implementierte Features

| Feature | Status | Details |
|---------|--------|---------|
| Webhook-Delivery | ✅ | HMAC-signiert, funktioniert |
| Email-Backup | ✅ | An ticket-support@visionarydata.de |
| Retry-Logik | ✅ | 3 Versuche, Backoff 1min/2min/5min |
| Admin-Alerts | ✅ | E-Mail + Slack (optional) bei permanentem Fehler |
| Ticket-ID-Extraktion | ✅ | Gehärtet am 13.01.2026 - Feldname `ticket_id` bestätigt |
| Exchange Logs | ✅ | Alle Deliveries protokolliert |
| Filament UI | ✅ | external_reference sichtbar + suchbar + filterbar |
| Unit Tests | ✅ | ExtractExternalIdTest.php mit 25+ Tests |

**Technischer Ablauf (verifiziert):**
```
HTTP POST → VisionaryData
    ↓
HTTP 2xx → SUCCESS (external_id gespeichert falls vorhanden)
    ↓
HTTP 4xx/5xx → return false → Exception → Retry nach Backoff
    ↓
Nach 3 Fehlern → failed() → Admin-Alert + Slack
```

**Wichtig:** Keine spezielle HTTP 429 Behandlung - alle HTTP-Fehler gleich behandelt |

---

## Response-Formate von VisionaryData

**Bis 07.01.2026:**
```json
{"message": "Workflow was started"}
```

**Ab 08.01.2026:**
```json
{"success": "Valid HMAC signature", "status": 200}
```

**Erwartet (sobald implementiert):**
```json
{"success": true, "ticket_id": "VD-12345"}
```

---

## Relevante Dateien

- `app/Services/ServiceGateway/OutputHandlers/WebhookOutputHandler.php` - Webhook-Delivery
- `app/Jobs/ServiceGateway/DeliverCaseOutputJob.php` - Retry-Logik
- `app/Mail/VisionaryDataBackupMail.php` - Backup-E-Mail
- `docs/service-gateway/VISIONARY_INTEGRATION_SPEC_CHECKLIST.md` - Offene Spezifikationsfragen
