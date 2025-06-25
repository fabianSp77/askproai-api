# Mobile API

The AskProAI Mobile API provides a comprehensive set of endpoints optimized for mobile applications, supporting both iOS and Android platforms with features like offline sync, push notifications, and optimized data transfer.

## Overview

The Mobile API is designed with mobile-first principles:
- 📱 Optimized payload sizes
- 🔄 Offline synchronization support
- 📍 Location-based features
- 🔔 Push notification integration
- 🔐 Biometric authentication support
- ⚡ Efficient pagination and caching

## Base URL

```
https://api.askproai.de/api/mobile/v1
```

## Authentication

### Initial Authentication
```http
POST /api/mobile/v1/auth/login
```

**Request:**
```json
{
    "email": "user@example.com",
    "password": "secure_password",
    "device": {
        "id": "device-uuid",
        "type": "ios",
        "model": "iPhone 14",
        "os_version": "16.5",
        "app_version": "1.2.0"
    }
}
```

**Response:**
```json
{
    "user": {
        "id": "user-uuid",
        "name": "Max Mustermann",
        "email": "user@example.com",
        "company_id": "company-uuid"
    },
    "tokens": {
        "access_token": "jwt-access-token",
        "refresh_token": "jwt-refresh-token",
        "expires_in": 3600
    },
    "permissions": ["view_appointments", "book_appointments", "manage_customers"]
}
```

### Biometric Authentication
```http
POST /api/mobile/v1/auth/biometric
```

**Request:**
```json
{
    "device_id": "device-uuid",
    "biometric_token": "encrypted-biometric-data"
}
```

### Token Refresh
```http
POST /api/mobile/v1/auth/refresh
```

## Dashboard

### Home Screen Data
```http
GET /api/mobile/v1/dashboard
```

**Response:**
```json
{
    "stats": {
        "today_appointments": 12,
        "pending_callbacks": 3,
        "new_customers": 5,
        "revenue_today": 450.00
    },
    "upcoming_appointments": [
        {
            "id": "apt-uuid",
            "customer_name": "Anna Schmidt",
            "service": "Haarschnitt",
            "time": "2025-06-26T10:00:00+02:00",
            "duration": 30,
            "staff_name": "Maria"
        }
    ],
    "recent_activities": [
        {
            "type": "new_booking",
            "description": "New appointment booked",
            "time": "2025-06-25T15:30:00+02:00"
        }
    ]
}
```

## Appointments

### List Appointments
```http
GET /api/mobile/v1/appointments
```

**Query Parameters:**
- `date`: Filter by date (YYYY-MM-DD)
- `status`: Filter by status (scheduled, completed, cancelled)
- `page`: Page number
- `per_page`: Items per page (default: 20)

**Response:**
```json
{
    "data": [
        {
            "id": "apt-uuid",
            "customer": {
                "id": "cust-uuid",
                "name": "Max Mustermann",
                "phone": "+4915123456789",
                "avatar_url": "https://cdn.askproai.de/avatars/max.jpg"
            },
            "service": {
                "id": "srv-uuid",
                "name": "Haarschnitt Herren",
                "duration": 30,
                "price": 25.00
            },
            "staff": {
                "id": "staff-uuid",
                "name": "Maria",
                "avatar_url": "https://cdn.askproai.de/avatars/maria.jpg"
            },
            "scheduled_at": "2025-06-26T10:00:00+02:00",
            "status": "scheduled",
            "notes": "Kunde bevorzugt kurze Haare"
        }
    ],
    "meta": {
        "current_page": 1,
        "total_pages": 5,
        "total_items": 94,
        "has_more": true
    }
}
```

### Create Quick Appointment
```http
POST /api/mobile/v1/appointments/quick
```

**Request:**
```json
{
    "customer_phone": "+4915123456789",
    "service_id": "srv-uuid",
    "datetime": "2025-06-26T14:00:00+02:00",
    "notes": "Walk-in customer"
}
```

### Update Appointment Status
```http
PATCH /api/mobile/v1/appointments/{id}/status
```

**Request:**
```json
{
    "status": "completed",
    "completion_notes": "Customer was satisfied"
}
```

## Customers

### Search Customers
```http
GET /api/mobile/v1/customers/search
```

**Query Parameters:**
- `q`: Search query (name, phone, email)
- `limit`: Max results (default: 10)

**Response:**
```json
{
    "results": [
        {
            "id": "cust-uuid",
            "name": "Anna Schmidt",
            "phone": "+4915198765432",
            "email": "anna@example.com",
            "last_visit": "2025-06-20",
            "total_visits": 15,
            "favorite_services": ["Haarschnitt", "Färben"],
            "notes": "Allergisch gegen bestimmte Produkte"
        }
    ]
}
```

### Customer Timeline
```http
GET /api/mobile/v1/customers/{id}/timeline
```

**Response:**
```json
{
    "customer": {
        "id": "cust-uuid",
        "name": "Anna Schmidt",
        "member_since": "2024-01-15"
    },
    "timeline": [
        {
            "date": "2025-06-20",
            "type": "appointment",
            "service": "Haarschnitt",
            "staff": "Maria",
            "price": 35.00,
            "rating": 5
        },
        {
            "date": "2025-05-15",
            "type": "call",
            "duration": 180,
            "outcome": "booked_appointment"
        }
    ]
}
```

## Calls

### Recent Calls
```http
GET /api/mobile/v1/calls
```

**Response:**
```json
{
    "calls": [
        {
            "id": "call-uuid",
            "from_number": "+4915123456789",
            "customer_name": "Max Mustermann",
            "duration": 120,
            "time": "2025-06-25T14:30:00+02:00",
            "outcome": "appointment_booked",
            "recording_url": "https://secure.askproai.de/recordings/call-uuid.mp3"
        }
    ]
}
```

### Call Details
```http
GET /api/mobile/v1/calls/{id}
```

**Response includes:**
- Full transcript
- AI analysis
- Detected intent
- Customer sentiment
- Action items

## Push Notifications

### Register Device
```http
POST /api/mobile/v1/devices/register
```

**Request:**
```json
{
    "device_id": "device-uuid",
    "push_token": "firebase-or-apns-token",
    "platform": "ios",
    "preferences": {
        "new_appointments": true,
        "cancellations": true,
        "reminders": true,
        "marketing": false
    }
}
```

### Update Notification Settings
```http
PATCH /api/mobile/v1/devices/{device_id}/notifications
```

## Offline Sync

### Sync Status
```http
GET /api/mobile/v1/sync/status
```

**Response:**
```json
{
    "last_sync": "2025-06-25T16:00:00+02:00",
    "pending_changes": 3,
    "sync_version": 156
}
```

### Sync Data
```http
POST /api/mobile/v1/sync
```

**Request:**
```json
{
    "last_sync_version": 155,
    "changes": [
        {
            "type": "appointment_update",
            "id": "apt-uuid",
            "data": {
                "status": "completed"
            },
            "timestamp": "2025-06-25T15:45:00+02:00"
        }
    ]
}
```

**Response:**
```json
{
    "sync_version": 156,
    "changes": [
        {
            "type": "new_appointment",
            "data": { /* appointment data */ }
        }
    ],
    "conflicts": []
}
```

## Analytics

### Performance Metrics
```http
GET /api/mobile/v1/analytics/performance
```

**Query Parameters:**
- `period`: day, week, month, year
- `branch_id`: Filter by branch (optional)

**Response:**
```json
{
    "period": "week",
    "metrics": {
        "appointments": {
            "total": 156,
            "completed": 145,
            "cancelled": 8,
            "no_show": 3
        },
        "revenue": {
            "total": 4580.00,
            "average_per_appointment": 31.59,
            "growth_percentage": 12.5
        },
        "customers": {
            "new": 23,
            "returning": 133,
            "satisfaction_score": 4.8
        }
    },
    "chart_data": {
        "labels": ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
        "appointments": [20, 22, 25, 24, 28, 32, 15],
        "revenue": [580, 640, 720, 695, 810, 925, 435]
    }
}
```

## Location Services

### Nearby Branches
```http
GET /api/mobile/v1/branches/nearby
```

**Query Parameters:**
- `lat`: Latitude
- `lng`: Longitude
- `radius`: Search radius in km (default: 10)

**Response:**
```json
{
    "branches": [
        {
            "id": "branch-uuid",
            "name": "AskProAI Berlin Mitte",
            "address": "Friedrichstraße 123, 10117 Berlin",
            "distance": 1.2,
            "phone": "+493012345678",
            "opening_hours": {
                "today": "09:00-18:00",
                "is_open": true
            },
            "services_available": ["Haarschnitt", "Färben", "Styling"]
        }
    ]
}
```

## Error Handling

### Error Response Format
```json
{
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "Invalid input data",
        "details": {
            "field": "customer_phone",
            "issue": "Invalid phone number format"
        }
    }
}
```

### Common Error Codes
- `AUTHENTICATION_REQUIRED`: Missing or invalid token
- `PERMISSION_DENIED`: Insufficient permissions
- `RESOURCE_NOT_FOUND`: Requested resource doesn't exist
- `VALIDATION_ERROR`: Input validation failed
- `SYNC_CONFLICT`: Offline sync conflict
- `RATE_LIMITED`: Too many requests

## Performance Guidelines

### Request Optimization
1. Use field filtering: `?fields=id,name,phone`
2. Implement pagination for lists
3. Cache responses with ETags
4. Use compression (gzip)
5. Batch multiple operations when possible

### Data Efficiency
```http
GET /api/mobile/v1/appointments?fields=id,customer.name,scheduled_at&slim=true
```

### Image Handling
All images support responsive sizing:
```
https://cdn.askproai.de/avatars/user.jpg?w=100&h=100&q=85
```

## SDK Integration

### iOS (Swift)
```swift
import AskProAIKit

let client = AskProAIClient(apiKey: "your-api-key")

// Fetch appointments
client.appointments.list(date: Date()) { result in
    switch result {
    case .success(let appointments):
        // Handle appointments
    case .failure(let error):
        // Handle error
    }
}
```

### Android (Kotlin)
```kotlin
val client = AskProAIClient.getInstance(context)

// Fetch appointments
client.appointments.list(date = LocalDate.now()) { appointments ->
    // Update UI
}
```

### React Native
```javascript
import { AskProAI } from '@askproai/mobile-sdk';

const client = new AskProAI({ apiKey: 'your-api-key' });

// Fetch appointments
const appointments = await client.appointments.list({
    date: new Date()
});
```

---

*Last updated: June 25, 2025*