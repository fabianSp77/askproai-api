# Webhook Preset Library - Technical Design Document

**Date**: 2026-01-04
**Status**: Implemented
**Author**: Backend Architect

---

## Overview

The Webhook Preset Library provides reusable, validated templates for webhook integrations with external ticketing and messaging systems. It extends the existing `WebhookOutputHandler` with enhanced template rendering capabilities.

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    ServiceOutputConfiguration                    │
│  ┌─────────────┐    ┌─────────────────┐    ┌────────────────┐  │
│  │ webhook_url │    │ webhook_preset_id│───▶│ WebhookPreset  │  │
│  └─────────────┘    └─────────────────┘    └────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                     WebhookOutputHandler                         │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │ UsesWebhookPresets Trait                                   │  │
│  │  └─ buildPayloadFromPreset()                              │  │
│  │  └─ buildHeadersFromPreset()                              │  │
│  └───────────────────────────────────────────────────────────┘  │
│                              │                                   │
│                              ▼                                   │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │ WebhookTemplateEngine                                      │  │
│  │  └─ render(preset, case, overrides)                       │  │
│  │  └─ processConditionals()  → {{#if}}...{{/if}}            │  │
│  │  └─ processVariables()     → {{variable|default:val}}     │  │
│  │  └─ validate()             → Syntax & required checks      │  │
│  └───────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

## Files Created/Modified

### New Files

| File | Purpose |
|------|---------|
| `database/migrations/2026_01_04_100000_create_webhook_presets_table.php` | Database schema |
| `app/Models/WebhookPreset.php` | Eloquent model with template methods |
| `app/Services/ServiceGateway/WebhookTemplateEngine.php` | Template rendering engine |
| `app/Services/ServiceGateway/Traits/UsesWebhookPresets.php` | Integration trait |
| `database/seeders/WebhookPresetSeeder.php` | System presets (Jira, ServiceNow, etc.) |

### Modified Files

| File | Changes |
|------|---------|
| `app/Models/ServiceOutputConfiguration.php` | Added `webhookPreset()` relationship |
| `app/Services/ServiceGateway/OutputHandlers/WebhookOutputHandler.php` | Integrated preset support |

---

## Database Schema

```sql
CREATE TABLE webhook_presets (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    company_id BIGINT NULL,              -- NULL = system preset
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    target_system VARCHAR(50) NOT NULL,   -- jira, servicenow, slack, etc.
    category VARCHAR(50) DEFAULT 'ticketing',
    payload_template JSON NOT NULL,       -- Template with {{variables}}
    headers_template JSON,
    variable_schema JSON,                 -- JSON Schema for validation
    default_values JSON,
    auth_type VARCHAR(50) DEFAULT 'hmac',
    auth_instructions TEXT,
    version VARCHAR(20) DEFAULT '1.0.0',
    is_active BOOLEAN DEFAULT TRUE,
    is_system BOOLEAN DEFAULT FALSE,      -- Protected from deletion
    documentation_url VARCHAR(255),
    example_response JSON,
    created_by CHAR(36),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,

    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES staff(id) ON DELETE SET NULL
);

-- Link to output configurations
ALTER TABLE service_output_configurations
ADD COLUMN webhook_preset_id BIGINT REFERENCES webhook_presets(id);
```

---

## Template Syntax

### Simple Variables

```json
{
  "summary": "{{case.subject}}",
  "priority": "{{case.priority}}"
}
```

### Default Values

```json
{
  "assignee": "{{customer.email|default:support@company.com}}",
  "queue": "{{otrs.queue|default:Raw}}"
}
```

### Conditional Blocks

```json
{
  "recording_url": "{{#if enrichment.audio_url}}{{enrichment.audio_url}}{{/if}}",
  "fallback_note": "{{#unless customer.email}}No email provided{{/unless}}"
}
```

### Available Variables

| Variable Path | Description |
|---------------|-------------|
| `ticket.reference` | Opaque reference (TKT-2026-00001) |
| `case.subject` | Case subject line |
| `case.description` | Full problem description |
| `case.case_type` | incident, request, inquiry |
| `case.priority` | critical, high, normal, low |
| `case.category` | Category name |
| `case.status` | new, open, pending, resolved, closed |
| `case.created_at` | ISO8601 timestamp |
| `customer.name` | Caller name |
| `customer.phone` | Caller phone |
| `customer.email` | Caller email |
| `context.problem_since` | Enriched timestamp |
| `context.others_affected` | Boolean |
| `enrichment.audio_url` | Presigned audio URL |
| `enrichment.transcript_available` | Boolean |
| `timestamp` | Current ISO8601 time |

---

## System Presets

The seeder creates these presets:

| Preset | Target System | Use Case |
|--------|---------------|----------|
| `jira-incident` | Jira | Bug/Incident tracking |
| `jira-service-request` | Jira | Task/Service requests |
| `servicenow-incident` | ServiceNow | ITSM incidents |
| `otrs-ticket` | OTRS | Ticket creation |
| `zendesk-ticket` | Zendesk | Support tickets |
| `slack-message` | Slack | Block Kit messages |
| `teams-adaptive-card` | Teams | Adaptive Card messages |
| `generic-rest` | Custom | Starting template |

---

## Example: Jira Incident Payload

```json
{
  "fields": {
    "project": {
      "key": "{{jira.project_key|default:SUPPORT}}"
    },
    "issuetype": {
      "name": "Bug"
    },
    "summary": "{{case.subject}}",
    "description": {
      "type": "doc",
      "version": 1,
      "content": [
        {
          "type": "heading",
          "attrs": {"level": 2},
          "content": [{"type": "text", "text": "Problembeschreibung"}]
        },
        {
          "type": "paragraph",
          "content": [{"type": "text", "text": "{{case.description}}"}]
        }
      ]
    },
    "priority": {"name": "{{case.priority|default:Medium}}"},
    "labels": ["ai-generated", "service-gateway"]
  }
}
```

---

## Example: ServiceNow Incident Payload

```json
{
  "short_description": "{{case.subject}}",
  "description": "{{case.description}}\n\n--- Caller Information ---\nName: {{customer.name|default:Unknown}}\nPhone: {{customer.phone|default:Not provided}}",
  "urgency": "{{servicenow.urgency|default:2}}",
  "impact": "{{servicenow.impact|default:2}}",
  "category": "{{servicenow.category|default:Inquiry / Help}}",
  "caller_id": "{{customer.email}}",
  "contact_type": "phone",
  "state": "1",
  "correlation_id": "{{ticket.reference}}"
}
```

---

## Integration Flow

### Payload Priority

1. **Preset Template** (if `webhook_preset_id` is set)
2. **Custom Template** (if `webhook_payload_template` is set)
3. **Default Payload** (built-in Jira/ServiceNow compatible)

### Usage in WebhookOutputHandler

```php
private function buildPayload(ServiceCase $case, ServiceOutputConfiguration $config): array
{
    // Priority 1: Try preset template first
    $presetPayload = $this->buildPayloadFromPreset($case, $config);
    if ($presetPayload !== null) {
        return $presetPayload;
    }

    // Priority 2: Custom template
    if (!empty($config->webhook_payload_template)) {
        return $this->renderTemplate($case, $config->webhook_payload_template);
    }

    // Priority 3: Default payload
    return $this->buildDefaultPayload($case, $config);
}
```

---

## Error Handling

### Graceful Fallback

If preset rendering fails, the handler falls back to the default payload:

```php
try {
    $payload = $engine->render($preset, $case, $overrides);
} catch (TemplateRenderException $e) {
    Log::error('[UsesWebhookPresets] Failed to render preset', [...]);
    return null;  // Triggers fallback
}
```

### Validation

```php
// Validate before save
$engine = new WebhookTemplateEngine();
$result = $engine->validate($template);

if (!$result['valid']) {
    foreach ($result['errors'] as $error) {
        // Handle: "Unbalanced {{#if}}/{{/if}} blocks"
    }
}
```

---

## Deployment Steps

```bash
# 1. Run migration
php artisan migrate

# 2. Seed system presets
php artisan db:seed --class=WebhookPresetSeeder

# 3. Clear caches
php artisan cache:clear
php artisan config:clear
```

---

## Filament Admin Integration (Future)

A Filament resource should be created to:

1. List available presets (system + company)
2. Allow companies to duplicate system presets
3. Create custom presets with validation
4. Link presets to output configurations
5. Preview rendered payloads

---

## Security Considerations

1. **No Internal IDs Exposed**: Uses `ticket.reference` instead of database IDs
2. **HMAC Signing**: Preserved from existing implementation
3. **System Preset Protection**: Cannot be deleted (`is_system = true`)
4. **Multi-Tenant Isolation**: Company presets scoped via `company_id`
5. **Input Validation**: JSON Schema validation before delivery

---

## Testing

```php
// Unit test example
public function test_template_engine_renders_conditionals(): void
{
    $engine = new WebhookTemplateEngine();

    $template = [
        'audio' => '{{#if enrichment.audio_url}}{{enrichment.audio_url}}{{/if}}',
    ];

    $context = [
        'enrichment' => ['audio_url' => 'https://example.com/audio.mp3'],
    ];

    $result = $engine->renderRaw($template, $context);

    $this->assertEquals('https://example.com/audio.mp3', $result['audio']);
}
```

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0.0 | 2026-01-04 | Initial implementation |
