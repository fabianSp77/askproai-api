# Customer Portal MVP - Detailed Requirements Document

**Date:** 2025-11-24
**Version:** 1.0
**Status:** Requirements Analysis Complete
**Backend Status:** ✅ 112/112 Tests Passing (Phases 4-5 Complete)

---

## 📋 Executive Summary

### Current State
- ✅ **Database Layer:** Complete (user_invitations, appointment_audit_logs, invitation_email_queue)
- ✅ **Service Layer:** Complete (UserManagementService, AppointmentRescheduleService, AppointmentCancellationService)
- ✅ **Background Jobs:** Complete (Email queue, cleanup jobs)
- ✅ **Observers:** Complete (Optimistic locking, audit trails, security)
- ✅ **Tests:** 112/112 passing
- ❌ **API Layer:** Missing (controllers, routes, resources)
- ❌ **Frontend:** Missing (views, components, authentication)
- ❌ **Admin Integration:** Missing (Filament resources)

### What We're Building
A customer-facing portal enabling:
1. ✅ One-time registration via email invitation
2. ✅ Token-based authentication (Laravel Sanctum)
3. ✅ View upcoming/past/cancelled appointments
4. ✅ Reschedule appointments with Cal.com sync
5. ✅ Cancel appointments with optional reason
6. ✅ Mobile-responsive design

### Critical Success Factors
- 🔒 **Security:** Multi-tenant isolation, privilege escalation prevention, audit trails
- ⚡ **Performance:** < 500ms API response, < 2s page load
- 🎯 **UX:** German language, mobile-first, zero cognitive load
- 🛡️ **Compliance:** GDPR, WCAG 2.1 AA, audit trails

---

## 🎯 Requirements by Priority

### P0 - Critical (Must Have for MVP)
1. Invitation acceptance flow (email → registration → portal access)
2. View appointments (list + detail)
3. Reschedule appointments (with alternatives)
4. Cancel appointments (with reason)
5. Multi-tenant isolation (company-scoped)
6. Sanctum token authentication
7. Filament admin integration (send invitation)

### P1 - Important (Should Have for MVP)
1. Mobile-responsive design
2. German translations
3. Optimistic locking conflict resolution
4. Email notifications (confirmation, reminders)
5. Audit trail for all actions
6. Rate limiting (60 req/min)

### P2 - Nice to Have (Could Have)
1. Alternative slot recommendations (same day)
2. Calendar export (.ics files)
3. Profile management (name, phone, email)
4. Appointment history analytics

### P3 - Future Enhancements (Won't Have in MVP)
1. Traditional login (email/password)
2. Recurring appointment patterns
3. Multi-language support (beyond German)
4. Push notifications
5. In-app chat with staff

---

## 🔌 API Specifications (Phase 6)

### Authentication Model
- **Type:** Token-based (Laravel Sanctum)
- **Flow:** One-time invitation → Registration → Token issuance → Store in localStorage
- **Token Storage:** Client-side (localStorage/sessionStorage)
- **Token Expiry:** 30 days (configurable)
- **Refresh:** Auto-refresh on activity

### Base URL Pattern
```
Production: https://api.askproai.de/api/v1/customer-portal
Staging:    https://staging-api.askproai.de/api/v1/customer-portal
Local:      http://localhost:8000/api/v1/customer-portal
```

---

### Endpoint 1: Validate Invitation Token

**Method:** `GET /invitations/{token}/validate`
**Auth:** None (public endpoint)
**Rate Limit:** 10 req/min per IP

#### Request
```http
GET /api/v1/customer-portal/invitations/a1b2c3d4.../validate
```

#### Success Response (200 OK)
```json
{
  "success": true,
  "data": {
    "token": "a1b2c3d4e5f6...",
    "email": "kunde@example.com",
    "company_name": "Salon Mitte",
    "role": "company_staff",
    "expires_at": "2025-11-27T15:30:00+01:00",
    "remaining_hours": 48,
    "is_valid": true
  },
  "message": "Invitation is valid"
}
```

#### Error Responses
```json
// 404 - Token not found
{
  "success": false,
  "error": "Invitation not found",
  "code": "INVITATION_NOT_FOUND"
}

// 422 - Token expired
{
  "success": false,
  "error": "This invitation has expired. Please request a new one.",
  "code": "INVITATION_EXPIRED",
  "data": {
    "expired_at": "2025-11-20T15:30:00+01:00",
    "contact_email": "info@salon-mitte.de"
  }
}

// 422 - Token already used
{
  "success": false,
  "error": "This invitation has already been used",
  "code": "INVITATION_ALREADY_ACCEPTED",
  "data": {
    "accepted_at": "2025-11-22T10:15:00+01:00"
  }
}
```

#### Business Rules
- ✅ Token must exist in database
- ✅ Token must not be expired (< 72 hours)
- ✅ Token must not be already accepted (accepted_at IS NULL)
- ✅ Token must not be soft-deleted
- ⚠️ **Security:** Do NOT expose internal user IDs in response

---

### Endpoint 2: Accept Invitation & Register

**Method:** `POST /invitations/{token}/accept`
**Auth:** None (public endpoint, requires valid token)
**Rate Limit:** 5 req/min per IP

#### Request
```http
POST /api/v1/customer-portal/invitations/a1b2c3d4.../accept
Content-Type: application/json

{
  "name": "Hans Müller",
  "email": "kunde@example.com",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!",
  "phone": "+49 151 12345678",
  "terms_accepted": true
}
```

#### Validation Rules
```php
[
    'name' => ['required', 'string', 'max:255', 'min:2'],
    'email' => ['required', 'email:rfc,dns', 'max:255'],
    'password' => [
        'required',
        'string',
        'min:8',
        'confirmed',
        'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
        // At least 1 lowercase, 1 uppercase, 1 digit, 1 special char
    ],
    'phone' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9\s\-()]+$/'],
    'terms_accepted' => ['required', 'accepted'],
]
```

#### Success Response (201 Created)
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 42,
      "name": "Hans Müller",
      "email": "kunde@example.com",
      "phone": "+49 151 12345678",
      "company_id": 5,
      "company_name": "Salon Mitte",
      "role": "company_staff",
      "created_at": "2025-11-24T16:30:00+01:00"
    },
    "token": {
      "access_token": "3|X8jK2pLm...",
      "token_type": "Bearer",
      "expires_at": "2025-12-24T16:30:00+01:00"
    }
  },
  "message": "Account successfully created. Welcome to Salon Mitte!"
}
```

#### Error Responses
```json
// 422 - Validation failed
{
  "success": false,
  "error": "Validation failed",
  "code": "VALIDATION_ERROR",
  "errors": {
    "email": ["Email does not match invitation"],
    "password": ["Password must contain at least one uppercase letter"]
  }
}

// 422 - Email mismatch
{
  "success": false,
  "error": "Email does not match invitation",
  "code": "EMAIL_MISMATCH",
  "data": {
    "invited_email": "kunde@example.com",
    "provided_email": "wrong@example.com"
  }
}

// 500 - User creation failed
{
  "success": false,
  "error": "Failed to create user account. Please try again.",
  "code": "USER_CREATION_FAILED"
}
```

#### Business Rules
- ✅ Email must exactly match invitation email (case-insensitive)
- ✅ Password strength: min 8 chars, mixed case, digit, special char
- ✅ User must accept terms & conditions
- ✅ Auto-verify email (invitation validates ownership)
- ✅ Assign role from invitation
- ✅ Mark invitation as accepted (accepted_at = now())
- ✅ Create Sanctum token for immediate login
- ✅ Trigger welcome email (async job)
- ✅ Audit log: "invitation_accepted"

#### Side Effects
1. Create User record (company_id, email_verified_at set)
2. Assign role from invitation
3. Mark invitation as accepted
4. Generate Sanctum token
5. Queue welcome email (non-blocking)
6. Create activity log entry

---

### Endpoint 3: List User Appointments

**Method:** `GET /appointments`
**Auth:** Bearer token (Sanctum)
**Rate Limit:** 60 req/min per user

#### Request
```http
GET /api/v1/customer-portal/appointments?status=upcoming&per_page=25
Authorization: Bearer 3|X8jK2pLm...
```

#### Query Parameters
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `status` | enum | `upcoming` | Filter: `upcoming`, `past`, `cancelled`, `all` |
| `from` | date | - | Start date filter (YYYY-MM-DD) |
| `to` | date | - | End date filter (YYYY-MM-DD) |
| `per_page` | int | 25 | Pagination: 10, 25, 50 |
| `page` | int | 1 | Page number |

#### Success Response (200 OK)
```json
{
  "success": true,
  "data": {
    "appointments": [
      {
        "id": 762,
        "start_time": "2025-11-25T10:00:00+01:00",
        "end_time": "2025-11-25T11:00:00+01:00",
        "duration_minutes": 60,
        "status": "confirmed",
        "service": {
          "id": 12,
          "name": "Herrenhaarschnitt",
          "duration": 60,
          "price": "25.00",
          "currency": "EUR"
        },
        "staff": {
          "id": 5,
          "name": "Fabian Spitzer",
          "avatar_url": null
        },
        "location": {
          "branch_name": "Salon Mitte",
          "address": "Hauptstraße 1, 10115 Berlin",
          "phone": "+49 30 12345678"
        },
        "permissions": {
          "can_reschedule": true,
          "can_cancel": true,
          "reschedule_deadline": "2025-11-24T10:00:00+01:00",
          "cancel_deadline": "2025-11-24T10:00:00+01:00"
        },
        "metadata": {
          "created_via": "phone_booking",
          "reschedule_count": 0,
          "is_composite": false
        }
      }
    ],
    "pagination": {
      "total": 42,
      "per_page": 25,
      "current_page": 1,
      "last_page": 2,
      "from": 1,
      "to": 25
    }
  }
}
```

#### Business Rules
- ✅ User can only see appointments where they are customer OR staff
- ✅ Multi-tenant isolation: company_id scope automatic
- ✅ Status filter logic:
  - `upcoming`: start_time >= now() AND status != 'cancelled'
  - `past`: start_time < now()
  - `cancelled`: status = 'cancelled'
  - `all`: no status filter
- ✅ Permissions calculated based on PolicyConfiguration
- ✅ Sort: upcoming ASC by start_time, past DESC by start_time

---

### Endpoint 4: Get Appointment Detail

**Method:** `GET /appointments/{id}`
**Auth:** Bearer token (Sanctum)
**Rate Limit:** 60 req/min per user

#### Request
```http
GET /api/v1/customer-portal/appointments/762
Authorization: Bearer 3|X8jK2pLm...
```

#### Success Response (200 OK)
```json
{
  "success": true,
  "data": {
    "appointment": {
      "id": 762,
      "start_time": "2025-11-25T10:00:00+01:00",
      "end_time": "2025-11-25T11:00:00+01:00",
      "duration_minutes": 60,
      "status": "confirmed",
      "version": 1,
      "service": {
        "id": 12,
        "name": "Herrenhaarschnitt",
        "description": "Klassischer Herrenhaarschnitt mit Waschen",
        "duration": 60,
        "price": "25.00",
        "currency": "EUR"
      },
      "staff": {
        "id": 5,
        "name": "Fabian Spitzer",
        "bio": "10 Jahre Erfahrung",
        "avatar_url": null
      },
      "location": {
        "branch_name": "Salon Mitte",
        "address": "Hauptstraße 1, 10115 Berlin",
        "phone": "+49 30 12345678",
        "coordinates": {
          "lat": 52.52,
          "lng": 13.405
        }
      },
      "permissions": {
        "can_reschedule": true,
        "can_cancel": true,
        "reschedule_deadline": "2025-11-24T10:00:00+01:00",
        "cancel_deadline": "2025-11-24T10:00:00+01:00",
        "min_notice_hours": 24
      },
      "history": [
        {
          "action": "created",
          "timestamp": "2025-11-20T14:30:00+01:00",
          "user": "System (Phone Booking)"
        }
      ],
      "composite_info": null
    },
    "metadata": {
      "reschedule_count": 0,
      "cancellation_count": 0,
      "last_modified": "2025-11-20T14:30:00+01:00"
    }
  }
}
```

#### Error Responses
```json
// 404 - Appointment not found
{
  "success": false,
  "error": "Appointment not found",
  "code": "APPOINTMENT_NOT_FOUND"
}

// 403 - Unauthorized access
{
  "success": false,
  "error": "You do not have permission to view this appointment",
  "code": "UNAUTHORIZED"
}
```

#### Business Rules
- ✅ User must own appointment (customer_id match OR staff_id match)
- ✅ Multi-tenant isolation enforced
- ✅ Include last 10 audit log entries
- ✅ Calculate permissions based on PolicyConfiguration
- ✅ Show composite service phases if applicable

---

### Endpoint 5: Get Available Slots (Reschedule)

**Method:** `GET /appointments/{id}/available-slots`
**Auth:** Bearer token (Sanctum)
**Rate Limit:** 30 req/min per user

#### Request
```http
GET /api/v1/customer-portal/appointments/762/available-slots?date=2025-11-26&days=7
Authorization: Bearer 3|X8jK2pLm...
```

#### Query Parameters
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `date` | date | today | Start date (YYYY-MM-DD) |
| `days` | int | 7 | Number of days to check (1-14) |
| `prefer_same_day` | bool | true | Prioritize same-day alternatives |

#### Success Response (200 OK)
```json
{
  "success": true,
  "data": {
    "original_appointment": {
      "id": 762,
      "start_time": "2025-11-25T10:00:00+01:00",
      "service_name": "Herrenhaarschnitt",
      "duration": 60
    },
    "alternatives": {
      "same_day": [
        {
          "start_time": "2025-11-25T09:00:00+01:00",
          "end_time": "2025-11-25T10:00:00+01:00",
          "staff": {
            "id": 5,
            "name": "Fabian Spitzer"
          },
          "available": true,
          "recommended": true,
          "reason": "Same staff, earlier time"
        },
        {
          "start_time": "2025-11-25T14:30:00+01:00",
          "end_time": "2025-11-25T15:30:00+01:00",
          "staff": {
            "id": 5,
            "name": "Fabian Spitzer"
          },
          "available": true,
          "recommended": false
        }
      ],
      "next_days": [
        {
          "date": "2025-11-26",
          "slots": [
            {
              "start_time": "2025-11-26T10:00:00+01:00",
              "end_time": "2025-11-26T11:00:00+01:00",
              "staff": {
                "id": 5,
                "name": "Fabian Spitzer"
              },
              "available": true
            }
          ]
        }
      ]
    },
    "metadata": {
      "total_slots": 15,
      "same_day_slots": 2,
      "search_range": {
        "from": "2025-11-25",
        "to": "2025-12-01"
      }
    }
  }
}
```

#### Business Rules
- ✅ User must own appointment (authorization check)
- ✅ Only show slots for same service duration
- ✅ Prefer same staff (show first)
- ✅ Filter business hours only
- ✅ Exclude already booked slots
- ✅ Highlight same-day alternatives
- ✅ Use Cal.com availability API

---

### Endpoint 6: Reschedule Appointment

**Method:** `PUT /appointments/{id}/reschedule`
**Auth:** Bearer token (Sanctum)
**Rate Limit:** 10 req/min per user

#### Request
```http
PUT /api/v1/customer-portal/appointments/762/reschedule
Authorization: Bearer 3|X8jK2pLm...
Content-Type: application/json

{
  "new_start_time": "2025-11-26T10:00:00+01:00",
  "reason": "Terminkonflikt",
  "version": 1
}
```

#### Validation Rules
```php
[
    'new_start_time' => [
        'required',
        'date',
        'after:now',
        'date_format:Y-m-d\TH:i:sP',
    ],
    'reason' => ['nullable', 'string', 'max:500'],
    'version' => ['required', 'integer', 'min:1'],
]
```

#### Success Response (200 OK)
```json
{
  "success": true,
  "data": {
    "appointment": {
      "id": 762,
      "start_time": "2025-11-26T10:00:00+01:00",
      "end_time": "2025-11-26T11:00:00+01:00",
      "version": 2,
      "status": "confirmed"
    },
    "calcom_sync": {
      "status": "success",
      "booking_uid": "abc123def456",
      "synced_at": "2025-11-24T16:45:00+01:00"
    },
    "audit": {
      "action": "rescheduled",
      "old_time": "2025-11-25T10:00:00+01:00",
      "new_time": "2025-11-26T10:00:00+01:00",
      "reason": "Terminkonflikt"
    }
  },
  "message": "Appointment successfully rescheduled. Confirmation email sent."
}
```

#### Error Responses
```json
// 409 - Optimistic lock conflict
{
  "success": false,
  "error": "This appointment was modified by another user. Please refresh and try again.",
  "code": "OPTIMISTIC_LOCK_CONFLICT",
  "data": {
    "current_version": 2,
    "provided_version": 1
  }
}

// 422 - Slot no longer available
{
  "success": false,
  "error": "The selected time slot is no longer available",
  "code": "SLOT_UNAVAILABLE"
}

// 422 - Outside minimum notice period
{
  "success": false,
  "error": "Reschedule must be at least 24 hours before appointment",
  "code": "MIN_NOTICE_VIOLATION",
  "data": {
    "min_notice_hours": 24,
    "appointment_time": "2025-11-25T10:00:00+01:00"
  }
}

// 503 - Cal.com sync failed
{
  "success": false,
  "error": "Appointment rescheduled locally, but Cal.com sync failed. Our team will resolve this shortly.",
  "code": "CALCOM_SYNC_FAILED",
  "data": {
    "appointment_id": 762,
    "retry_scheduled": true
  }
}
```

#### Business Rules
- ✅ User must own appointment (authorization)
- ✅ Appointment cannot be in past
- ✅ Appointment cannot be already cancelled
- ✅ Must respect minimum notice period (PolicyConfiguration)
- ✅ Optimistic locking: version must match
- ✅ Check Cal.com availability before committing
- ✅ Sync to Cal.com (or queue if circuit breaker open)
- ✅ Create audit log entry
- ✅ Send confirmation email (async)
- ✅ Increment version number

#### Side Effects
1. Update appointment (start_time, version++)
2. Create audit log: "rescheduled"
3. Sync to Cal.com (via SyncAppointmentToCalcomJob)
4. Send confirmation email (async)
5. Invalidate availability cache

---

### Endpoint 7: Cancel Appointment

**Method:** `DELETE /appointments/{id}`
**Auth:** Bearer token (Sanctum)
**Rate Limit:** 10 req/min per user

#### Request
```http
DELETE /api/v1/customer-portal/appointments/762
Authorization: Bearer 3|X8jK2pLm...
Content-Type: application/json

{
  "reason": "Krankheit",
  "version": 1,
  "notify_preference": "email"
}
```

#### Validation Rules
```php
[
    'reason' => ['nullable', 'string', 'max:500'],
    'version' => ['required', 'integer', 'min:1'],
    'notify_preference' => ['nullable', 'in:email,sms,none'],
]
```

#### Success Response (200 OK)
```json
{
  "success": true,
  "data": {
    "appointment": {
      "id": 762,
      "status": "cancelled",
      "cancelled_at": "2025-11-24T16:50:00+01:00",
      "version": 2
    },
    "cancellation_policy": {
      "fee_applied": false,
      "fee_amount": null,
      "reason": "Cancelled within free cancellation period"
    },
    "calcom_sync": {
      "status": "success",
      "cancelled_at": "2025-11-24T16:50:15+01:00"
    }
  },
  "message": "Appointment cancelled successfully. Cancellation confirmation sent."
}
```

#### Error Responses
```json
// 409 - Optimistic lock conflict
{
  "success": false,
  "error": "This appointment was modified by another user. Please refresh and try again.",
  "code": "OPTIMISTIC_LOCK_CONFLICT"
}

// 422 - Already cancelled
{
  "success": false,
  "error": "This appointment is already cancelled",
  "code": "ALREADY_CANCELLED"
}

// 422 - Past deadline
{
  "success": false,
  "error": "Cancellation is no longer possible for this appointment",
  "code": "CANCELLATION_DEADLINE_PASSED",
  "data": {
    "deadline": "2025-11-24T10:00:00+01:00",
    "now": "2025-11-24T16:50:00+01:00",
    "contact_phone": "+49 30 12345678"
  }
}
```

#### Business Rules
- ✅ User must own appointment
- ✅ Appointment cannot already be cancelled
- ✅ Respect cancellation deadline (PolicyConfiguration)
- ✅ Optimistic locking enforcement
- ✅ Calculate cancellation fee if applicable
- ✅ Sync to Cal.com
- ✅ Release time slot (delete reservation)
- ✅ Create audit log
- ✅ Send cancellation confirmation email

#### Side Effects
1. Update appointment (status = 'cancelled', cancelled_at = now())
2. Create audit log: "cancelled"
3. Sync to Cal.com (cancel booking)
4. Send cancellation email (async)
5. Delete appointment reservation (release slot)
6. Invalidate availability cache

---

## 🎨 Frontend Specifications (Phase 7)

### Tech Stack Decision
**Selected:** Blade + Alpine.js + Tailwind CSS

**Rationale:**
- ✅ Laravel native (no build complexity)
- ✅ Fast development (2-3 days vs 4-5 for Inertia)
- ✅ SEO friendly (server-side rendering)
- ✅ Minimal JS payload (~50KB vs 200KB for Vue)
- ✅ Progressive enhancement (works without JS)

**Trade-offs:**
- ❌ Less interactive than SPA
- ❌ Full page reloads (mitigated with Turbo/Livewire if needed later)

---

### Page 1: Invitation Landing & Registration

**URL:** `https://portal.askproai.de/einladung/{token}`
**Auth:** None (public)
**Layout:** Minimal (no navigation)

#### User Flow
1. User clicks email link → Lands on invitation page
2. Client-side JS validates token (API call)
3. If valid → Show registration form
4. User fills: name, email (pre-filled), password, phone, terms
5. Submit → API call → Success = store token → Redirect to `/meine-termine`

#### UI Components
```html
<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
  <div class="container mx-auto px-4 py-16">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-xl p-8">

      <!-- Header -->
      <div class="text-center mb-8">
        <img src="{{ $company->logo_url }}" class="h-16 mx-auto mb-4">
        <h1 class="text-2xl font-bold text-gray-900">
          Willkommen bei {{ $company->name }}
        </h1>
        <p class="text-gray-600 mt-2">
          Erstellen Sie Ihr Konto, um Ihre Termine zu verwalten
        </p>
      </div>

      <!-- Token Validation (Alpine.js) -->
      <div x-data="invitationForm()" x-init="validateToken()">

        <!-- Loading State -->
        <div x-show="loading" class="text-center py-8">
          <svg class="animate-spin h-10 w-10 mx-auto text-blue-600">...</svg>
          <p class="mt-4 text-gray-600">Einladung wird überprüft...</p>
        </div>

        <!-- Error State -->
        <div x-show="error" class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-red-400">...</svg>
            </div>
            <div class="ml-3">
              <p class="text-sm text-red-700" x-text="errorMessage"></p>
            </div>
          </div>
        </div>

        <!-- Registration Form -->
        <form x-show="!loading && !error" @submit.prevent="submitForm()">

          <!-- Name Field -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Vollständiger Name *
            </label>
            <input
              type="text"
              x-model="form.name"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              required
            >
            <p x-show="errors.name" class="mt-1 text-sm text-red-600" x-text="errors.name"></p>
          </div>

          <!-- Email Field (Pre-filled, Read-only) -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              E-Mail-Adresse *
            </label>
            <input
              type="email"
              x-model="form.email"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50"
              readonly
            >
          </div>

          <!-- Password Field -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Passwort *
            </label>
            <div class="relative">
              <input
                :type="showPassword ? 'text' : 'password'"
                x-model="form.password"
                @input="checkPasswordStrength()"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                required
              >
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute right-3 top-2.5"
              >
                <svg>...</svg>
              </button>
            </div>
            <!-- Password Strength Indicator -->
            <div class="mt-2 h-1 bg-gray-200 rounded">
              <div
                :class="passwordStrengthClass"
                :style="`width: ${passwordStrength}%`"
                class="h-full rounded transition-all"
              ></div>
            </div>
            <p class="mt-1 text-xs text-gray-600">
              Mind. 8 Zeichen, Groß-/Kleinbuchstaben, Zahl, Sonderzeichen
            </p>
          </div>

          <!-- Password Confirmation -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Passwort wiederholen *
            </label>
            <input
              type="password"
              x-model="form.password_confirmation"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg"
              required
            >
          </div>

          <!-- Phone Field (Optional) -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Telefonnummer (optional)
            </label>
            <input
              type="tel"
              x-model="form.phone"
              placeholder="+49 151 12345678"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg"
            >
          </div>

          <!-- Terms & Conditions -->
          <div class="mb-6">
            <label class="flex items-start">
              <input
                type="checkbox"
                x-model="form.terms_accepted"
                class="mt-1 h-4 w-4 text-blue-600 border-gray-300 rounded"
                required
              >
              <span class="ml-2 text-sm text-gray-700">
                Ich akzeptiere die
                <a href="/terms" target="_blank" class="text-blue-600 hover:underline">
                  Nutzungsbedingungen
                </a> und
                <a href="/privacy" target="_blank" class="text-blue-600 hover:underline">
                  Datenschutzerklärung
                </a>
              </span>
            </label>
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            :disabled="submitting"
            class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 disabled:bg-gray-400"
          >
            <span x-show="!submitting">Konto erstellen</span>
            <span x-show="submitting">Wird erstellt...</span>
          </button>

        </form>

      </div>

    </div>
  </div>
</div>
```

#### Alpine.js Component
```javascript
function invitationForm() {
  return {
    loading: true,
    error: false,
    errorMessage: '',
    submitting: false,
    showPassword: false,
    passwordStrength: 0,
    passwordStrengthClass: '',
    form: {
      name: '',
      email: '',
      password: '',
      password_confirmation: '',
      phone: '',
      terms_accepted: false
    },
    errors: {},

    async validateToken() {
      const token = window.location.pathname.split('/').pop();

      try {
        const response = await fetch(`/api/v1/customer-portal/invitations/${token}/validate`);
        const data = await response.json();

        if (data.success) {
          this.form.email = data.data.email;
          this.loading = false;
        } else {
          this.error = true;
          this.errorMessage = data.error;
        }
      } catch (error) {
        this.error = true;
        this.errorMessage = 'Verbindungsfehler. Bitte versuchen Sie es später erneut.';
      }
    },

    checkPasswordStrength() {
      const password = this.form.password;
      let strength = 0;

      if (password.length >= 8) strength += 25;
      if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 25;
      if (/\d/.test(password)) strength += 25;
      if (/[@$!%*?&]/.test(password)) strength += 25;

      this.passwordStrength = strength;

      if (strength < 50) this.passwordStrengthClass = 'bg-red-500';
      else if (strength < 75) this.passwordStrengthClass = 'bg-yellow-500';
      else this.passwordStrengthClass = 'bg-green-500';
    },

    async submitForm() {
      this.submitting = true;
      this.errors = {};
      const token = window.location.pathname.split('/').pop();

      try {
        const response = await fetch(`/api/v1/customer-portal/invitations/${token}/accept`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify(this.form)
        });

        const data = await response.json();

        if (data.success) {
          // Store token in localStorage
          localStorage.setItem('portal_token', data.data.token.access_token);
          localStorage.setItem('portal_user', JSON.stringify(data.data.user));

          // Redirect to appointments
          window.location.href = '/portal/meine-termine';
        } else {
          this.errors = data.errors || {};
          alert(data.error);
        }
      } catch (error) {
        alert('Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.');
      } finally {
        this.submitting = false;
      }
    }
  }
}
```

#### Validation Rules (Client-side)
- Name: min 2 chars, max 255 chars
- Email: valid format, matches invitation
- Password: min 8 chars, mixed case, digit, special char
- Terms: must be checked

---

### Page 2: My Appointments (List)

**URL:** `https://portal.askproai.de/meine-termine`
**Auth:** Required (Sanctum token)
**Layout:** Full (navigation, footer)

#### UI Structure
```
┌────────────────────────────────────┐
│  Header (Logo, User Menu, Logout)  │
├────────────────────────────────────┤
│  Tabs: Anstehend | Vergangene |   │
│        Storniert                    │
├────────────────────────────────────┤
│  ┌──────────────────────────────┐  │
│  │  Appointment Card            │  │
│  │  - Date, Time, Duration      │  │
│  │  - Service Name              │  │
│  │  - Staff Name + Avatar       │  │
│  │  - Location                  │  │
│  │  - Actions: Details, Reschedule, Cancel │
│  └──────────────────────────────┘  │
│  [... more cards ...]              │
├────────────────────────────────────┤
│  Pagination                         │
└────────────────────────────────────┘
```

#### Mobile-Responsive Breakpoints
- **Mobile (<640px):** Single column, stack actions vertically
- **Tablet (640-1024px):** 2 columns
- **Desktop (>1024px):** 3 columns

---

### Page 3: Reschedule Appointment

**URL:** `https://portal.askproai.de/termin/{id}/umbuchen`
**Auth:** Required
**Layout:** Full

#### Flow
1. Show current appointment (greyed out)
2. Load available slots (loading spinner)
3. Show calendar with alternatives:
   - Same day (highlighted in green)
   - Next 7 days (grouped by date)
4. User selects slot → Confirmation modal
5. User confirms → API call → Success/Error message
6. Success → Redirect to appointment detail

#### UI Components
- Calendar grid (7 days)
- Time slot picker (15-min intervals)
- Staff filter (if multiple available)
- Loading skeleton
- Confirmation modal
- Error toast

---

### Page 4: Cancel Appointment

**URL:** `https://portal.askproai.de/termin/{id}/stornieren`
**Auth:** Required
**Layout:** Full

#### Flow
1. Show appointment details
2. Show cancellation policy warning (if fee applies)
3. Textarea for optional reason (pre-filled suggestions)
4. Checkbox: "Notify when slot becomes available"
5. Confirm button → Modal confirmation
6. Submit → API call → Success/Error
7. Success → Redirect to appointments list

---

## 🔐 Security Requirements

### Authentication & Authorization
- ✅ **Sanctum Token:** 30-day expiry, auto-refresh on activity
- ✅ **Multi-Tenant Isolation:** company_id scope on ALL queries
- ✅ **Authorization Policies:** AppointmentPolicy enforces ownership
- ✅ **Rate Limiting:** 60 req/min per user, 10 req/min per IP (public endpoints)
- ✅ **CSRF Protection:** Sanctum handles automatically
- ✅ **XSS Prevention:** Blade escaping ({{ }} not {!! !!})
- ✅ **SQL Injection:** Eloquent ORM (parameterized queries)

### Critical Vulnerabilities Prevented
1. **VULN-PORTAL-001:** Panel access control bypass → Fixed via `canAccessCustomerPortal()` gate
2. **VULN-PORTAL-002:** Missing authorization policy → Fixed via AppointmentPolicy
3. **VULN-009:** Mass assignment → Fixed via `$guarded` on models
4. **Privilege Escalation:** Cannot invite higher role → Fixed in UserManagementService

### Audit Trail Requirements
- ✅ Log ALL appointment modifications (create, reschedule, cancel)
- ✅ Store: user_id, action, old_values, new_values, IP, user_agent, reason
- ✅ Immutable logs (no updated_at column)
- ✅ Retention: 7 years (legal requirement)

### GDPR Compliance
- ✅ Data minimization (only collect necessary fields)
- ✅ Right to access (export appointments)
- ✅ Right to deletion (soft delete with anonymization)
- ✅ Audit trail for all access
- ✅ Privacy consent tracking (privacy_consent_at)

---

## ⚡ Performance Requirements

### API Response Times (p95)
- Token validation: < 200ms
- List appointments: < 500ms
- Reschedule: < 1000ms (includes Cal.com sync)
- Cancel: < 1000ms (includes Cal.com sync)

### Frontend Load Times (p95)
- Initial page load: < 2s
- Interactive (TTI): < 3s
- Lighthouse score: > 90

### Optimization Strategies
1. **Database:** Indexes on (company_id, start_time, status)
2. **Caching:** Redis for availability (5-min TTL)
3. **N+1 Prevention:** Eager load (with(['service', 'staff']))
4. **Pagination:** 25 items per page default
5. **Asset Optimization:** Tailwind CSS purge, Alpine.js CDN

---

## 🧪 Testing Requirements

### API Tests (PHPUnit/Pest)
- ✅ Invitation validation (valid, expired, used, not found)
- ✅ Registration (success, validation errors, email mismatch)
- ✅ List appointments (filtering, pagination, authorization)
- ✅ Reschedule (success, optimistic lock, slot unavailable)
- ✅ Cancel (success, deadline violation, already cancelled)
- ✅ Multi-tenant isolation (cannot see other company data)

### E2E Tests (Playwright)
- ✅ Happy path: Accept invitation → Register → View appointments
- ✅ Happy path: Reschedule appointment
- ✅ Happy path: Cancel appointment
- ✅ Edge case: Expired invitation
- ✅ Edge case: Optimistic lock conflict
- ✅ Edge case: Cal.com sync failure

### Manual Testing Checklist
- [ ] Mobile responsive (iOS Safari, Android Chrome)
- [ ] Browser compatibility (Chrome, Firefox, Safari, Edge)
- [ ] Keyboard navigation (accessibility)
- [ ] Screen reader (NVDA/JAWS)
- [ ] German translations (no English fallback)
- [ ] Email delivery (invitation, confirmation, cancellation)

---

## 📋 Acceptance Criteria

### Phase 6: API Layer
- [ ] All 7 endpoints implemented
- [ ] Request validation working
- [ ] API Resources transforming data correctly
- [ ] Authorization middleware enforcing policies
- [ ] Multi-tenant isolation verified
- [ ] API tests passing (100%)
- [ ] Postman collection created

### Phase 7: Frontend
- [ ] All 4 pages implemented (invitation, list, reschedule, cancel)
- [ ] Mobile-responsive (tested on 3+ devices)
- [ ] Browser compatibility (4 browsers)
- [ ] Loading states for all async operations
- [ ] Error handling for all failure scenarios
- [ ] Accessibility (keyboard nav, ARIA labels)
- [ ] German translations complete (0 English strings)

### Phase 8: Integration
- [ ] Filament admin panel (UserInvitationResource)
- [ ] Email templates designed (3 templates)
- [ ] E2E tests passing (5 scenarios)
- [ ] Manual testing checklist completed
- [ ] Staging environment deployed
- [ ] Performance benchmarks met (< 500ms API, < 2s page load)
- [ ] Security audit passed (no critical vulnerabilities)

---

## 🚨 Known Gaps & Ambiguities

### CRITICAL: Missing Specifications

#### 1. Customer vs User Model Ambiguity
**Issue:** Implementation plan assumes User model, but existing code has separate Customer model.

**Questions:**
- Q1: Are customers separate from users? (Current: Yes, Customer has portal_access_token field)
- Q2: Should invitation create User OR Customer? (Recommendation: User, link to Customer via customer_id)
- Q3: How do existing customers (from phone bookings) get portal access? (Migration needed?)

**Recommendation:**
```
Decision: User model for authentication, Customer model for appointment history
Flow: Invitation → User (with customer_id) → Customer (existing or new)
Migration: Add "Link to Portal" button in Filament for existing customers
```

#### 2. Cal.com Sync Strategy Not Defined
**Issue:** What happens if Cal.com API is down during reschedule/cancel?

**Questions:**
- Q1: Should operation fail or succeed locally? (Recommendation: Succeed locally, queue sync)
- Q2: Retry mechanism? (Recommendation: Exponential backoff, 3 retries max)
- Q3: User notification if sync fails? (Recommendation: Show warning, staff notified)

**Recommendation:**
```
Strategy: Optimistic with circuit breaker
1. Check Cal.com availability (circuit breaker)
2. If open: Warn user, proceed locally, queue sync
3. If closed: Sync immediately
4. On failure: Log error, queue retry job, notify staff
```

#### 3. Email Service Provider Not Specified
**Issue:** Email templates exist, but provider (SMTP, SendGrid, Mailgun) not specified.

**Questions:**
- Q1: Which email provider? (Check .env)
- Q2: Transactional vs marketing emails? (Recommendation: Transactional only for MVP)
- Q3: Email deliverability testing? (Recommendation: Test with Gmail, Outlook, ProtonMail)

#### 4. Frontend Asset Build Process
**Issue:** Tailwind CSS requires build process, but implementation plan says "no build complexity".

**Questions:**
- Q1: Use Tailwind CDN (larger payload) or build (complexity)? (Recommendation: Build with Vite)
- Q2: PurgeCSS configuration? (Recommendation: Yes, production only)

**Recommendation:**
```bash
# Use Laravel Mix / Vite for asset compilation
npm install && npm run build
```

#### 5. Mobile App vs Mobile Web
**Issue:** Implementation plan says "mobile-responsive" but not clear if native app planned.

**Clarification:** MVP is mobile-responsive web only. Native app (iOS/Android) is P3 (future).

---

## 🎯 Recommendations

### Priority Adjustments
1. **Add to P0:** Customer-User linking mechanism (migration for existing customers)
2. **Add to P1:** Cal.com circuit breaker UI feedback (user-facing)
3. **Move to P2:** Alternative slot recommendations (complex logic, can be added post-MVP)

### Risk Mitigation
1. **Risk:** Cal.com API rate limits during high traffic
   - **Mitigation:** Implement Redis caching for availability (5-min TTL)

2. **Risk:** Optimistic locking causing user frustration
   - **Mitigation:** Show clear conflict resolution UI with "Refresh" button

3. **Risk:** Email deliverability issues
   - **Mitigation:** Implement retry queue with exponential backoff (InvitationEmailQueue)

### Technical Debt Prevention
1. Extract Cal.com sync logic to dedicated service (CalcomSyncService)
2. Add feature flags for gradual rollout (is_pilot column exists)
3. Implement comprehensive logging (Laravel Log channels)

---

## 📝 Next Steps

### Immediate Actions
1. **Clarify Customer vs User model** (decision needed before Phase 6)
2. **Define Cal.com failure handling** (sync strategy document)
3. **Set up email provider** (configure .env, test deliverability)
4. **Create Postman collection** (for API testing)

### Agent Delegation Plan
1. **Requirements Analyst (this document):** ✅ Complete
2. **Backend Architect:** Implement Phase 6 (API Layer)
3. **Frontend Architect:** Implement Phase 7 (Views + Components)
4. **Quality Engineer:** Implement Phase 8 (Tests + Validation)
5. **DevOps Architect:** Deployment + Feature flags

---

## 📊 Complexity Estimation

### Development Time
- Phase 6 (API Layer): 6 hours (realistic)
- Phase 7 (Frontend): 10 hours (realistic)
- Phase 8 (Testing): 8 hours (realistic)
- **Total:** 24 hours (3 days with 1 developer)

### Risk Assessment
- **Technical Risk:** Medium (Cal.com integration, optimistic locking)
- **Scope Risk:** Low (clear boundaries, backend complete)
- **Security Risk:** Low (comprehensive security tests exist)

### Confidence Level
- **Phase 6:** 95% (backend infrastructure solid)
- **Phase 7:** 85% (Blade + Alpine.js mature stack)
- **Phase 8:** 90% (test patterns established)

---

**Document Status:** ✅ Ready for Implementation
**Blockers:** Customer vs User model decision (critical)
**Approved By:** Pending stakeholder review
**Next Review:** After Phase 6 completion
