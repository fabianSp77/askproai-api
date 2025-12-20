# Service Gateway: Schema v1.0 - ticket.created

**Version**: 1.0
**Status**: MVP (Demo-Ready)
**Datum**: 20. Dezember 2025

---

## Executive Summary

Dieses Schema definiert die Struktur des `ticket.created` Events, das an externe Systeme (Visionary Data, PSA-Systeme) gesendet wird, wenn ein Ticket im Service Gateway erstellt wird.

**Design-Prinzipien**:
- Stabiler Kern mit 10 Pflichtfeldern
- Optionale Erweiterungsblöcke
- No-Leak Guarantee (keine internen IDs, Kosten, Prompts)
- Idempotent via `event_id`

---

## Pflichtfelder (Must-Show, v1.0)

| # | Feld | Typ | Beispiel | Beschreibung |
|---|------|-----|----------|--------------|
| 1 | `ticket_id` | string | `TKT-2025-00042` | Eindeutige Ticket-ID (Format: TKT-YYYY-NNNNN) |
| 2 | `created_at` | ISO 8601 | `2025-12-20T14:30:00+01:00` | Erstellungszeitpunkt |
| 3 | `category` | enum | `network` | Kategorie: network, m365, endpoint, print, identity, other |
| 4 | `priority` | enum | `high` | Prioritaet: critical, high, normal, low |
| 5 | `status` | enum | `new` | Status: new, open, pending, resolved, closed |
| 6 | `subject` | string | `Internet funktioniert nicht` | Kurze Betreffzeile (max 255 Zeichen) |
| 7 | `description` | string | `Seit heute Morgen...` | Problembeschreibung (1-3 Saetze) |
| 8 | `customer_name` | string | `Max Mustermann` | Name des Anrufers |
| 9 | `customer_phone` | string | `+4915123456789` | Telefonnummer (E.164 Format) |
| 10 | `customer_location` | string | `Buero 3. Stock` | Standort/Abteilung des Kunden |

---

## Event Envelope

```json
{
  "event_type": "ticket.created",
  "event_id": "550e8400-e29b-41d4-a716-446655440000",
  "idempotency_key": "TKT-2025-00042-v1",
  "timestamp": "2025-12-20T14:30:00+01:00",
  "source": "askpro-gateway",
  "version": "1.0",
  "payload": {
    // ... siehe unten
  }
}
```

---

## Vollstaendiges Payload-Beispiel

```json
{
  "event_type": "ticket.created",
  "event_id": "550e8400-e29b-41d4-a716-446655440000",
  "idempotency_key": "TKT-2025-00042-v1",
  "timestamp": "2025-12-20T14:30:00+01:00",
  "source": "askpro-gateway",
  "version": "1.0",
  "payload": {
    "ticket": {
      "id": "TKT-2025-00042",
      "internal_id": 42,
      "subject": "Internet funktioniert nicht",
      "description": "Seit heute Morgen habe ich keinen Internetzugang mehr. Der Browser zeigt 'Keine Verbindung' an. Neustart hat nicht geholfen.",
      "case_type": "incident",
      "category": "network",
      "priority": "high",
      "urgency": "high",
      "impact": "normal",
      "status": "new",
      "created_at": "2025-12-20T14:30:00+01:00",
      "sla_response_due_at": "2025-12-20T16:30:00+01:00",
      "sla_resolution_due_at": "2025-12-21T14:30:00+01:00"
    },
    "customer": {
      "name": "Max Mustermann",
      "phone": "+4915123456789",
      "email": null,
      "location": "Buero 3. Stock, Gebaeude A"
    },
    "call": {
      "id": "call_abc123xyz",
      "from_number": "+4915123456789",
      "to_number": "+4930123456789",
      "duration_seconds": 180,
      "started_at": "2025-12-20T14:25:00+01:00",
      "ended_at": "2025-12-20T14:28:00+01:00",
      "sentiment": "neutral"
    },
    "ai_analysis": {
      "summary": "Kunde meldet Internetausfall seit heute Morgen. Browser zeigt keine Verbindung. Neustart erfolglos.",
      "others_affected": "Nicht bekannt",
      "additional_notes": null,
      "confidence": 0.92
    },
    "meta": {
      "company_id": 5,
      "company_name": "IT-Systemhaus GmbH",
      "generated_at": "2025-12-20T14:30:05+01:00"
    }
  }
}
```

---

## Optionale Bloecke

### 1. Transcript Block

**Wann inkludieren**: Wenn `include_transcript: true` in OutputConfig

```json
"transcript": {
  "format": "inline",
  "segment_count": 12,
  "segments": [
    {
      "sequence": 1,
      "role": "agent",
      "text": "Guten Tag, wie kann ich Ihnen helfen?",
      "offset_ms": 5000,
      "sentiment": "neutral"
    },
    {
      "sequence": 2,
      "role": "user",
      "text": "Mein Internet funktioniert nicht mehr.",
      "offset_ms": 12000,
      "sentiment": "negative"
    }
  ]
}
```

**Alternative Formate**:
- `format: "attachment"` - Transcript als separate .txt Datei
- `format: "reference"` - Nur Link/ID zum Abruf via API

**Limit**: Max 20.000 Zeichen inline, danach automatisch `attachment`

### 2. Structured Data Block

**Wann inkludieren**: Wenn zusaetzliche Felder via Formular erfasst wurden

```json
"structured_data": {
  "affected_systems": ["Laptop", "Desktop"],
  "error_codes": ["ERR_NETWORK_CHANGED"],
  "last_working": "gestern",
  "vpn_connected": false
}
```

### 3. AI Metadata Block

**Wann inkludieren**: Fuer erweiterte Analyse

```json
"ai_metadata": {
  "intent": "report_incident",
  "confidence": 0.92,
  "keywords": ["internet", "verbindung", "browser"],
  "language": "de",
  "sentiment_overall": "frustrated"
}
```

---

## Kategorie-Mapping

| Kategorie | Beschreibung | Beispiele |
|-----------|--------------|-----------|
| `network` | Netzwerk & Internet | WLAN, VPN, DNS, Firewall |
| `m365` | Microsoft 365 | Outlook, Teams, SharePoint, OneDrive |
| `endpoint` | Endgeraete | Laptop, Desktop, Drucker-Treiber |
| `print` | Druckprobleme | Drucker, Scanner, Kopierer |
| `identity` | Identitaet & Zugang | Passwort, Login, MFA, Berechtigungen |
| `other` | Sonstiges | Alles andere |

---

## Priority-Mapping

| Priority | SLA Response | SLA Resolution | Anwendungsfall |
|----------|--------------|----------------|----------------|
| `critical` | 1h | 4h | Totalausfall, Security Incident |
| `high` | 2h | 8h | Produktionsbeeintraechtigung |
| `normal` | 4h | 24h | Standardanfrage |
| `low` | 8h | 48h | Nice-to-have, Optimierung |

---

## No-Leak Guarantee

Folgende Felder werden **NIEMALS** im Payload uebertragen:

| Kategorie | Verbotene Felder |
|-----------|------------------|
| **Kosten** | cost, price, margin, profit, revenue, retell_cost |
| **Interne IDs** | agent_id, llm_id, voice_id, user_id, admin_id |
| **Credentials** | api_key, secret, token, password, twilio_sid |
| **Prompts** | prompt, system_prompt, begin_message, general_prompt |
| **Interne URLs** | Alles mit localhost, api-gateway, /admin/, /internal/ |

---

## Idempotenz

**Garantie**: Dasselbe Event kann mehrfach empfangen werden, ohne Duplikate zu erzeugen.

**Implementierung**:
- `event_id` ist UUID, global eindeutig
- `idempotency_key` ist `{ticket_id}-v{version}`
- Empfaenger speichert empfangene `event_id`s und ignoriert Duplikate

---

## Versionshistorie

| Version | Datum | Aenderungen |
|---------|-------|-------------|
| 1.0 | 2025-12-20 | Initial Release (MVP) |

---

## Naechste Schritte (v1.1)

- [ ] Attachment-Support fuer grosse Transcripts
- [ ] Webhook Callback fuer Status-Updates
- [ ] Recording URL (Audio) als optionaler Block
- [ ] Retry-Semantik dokumentieren
