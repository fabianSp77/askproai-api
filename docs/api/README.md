# 🔌 AskProAI API Documentation

**Version:** 1.2.0  
**Base URL:** `https://api.askproai.de`  
**Authentication:** Bearer Token / API Key

## 📑 Table of Contents

1. [Authentication](#authentication)
2. [Webhooks](#webhooks)
3. [REST API Endpoints](#rest-api-endpoints)
4. [Error Handling](#error-handling)
5. [Rate Limiting](#rate-limiting)
6. [Response Formats](#response-formats)
7. [Testing](#testing)

---

## 🔐 Authentication

AskProAI API supports two authentication methods:

### Bearer Token
```bash
Authorization: Bearer YOUR_API_TOKEN
```

### API Key (Alternative)
```bash
X-API-Key: YOUR_API_KEY
```

### Getting API Keys
1. Login to Admin Panel: `/admin`
2. Navigate to Settings → API Keys
3. Generate new API Key
4. Copy the key (only shown once!)

---

## 🔗 Webhooks

### RetellAI Webhook

**Endpoint:** `POST /api/retell/webhook`  
**Description:** Processes incoming calls from RetellAI system  
**Authentication:** Signature verification

#### Headers
```bash
Content-Type: application/json
X-Retell-Signature: sha256=<signature>
```

#### Request Body
```json
{
  "event": "call_ended",
  "data": {
    "call_id": "call_abc123def456",
    "conversation_id": "conv_xyz789",
    "from_number": "+491234567890",
    "to_number": "+494012345678",
    "start_timestamp": 1699123456.789,
    "end_timestamp": 1699123556.123,
    "duration": 99.334,
    "transcript": "Hallo, ich möchte gerne einen Termin für nächste Woche buchen...",
    "call_analysis": {
      "intent": "appointment_booking",
      "sentiment": "positive",
      "confidence": 0.95,
      "entities": {
        "service": "Haarschnitt",
        "preferred_date": "2025-08-20",
        "customer_name": "Max Mustermann",
        "phone": "+491234567890"
      }
    },
    "call_successful": true,
    "disconnect_reason": "hangup_by_customer"
  }
}
```

#### Response
```json
{
  "success": true,
  "message": "Call processed successfully",
  "call_id": 12345,
  "appointment_created": true,
  "appointment_id": 67890
}
```

#### Event Types
- `call_started` - Call initiated
- `call_ended` - Call completed
- `call_failed` - Call failed to connect
- `call_transferred` - Call transferred to human

---

### Cal.com Webhook

**Endpoint:** `POST /api/calcom/webhook`  
**Description:** Processes booking events from Cal.com  
**Authentication:** Signature verification

#### Headers
```bash
Content-Type: application/json
X-Cal-Signature-256: <signature>
```

#### Request Body
```json
{
  "triggerEvent": "BOOKING_CREATED",
  "createdAt": "2025-08-14T10:00:00.000Z",
  "payload": {
    "bookingId": 12345,
    "uid": "abc123def456",
    "type": "Beratungsgespräch",
    "title": "Beratungsgespräch between John Doe and Jane Smith",
    "description": "30 Minuten Beratungstermin",
    "customInputs": {},
    "startTime": "2025-08-15T14:00:00.000Z",
    "endTime": "2025-08-15T15:00:00.000Z",
    "organizer": {
      "id": 1001,
      "name": "Jane Smith",
      "email": "jane.smith@salon-beispiel.de",
      "username": "janesmith",
      "timeZone": "Europe/Berlin"
    },
    "attendees": [
      {
        "name": "John Doe",
        "email": "john.doe@example.com", 
        "timeZone": "Europe/Berlin",
        "language": {
          "locale": "de"
        }
      }
    ],
    "location": "Salon Beispiel, Musterstraße 123, Berlin",
    "destinationCalendar": {
      "integration": "google_calendar",
      "externalId": "primary"
    },
    "cancelReason": null,
    "rejectionReason": null,
    "status": "ACCEPTED"
  }
}
```

#### Response
```json
{
  "received": true,
  "status": "processed",
  "appointment_id": 67890
}
```

#### Event Types
- `BOOKING_CREATED` - New booking created
- `BOOKING_RESCHEDULED` - Booking time changed
- `BOOKING_CANCELLED` - Booking cancelled
- `BOOKING_CONFIRMED` - Booking confirmed
- `MEETING_ENDED` - Meeting completed

---

## 🛠 REST API Endpoints

### Calls API

#### List All Calls
```bash
GET /api/calls
```

**Parameters:**
| Parameter | Type | Description | Default |
|-----------|------|-------------|---------|
| page | integer | Page number | 1 |
| per_page | integer | Items per page (max 100) | 15 |
| from_date | string | Start date (YYYY-MM-DD) | - |
| to_date | string | End date (YYYY-MM-DD) | - |
| status | string | Filter by status | - |
| customer_id | integer | Filter by customer | - |

**Example Request:**
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "https://api.askproai.de/api/calls?page=1&per_page=20&from_date=2025-08-01"
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 12345,
      "call_id": "call_abc123",
      "conversation_id": "conv_xyz789",
      "from_number": "+491234567890",
      "to_number": "+494012345678",
      "customer": {
        "id": 101,
        "name": "Max Mustermann",
        "email": "max@example.com",
        "phone": "+491234567890"
      },
      "start_timestamp": "2025-08-14T10:30:00.000000Z",
      "end_timestamp": "2025-08-14T10:33:15.000000Z",
      "duration_sec": 195,
      "call_successful": true,
      "transcript": "Hallo, ich möchte einen Termin buchen...",
      "analysis": {
        "intent": "appointment_booking",
        "sentiment": "positive",
        "confidence": 0.95
      },
      "created_at": "2025-08-14T10:30:00.000000Z",
      "updated_at": "2025-08-14T10:33:15.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 156,
    "total_pages": 8,
    "has_next_page": true
  }
}
```

#### Get Single Call
```bash
GET /api/calls/{id}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 12345,
    "call_id": "call_abc123",
    "conversation_id": "conv_xyz789",
    "from_number": "+491234567890",
    "to_number": "+494012345678",
    "customer": {
      "id": 101,
      "name": "Max Mustermann",
      "email": "max@example.com",
      "phone": "+491234567890",
      "created_at": "2025-07-01T00:00:00.000000Z"
    },
    "agent": {
      "id": 1,
      "name": "AskProAI Assistant",
      "type": "ai"
    },
    "start_timestamp": "2025-08-14T10:30:00.000000Z",
    "end_timestamp": "2025-08-14T10:33:15.000000Z",
    "duration_sec": 195,
    "call_successful": true,
    "disconnect_reason": "hangup_by_customer",
    "transcript": "Hallo, ich möchte einen Termin für nächste Woche buchen. Geht Donnerstag um 14 Uhr?",
    "analysis": {
      "intent": "appointment_booking",
      "sentiment": "positive",
      "confidence": 0.95,
      "entities": {
        "service": "Haarschnitt",
        "preferred_date": "2025-08-20",
        "preferred_time": "14:00",
        "customer_name": "Max Mustermann"
      },
      "summary": "Kunde möchte Termin für Haarschnitt am Donnerstag um 14 Uhr buchen."
    },
    "appointments": [
      {
        "id": 67890,
        "service": "Haarschnitt",
        "start_time": "2025-08-20T14:00:00.000000Z",
        "end_time": "2025-08-20T15:00:00.000000Z",
        "status": "scheduled"
      }
    ],
    "created_at": "2025-08-14T10:30:00.000000Z",
    "updated_at": "2025-08-14T10:33:15.000000Z"
  }
}
```

#### Create Call (Testing)
```bash
POST /api/calls
```

**Request Body:**
```json
{
  "from_number": "+491234567890",
  "to_number": "+494012345678",
  "customer_name": "Test Customer",
  "customer_email": "test@example.com",
  "transcript": "Test call transcript",
  "duration_sec": 120,
  "call_successful": true
}
```

---

### Appointments API

#### List Appointments
```bash
GET /api/appointments
```

**Parameters:**
| Parameter | Type | Description | Default |
|-----------|------|-------------|---------|
| page | integer | Page number | 1 |
| per_page | integer | Items per page | 15 |
| status | string | scheduled/completed/cancelled | - |
| from_date | string | Start date filter | - |
| to_date | string | End date filter | - |
| staff_id | integer | Filter by staff member | - |
| service_id | integer | Filter by service | - |

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 67890,
      "customer": {
        "id": 101,
        "name": "Max Mustermann",
        "email": "max@example.com",
        "phone": "+491234567890"
      },
      "staff": {
        "id": 201,
        "name": "Jane Smith",
        "email": "jane@salon-beispiel.de"
      },
      "service": {
        "id": 301,
        "name": "Haarschnitt",
        "duration_minutes": 60,
        "price_cents": 5000
      },
      "branch": {
        "id": 401,
        "name": "Salon Mitte",
        "address": "Musterstraße 123, 10115 Berlin"
      },
      "start_time": "2025-08-20T14:00:00.000000Z",
      "end_time": "2025-08-20T15:00:00.000000Z",
      "status": "scheduled",
      "notes": "Kunde möchte moderne Frisur",
      "calcom_booking_id": "12345",
      "call_id": 12345,
      "created_at": "2025-08-14T10:33:15.000000Z",
      "updated_at": "2025-08-14T10:33:15.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 45,
    "total_pages": 3
  }
}
```

#### Create Appointment
```bash
POST /api/appointments
```

**Request Body:**
```json
{
  "customer_id": 101,
  "staff_id": 201,
  "service_id": 301,
  "branch_id": 401,
  "start_time": "2025-08-20T14:00:00Z",
  "end_time": "2025-08-20T15:00:00Z",
  "notes": "Kunde möchte moderne Frisur",
  "call_id": 12345
}
```

**Response:**
```json
{
  "success": true,
  "message": "Appointment created successfully",
  "data": {
    "id": 67890,
    "start_time": "2025-08-20T14:00:00.000000Z",
    "end_time": "2025-08-20T15:00:00.000000Z",
    "status": "scheduled",
    "calcom_booking_id": "abc123def456",
    "created_at": "2025-08-14T10:33:15.000000Z"
  }
}
```

#### Update Appointment
```bash
PUT /api/appointments/{id}
```

**Request Body:**
```json
{
  "start_time": "2025-08-20T15:00:00Z",
  "end_time": "2025-08-20T16:00:00Z",
  "notes": "Zeit geändert auf Kundenwunsch",
  "status": "scheduled"
}
```

#### Cancel Appointment
```bash
DELETE /api/appointments/{id}
```

**Request Body (Optional):**
```json
{
  "cancel_reason": "Kunde hat abgesagt",
  "notify_customer": true
}
```

**Response:**
```json
{
  "success": true,
  "message": "Appointment cancelled successfully",
  "data": {
    "id": 67890,
    "status": "cancelled",
    "cancelled_at": "2025-08-14T10:45:00.000000Z"
  }
}
```

---

### Customers API

#### List Customers
```bash
GET /api/customers
```

**Parameters:**
| Parameter | Type | Description | Default |
|-----------|------|-------------|---------|
| search | string | Search by name/email/phone | - |
| page | integer | Page number | 1 |
| per_page | integer | Items per page | 15 |
| created_after | string | Filter by creation date | - |

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 101,
      "name": "Max Mustermann",
      "email": "max@example.com",
      "phone": "+491234567890",
      "birthdate": "1990-05-15",
      "total_calls": 5,
      "total_appointments": 8,
      "last_call_at": "2025-08-14T10:30:00.000000Z",
      "last_appointment_at": "2025-08-10T14:00:00.000000Z",
      "customer_since": "2025-07-01T00:00:00.000000Z",
      "created_at": "2025-07-01T00:00:00.000000Z",
      "updated_at": "2025-08-14T10:30:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 234,
    "total_pages": 16
  }
}
```

#### Get Customer Details
```bash
GET /api/customers/{id}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 101,
    "name": "Max Mustermann",
    "email": "max@example.com",
    "phone": "+491234567890",
    "birthdate": "1990-05-15",
    "preferences": {
      "preferred_staff": 201,
      "preferred_time": "afternoon",
      "communication_channel": "phone"
    },
    "statistics": {
      "total_calls": 5,
      "successful_calls": 5,
      "total_appointments": 8,
      "completed_appointments": 6,
      "cancelled_appointments": 1,
      "no_shows": 1,
      "total_spent_cents": 40000,
      "average_rating": 4.8
    },
    "recent_calls": [
      {
        "id": 12345,
        "call_id": "call_abc123",
        "start_timestamp": "2025-08-14T10:30:00.000000Z",
        "duration_sec": 195,
        "call_successful": true,
        "intent": "appointment_booking"
      }
    ],
    "upcoming_appointments": [
      {
        "id": 67890,
        "service": "Haarschnitt",
        "staff_name": "Jane Smith",
        "start_time": "2025-08-20T14:00:00.000000Z",
        "status": "scheduled"
      }
    ],
    "created_at": "2025-07-01T00:00:00.000000Z",
    "updated_at": "2025-08-14T10:30:00.000000Z"
  }
}
```

#### Create Customer
```bash
POST /api/customers
```

**Request Body:**
```json
{
  "name": "Maria Schmidt",
  "email": "maria@example.com",
  "phone": "+491234567891",
  "birthdate": "1985-12-03"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Customer created successfully",
  "data": {
    "id": 102,
    "name": "Maria Schmidt",
    "email": "maria@example.com",
    "phone": "+491234567891",
    "birthdate": "1985-12-03",
    "created_at": "2025-08-14T11:00:00.000000Z"
  }
}
```

---

### Staff API

#### List Staff Members
```bash
GET /api/staff
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 201,
      "name": "Jane Smith",
      "email": "jane@salon-beispiel.de",
      "phone": "+494012345678",
      "specializations": ["Haarschnitt", "Coloration", "Styling"],
      "availability": {
        "monday": ["09:00-17:00"],
        "tuesday": ["09:00-17:00"],
        "wednesday": ["09:00-17:00"],
        "thursday": ["09:00-17:00"],
        "friday": ["09:00-17:00"],
        "saturday": ["10:00-16:00"],
        "sunday": []
      },
      "home_branch": {
        "id": 401,
        "name": "Salon Mitte",
        "address": "Musterstraße 123, 10115 Berlin"
      },
      "statistics": {
        "total_appointments": 156,
        "completed_appointments": 142,
        "average_rating": 4.9,
        "total_revenue_cents": 780000
      },
      "created_at": "2025-06-01T00:00:00.000000Z",
      "updated_at": "2025-08-14T11:00:00.000000Z"
    }
  ]
}
```

---

### Services API

#### List Services
```bash
GET /api/services
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 301,
      "name": "Haarschnitt",
      "description": "Professioneller Haarschnitt mit Beratung",
      "duration_minutes": 60,
      "price_cents": 5000,
      "category": "Hair",
      "requires_consultation": false,
      "available_staff": [201, 202, 203],
      "bookable_online": true,
      "preparation_time": 5,
      "cleanup_time": 10,
      "created_at": "2025-06-01T00:00:00.000000Z",
      "updated_at": "2025-06-01T00:00:00.000000Z"
    }
  ]
}
```

---

### Analytics API

#### Dashboard Stats
```bash
GET /api/analytics/dashboard
```

**Parameters:**
| Parameter | Type | Description | Default |
|-----------|------|-------------|---------|
| period | string | daily/weekly/monthly | daily |
| from_date | string | Start date | 7 days ago |
| to_date | string | End date | today |

**Response:**
```json
{
  "success": true,
  "data": {
    "period": "daily",
    "from_date": "2025-08-07",
    "to_date": "2025-08-14",
    "totals": {
      "total_calls": 156,
      "successful_calls": 142,
      "total_appointments": 89,
      "completed_appointments": 76,
      "total_revenue_cents": 380000,
      "new_customers": 23
    },
    "trends": {
      "calls_growth": 12.5,
      "appointments_growth": 8.3,
      "revenue_growth": 15.7,
      "conversion_rate": 57.1
    },
    "daily_stats": [
      {
        "date": "2025-08-14",
        "calls": 25,
        "appointments": 14,
        "revenue_cents": 70000,
        "conversion_rate": 56.0
      }
    ],
    "top_services": [
      {
        "service_id": 301,
        "service_name": "Haarschnitt",
        "appointments": 45,
        "revenue_cents": 225000
      }
    ],
    "top_staff": [
      {
        "staff_id": 201,
        "staff_name": "Jane Smith",
        "appointments": 32,
        "revenue_cents": 160000,
        "rating": 4.9
      }
    ]
  }
}
```

---

## ❌ Error Handling

### Error Response Format
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "details": {
      "email": ["The email field is required."],
      "phone": ["The phone format is invalid."]
    }
  },
  "request_id": "req_abc123def456"
}
```

### HTTP Status Codes
| Code | Description | Usage |
|------|-------------|-------|
| 200 | OK | Successful GET, PUT |
| 201 | Created | Successful POST |
| 204 | No Content | Successful DELETE |
| 400 | Bad Request | Invalid request data |
| 401 | Unauthorized | Invalid/missing auth |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Resource doesn't exist |
| 422 | Unprocessable Entity | Validation errors |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server error |

### Common Error Codes
- `VALIDATION_ERROR` - Request data validation failed
- `AUTHENTICATION_ERROR` - Invalid credentials
- `AUTHORIZATION_ERROR` - Insufficient permissions
- `RESOURCE_NOT_FOUND` - Requested resource not found
- `RATE_LIMIT_EXCEEDED` - Too many requests
- `WEBHOOK_SIGNATURE_INVALID` - Invalid webhook signature
- `SERVICE_UNAVAILABLE` - External service error

---

## ⚡ Rate Limiting

### Limits
- **API Calls:** 1000 requests per hour per API key
- **Webhooks:** No limit (signature verified)
- **Authentication:** 10 login attempts per minute per IP

### Headers
Rate limit information is included in response headers:
```bash
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 856
X-RateLimit-Reset: 1699123456
```

### Rate Limit Response
```json
{
  "success": false,
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "Rate limit exceeded. Try again in 45 seconds.",
    "details": {
      "limit": 1000,
      "remaining": 0,
      "reset_at": "2025-08-14T12:00:00.000000Z"
    }
  }
}
```

---

## 📊 Response Formats

### Pagination
```json
{
  "success": true,
  "data": [...],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 156,
    "total_pages": 11,
    "has_next_page": true,
    "has_prev_page": false
  },
  "links": {
    "first": "https://api.askproai.de/api/calls?page=1",
    "prev": null,
    "next": "https://api.askproai.de/api/calls?page=2",
    "last": "https://api.askproai.de/api/calls?page=11"
  }
}
```

### Date Formats
- **Timestamps:** ISO 8601 UTC format (`2025-08-14T10:30:00.000000Z`)
- **Dates:** ISO 8601 date format (`2025-08-14`)
- **Time:** 24-hour format (`14:30:00`)

### Currency
All monetary values are in **cents** (e.g., €50.00 = 5000 cents)

---

## 🧪 Testing

### API Testing Environment
**Base URL:** `https://api-staging.askproai.de`

### Test API Keys
```bash
# Read-only test key
X-API-Key: test_readonly_abc123def456

# Full access test key  
X-API-Key: test_fullaccess_xyz789
```

### Postman Collection
Import our Postman collection for easy testing:
```bash
curl -o askproai-api.postman_collection.json \
  https://api.askproai.de/docs/postman/collection.json
```

### cURL Examples

#### Test Authentication
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  https://api.askproai.de/api/user
```

#### Test Webhook (Local)
```bash
curl -X POST http://localhost:8000/api/retell/webhook \
  -H "Content-Type: application/json" \
  -H "X-Retell-Signature: sha256=test_signature" \
  -d '{
    "event": "call_ended",
    "data": {
      "call_id": "test_call_123",
      "from_number": "+491234567890",
      "transcript": "Test call",
      "call_successful": true
    }
  }'
```

#### Create Test Appointment
```bash
curl -X POST https://api.askproai.de/api/appointments \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_id": 1,
    "staff_id": 1,
    "service_id": 1,
    "start_time": "2025-08-20T14:00:00Z",
    "end_time": "2025-08-20T15:00:00Z"
  }'
```

---

## 🔗 SDKs & Libraries

### Official SDKs (Coming Soon)
- **PHP SDK** - `composer require askproai/php-sdk`
- **Node.js SDK** - `npm install @askproai/node-sdk`
- **Python SDK** - `pip install askproai-python-sdk`

### Community Libraries
- **Laravel Package** - Integration for Laravel apps
- **WordPress Plugin** - WordPress integration
- **Zapier App** - No-code integrations

---

## 📞 Support

### API Support
- 📧 **Email:** api-support@askproai.de
- 📚 **Documentation:** [Full API Docs](https://docs.askproai.de/api)
- 💬 **Discord:** [Join our Community](https://discord.gg/askproai)

### SLA & Uptime
- **Uptime Target:** 99.9%
- **Response Time:** < 200ms average
- **Support Hours:** 24/7 for critical issues

---

*API Documentation v1.2.0*  
*Last Updated: August 14, 2025*  
*© 2025 AskProAI - All Rights Reserved*