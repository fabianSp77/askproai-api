# REST API v2 Reference

## Overview

The AskProAI REST API v2 provides a comprehensive interface for managing appointments, customers, and phone-based booking services. This API follows RESTful principles and returns JSON responses.

## Base URL

```
https://api.askproai.de/api/v2
```

## API Versioning

We use URL-based versioning. The current stable version is `v2`. The legacy `v1` API is deprecated and will be removed in Q3 2025.

## Core Resources

### Appointments

Manage appointment bookings across all channels.

#### List Appointments

```http
GET /appointments
```

**Query Parameters:**

| Parameter | Type | Description | Default |
|-----------|------|-------------|---------|
| `page` | integer | Page number | 1 |
| `per_page` | integer | Results per page (max 100) | 20 |
| `status` | string | Filter by status | - |
| `start_date` | date | Filter from date | - |
| `end_date` | date | Filter to date | - |
| `branch_id` | integer | Filter by branch | - |
| `customer_id` | integer | Filter by customer | - |

**Example Request:**

```bash
curl -X GET "https://api.askproai.de/api/v2/appointments?status=confirmed&start_date=2025-06-23" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "X-Company-ID: 1"
```

**Example Response:**

```json
{
  "data": [
    {
      "id": 12345,
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "status": "confirmed",
      "start_time": "2025-06-23T10:00:00Z",
      "end_time": "2025-06-23T10:30:00Z",
      "service": {
        "id": 1,
        "name": "Haircut",
        "duration": 30,
        "price": 35.00
      },
      "staff": {
        "id": 5,
        "name": "John Doe",
        "email": "john@example.com"
      },
      "customer": {
        "id": 789,
        "name": "Jane Smith",
        "phone": "+49 30 123456",
        "email": "jane@example.com"
      },
      "branch": {
        "id": 1,
        "name": "Berlin Central",
        "address": "Alexanderplatz 1, 10178 Berlin"
      },
      "notes": "Prefers short haircut",
      "created_at": "2025-06-20T08:00:00Z",
      "updated_at": "2025-06-20T08:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total_pages": 5,
    "total_count": 98,
    "per_page": 20
  }
}
```

#### Create Appointment

```http
POST /appointments
```

**Request Body:**

```json
{
  "service_id": 1,
  "staff_id": 5,
  "customer": {
    "name": "Jane Smith",
    "phone": "+49 30 123456",
    "email": "jane@example.com"
  },
  "start_time": "2025-06-24T10:00:00Z",
  "branch_id": 1,
  "notes": "First time customer",
  "send_confirmation": true
}
```

**Response:**

```json
{
  "data": {
    "id": 12346,
    "uuid": "550e8400-e29b-41d4-a716-446655440001",
    "status": "confirmed",
    "confirmation_sent": true,
    "booking_url": "https://cal.com/reschedule/550e8400"
  }
}
```

#### Get Single Appointment

```http
GET /appointments/{id}
```

#### Update Appointment

```http
PUT /appointments/{id}
```

#### Cancel Appointment

```http
POST /appointments/{id}/cancel
```

**Request Body:**

```json
{
  "reason": "Customer requested cancellation",
  "notify_customer": true
}
```

### Customers

Manage customer information and history.

#### Search Customers

```http
GET /customers/search
```

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `q` | string | Search query (name, phone, email) |
| `phone` | string | Exact phone match |
| `email` | string | Exact email match |

#### Get Customer History

```http
GET /customers/{id}/appointments
```

Returns all appointments for a specific customer.

### Availability

Check available time slots for booking.

#### Get Available Slots

```http
GET /availability
```

**Query Parameters:**

| Parameter | Type | Description | Required |
|-----------|------|-------------|----------|
| `service_id` | integer | Service to book | Yes |
| `staff_id` | integer | Specific staff member | No |
| `date` | date | Date to check | Yes |
| `branch_id` | integer | Branch location | Yes |

**Response:**

```json
{
  "data": {
    "date": "2025-06-24",
    "slots": [
      {
        "time": "09:00",
        "available": true,
        "staff_available": [1, 2, 5]
      },
      {
        "time": "09:30",
        "available": true,
        "staff_available": [2, 5]
      },
      {
        "time": "10:00",
        "available": false,
        "reason": "fully_booked"
      }
    ]
  }
}
```

### Branches

Manage company locations.

#### List Branches

```http
GET /branches
```

**Response:**

```json
{
  "data": [
    {
      "id": 1,
      "name": "Berlin Central",
      "address": "Alexanderplatz 1, 10178 Berlin",
      "phone": "+49 30 123456",
      "email": "berlin@example.com",
      "operating_hours": {
        "monday": {"open": "09:00", "close": "18:00"},
        "tuesday": {"open": "09:00", "close": "18:00"}
      },
      "services": [1, 2, 3, 5],
      "staff_count": 8,
      "active": true
    }
  ]
}
```

### Services

Get available services and pricing.

#### List Services

```http
GET /services
```

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `branch_id` | integer | Filter by branch |
| `category` | string | Filter by category |

### Phone Calls

Access call history and recordings.

#### List Recent Calls

```http
GET /calls
```

**Response:**

```json
{
  "data": [
    {
      "id": "call_abc123",
      "phone_number": "+49 30 987654",
      "duration": 245,
      "status": "completed",
      "ai_summary": "Customer wanted to book haircut for next Tuesday",
      "appointment_created": true,
      "appointment_id": 12345,
      "recording_url": "https://secure.retellai.com/recordings/...",
      "transcript": "...",
      "created_at": "2025-06-23T09:45:00Z"
    }
  ]
}
```

## Webhooks

Configure webhooks to receive real-time updates.

#### Register Webhook

```http
POST /webhooks
```

**Request Body:**

```json
{
  "url": "https://your-app.com/webhook",
  "events": ["appointment.created", "appointment.cancelled"],
  "secret": "your-webhook-secret"
}
```

## Pagination

All list endpoints support pagination:

```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "total_pages": 10,
    "total_count": 193,
    "per_page": 20
  },
  "links": {
    "first": "https://api.askproai.de/api/v2/resource?page=1",
    "last": "https://api.askproai.de/api/v2/resource?page=10",
    "next": "https://api.askproai.de/api/v2/resource?page=2",
    "prev": null
  }
}
```

## Filtering & Sorting

Most endpoints support filtering and sorting:

```http
GET /appointments?status=confirmed&sort=-created_at
```

Sort prefixes:
- No prefix: Ascending order
- `-` prefix: Descending order

## Rate Limiting

See [Rate Limiting](/api/rate-limiting) documentation for details.

## Error Handling

See [Error Codes](/api/error-codes) documentation for comprehensive error reference.

## SDKs & Libraries

Official SDKs coming soon:
- PHP SDK
- JavaScript/TypeScript SDK
- Python SDK

## Postman Collection

Download our Postman collection for easy API testing:
[Download Postman Collection](https://api.askproai.de/downloads/askproai-api-v2.postman.json)