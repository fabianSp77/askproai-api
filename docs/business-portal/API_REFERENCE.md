# Business Portal API Reference

## Overview

The Business Portal API provides comprehensive access to all portal functionality. All endpoints require authentication and return JSON responses.

## Base URL

```
Production: https://api.askproai.de/api/v2/portal
Staging: https://staging.askproai.de/api/v2/portal
Local: http://localhost:8000/api/v2/portal
```

## Authentication

### Login

```http
POST /auth/login
Content-Type: application/json

{
  "email": "user@company.com",
  "password": "password123",
  "two_factor_code": "123456"  // Optional, required if 2FA enabled
}
```

**Response:**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "user@company.com",
    "company_id": 1,
    "role": "admin",
    "permissions": ["calls.view", "appointments.manage"],
    "two_factor_enabled": true
  },
  "company": {
    "id": 1,
    "name": "ACME Corp",
    "settings": {}
  }
}
```

### Logout

```http
POST /auth/logout
Authorization: Bearer {token}
```

### Refresh Token

```http
POST /auth/refresh
Authorization: Bearer {token}
```

### Register

```http
POST /auth/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@company.com",
  "password": "SecurePassword123!",
  "company_name": "ACME Corp",
  "phone": "+49123456789",
  "terms_accepted": true
}
```

## Headers

All API requests must include:

```http
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
X-Requested-With: XMLHttpRequest
```

## Dashboard

### Get Dashboard Data

```http
GET /dashboard
```

**Response:**
```json
{
  "stats": {
    "total_calls": 1250,
    "total_appointments": 450,
    "conversion_rate": 36.0,
    "revenue": 125000,
    "period_comparison": {
      "calls_change": 12.5,
      "appointments_change": -5.2,
      "revenue_change": 8.7
    }
  },
  "recent_activity": [
    {
      "id": 1,
      "type": "call_received",
      "description": "New call from +49123456789",
      "timestamp": "2025-01-10T10:30:00Z",
      "metadata": {}
    }
  ],
  "chart_data": {
    "calls_by_day": [],
    "appointments_by_service": [],
    "revenue_by_month": []
  },
  "goals": [
    {
      "id": 1,
      "name": "Q1 Revenue Target",
      "current_value": 45000,
      "target_value": 100000,
      "percentage": 45.0
    }
  ]
}
```

## Calls

### List Calls

```http
GET /calls
```

**Query Parameters:**
- `page` (integer): Page number (default: 1)
- `per_page` (integer): Items per page (default: 20, max: 100)
- `search` (string): Search by phone, customer name
- `status` (string): Filter by status (completed, missed, failed)
- `date_from` (date): Start date (YYYY-MM-DD)
- `date_to` (date): End date (YYYY-MM-DD)
- `branch_id` (integer): Filter by branch
- `staff_id` (integer): Filter by staff member
- `sort_by` (string): Sort field (created_at, duration, customer_name)
- `sort_order` (string): Sort order (asc, desc)

**Response:**
```json
{
  "data": [
    {
      "id": 123,
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "phone_number": "+49123456789",
      "customer": {
        "id": 45,
        "name": "Max Mustermann",
        "email": "max@example.com",
        "phone": "+49123456789",
        "total_appointments": 5,
        "lifetime_value": 500.00
      },
      "duration": 180,
      "status": "completed",
      "recording_url": "https://recordings.retell.ai/...",
      "transcript": "Full conversation transcript...",
      "summary": "Customer called to book appointment for next week",
      "sentiment": "positive",
      "appointment_booked": true,
      "created_at": "2025-01-10T10:30:00Z",
      "metadata": {
        "ai_agent": "dental_assistant",
        "language": "de"
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 20,
    "total": 200
  }
}
```

### Get Call Details

```http
GET /calls/{id}
```

**Response:**
```json
{
  "id": 123,
  "uuid": "550e8400-e29b-41d4-a716-446655440000",
  "phone_number": "+49123456789",
  "customer": {
    "id": 45,
    "name": "Max Mustermann",
    "email": "max@example.com",
    "journey_stage": "customer",
    "risk_score": 0.2
  },
  "duration": 180,
  "status": "completed",
  "recording_url": "https://recordings.retell.ai/...",
  "transcript": "Full conversation transcript...",
  "summary": "Customer called to book appointment",
  "action_items": [
    "Book appointment for dental cleaning",
    "Send reminder 24h before"
  ],
  "appointments": [
    {
      "id": 789,
      "scheduled_at": "2025-01-15T14:00:00Z",
      "service": "Dental Cleaning",
      "staff": "Dr. Smith"
    }
  ],
  "timeline": [
    {
      "timestamp": "2025-01-10T10:30:00Z",
      "event": "call_started",
      "details": {}
    },
    {
      "timestamp": "2025-01-10T10:32:45Z", 
      "event": "appointment_requested",
      "details": {"service": "cleaning"}
    }
  ],
  "analytics": {
    "talk_time_ratio": 0.65,
    "keywords": ["appointment", "cleaning", "next week"],
    "topics": ["scheduling", "services", "pricing"]
  }
}
```

### Export Calls

```http
POST /calls/export
Content-Type: application/json

{
  "format": "csv",
  "filters": {
    "date_from": "2025-01-01",
    "date_to": "2025-01-31",
    "status": "completed"
  },
  "fields": ["id", "phone_number", "customer_name", "duration", "created_at"]
}
```

**Response:**
```json
{
  "download_url": "https://api.askproai.de/downloads/calls-export-20250110.csv",
  "expires_at": "2025-01-11T10:30:00Z"
}
```

## Appointments

### List Appointments

```http
GET /appointments
```

**Query Parameters:**
- `page`, `per_page`: Pagination
- `status`: scheduled, confirmed, completed, cancelled, no_show
- `date_from`, `date_to`: Date range
- `branch_id`, `staff_id`, `service_id`: Filters
- `customer_search`: Search by customer name/phone

**Response:**
```json
{
  "data": [
    {
      "id": 789,
      "customer": {
        "id": 45,
        "name": "Max Mustermann",
        "phone": "+49123456789"
      },
      "service": {
        "id": 12,
        "name": "Dental Cleaning",
        "duration": 60,
        "price": 80.00
      },
      "staff": {
        "id": 5,
        "name": "Dr. Smith"
      },
      "scheduled_at": "2025-01-15T14:00:00Z",
      "end_at": "2025-01-15T15:00:00Z",
      "status": "confirmed",
      "notes": "First time customer",
      "created_at": "2025-01-10T10:30:00Z",
      "source": "ai_phone"
    }
  ],
  "meta": {}
}
```

### Create Appointment

```http
POST /appointments
Content-Type: application/json

{
  "customer_id": 45,
  "service_id": 12,
  "staff_id": 5,
  "branch_id": 1,
  "scheduled_at": "2025-01-15T14:00:00Z",
  "notes": "Customer prefers afternoon appointments",
  "send_confirmation": true
}
```

### Update Appointment

```http
PUT /appointments/{id}
Content-Type: application/json

{
  "scheduled_at": "2025-01-16T15:00:00Z",
  "status": "rescheduled",
  "notes": "Customer requested different time"
}
```

### Cancel Appointment

```http
POST /appointments/{id}/cancel
Content-Type: application/json

{
  "reason": "Customer cancelled",
  "notify_customer": true
}
```

### Check Availability

```http
POST /appointments/check-availability
Content-Type: application/json

{
  "service_id": 12,
  "staff_id": 5,
  "date": "2025-01-15",
  "branch_id": 1
}
```

**Response:**
```json
{
  "available_slots": [
    {
      "start": "2025-01-15T09:00:00Z",
      "end": "2025-01-15T10:00:00Z"
    },
    {
      "start": "2025-01-15T10:00:00Z",
      "end": "2025-01-15T11:00:00Z"
    }
  ]
}
```

## Customers

### List Customers

```http
GET /customers
```

**Query Parameters:**
- `search`: Search by name, email, phone
- `journey_stage`: Filter by journey stage
- `risk_level`: high, medium, low
- `has_appointments`: true/false
- `sort_by`: name, created_at, lifetime_value, last_appointment

### Get Customer Details

```http
GET /customers/{id}
```

**Response:**
```json
{
  "id": 45,
  "name": "Max Mustermann",
  "email": "max@example.com",
  "phone": "+49123456789",
  "address": {
    "street": "Hauptstraße 1",
    "city": "Berlin",
    "postal_code": "10115",
    "country": "DE"
  },
  "journey": {
    "current_stage": "regular",
    "stage_entered_at": "2024-10-15T10:00:00Z",
    "lifetime_value": 1250.00,
    "total_appointments": 12,
    "risk_score": 0.2,
    "engagement_score": 0.8
  },
  "stats": {
    "first_appointment": "2024-01-15T10:00:00Z",
    "last_appointment": "2024-12-20T14:00:00Z",
    "total_spent": 1250.00,
    "average_spend": 104.17,
    "no_show_count": 1,
    "cancellation_count": 2
  },
  "timeline": [],
  "tags": ["vip", "prefers_morning"],
  "notes": "Excellent customer, always on time"
}
```

### Update Customer

```http
PUT /customers/{id}
Content-Type: application/json

{
  "name": "Max Mustermann",
  "email": "max.new@example.com",
  "tags": ["vip", "prefers_morning", "birthday_discount"]
}
```

### Get Customer Journey

```http
GET /customers/{id}/journey
```

### Update Journey Stage

```http
PUT /customers/{id}/journey/stage
Content-Type: application/json

{
  "stage": "vip",
  "reason": "High lifetime value and frequency"
}
```

## Team Management

### List Team Members

```http
GET /team
```

### Invite Team Member

```http
POST /team/invite
Content-Type: application/json

{
  "email": "newmember@company.com",
  "name": "Jane Doe",
  "role": "staff",
  "permissions": ["calls.view", "appointments.view"],
  "branch_ids": [1, 2]
}
```

### Update Team Member

```http
PUT /team/{id}
Content-Type: application/json

{
  "role": "manager",
  "permissions": ["calls.view", "appointments.manage", "team.view"]
}
```

### Remove Team Member

```http
DELETE /team/{id}
```

## Settings

### Get Company Settings

```http
GET /settings
```

**Response:**
```json
{
  "company": {
    "id": 1,
    "name": "ACME Corp",
    "logo_url": "https://...",
    "website": "https://acme.com",
    "timezone": "Europe/Berlin",
    "currency": "EUR",
    "language": "de"
  },
  "notifications": {
    "email_notifications": true,
    "sms_notifications": false,
    "appointment_reminders": true,
    "daily_summary": true
  },
  "integrations": {
    "calcom": {
      "connected": true,
      "last_sync": "2025-01-10T10:00:00Z"
    },
    "retell": {
      "connected": true,
      "agent_count": 3
    }
  },
  "features": {
    "goals_enabled": true,
    "journey_enabled": true,
    "multi_branch": true,
    "custom_fields": false
  }
}
```

### Update Settings

```http
PUT /settings
Content-Type: application/json

{
  "company": {
    "name": "ACME Corporation",
    "timezone": "Europe/Berlin"
  },
  "notifications": {
    "email_notifications": false
  }
}
```

## Analytics

### Get Analytics Overview

```http
GET /analytics/overview
```

**Query Parameters:**
- `period`: today, week, month, quarter, year, custom
- `date_from`, `date_to`: For custom period
- `branch_id`: Filter by branch
- `compare`: true/false (compare with previous period)

**Response:**
```json
{
  "summary": {
    "total_calls": 1250,
    "total_appointments": 450,
    "conversion_rate": 36.0,
    "average_call_duration": 185,
    "total_revenue": 125000,
    "average_appointment_value": 277.78
  },
  "comparison": {
    "calls_change": 12.5,
    "appointments_change": -5.2,
    "conversion_change": 2.1,
    "revenue_change": 8.7
  },
  "charts": {
    "calls_timeline": [],
    "appointments_by_service": [],
    "revenue_by_branch": [],
    "peak_hours": []
  }
}
```

### Get Call Analytics

```http
GET /analytics/calls
```

### Get Appointment Analytics

```http
GET /analytics/appointments
```

### Get Revenue Analytics

```http
GET /analytics/revenue
```

### Export Analytics Report

```http
POST /analytics/export
Content-Type: application/json

{
  "type": "comprehensive",
  "period": "month",
  "format": "pdf",
  "sections": ["summary", "calls", "appointments", "revenue", "goals"]
}
```

## Goal System

### List Goals

```http
GET /goals
```

**Query Parameters:**
- `status`: active, completed, failed, paused
- `type`: revenue, volume, conversion, custom

### Create Goal

```http
POST /goals
Content-Type: application/json

{
  "name": "Q1 Revenue Target",
  "type": "revenue",
  "target_value": 100000,
  "unit": "EUR",
  "start_date": "2025-01-01",
  "end_date": "2025-03-31",
  "calculation_method": "sum_appointment_revenue"
}
```

### Update Goal Progress

```http
POST /goals/{id}/update-progress
```

### Get Goal Metrics

```http
GET /goals/{id}/metrics
```

### Get Goal Achievements

```http
GET /goals/{id}/achievements
```

## Customer Journey

### Get Journey Stages

```http
GET /customer-journey/stages
```

### Update Stage Configuration

```http
PUT /customer-journey/stages/{stage_key}
Content-Type: application/json

{
  "name": "VIP Customer",
  "color": "#8B5CF6",
  "auto_rules": {
    "trigger": "lifetime_value",
    "conditions": {"value": ">=2000"}
  }
}
```

### Get Journey Analytics

```http
GET /customer-journey/analytics
```

## Billing

### Get Billing Overview

```http
GET /billing
```

### Get Invoices

```http
GET /billing/invoices
```

### Get Usage

```http
GET /billing/usage
```

### Create Top-up

```http
POST /billing/topup
Content-Type: application/json

{
  "amount": 100,
  "payment_method_id": "pm_1234567890"
}
```

## Webhooks

### Register Webhook

```http
POST /webhooks
Content-Type: application/json

{
  "url": "https://your-domain.com/webhook",
  "events": ["call.received", "appointment.created", "appointment.cancelled"],
  "secret": "your-webhook-secret"
}
```

### List Webhooks

```http
GET /webhooks
```

### Delete Webhook

```http
DELETE /webhooks/{id}
```

## Audit Logs

### Get Audit Logs

```http
GET /audit-logs
```

**Query Parameters:**
- `user_id`: Filter by user
- `action`: Filter by action
- `model_type`: Filter by resource type
- `risk_level`: high, medium, low
- `date_from`, `date_to`: Date range

### Export Audit Logs

```http
POST /audit-logs/export
Content-Type: application/json

{
  "format": "csv",
  "date_from": "2025-01-01",
  "date_to": "2025-01-31"
}
```

## Security

### Enable Two-Factor Authentication

```http
POST /security/2fa/enable
```

**Response:**
```json
{
  "secret": "JBSWY3DPEHPK3PXP",
  "qr_code": "data:image/png;base64,...",
  "recovery_codes": [
    "a1b2c3d4e5",
    "f6g7h8i9j0"
  ]
}
```

### Verify 2FA

```http
POST /security/2fa/verify
Content-Type: application/json

{
  "code": "123456"
}
```

### Get Active Sessions

```http
GET /security/sessions
```

### Terminate Session

```http
DELETE /security/sessions/{id}
```

## Error Responses

All errors follow this format:

```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "details": {
      "email": ["The email field is required."],
      "password": ["The password must be at least 8 characters."]
    }
  },
  "meta": {
    "timestamp": "2025-01-10T10:30:00Z",
    "request_id": "550e8400-e29b-41d4-a716-446655440000"
  }
}
```

### Error Codes

- `AUTHENTICATION_REQUIRED`: 401 - No valid token provided
- `UNAUTHORIZED`: 403 - Valid token but insufficient permissions
- `NOT_FOUND`: 404 - Resource not found
- `VALIDATION_ERROR`: 422 - Invalid input data
- `RATE_LIMITED`: 429 - Too many requests
- `SERVER_ERROR`: 500 - Internal server error

## Rate Limiting

- Default: 60 requests per minute per user
- Auth endpoints: 5 requests per minute per IP
- Export endpoints: 10 requests per hour per user

Rate limit headers:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1610000000
```

## Pagination

All list endpoints support pagination:

```json
{
  "data": [...],
  "links": {
    "first": "https://api.askproai.de/api/v2/portal/calls?page=1",
    "last": "https://api.askproai.de/api/v2/portal/calls?page=10",
    "prev": null,
    "next": "https://api.askproai.de/api/v2/portal/calls?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 10,
    "path": "https://api.askproai.de/api/v2/portal/calls",
    "per_page": 20,
    "to": 20,
    "total": 200
  }
}
```

## Versioning

The API uses URL versioning. Current version: `v2`

Deprecated endpoints will include:
```
X-API-Deprecation: true
X-API-Deprecation-Date: 2025-06-01
X-API-Deprecation-Info: https://docs.askproai.de/deprecations
```

## SDK Examples

### JavaScript/TypeScript

```typescript
import { PortalAPI } from '@askproai/portal-sdk';

const api = new PortalAPI({
  baseURL: 'https://api.askproai.de',
  token: 'your-api-token'
});

// Get dashboard data
const dashboard = await api.dashboard.get();

// List calls with filters
const calls = await api.calls.list({
  status: 'completed',
  dateFrom: '2025-01-01',
  perPage: 50
});

// Create appointment
const appointment = await api.appointments.create({
  customerId: 45,
  serviceId: 12,
  staffId: 5,
  scheduledAt: '2025-01-15T14:00:00Z'
});
```

### PHP

```php
use AskProAI\PortalSDK\Client;

$client = new Client([
    'base_url' => 'https://api.askproai.de',
    'token' => 'your-api-token'
]);

// Get dashboard data
$dashboard = $client->dashboard()->get();

// List calls
$calls = $client->calls()->list([
    'status' => 'completed',
    'date_from' => '2025-01-01'
]);

// Create appointment
$appointment = $client->appointments()->create([
    'customer_id' => 45,
    'service_id' => 12,
    'staff_id' => 5,
    'scheduled_at' => '2025-01-15T14:00:00Z'
]);
```

---

*For implementation details and more examples, see the [main documentation](./BUSINESS_PORTAL_COMPLETE_DOCUMENTATION.md)*