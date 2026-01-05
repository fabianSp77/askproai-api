# Service Gateway API

The Service Gateway manages customer service cases and ticket routing.

## Overview

The Service Gateway provides:
- Multi-tenant case management
- Intelligent ticket categorization
- Flexible output routing (Email, Webhook, Hybrid)
- SLA tracking and escalation
- Integration with ticketing systems

## Endpoints

### List Service Cases

```
GET /api/v1/service-cases
```

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| status | string | Filter by status (open, in_progress, resolved, closed) |
| category_id | integer | Filter by category |
| priority | string | Filter by priority (low, medium, high, critical) |
| from_date | date | Cases created after |
| to_date | date | Cases created before |
| page | integer | Page number |
| per_page | integer | Results per page (max 100) |

**Response:**

```json
{
  "data": [
    {
      "id": 123,
      "subject": "Drucker funktioniert nicht",
      "description": "Der Drucker im 2. Stock druckt nicht mehr...",
      "status": "open",
      "priority": "medium",
      "category": {
        "id": 5,
        "name": "Hardware",
        "sla_response_hours": 4,
        "sla_resolution_hours": 24
      },
      "customer": {
        "name": "Max Mustermann",
        "phone": "+491234567890",
        "email": "max@example.com"
      },
      "created_at": "2024-01-10T14:30:00.000Z",
      "updated_at": "2024-01-10T14:30:00.000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 95
  }
}
```

### Get Single Case

```
GET /api/v1/service-cases/{id}
```

**Response:**

```json
{
  "data": {
    "id": 123,
    "subject": "Drucker funktioniert nicht",
    "description": "Der Drucker im 2. Stock druckt nicht mehr...",
    "status": "open",
    "priority": "medium",
    "category": {...},
    "customer": {...},
    "call": {
      "id": 456,
      "retell_call_id": "call_abc123",
      "duration_seconds": 180,
      "recording_url": "https://..."
    },
    "notes": [
      {
        "id": 1,
        "content": "Kunde kontaktiert",
        "created_by": "Anna Schmidt",
        "created_at": "2024-01-10T15:00:00.000Z"
      }
    ],
    "activity_log": [
      {
        "action": "created",
        "details": "Case created from Retell call",
        "created_at": "2024-01-10T14:30:00.000Z"
      }
    ],
    "sla": {
      "response_due": "2024-01-10T18:30:00.000Z",
      "resolution_due": "2024-01-11T14:30:00.000Z",
      "response_met": true,
      "resolution_met": null
    },
    "created_at": "2024-01-10T14:30:00.000Z"
  }
}
```

### Create Service Case

```
POST /api/v1/service-cases
```

**Request Body:**

```json
{
  "subject": "Neues IT-Problem",
  "description": "Detaillierte Beschreibung...",
  "category_id": 5,
  "priority": "high",
  "customer": {
    "name": "Max Mustermann",
    "phone": "+491234567890",
    "email": "max@example.com"
  },
  "metadata": {
    "department": "Marketing",
    "location": "Building A"
  }
}
```

**Response:**

```json
{
  "data": {
    "id": 124,
    "subject": "Neues IT-Problem",
    "status": "open",
    ...
  },
  "message": "Service case created successfully"
}
```

### Update Service Case

```
PUT /api/v1/service-cases/{id}
```

**Request Body:**

```json
{
  "status": "in_progress",
  "priority": "critical",
  "assigned_to": 5
}
```

### Add Note to Case

```
POST /api/v1/service-cases/{id}/notes
```

**Request Body:**

```json
{
  "content": "Techniker wurde informiert und ist unterwegs.",
  "internal": false
}
```

### Close Case

```
POST /api/v1/service-cases/{id}/close
```

**Request Body:**

```json
{
  "resolution": "Problem durch Neustart behoben",
  "resolution_code": "resolved"
}
```

## Categories

### List Categories

```
GET /api/v1/service-case-categories
```

**Response:**

```json
{
  "data": [
    {
      "id": 1,
      "name": "Hardware",
      "description": "Hardware-related issues",
      "sla_response_hours": 4,
      "sla_resolution_hours": 24,
      "priority_default": "medium",
      "active": true
    },
    {
      "id": 2,
      "name": "Software",
      "description": "Software issues and installations",
      "sla_response_hours": 8,
      "sla_resolution_hours": 48,
      "priority_default": "low",
      "active": true
    }
  ]
}
```

## Output Configurations

### List Output Configurations

```
GET /api/v1/service-output-configurations
```

**Response:**

```json
{
  "data": [
    {
      "id": 1,
      "name": "IT Support Email",
      "output_type": "email",
      "category_id": 1,
      "email_to": ["it-support@company.com"],
      "active": true
    },
    {
      "id": 2,
      "name": "Jira Integration",
      "output_type": "webhook",
      "category_id": null,
      "webhook_url": "https://jira.company.com/webhook",
      "active": true
    }
  ]
}
```

### Test Webhook Configuration

```
POST /api/v1/service-output-configurations/{id}/test
```

**Response:**

```json
{
  "success": true,
  "status_code": 200,
  "response_time_ms": 245,
  "message": "Webhook test successful"
}
```

## Webhooks (Incoming)

### Create Case via Webhook

```
POST /webhooks/service-gateway/create-case
```

Used by external systems to create cases in AskPro.

**Headers:**

```
Content-Type: application/json
X-API-Key: your_api_key
```

**Request Body:**

```json
{
  "subject": "Ticket from external system",
  "description": "...",
  "external_id": "EXT-12345",
  "category_code": "hardware",
  "priority": "high",
  "customer": {
    "name": "Max Mustermann",
    "email": "max@example.com"
  }
}
```

## Webhooks (Outgoing)

### Payload Format

When Service Gateway sends webhooks to external systems:

```json
{
  "event": "case_created",
  "timestamp": "2024-01-10T14:30:00.000Z",
  "case": {
    "id": 123,
    "external_id": null,
    "subject": "{{subject}}",
    "description": "{{description}}",
    "priority": "{{priority}}",
    "category": "{{category}}",
    "customer": {
      "name": "{{customer_name}}",
      "phone": "{{customer_phone}}",
      "email": "{{customer_email}}"
    }
  }
}
```

### Webhook Events

| Event | Trigger |
|-------|---------|
| `case_created` | New case created |
| `case_updated` | Case status/priority changed |
| `case_assigned` | Case assigned to user |
| `case_resolved` | Case marked resolved |
| `case_escalated` | SLA breach or manual escalation |

## Error Codes

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `case_not_found` | 404 | Service case doesn't exist |
| `category_not_found` | 404 | Category doesn't exist |
| `invalid_status` | 422 | Invalid status transition |
| `sla_violation` | 422 | Operation would violate SLA |
| `webhook_failed` | 502 | External webhook delivery failed |

## Rate Limits

| Endpoint | Limit |
|----------|-------|
| List cases | 60/min |
| Create case | 20/min |
| Update case | 30/min |
| Webhook receive | 100/min |

## Filtering & Sorting

### Advanced Filters

```
GET /api/v1/service-cases?filter[status]=open&filter[priority]=high,critical
```

### Sorting

```
GET /api/v1/service-cases?sort=-created_at,priority
```

Prefix with `-` for descending order.

### Including Relations

```
GET /api/v1/service-cases?include=category,customer,notes,call
```

## Pagination

All list endpoints return paginated results:

```json
{
  "data": [...],
  "links": {
    "first": "...?page=1",
    "last": "...?page=5",
    "prev": null,
    "next": "...?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 20,
    "to": 20,
    "total": 95
  }
}
```
