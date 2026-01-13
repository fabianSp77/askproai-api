# Service Gateway Architecture

## System Overview

Das Service Gateway verarbeitet eingehende Voice-Calls von Retell AI und transformiert sie in strukturierte Service Cases, die an externe Ticket-Systeme weitergeleitet werden.

---

## Data Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                     PHASE 1: CAPTURE                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  Retell AI Voice Call                                           │
│         │                                                        │
│         ▼                                                        │
│  RetellWebhookController::handleCallAnalyzed()                  │
│         │                                                        │
│         ▼                                                        │
│  GatewayModeResolver::resolve()                                 │
│    ├─ Mode: appointment → AppointmentCreationService            │
│    ├─ Mode: service_desk → ServiceDeskHandler                   │
│    └─ Mode: hybrid → IntentDetectionService → Route             │
│         │                                                        │
│         ▼                                                        │
│  ServiceDeskHandler::handleServiceRequest()                     │
│         │                                                        │
│         ▼                                                        │
│  IssueCapturingService::captureIssue()                          │
│         │                                                        │
│         ▼                                                        │
│  ServiceCase::create([                                          │
│      status: 'new',                                             │
│      enrichment_status: 'pending',                              │
│      output_status: null                                        │
│  ])                                                             │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                     PHASE 2: ENRICHMENT                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  EnrichServiceCaseJob (queued, delayed 90s)                     │
│         │                                                        │
│         ├─► Customer Matching                                   │
│         │   ├─ Phone number match (highest priority)            │
│         │   ├─ Email match                                      │
│         │   └─ Fuzzy name match                                 │
│         │                                                        │
│         ├─► Audio Processing                                    │
│         │   └─ ProcessCallRecordingJob                          │
│         │       ├─ Download from Retell                         │
│         │       ├─ Store in S3/local                            │
│         │       └─ Generate signed URL                          │
│         │                                                        │
│         └─► Update ServiceCase                                  │
│             enrichment_status: 'enriched'                       │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                     PHASE 3: DELIVERY                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  DeliverCaseOutputJob (queued after enrichment)                 │
│         │                                                        │
│         ▼                                                        │
│  OutputHandlerFactory::create(config)                           │
│    ├─ 'email' → EmailOutputHandler                              │
│    ├─ 'webhook' → WebhookOutputHandler                          │
│    └─ 'hybrid' → HybridOutputHandler                            │
│         │                                                        │
│         ▼                                                        │
│  Handler::deliver(serviceCase, config)                          │
│         │                                                        │
│         ├─► Success                                             │
│         │   └─ output_status: 'sent'                            │
│         │                                                        │
│         └─► Failure                                             │
│             ├─ Retry with backoff [60s, 120s, 300s]             │
│             └─ After 3 failures:                                │
│                 ├─ output_status: 'failed'                      │
│                 └─ DeliveryFailedNotification                   │
│                     ├─ Email to admin                           │
│                     └─ Slack webhook                            │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                     PHASE 4: AUDIT                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ServiceGatewayExchangeLog::create([                            │
│      service_case_id,                                           │
│      direction: 'outbound',                                     │
│      handler_type: 'email|webhook|hybrid',                      │
│      endpoint_url,                                              │
│      request_payload,                                           │
│      response_body,                                             │
│      status_code: 200|422|500,                                  │
│      error_class,                                               │
│      error_message,                                             │
│      duration_ms                                                │
│  ])                                                             │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## Component Details

### 1. GatewayModeResolver

Bestimmt den Verarbeitungsmodus basierend auf Company-Konfiguration:

```php
class GatewayModeResolver
{
    public function resolve(Company $company, array $callData): string
    {
        $config = $company->gatewayConfiguration;

        return match($config?->gateway_mode ?? 'appointment') {
            'service_desk' => 'service_desk',
            'hybrid' => $this->detectIntent($callData, $config),
            default => 'appointment',
        };
    }
}
```

### 2. OutputHandlerFactory

Factory Pattern für Handler-Auswahl:

```php
class OutputHandlerFactory
{
    public function create(ServiceOutputConfiguration $config): OutputHandlerInterface
    {
        return match($config->output_type) {
            'email' => new EmailOutputHandler($config),
            'webhook' => new WebhookOutputHandler($config),
            'hybrid' => new HybridOutputHandler($config),
            default => throw new InvalidArgumentException(),
        };
    }
}
```

### 3. WebhookTemplateEngine

Blade-basiertes Template-Rendering für Webhook-Payloads:

```php
class WebhookTemplateEngine
{
    public function render(string $template, ServiceCase $case): string
    {
        $variables = [
            'case' => $case,
            'caller_name' => $case->caller_name,
            'caller_phone' => $case->caller_phone,
            'summary' => $case->summary,
            'description' => $case->description,
            'priority' => $case->priority,
            'category' => $case->category?->name,
            'audio_url' => $case->getSignedAudioUrl(),
            'admin_url' => route('filament.admin.resources.service-cases.view', $case),
            'timestamp' => now()->toISOString(),
        ];

        return Blade::render($template, $variables);
    }
}
```

### 4. DeliverCaseOutputJob

Hauptjob mit Backoff und Error-Handling:

```php
class DeliverCaseOutputJob implements ShouldQueue
{
    public $tries = 3;
    public $backoff = [60, 120, 300]; // 1min, 2min, 5min

    public function handle(): void
    {
        $handler = OutputHandlerFactory::create($this->config);
        $result = $handler->deliver($this->serviceCase, $this->config);

        if ($result['success']) {
            $this->serviceCase->markAsDelivered();
        } else {
            throw new DeliveryFailedException($result['error']);
        }
    }

    public function failed(Throwable $exception): void
    {
        $this->serviceCase->markAsFailed($exception->getMessage());
        $this->notifyAdmins($exception);
    }
}
```

---

## Database Schema

### ServiceCase States

```
enrichment_status:
  - pending    → Initial state after creation
  - enriching  → EnrichServiceCaseJob running
  - enriched   → Ready for delivery
  - failed     → Enrichment error

output_status:
  - null       → Not yet attempted
  - pending    → Queued for delivery
  - sent       → Successfully delivered
  - failed     → All retries exhausted
```

### Key Relationships

```
Company
  └─► CompanyGatewayConfiguration (1:1)
  └─► ServiceCase (1:N)

ServiceCaseCategory
  └─► ServiceOutputConfiguration (1:1)
  └─► ServiceCase (1:N)

ServiceOutputConfiguration
  └─► WebhookPreset (N:1, optional)

ServiceCase
  └─► ServiceGatewayExchangeLog (1:N)
  └─► Customer (N:1, optional)
  └─► Call (N:1, optional)
```

---

## Configuration Hierarchy

```
1. Global: config/gateway.php
   └─ Default values for all companies

2. Company: CompanyGatewayConfiguration
   └─ Per-company overrides (mode, alerts, etc.)

3. Category: ServiceOutputConfiguration
   └─ Per-category output config (email/webhook/hybrid)

4. Preset: WebhookPreset
   └─ Reusable webhook templates
```

---

## Error Handling Strategy

### Retry Logic
```
Attempt 1 → Fail → Wait 60s
Attempt 2 → Fail → Wait 120s
Attempt 3 → Fail → Wait 300s
Final Failure → Admin Notification
```

### Alert Channels
- Email: `CompanyGatewayConfiguration.admin_email`
- Slack: `CompanyGatewayConfiguration.slack_webhook`

### Audit Trail
Jeder Delivery-Versuch wird in `ServiceGatewayExchangeLog` protokolliert:
- Request payload
- Response body
- Status code
- Error details
- Duration

---

## Security Considerations

### Multi-Tenancy
- Alle Queries via `company_id` gefiltert
- CompanyScope Middleware aktiv
- Row-Level Security in Dashboard Widgets

### Webhook Security
- Optional: HMAC Signature in Header
- Secret pro ServiceOutputConfiguration
- SSL/TLS required für Production

### Audio URLs
- Signed URLs mit TTL (default: 60 min)
- Kein permanenter öffentlicher Zugriff

---

*Last Updated: 2026-01-04*
