# Customer Portal API Documentation

**Version:** 1.0
**Date:** 2025-11-24
**Status:** Phase 6 Complete - API Layer Implemented

---

## Table of Contents

1. [Overview](#overview)
2. [Authentication](#authentication)
3. [API Endpoints](#api-endpoints)
4. [Error Handling](#error-handling)
5. [Rate Limiting](#rate-limiting)
6. [Examples](#examples)
7. [Security](#security)

---

## Overview

The Customer Portal API provides RESTful endpoints for customers to manage their appointments. The API uses token-based authentication via Laravel Sanctum.

**Base URL:** `https://api.askproai.de/api/customer-portal`

**Content Type:** `application/json`

**Authentication:** Bearer Token (Sanctum)

---

## Authentication

### Token-Based Invitation Flow

1. User receives email with invitation link containing token
2. Frontend validates token via `GET /invitations/{token}/validate`
3. User fills registration form
4. Frontend submits via `POST /invitations/{token}/accept`
5. API returns Sanctum token for subsequent requests
6. Token stored in localStorage (client-side)

### Using the Token

Include the token in the `Authorization` header:

```http
Authorization: Bearer {access_token}
```

---

## API Endpoints

### 1. Validate Invitation Token

**Endpoint:** `GET /api/customer-portal/invitations/{token}/validate`

**Authentication:** None (public)

**Rate Limit:** 60 requests per minute

**Description:** Check if invitation token is valid, not expired, and not already used.

**Response (200 OK):**

```json
{
  "valid": true,
  "invitation": {
    "email": "customer@example.com",
    "status": "pending",
    "expires_at": "2025-11-27T10:00:00+01:00",
    "expires_at_human": "in 3 days",
    "is_expired": false,
    "is_accepted": false,
    "company": {
      "id": 1,
      "name": "Salon Mitte"
    },
    "role": {
      "id": 5,
      "name": "company_staff",
      "display_name": "Mitarbeiter"
    },
    "invited_at": "2025-11-24T10:00:00+01:00",
    "invited_by": "Admin User"
  }
}
```

**Response (422 Unprocessable Entity - Expired):**

```json
{
  "valid": false,
  "error": "This invitation has expired. Please request a new one.",
  "error_code": "TOKEN_EXPIRED",
  "expired_at": "2025-11-23T10:00:00+01:00"
}
```

**Response (422 Unprocessable Entity - Already Used):**

```json
{
  "valid": false,
  "error": "This invitation has already been used.",
  "error_code": "TOKEN_ALREADY_USED"
}
```

**Response (404 Not Found):**

```json
{
  "valid": false,
  "error": "Invalid invitation token.",
  "error_code": "TOKEN_NOT_FOUND"
}
```

---

### 2. Accept Invitation

**Endpoint:** `POST /api/customer-portal/invitations/{token}/accept`

**Authentication:** None (public)

**Rate Limit:** 10 requests per minute

**Description:** Accept invitation and create user account.

**Request Body:**

```json
{
  "name": "Max Mustermann",
  "email": "customer@example.com",
  "password": "SecurePass123",
  "password_confirmation": "SecurePass123",
  "phone": "+49 151 1234567",
  "terms_accepted": true
}
```

**Validation Rules:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| name | string | Yes | Min: 2, Max: 255 |
| email | string | Yes | Valid email, Max: 255 |
| password | string | Yes | Min: 8, Must contain letters & numbers |
| password_confirmation | string | Yes | Must match password |
| phone | string | No | Max: 20, Valid phone format |
| terms_accepted | boolean | Yes | Must be true |

**Response (201 Created):**

```json
{
  "success": true,
  "message": "Account created successfully. Welcome!",
  "user": {
    "id": 123,
    "name": "Max Mustermann",
    "email": "customer@example.com",
    "phone": "+49 151 1234567",
    "email_verified_at": "2025-11-24T10:00:00+01:00",
    "company": {
      "id": 1,
      "name": "Salon Mitte",
      "email": "info@salon-mitte.de",
      "phone": "+49 30 12345678"
    },
    "role": "company_staff",
    "created_at": "2025-11-24T10:00:00+01:00",
    "updated_at": "2025-11-24T10:00:00+01:00"
  },
  "access_token": "1|abc123def456...",
  "token_type": "Bearer"
}
```

**Response (422 Unprocessable Entity - Email Mismatch):**

```json
{
  "success": false,
  "error": "Email does not match invitation.",
  "error_code": "EMAIL_MISMATCH"
}
```

---

### 3. List Appointments

**Endpoint:** `GET /api/customer-portal/appointments`

**Authentication:** Bearer Token (required)

**Rate Limit:** 60 requests per minute

**Description:** List all appointments for authenticated user.

**Query Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| status | string | No | upcoming | Filter: `upcoming`, `past`, `cancelled` |
| from_date | date | No | - | ISO8601 date (e.g., 2025-11-24) |
| to_date | date | No | - | ISO8601 date (e.g., 2025-11-30) |

**Response (200 OK):**

```json
{
  "success": true,
  "data": [
    {
      "id": 762,
      "start_time": "2025-11-25T10:00:00+01:00",
      "start_time_human": "Montag, 25. November 2025 um 10:00 Uhr",
      "end_time": "2025-11-25T11:00:00+01:00",
      "duration_minutes": 60,
      "status": "confirmed",
      "status_label": "Bestätigt",
      "service": {
        "id": 5,
        "name": "Herrenhaarschnitt",
        "description": "Klassischer Herrenhaarschnitt mit Waschen und Föhnen",
        "duration": 60,
        "price": "25,00 EUR"
      },
      "staff": {
        "id": 3,
        "name": "Fabian Spitzer",
        "avatar_url": null,
        "bio": "Meisterfriseur mit 10 Jahren Erfahrung"
      },
      "location": {
        "branch_name": "Salon Mitte",
        "address": "Hauptstraße 1, 10115 Berlin",
        "phone": "+49 30 12345678",
        "email": "info@salon-mitte.de"
      },
      "is_composite": false,
      "can_reschedule": true,
      "can_cancel": true,
      "reschedule_deadline": "2025-11-24T10:00:00+01:00",
      "cancel_deadline": "2025-11-24T10:00:00+01:00",
      "notes": null,
      "version": 1,
      "created_at": "2025-11-20T14:30:00+01:00",
      "last_modified_at": null
    }
  ],
  "meta": {
    "total": 1,
    "status": "upcoming"
  }
}
```

---

### 4. Show Single Appointment

**Endpoint:** `GET /api/customer-portal/appointments/{id}`

**Authentication:** Bearer Token (required)

**Rate Limit:** 60 requests per minute

**Description:** Show detailed information for a single appointment.

**Response (200 OK):**

Same structure as list endpoint, but single object instead of array.

**Response (403 Forbidden):**

```json
{
  "success": false,
  "error": "You are not authorized to view this appointment."
}
```

**Response (404 Not Found):**

```json
{
  "success": false,
  "error": "Appointment not found."
}
```

---

### 5. Get Alternative Time Slots

**Endpoint:** `GET /api/customer-portal/appointments/{id}/alternatives`

**Authentication:** Bearer Token (required)

**Rate Limit:** 60 requests per minute

**Description:** Get alternative time slots for rescheduling (same day preferred, next 7 days).

**Response (200 OK):**

```json
{
  "success": true,
  "data": {
    "original_appointment": {
      "id": 762,
      "start_time": "2025-11-25T10:00:00+01:00",
      "service": "Herrenhaarschnitt",
      "staff": "Fabian Spitzer"
    },
    "alternatives": [
      {
        "time": "2025-11-25T09:00:00+01:00",
        "time_human": "Montag, 25. November 2025 um 09:00 Uhr",
        "type": "same_day_earlier",
        "available": true,
        "staff_name": "Fabian Spitzer"
      },
      {
        "time": "2025-11-25T14:00:00+01:00",
        "time_human": "Montag, 25. November 2025 um 14:00 Uhr",
        "type": "same_day_later",
        "available": true,
        "staff_name": "Fabian Spitzer"
      },
      {
        "time": "2025-11-26T10:00:00+01:00",
        "time_human": "Dienstag, 26. November 2025 um 10:00 Uhr",
        "type": "next_day",
        "available": true,
        "staff_name": "Fabian Spitzer"
      }
    ],
    "search_parameters": {
      "days_searched": 7,
      "service_duration": 60,
      "same_staff_only": true
    }
  }
}
```

---

### 6. Reschedule Appointment

**Endpoint:** `PUT /api/customer-portal/appointments/{id}/reschedule`

**Authentication:** Bearer Token (required)

**Rate Limit:** 10 requests per minute

**Description:** Reschedule appointment to new time slot.

**Request Body:**

```json
{
  "new_start_time": "2025-11-26T10:00:00+01:00",
  "reason": "Terminkonflikt - muss einen Tag später kommen"
}
```

**Validation Rules:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| new_start_time | datetime | Yes | ISO8601 format, future date |
| reason | string | No | Max: 500 characters |

**Response (200 OK):**

```json
{
  "success": true,
  "message": "Appointment rescheduled successfully.",
  "data": {
    "id": 762,
    "start_time": "2025-11-26T10:00:00+01:00",
    "start_time_human": "Dienstag, 26. November 2025 um 10:00 Uhr",
    "status": "confirmed",
    "version": 2,
    "last_modified_at": "2025-11-24T10:15:00+01:00"
  }
}
```

**Response (403 Forbidden):**

```json
{
  "success": false,
  "error": "You are not authorized to reschedule this appointment."
}
```

**Response (409 Conflict - Optimistic Lock):**

```json
{
  "success": false,
  "error": "Appointment was modified by another user. Please refresh and try again.",
  "error_code": "OPTIMISTIC_LOCK_CONFLICT"
}
```

**Response (422 Unprocessable Entity - Minimum Notice):**

```json
{
  "success": false,
  "error": "Appointments must be rescheduled at least 24 hours in advance.",
  "error_code": "MINIMUM_NOTICE_VIOLATION"
}
```

**Response (422 Unprocessable Entity - Slot Not Available):**

```json
{
  "success": false,
  "error": "The selected time slot is no longer available.",
  "error_code": "SLOT_NOT_AVAILABLE"
}
```

---

### 7. Cancel Appointment

**Endpoint:** `DELETE /api/customer-portal/appointments/{id}`

**Authentication:** Bearer Token (required)

**Rate Limit:** 10 requests per minute

**Description:** Cancel appointment with reason.

**Request Body:**

```json
{
  "reason": "Krankheit - kann leider nicht kommen"
}
```

**Validation Rules:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| reason | string | Yes | Min: 10, Max: 500 characters |

**Response (200 OK):**

```json
{
  "success": true,
  "message": "Appointment cancelled successfully.",
  "data": {
    "appointment_id": 762,
    "cancelled_at": "2025-11-24T10:20:00+01:00",
    "cancellation_confirmed": true
  }
}
```

**Response (403 Forbidden):**

```json
{
  "success": false,
  "error": "You are not authorized to cancel this appointment."
}
```

**Response (422 Unprocessable Entity - Already Cancelled):**

```json
{
  "success": false,
  "error": "Cannot cancel already cancelled appointments.",
  "error_code": "ALREADY_CANCELLED"
}
```

**Response (422 Unprocessable Entity - Past Appointment):**

```json
{
  "success": false,
  "error": "Cannot cancel past appointments.",
  "error_code": "PAST_APPOINTMENT"
}
```

---

## Error Handling

### Error Response Format

All error responses follow this structure:

```json
{
  "success": false,
  "error": "Human-readable error message",
  "error_code": "MACHINE_READABLE_CODE"
}
```

### HTTP Status Codes

| Code | Meaning | When |
|------|---------|------|
| 200 | OK | Successful request |
| 201 | Created | Resource created successfully |
| 400 | Bad Request | Invalid request format |
| 401 | Unauthorized | Missing or invalid authentication token |
| 403 | Forbidden | User not authorized for this action |
| 404 | Not Found | Resource does not exist |
| 409 | Conflict | Optimistic lock conflict (version mismatch) |
| 422 | Unprocessable Entity | Validation errors |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Unexpected server error |
| 503 | Service Unavailable | Cal.com API unavailable |

### Error Codes

#### Authentication Errors
- `TOKEN_NOT_FOUND` - Invitation token does not exist
- `TOKEN_EXPIRED` - Invitation token has expired
- `TOKEN_ALREADY_USED` - Invitation has already been accepted
- `EMAIL_MISMATCH` - Email does not match invitation

#### Authorization Errors
- `UNAUTHORIZED` - Missing or invalid authentication
- `FORBIDDEN` - User not authorized for this action

#### Validation Errors
- `VALIDATION_ERROR` - Input validation failed
- `MINIMUM_NOTICE_VIOLATION` - Action violates minimum notice period
- `OUTSIDE_BUSINESS_HOURS` - Time outside business hours

#### Conflict Errors
- `OPTIMISTIC_LOCK_CONFLICT` - Concurrent modification detected
- `SLOT_NOT_AVAILABLE` - Time slot no longer available
- `ALREADY_CANCELLED` - Appointment already cancelled
- `PAST_APPOINTMENT` - Cannot modify past appointment

#### System Errors
- `RESCHEDULE_ERROR` - Generic reschedule error
- `CANCELLATION_ERROR` - Generic cancellation error
- `CALCOM_ERROR` - Cal.com API error

---

## Rate Limiting

### Limits by Endpoint Type

| Endpoint Type | Limit | Period |
|---------------|-------|--------|
| Public (validate, accept) | 60 req | 1 minute |
| Read (list, show, alternatives) | 60 req | 1 minute |
| Write (reschedule, cancel) | 10 req | 1 minute |

### Rate Limit Headers

All responses include rate limit information:

```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1700820000
```

### Rate Limit Exceeded Response (429)

```json
{
  "message": "Too Many Requests",
  "retry_after": 60
}
```

---

## Examples

### Complete Flow Example (cURL)

#### 1. Validate Token

```bash
curl -X GET \
  https://api.askproai.de/api/customer-portal/invitations/abc123def456/validate \
  -H 'Content-Type: application/json'
```

#### 2. Accept Invitation

```bash
curl -X POST \
  https://api.askproai.de/api/customer-portal/invitations/abc123def456/accept \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "Max Mustermann",
    "email": "max@example.com",
    "password": "SecurePass123",
    "password_confirmation": "SecurePass123",
    "phone": "+49 151 1234567",
    "terms_accepted": true
  }'
```

Save the `access_token` from response.

#### 3. List Appointments

```bash
curl -X GET \
  https://api.askproai.de/api/customer-portal/appointments?status=upcoming \
  -H 'Authorization: Bearer 1|abc123def456...' \
  -H 'Content-Type: application/json'
```

#### 4. Get Alternative Slots

```bash
curl -X GET \
  https://api.askproai.de/api/customer-portal/appointments/762/alternatives \
  -H 'Authorization: Bearer 1|abc123def456...' \
  -H 'Content-Type: application/json'
```

#### 5. Reschedule Appointment

```bash
curl -X PUT \
  https://api.askproai.de/api/customer-portal/appointments/762/reschedule \
  -H 'Authorization: Bearer 1|abc123def456...' \
  -H 'Content-Type: application/json' \
  -d '{
    "new_start_time": "2025-11-26T10:00:00+01:00",
    "reason": "Terminkonflikt"
  }'
```

#### 6. Cancel Appointment

```bash
curl -X DELETE \
  https://api.askproai.de/api/customer-portal/appointments/762 \
  -H 'Authorization: Bearer 1|abc123def456...' \
  -H 'Content-Type: application/json' \
  -d '{
    "reason": "Krankheit - kann leider nicht kommen"
  }'
```

---

## Security

### Authentication

- **Token-Based:** Laravel Sanctum tokens
- **Token Storage:** Client-side localStorage (HTTPS only)
- **Token Expiry:** 30 days (configurable)
- **Token Rotation:** Not implemented (future enhancement)

### Authorization

- **Policy-Based:** All endpoints use Laravel policies
- **Ownership Validation:** Users can only access their own appointments
- **Multi-Tenant Isolation:** Company-scoped via customer_id

### Data Protection

- **HTTPS Only:** All requests must use HTTPS
- **Password Hashing:** Bcrypt with cost factor 10
- **Input Validation:** All inputs validated server-side
- **SQL Injection Prevention:** Eloquent ORM parameterized queries
- **XSS Prevention:** Blade escaping on all outputs

### Audit Trail

- **Immutable Logs:** All actions logged to `appointment_audit_logs`
- **User Tracking:** Every modification tracks user_id
- **Version Control:** Optimistic locking with version field
- **Timestamp Tracking:** created_at, updated_at, last_modified_at

### Rate Limiting

- **Per-User Limits:** Enforced via Sanctum token
- **IP-Based Fallback:** For public endpoints
- **Sliding Window:** 1-minute window

### CORS

- **Allowed Origins:** Portal subdomain only
- **Allowed Methods:** GET, POST, PUT, DELETE
- **Allowed Headers:** Authorization, Content-Type
- **Credentials:** Allowed

---

## Implementation Details

### Controllers

- `/app/Http/Controllers/CustomerPortal/AuthController.php` - Authentication
- `/app/Http/Controllers/CustomerPortal/AppointmentController.php` - Appointments

### Request Validators

- `/app/Http/Requests/CustomerPortal/AcceptInvitationRequest.php`
- `/app/Http/Requests/CustomerPortal/RescheduleAppointmentRequest.php`
- `/app/Http/Requests/CustomerPortal/CancelAppointmentRequest.php`

### API Resources

- `/app/Http/Resources/CustomerPortal/AppointmentResource.php`
- `/app/Http/Resources/CustomerPortal/UserResource.php`
- `/app/Http/Resources/CustomerPortal/InvitationResource.php`

### Policies

- `/app/Policies/CustomerPortal/AppointmentPolicy.php`

### Routes

- `/routes/api.php` - All API routes registered

### Services

- `/app/Services/CustomerPortal/UserManagementService.php`
- `/app/Services/CustomerPortal/AppointmentRescheduleService.php`
- `/app/Services/CustomerPortal/AppointmentCancellationService.php`

### Tests

- `/tests/Feature/CustomerPortal/InvitationTest.php` - 9 tests
- `/tests/Feature/CustomerPortal/AppointmentTest.php` - 10 tests

---

## Testing

### Run All Tests

```bash
php artisan test --filter=CustomerPortal
```

### Run Specific Test Suite

```bash
# Invitation tests
php artisan test tests/Feature/CustomerPortal/InvitationTest.php

# Appointment tests
php artisan test tests/Feature/CustomerPortal/AppointmentTest.php
```

### Test Coverage

- **Invitation Flow:** 9 tests (validation, acceptance, security)
- **Appointment Management:** 10 tests (CRUD, authorization, isolation)
- **Total Coverage:** 19 tests

---

## Next Steps (Phase 7 & 8)

### Phase 7: Frontend Implementation
1. Blade templates with Alpine.js + Tailwind
2. Registration page (`/portal/einladung/{token}`)
3. Appointment list page (`/portal/meine-termine`)
4. Reschedule page (`/portal/termin/{id}/umbuchen`)
5. Cancel page (`/portal/termin/{id}/stornieren`)
6. Mobile-responsive design

### Phase 8: Integration & Testing
1. Filament Admin Panel integration
2. Email templates (invitation, confirmation, cancellation)
3. E2E tests (Playwright)
4. Manual testing checklist
5. Production deployment

---

**Document Version:** 1.0
**Last Updated:** 2025-11-24
**Author:** Backend Architect (Claude Code)
