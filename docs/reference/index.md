# Data Models

This reference documents the core data models used in the AskPro API Gateway.

## ServiceCase

Represents a service desk case/ticket.

```typescript
interface ServiceCase {
  id: number;
  company_id: number;
  call_id: number | null;
  case_number: string;           // Format: CASE-YYYYMMDD-XXXXX

  // Customer Information
  caller_name: string;
  caller_phone: string | null;
  caller_email: string | null;

  // Case Details
  subject: string;
  description: string;
  category_id: number;
  priority: 'low' | 'normal' | 'high' | 'critical';
  status: 'open' | 'in_progress' | 'pending' | 'resolved' | 'closed';

  // Assignment
  assigned_to: number | null;    // Staff ID
  assignment_group_id: number | null;

  // SLA
  sla_response_due_at: datetime | null;
  sla_resolution_due_at: datetime | null;
  sla_response_met: boolean | null;

  // Enrichment
  enrichment_started_at: datetime | null;
  enrichment_completed_at: datetime | null;

  // Output
  output_status: 'pending' | 'sent' | 'failed' | null;
  output_sent_at: datetime | null;

  // Timestamps
  created_at: datetime;
  updated_at: datetime;
}
```

## ServiceCaseCategory

Defines case categories with SLA settings.

```typescript
interface ServiceCaseCategory {
  id: number;
  company_id: number;
  name: string;
  description: string | null;
  parent_id: number | null;

  // SLA Configuration
  sla_response_hours: number | null;
  sla_resolution_hours: number | null;

  is_active: boolean;
  sort_order: number;
}
```

## ServiceOutputConfiguration

Configures how cases are delivered to external systems.

```typescript
interface ServiceOutputConfiguration {
  id: number;
  company_id: number;
  name: string;

  output_type: 'email' | 'webhook' | 'hybrid';
  is_active: boolean;

  // Email Settings
  email_recipients: string[];
  email_cc: string[] | null;
  email_subject_template: string | null;
  email_template_type: 'default' | 'it_support' | 'custom';

  // Webhook Settings
  webhook_url: string | null;
  webhook_method: 'POST' | 'PUT' | 'PATCH';
  webhook_headers: object | null;
  webhook_body_template: string | null;
  webhook_preset: string | null;
  webhook_secret: string | null;

  // Retry Configuration
  retry_enabled: boolean;
  max_retries: number;
  retry_delay_seconds: number;
}
```

## Call

Represents a voice call session.

```typescript
interface Call {
  id: number;
  company_id: number;
  retell_call_id: string;

  status: 'ongoing' | 'completed' | 'cancelled' | 'rejected';
  direction: 'inbound' | 'outbound';

  // Caller Information
  from_number: string;
  to_number: string;

  // Timing
  started_at: datetime;
  ended_at: datetime | null;
  duration_seconds: number | null;

  // Intent Detection
  detected_intent: string | null;
  intent_confidence: number | null;

  // Relationships
  customer_id: number | null;
  appointment_id: number | null;
  service_case_id: number | null;
}
```

## Enums

### Priority
```typescript
enum Priority {
  LOW = 'low',
  NORMAL = 'normal',
  HIGH = 'high',
  CRITICAL = 'critical'
}
```

### CaseStatus
```typescript
enum CaseStatus {
  OPEN = 'open',
  IN_PROGRESS = 'in_progress',
  PENDING = 'pending',
  RESOLVED = 'resolved',
  CLOSED = 'closed'
}
```

### OutputType
```typescript
enum OutputType {
  EMAIL = 'email',
  WEBHOOK = 'webhook',
  HYBRID = 'hybrid'
}
```

### WebhookPreset
```typescript
enum WebhookPreset {
  JIRA = 'jira',
  SERVICENOW = 'servicenow',
  OTRS = 'otrs',
  ZENDESK = 'zendesk',
  FRESHDESK = 'freshdesk',
  SLACK = 'slack',
  TEAMS = 'teams',
  GENERIC = 'generic'
}
```
