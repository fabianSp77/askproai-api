# Service Gateway Documentation Index

## Overview

Das Service Gateway ist ein Multi-Tenant Enterprise-System für die automatisierte Verarbeitung von Service-Anfragen via Retell AI Voice Calls. Es unterstützt Email- und Webhook-Outputs an externe Ticket-Systeme.

**Status**: Production Ready (2026-01-04)
**Tests**: 104 passed (255 assertions)

---

## Documentation Files

| File | Description |
|------|-------------|
| `INDEX.md` | This file - Navigation |
| `ARCHITECTURE.md` | System architecture & data flow |
| `WEBHOOK_PRESET_LIBRARY_2026-01-04.md` | Webhook preset configuration |
| `ONBOARDING_WIZARD.md` | Company onboarding process |
| `DASHBOARD_WIDGETS.md` | Dashboard & widget documentation |

---

## Quick Reference

### Key Files

```
Services:
├─ app/Services/ServiceGateway/
│   ├─ OutputHandlers/
│   │   ├─ EmailOutputHandler.php      # Email delivery
│   │   ├─ WebhookOutputHandler.php    # Webhook delivery
│   │   └─ HybridOutputHandler.php     # Both channels
│   ├─ OutputHandlerFactory.php        # Handler selection
│   ├─ WebhookTemplateEngine.php       # Template rendering
│   └─ ExchangeLogService.php          # Audit logging

Jobs:
├─ app/Jobs/ServiceGateway/
│   ├─ DeliverCaseOutputJob.php        # Main delivery job
│   ├─ EnrichServiceCaseJob.php        # 2-Phase enrichment
│   └─ ProcessCallRecordingJob.php     # Audio processing

Models:
├─ app/Models/
│   ├─ ServiceCase.php                 # Core case model
│   ├─ ServiceCaseCategory.php         # Categories + SLA
│   ├─ ServiceOutputConfiguration.php  # Output config
│   ├─ CompanyGatewayConfiguration.php # Company settings
│   └─ WebhookPreset.php               # Reusable templates

Filament:
├─ app/Filament/
│   ├─ Pages/
│   │   ├─ ServiceGatewayDashboard.php # Main dashboard
│   │   └─ CompanyOnboardingWizard.php # 7-Step wizard
│   ├─ Resources/
│   │   ├─ ServiceCaseResource.php
│   │   ├─ ServiceCaseCategoryResource.php
│   │   ├─ ServiceOutputConfigurationResource.php
│   │   ├─ CompanyGatewayConfigurationResource.php
│   │   └─ ServiceGatewayExchangeLogResource.php
│   └─ Widgets/ServiceGateway/         # 9 dashboard widgets
```

### Configuration

```php
// config/gateway.php
return [
    'mode_enabled' => env('GATEWAY_MODE_ENABLED', false),
    'default_mode' => env('GATEWAY_DEFAULT_MODE', 'appointment'),
    'enrichment' => [
        'enabled' => true,
        'initial_delay_seconds' => 90,
        'timeout_seconds' => 180,
    ],
    'delivery' => [
        'backoff' => [60, 120, 300], // 1min, 2min, 5min
        'max_attempts' => 3,
    ],
];
```

### Test Commands

```bash
# Full test suite
vendor/bin/pest tests/Feature/ServiceGateway/ tests/Unit/Jobs/ServiceGateway/

# Backward compatibility only
vendor/bin/pest tests/Feature/ServiceGateway/BackwardCompatibilityTest.php

# Specific handler tests
vendor/bin/pest tests/Unit/ServiceGateway/WebhookOutputHandlerTest.php
vendor/bin/pest tests/Unit/ServiceGateway/EmailOutputHandlerTest.php
```

---

## Feature Overview

### 1. Case Lifecycle

```
Voice Call → ServiceCase (new) → Enrichment → Delivery → Audit Log
```

### 2. Output Types

| Type | Handler | Use Case |
|------|---------|----------|
| `email` | EmailOutputHandler | Direct notifications |
| `webhook` | WebhookOutputHandler | Ticket system integration |
| `hybrid` | HybridOutputHandler | Both channels |

### 3. Webhook Presets

8 pre-configured templates for common systems:
- Jira Service Management
- ServiceNow
- OTRS
- Zendesk
- Freshdesk
- Slack
- Microsoft Teams
- Generic Webhook

### 4. Error Handling

- Exponential backoff: 60s → 120s → 300s
- Admin alerts on final failure (Email + Slack)
- Full audit trail in ServiceGatewayExchangeLog

### 5. Multi-Tenancy

- Per-company gateway configuration
- Per-category output configuration
- Company filter in dashboard (super-admin)
- Row-level security via company_id

---

## Related Documentation

- **Retell AI**: `claudedocs/03_API/Retell_AI/`
- **Email Templates**: `claudedocs/EMAIL_TEMPLATES/`
- **RCA Reports**: `claudedocs/08_REFERENCE/RCA/`

---

*Last Updated: 2026-01-04*
