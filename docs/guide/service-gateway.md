# Service Gateway

Das Service Gateway ist ein intelligentes Multi-Tenant Case Management System, das Voice Call Ergebnisse verarbeitet und an verschiedene Output-Kanäle weiterleitet.

## Übersicht

```
Voice Call → Intent Detection → Case Creation → Enrichment → Output Delivery
```

Das Service Gateway erweitert den bestehenden Terminbuchungs-Bot um Service-Desk-Funktionen. Kunden können sowohl Termine buchen als auch Support-Anfragen stellen - das System erkennt automatisch die Absicht.

## System Architektur

### Komponenten-Übersicht

```
┌─────────────────────────────────────────────────────────────────┐
│                     INCOMING CALL                               │
│                    Retell.ai Voice                              │
└─────────────────────┬───────────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────────┐
│              GatewayModeResolver                                │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ 1. Feature Flag Check (gateway.mode_enabled)            │   │
│  │ 2. Call → Company Resolution                            │   │
│  │ 3. PolicyConfiguration Lookup (cached 5min)             │   │
│  │ 4. Mode Validation                                       │   │
│  │ 5. Hybrid: IntentDetectionService                        │   │
│  └─────────────────────────────────────────────────────────┘   │
└─────────────────────┬───────────────────────────────────────────┘
                      │
          ┌───────────┼───────────┐
          ▼           ▼           ▼
    ┌──────────┐ ┌──────────┐ ┌──────────┐
    │appointment│ │service_  │ │  hybrid  │
    │   Mode   │ │  desk    │ │   Mode   │
    └────┬─────┘ └────┬─────┘ └────┬─────┘
         │            │            │
         │            ▼            │
         │     ┌───────────────┐   │
         │     │ ServiceCase   │◄──┘
         │     │  Creation     │
         │     └───────┬───────┘
         │             │
         │             ▼
         │     ┌───────────────┐
         │     │  Enrichment   │ (Queue: enrichment)
         │     │  • Customer   │
         │     │  • Audio      │
         │     │  • Transcript │
         │     └───────┬───────┘
         │             │
         ▼             ▼
    ┌──────────┐ ┌───────────────┐
    │ Cal.com  │ │Output Delivery│
    │ Booking  │ │ • Email       │
    └──────────┘ │ • Webhook     │
                 │ • Hybrid      │
                 └───────────────┘
```

## Gateway Modi

| Modus | Beschreibung | Use Case |
|-------|--------------|----------|
| `appointment` | Klassische Terminbuchung | Friseure, Ärzte, Handwerker |
| `service_desk` | Ticket/Case-Erstellung | IT-Support, Kundenservice |
| `hybrid` | Kombinierter Modus mit Intent-Erkennung | Komplexe Workflows |

### Appointment Modus

Der Standard-Modus für Terminbuchungen:
- Verfügbarkeit aus Cal.com prüfen
- Termine direkt buchen
- Bestätigung via SMS/Email

### Service Desk Modus

Für Support-Anfragen und Ticket-Erstellung:
- Problem strukturiert erfassen
- ITIL-basierte Kategorisierung (Incident, Request, Inquiry)
- Automatisches Routing an Zielsysteme

### Hybrid Modus

Intelligente Erkennung des Anliegens:

```php
// IntentDetectionService wertet erste Äußerung aus
$result = $intentService->detectIntent($utterance, $companyId);

// Beispiel-Output:
[
    'intent' => 'service_desk',
    'confidence' => 0.92,
    'detected_keywords' => ['problem', 'drucker', 'funktioniert nicht'],
    'explanation' => 'Erkannte Keywords: problem, drucker, funktioniert nicht'
]
```

**Keyword-basierte Erkennung:**

| Kontext | Keywords (Deutsch) |
|---------|-------------------|
| Termin | termin, buchen, reservieren, haarschnitt, färben, massage |
| Service | problem, fehler, funktioniert nicht, drucker, support, störung |

## Datenfluss

### 1. Call-Eingang

```
Retell.ai Call
     │
     ▼
RetellFunctionCallHandler
     │
     ├── extractCallIdLayered()
     │
     ▼
GatewayModeResolver::resolve($callId)
```

### 2. Mode Resolution

```php
// app/Services/Gateway/GatewayModeResolver.php

public function resolve(string $callId): string
{
    // 1. Feature Flag Check
    if (!config('gateway.mode_enabled', false)) {
        return 'appointment';
    }

    // 2. Call → Company Resolution
    $call = Call::where('retell_call_id', $callId)->first();

    // 3. Company Policy Lookup (cached)
    $policy = PolicyConfiguration::getCachedPolicy(
        $call->company,
        PolicyConfiguration::POLICY_TYPE_GATEWAY_MODE
    );

    // 4. Mode aus Policy oder Default
    $mode = $policy?->config['mode'] ?? config('gateway.default_mode');

    // 5. Hybrid: Intent Detection
    if ($mode === 'hybrid') {
        $utterance = $this->getInitialUtterance($callId);
        $mode = $intentService->determineMode($utterance, $call->company_id);
    }

    return $mode;
}
```

### 3. Case Creation (Service Desk)

```
ServiceDeskHandler::create()
     │
     ├── IssueCapturingService::capture()
     │
     ├── TicketCategorizationService::categorize()
     │
     ▼
ServiceCase::create([
    'company_id' => $companyId,
    'call_id' => $callId,
    'subject' => $subject,
    'description' => $description,
    'category_id' => $categoryId,
    'priority' => $priority,
    'status' => 'open',
])
```

### 4. 2-Phase Delivery Pattern

```
Phase 1: Enrichment
─────────────────────────────────
ServiceCase Created
        │
        ▼
EnrichServiceCaseJob (Queue: enrichment)
        │
        ├── Customer Matching
        ├── Audio Processing
        ├── Transcript Extraction
        │
        ▼
ServiceCase.enrichment_completed_at = now()


Phase 2: Output Delivery
─────────────────────────────────
Enrichment Complete
        │
        ▼
DeliverCaseOutputJob (Queue: default)
        │
        ├── EmailOutputHandler OR
        ├── WebhookOutputHandler OR
        └── HybridOutputHandler
        │
        ▼
ServiceCase.output_status = 'sent'
```

## Output-Typen

### Email Output

Sendet formatierte E-Mail-Benachrichtigungen:

```php
ServiceOutputConfiguration::create([
    'company_id' => $company->id,
    'name' => 'IT Support Email',
    'output_type' => 'email',
    'email_recipients' => ['support@company.com'],
    'email_subject_template' => '[Ticket #{case_number}] {subject}',
]);
```

### Webhook Output

Liefert JSON-Payloads an externe Systeme:

```php
ServiceOutputConfiguration::create([
    'company_id' => $company->id,
    'name' => 'Jira Integration',
    'output_type' => 'webhook',
    'webhook_url' => 'https://jira.company.com/rest/api/2/issue',
    'webhook_method' => 'POST',
    'webhook_preset' => 'jira',
]);
```

### Webhook Presets

| Preset | System | Features |
|--------|--------|----------|
| `jira` | Atlassian Jira | Issue-Erstellung mit Custom Fields |
| `servicenow` | ServiceNow | Incident Management |
| `otrs` | OTRS | Ticket-Erstellung |
| `zendesk` | Zendesk | Support Tickets |
| `slack` | Slack | Channel-Benachrichtigungen |
| `teams` | Microsoft Teams | Webhook Cards |
| `generic` | Beliebige REST API | Konfigurierbares Template |

### Webhook Payload Template

```json
{
    "ticket": {
        "title": "{{ $case->subject }}",
        "description": "{{ $case->description }}",
        "priority": "{{ $case->priority }}",
        "type": "{{ $case->case_type }}",
        "customer": {
            "name": "{{ $case->customer->name }}",
            "phone": "{{ $case->customer->phone }}"
        },
        "metadata": {
            "source": "voice",
            "call_id": "{{ $case->call_id }}",
            "created_at": "{{ $case->created_at->toIso8601String() }}"
        }
    }
}
```

## Sicherheit

### H-001: Cache Isolation

Alle Cache-Keys enthalten `company_id` für Tenant-Isolation:

```
service_desk:{company_id}:{type}:{call_id}
```

### H-002: Authorization Guard

API-Anfragen werden via `validateApiContext()` validiert:
- Call Context Verification
- Company Isolation Check
- Status Validation (rejected/cancelled calls werden abgelehnt)

### SSRF Protection

10-Layer Defense für Webhook URLs:
1. URL-Struktur-Validierung
2. Protocol Whitelist (HTTPS only in Production)
3. Port Whitelist (80, 443)
4. Hostname Blocklist (localhost, metadata endpoints)
5. IP-Normalisierung (decimal, octal, hex)
6. IPv4-mapped IPv6 Detection
7. DNS Resolution Validation
8. Redirect Prevention

### Rate Limiting

- 20 Operationen pro Call pro Minute
- Verhindert Missbrauch während Voice Calls

## SLA Tracking

Kategorien können SLA-Ziele definieren:

```php
ServiceCaseCategory::create([
    'name' => 'Critical Incident',
    'sla_response_hours' => 1,    // 1 Stunde Response
    'sla_resolution_hours' => 4,  // 4 Stunden Resolution
]);
```

Dashboard-Widgets zeigen:
- Cases mit drohendem SLA-Breach
- SLA Compliance Rates
- Durchschnittliche Response-Zeiten

## Konfiguration

### Environment Variables

```bash
# Feature Flag
GATEWAY_MODE_ENABLED=true
GATEWAY_DEFAULT_MODE=appointment

# Hybrid Mode
GATEWAY_HYBRID_FALLBACK=appointment

# 2-Phase Delivery
GATEWAY_ENRICHMENT_ENABLED=true
GATEWAY_AUDIO_IN_WEBHOOK=true
GATEWAY_DELIVERY_INITIAL_DELAY=90
GATEWAY_ENRICHMENT_TIMEOUT=180

# Cache (Sekunden)
GATEWAY_CACHE_WIDGET_STATS=55
GATEWAY_CACHE_WIDGET_TRENDS=300
GATEWAY_CACHE_RECENT_ACTIVITY=30

# Admin Alerts
GATEWAY_ADMIN_EMAIL=admin@company.com
GATEWAY_ALERTS_ENABLED=true
GATEWAY_SLACK_WEBHOOK=https://hooks.slack.com/...
```

### Company-Level Settings

```php
CompanyGatewayConfiguration::create([
    'company_id' => $company->id,
    'gateway_mode' => 'service_desk',
    'default_category_id' => $category->id,
    'auto_assign_enabled' => true,
    'sla_tracking_enabled' => true,
]);
```

## Filament Admin

### Service Cases Resource

Verwalte alle Service Cases im Admin Panel:
- Filter nach Status, Priorität, Kategorie
- Inline-Bearbeitung
- Bulk-Aktionen
- SLA-Countdown-Anzeige

### Dashboard Widget

Das Service Gateway Dashboard zeigt:
- Offene Cases nach Priorität
- SLA-Status-Übersicht
- Trend-Diagramme
- Letzte Aktivitäten

## API Endpoints

| Method | Endpoint | Beschreibung |
|--------|----------|--------------|
| POST | `/api/service-gateway/cases` | Case erstellen |
| GET | `/api/service-gateway/cases/{id}` | Case abrufen |
| PATCH | `/api/service-gateway/cases/{id}` | Case aktualisieren |
| POST | `/api/service-gateway/finalize` | Ticket finalisieren |

Siehe [Interactive API Docs](/docs/api) für die vollständige Spezifikation.

## Rollback-Strategie

Das Service Gateway wurde mit minimaler Invasivität implementiert:

```bash
# Sofortige Deaktivierung ohne Deploy
GATEWAY_MODE_ENABLED=false
```

Bei Problemen:
1. Feature Flag deaktivieren
2. System fällt auf `appointment` Modus zurück
3. Bestehende Terminbuchung funktioniert weiter

## Weitere Ressourcen

- [Service Gateway API](/api/service-gateway)
- [Webhooks Konfiguration](/guide/webhooks)
- [Retell.ai Integration](/guide/retell)
