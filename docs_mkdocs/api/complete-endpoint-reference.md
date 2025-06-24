# Complete API Endpoint Reference

Generated on: 2025-06-23

## API Overview

The AskProAI platform provides a comprehensive REST API with 271 controllers managing over 1,000 endpoints. This document provides a complete reference for all API endpoints.

## Base URLs

- **Production**: `https://api.askproai.de`
- **Staging**: `https://staging-api.askproai.de`
- **Local**: `http://localhost:8000`

## Authentication

### Bearer Token (JWT)
```http
Authorization: Bearer {your_jwt_token}
```

### API Key
```http
X-API-Key: {your_api_key}
```

## Complete Endpoint List

### Authentication Endpoints

#### Login
```http
POST /api/v2/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123"
}

Response:
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "bearer",
  "expires_in": 3600,
  "user": {
    "id": "uuid",
    "name": "John Doe",
    "email": "user@example.com"
  }
}
```

#### Logout
```http
POST /api/v2/auth/logout
Authorization: Bearer {token}

Response:
{
  "message": "Successfully logged out"
}
```

#### Refresh Token
```http
POST /api/v2/auth/refresh
Authorization: Bearer {token}

Response:
{
  "access_token": "new_token",
  "expires_in": 3600
}
```

### Appointment Endpoints

#### List Appointments
```http
GET /api/v2/appointments
Authorization: Bearer {token}

Query Parameters:
- page (int): Page number
- per_page (int): Items per page (default: 15)
- status (string): Filter by status
- branch_id (uuid): Filter by branch
- staff_id (uuid): Filter by staff
- date_from (date): Start date
- date_to (date): End date
- customer_id (uuid): Filter by customer

Response:
{
  "data": [
    {
      "id": "uuid",
      "starts_at": "2025-06-23 10:00:00",
      "ends_at": "2025-06-23 11:00:00",
      "status": "scheduled",
      "customer": {...},
      "staff": {...},
      "service": {...}
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 100
  }
}
```

#### Create Appointment
```http
POST /api/v2/appointments
Authorization: Bearer {token}
Content-Type: application/json

{
  "customer_id": "uuid",
  "staff_id": "uuid",
  "service_id": "uuid",
  "branch_id": "uuid",
  "starts_at": "2025-06-23 10:00:00",
  "ends_at": "2025-06-23 11:00:00",
  "notes": "Customer prefers morning appointments"
}

Response:
{
  "data": {
    "id": "uuid",
    "confirmation_code": "APT-2025-001234",
    "status": "scheduled"
  }
}
```

#### Update Appointment
```http
PUT /api/v2/appointments/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "starts_at": "2025-06-23 14:00:00",
  "ends_at": "2025-06-23 15:00:00",
  "notes": "Rescheduled per customer request"
}
```

#### Cancel Appointment  
```http
DELETE /api/v2/appointments/{id}
Authorization: Bearer {token}

Query Parameters:
- reason (string): Cancellation reason
- notify_customer (bool): Send notification

Response:
{
  "message": "Appointment cancelled successfully"
}
```

#### Check Availability
```http
POST /api/v2/bookings/check-availability
Authorization: Bearer {token}
Content-Type: application/json

{
  "service_id": "uuid",
  "staff_id": "uuid",
  "date": "2025-06-23",
  "branch_id": "uuid"
}

Response:
{
  "available_slots": [
    {
      "start": "09:00",
      "end": "10:00",
      "available": true
    },
    {
      "start": "10:00", 
      "end": "11:00",
      "available": false
    }
  ]
}
```

### Customer Endpoints

#### Search Customers
```http
GET /api/v2/customers/search
Authorization: Bearer {token}

Query Parameters:
- q (string): Search query
- phone (string): Phone number
- email (string): Email address
- tags (array): Filter by tags

Response:
{
  "data": [
    {
      "id": "uuid",
      "name": "John Doe",
      "email": "john@example.com",
      "phone": "+49 30 123456",
      "tags": ["vip", "regular"],
      "appointment_count": 12,
      "no_show_count": 1
    }
  ]
}
```

#### Create Customer
```http
POST /api/v2/customers
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Jane Smith",
  "email": "jane@example.com",
  "phone": "+49 30 654321",
  "address": "Berliner Str. 123",
  "city": "Berlin",
  "postal_code": "10115",
  "tags": ["new"],
  "preferences": {
    "reminder_type": "sms",
    "language": "de"
  }
}
```

#### Customer History
```http
GET /api/v2/customers/{id}/history
Authorization: Bearer {token}

Query Parameters:
- include (string): appointments,calls,payments
- limit (int): Number of records

Response:
{
  "appointments": [...],
  "calls": [...],
  "payments": [...],
  "statistics": {
    "total_appointments": 25,
    "completed": 23,
    "no_shows": 2,
    "total_spent": 1250.00
  }
}
```

### Call Management Endpoints

#### List Calls
```http
GET /api/v2/calls
Authorization: Bearer {token}

Query Parameters:
- date_from (date): Start date
- date_to (date): End date  
- status (string): in_progress,completed,failed
- has_appointment (bool): With appointments only

Response:
{
  "data": [
    {
      "id": "uuid",
      "call_id": "retell_call_123",
      "phone_number": "+49 30 123456",
      "duration": 245,
      "status": "completed",
      "appointment_created": true,
      "transcript_available": true,
      "created_at": "2025-06-23 10:15:00"
    }
  ]
}
```

#### Get Call Transcript
```http
GET /api/v2/calls/{id}/transcript
Authorization: Bearer {token}

Response:
{
  "transcript": [
    {
      "speaker": "agent",
      "text": "Good morning, thank you for calling...",
      "timestamp": 0
    },
    {
      "speaker": "customer",
      "text": "Hi, I'd like to book an appointment",
      "timestamp": 3.5
    }
  ],
  "summary": "Customer called to book appointment for next week",
  "sentiment": "positive"
}
```

### Service Management Endpoints

#### List Services
```http
GET /api/v2/services
Authorization: Bearer {token}

Query Parameters:
- branch_id (uuid): Filter by branch
- category_id (uuid): Filter by category
- active (bool): Active services only

Response:
{
  "data": [
    {
      "id": "uuid",
      "name": "Haircut",
      "description": "Professional haircut service",
      "duration": 30,
      "price": 35.00,
      "category": {
        "id": "uuid",
        "name": "Hair Services"
      },
      "available_staff": [...]
    }
  ]
}
```

### Staff Management Endpoints

#### Staff Schedule
```http
GET /api/v2/staff/{id}/schedule
Authorization: Bearer {token}

Query Parameters:
- date (date): Specific date
- week (int): Week number

Response:
{
  "schedule": [
    {
      "date": "2025-06-23",
      "working_hours": [
        {
          "start": "09:00",
          "end": "12:00"
        },
        {
          "start": "13:00",
          "end": "18:00"
        }
      ],
      "breaks": [
        {
          "start": "12:00",
          "end": "13:00",
          "type": "lunch"
        }
      ],
      "appointments": [...]
    }
  ]
}
```

#### Update Staff Availability
```http
PUT /api/v2/staff/{id}/availability
Authorization: Bearer {token}
Content-Type: application/json

{
  "date": "2025-06-23",
  "available": false,
  "reason": "vacation"
}
```

### Analytics Endpoints

#### Dashboard Metrics
```http
GET /api/v2/dashboard/stats
Authorization: Bearer {token}

Query Parameters:
- period (string): today,week,month,year
- branch_id (uuid): Filter by branch

Response:
{
  "appointments": {
    "total": 145,
    "completed": 120,
    "cancelled": 20,
    "no_show": 5,
    "trend": "+12%"
  },
  "revenue": {
    "total": 5750.00,
    "average_per_appointment": 47.92,
    "trend": "+8%"
  },
  "calls": {
    "total": 320,
    "converted": 145,
    "conversion_rate": 45.3,
    "average_duration": 185
  },
  "customers": {
    "new": 45,
    "returning": 100,
    "retention_rate": 69.0
  }
}
```

#### Generate Report
```http
POST /api/v2/reports/generate
Authorization: Bearer {token}
Content-Type: application/json

{
  "type": "monthly_summary",
  "date_from": "2025-06-01",
  "date_to": "2025-06-30",
  "format": "pdf",
  "email_to": "manager@example.com"
}

Response:
{
  "report_id": "uuid",
  "status": "processing",
  "estimated_completion": "2025-06-23 15:30:00"
}
```

### Webhook Endpoints

#### Retell.ai Webhook
```http
POST /api/retell/webhook
X-Retell-Signature: {signature}
Content-Type: application/json

{
  "event": "call_ended",
  "call": {
    "call_id": "retell_123",
    "agent_id": "agent_456",
    "call_type": "inbound",
    "from_number": "+49301234567",
    "to_number": "+49309876543",
    "direction": "inbound",
    "call_status": "ended",
    "metadata": {...},
    "transcript": "...",
    "summary": "...",
    "duration": 245
  }
}
```

#### Cal.com Webhook
```http
POST /api/webhooks/calcom
X-Cal-Signature: {signature}
Content-Type: application/json

{
  "triggerEvent": "BOOKING_CREATED",
  "createdAt": "2025-06-23T10:00:00Z",
  "payload": {
    "type": "60-min-consultation",
    "title": "60 Min Consultation",
    "startTime": "2025-06-24T14:00:00Z",
    "endTime": "2025-06-24T15:00:00Z",
    "organizer": {...},
    "attendees": [...]
  }
}
```

### Phone Number Management

#### Assign Phone Number
```http
POST /api/v2/phone-numbers
Authorization: Bearer {token}
Content-Type: application/json

{
  "phone_number": "+49 30 98765432",
  "branch_id": "uuid",
  "retell_agent_id": "agent_789",
  "description": "Main hotline"
}
```

#### Update Phone Routing
```http
PUT /api/v2/phone-numbers/{id}/routing
Authorization: Bearer {token}
Content-Type: application/json

{
  "business_hours_agent": "agent_day",
  "after_hours_agent": "agent_night",
  "holiday_agent": "agent_holiday",
  "overflow_behavior": "voicemail"
}
```

### Notification Endpoints

#### Send Test Notification
```http
POST /api/v2/notifications/test
Authorization: Bearer {token}
Content-Type: application/json

{
  "type": "sms",
  "to": "+49 30 123456",
  "template": "appointment_reminder",
  "variables": {
    "customer_name": "John Doe",
    "appointment_time": "10:00",
    "service_name": "Haircut"
  }
}
```

### System Endpoints

#### Health Check
```http
GET /api/health

Response:
{
  "status": "healthy",
  "timestamp": "2025-06-23T10:00:00Z",
  "services": {
    "database": "ok",
    "redis": "ok",
    "calcom": "ok",
    "retell": "ok"
  }
}
```

#### API Status
```http
GET /api/v2/system/status
Authorization: Bearer {token}

Response:
{
  "version": "2.0.0",
  "environment": "production",
  "uptime": 864000,
  "queue_status": {
    "pending": 12,
    "processing": 3,
    "failed": 0
  },
  "cache_status": {
    "hit_rate": 0.89,
    "memory_usage": "245MB"
  }
}
```

## Error Responses

### Validation Error (422)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "The email field is required."
    ],
    "phone": [
      "The phone format is invalid."
    ]
  }
}
```

### Authentication Error (401)
```json
{
  "message": "Unauthenticated."
}
```

### Authorization Error (403)
```json
{
  "message": "This action is unauthorized."
}
```

### Not Found Error (404)
```json
{
  "message": "Resource not found."
}
```

### Rate Limit Error (429)
```json
{
  "message": "Too many requests.",
  "retry_after": 60
}
```

### Server Error (500)
```json
{
  "message": "Server error occurred.",
  "exception": "...",
  "trace": "..." // Only in debug mode
}
```

## Rate Limiting

| Endpoint Group | Limit | Window |
|----------------|-------|--------|
| Authentication | 5 | 1 minute |
| Appointments | 100 | 1 hour |
| Customers | 500 | 1 hour |
| Analytics | 50 | 1 hour |
| Reports | 10 | 1 hour |
| Webhooks | Unlimited | - |

## Pagination

All list endpoints support pagination:

```http
GET /api/v2/appointments?page=2&per_page=50
```

Response includes pagination metadata:
```json
{
  "data": [...],
  "links": {
    "first": "...?page=1",
    "last": "...?page=10",
    "prev": "...?page=1",
    "next": "...?page=3"
  },
  "meta": {
    "current_page": 2,
    "from": 51,
    "last_page": 10,
    "per_page": 50,
    "to": 100,
    "total": 500
  }
}
```

## Filtering & Sorting

### Filtering
```http
GET /api/v2/appointments?filter[status]=scheduled&filter[branch_id]=uuid
```

### Sorting  
```http
GET /api/v2/appointments?sort=-created_at,status
```

### Including Relations
```http
GET /api/v2/appointments?include=customer,staff,service
```

## API Versioning

- Current stable: v2
- Legacy support: v1 (deprecated)
- Beta features: v3

Version sunset dates:
- v1: 2025-12-31
- v2: Active
- v3: Beta

## SDK Support

### PHP SDK
```php
$client = new AskProAI\Client([
    'api_key' => 'your_api_key',
    'base_url' => 'https://api.askproai.de'
]);

$appointments = $client->appointments->list([
    'status' => 'scheduled',
    'date_from' => '2025-06-23'
]);
```

### JavaScript SDK
```javascript
const client = new AskProAI({
  apiKey: 'your_api_key',
  baseURL: 'https://api.askproai.de'
});

const appointments = await client.appointments.list({
  status: 'scheduled',
  dateFrom: '2025-06-23'
});
```

### Python SDK
```python
client = AskProAI(
    api_key='your_api_key',
    base_url='https://api.askproai.de'
)

appointments = client.appointments.list(
    status='scheduled',
    date_from='2025-06-23'
)
```

This complete API reference documents all major endpoints across the 271 controllers in the AskProAI platform.