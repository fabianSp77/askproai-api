# Service Gateway

The Service Gateway is a multi-tenant case management system that processes voice call outcomes and delivers them to various output channels.

## Overview

```
Voice Call → Intent Detection → Case Creation → Enrichment → Output Delivery
```

## Gateway Modes

| Mode | Description | Use Case |
|------|-------------|----------|
| `appointment` | Traditional appointment booking | Scheduling services |
| `service_desk` | Pure ticket/case creation | IT Support, Customer Service |
| `hybrid` | Combined mode | Complex workflows |

## Output Types

### Email Output
Sends formatted email notifications with case details.

```php
// Configuration
'output_type' => 'email',
'email_recipients' => ['support@company.com'],
'email_subject_template' => '[Ticket #{case_number}] {subject}',
```

### Webhook Output
Delivers JSON payloads to external systems (Jira, ServiceNow, OTRS, etc.).

```php
// Configuration
'output_type' => 'webhook',
'webhook_url' => 'https://jira.company.com/rest/api/2/issue',
'webhook_method' => 'POST',
'webhook_preset' => 'jira',
```

### Hybrid Output
Combines both email and webhook for redundant delivery.

## 2-Phase Delivery Pattern

The Service Gateway uses a sophisticated delivery pattern:

### Phase 1: Enrichment
```
ServiceCase Created
       ↓
EnrichServiceCaseJob (Queue: enrichment)
       ↓
- Customer matching
- Audio processing
- Transcript extraction
       ↓
ServiceCase.enrichment_completed_at = now()
```

### Phase 2: Output Delivery
```
Enrichment Complete
       ↓
DeliverCaseOutputJob (Queue: default)
       ↓
- EmailOutputHandler OR
- WebhookOutputHandler OR
- HybridOutputHandler
       ↓
ServiceCase.output_status = 'sent'
```

## Webhook Presets

Built-in presets for popular ticketing systems:

| Preset | System | Features |
|--------|--------|----------|
| `jira` | Atlassian Jira | Issue creation with custom fields |
| `servicenow` | ServiceNow | Incident management |
| `otrs` | OTRS | Ticket creation |
| `zendesk` | Zendesk | Support tickets |
| `slack` | Slack | Channel notifications |
| `teams` | Microsoft Teams | Webhook cards |
| `generic` | Any REST API | Customizable template |

## Security Features

### H-001: Cache Isolation
All cache keys include `company_id` for tenant isolation:
```
service_desk:{company_id}:{type}:{call_id}
```

### H-002: Authorization Guard
API requests validated via `validateApiContext()`:
- Call context verification
- Company isolation check
- Status validation (rejects cancelled/rejected)

### SSRF Protection
10-layer defense for webhook URLs:
1. URL structure validation
2. Protocol whitelist (HTTPS only in production)
3. Port whitelist (80, 443)
4. Hostname blocklist (localhost, metadata endpoints)
5. IP normalization (decimal, octal, hex)
6. IPv4-mapped IPv6 detection
7. DNS resolution validation
8. Redirect prevention

### Rate Limiting
- 20 operations per call per minute
- Prevents abuse during voice calls

## SLA Tracking

Categories can define SLA targets:

```php
ServiceCaseCategory::create([
    'name' => 'Critical Incident',
    'sla_response_hours' => 1,    // 1 hour response
    'sla_resolution_hours' => 4,  // 4 hour resolution
]);
```

Dashboard widgets show:
- Cases approaching SLA breach
- SLA compliance rates
- Average response times

## Configuration

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

### Output Configuration

```php
ServiceOutputConfiguration::create([
    'company_id' => $company->id,
    'name' => 'IT Support Webhook',
    'output_type' => 'webhook',
    'is_active' => true,
    'webhook_url' => 'https://...',
    'webhook_preset' => 'servicenow',
]);
```

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/service-gateway/cases` | Create case |
| GET | `/api/service-gateway/cases/{id}` | Get case |
| PATCH | `/api/service-gateway/cases/{id}` | Update case |
| POST | `/api/service-gateway/finalize` | Finalize ticket |

See [Interactive API Docs](/docs/api) for full specification.
