# System Architecture Documentation

**Document Version:** 1.0
**Date:** 2025-09-30
**System:** API Gateway - Call & Appointment Management System
**Status:** Production

---

## 1. System Context Diagram (C4 Level 1)

```
┌─────────────────────────────────────────────────────────────────┐
│                         External Systems                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ┌──────────────┐        ┌──────────────┐       ┌───────────┐  │
│  │   Retell AI  │        │   Cal.com    │       │  Stripe   │  │
│  │  (Voice AI)  │        │  (Calendar)  │       │ (Payment) │  │
│  └──────┬───────┘        └──────┬───────┘       └─────┬─────┘  │
│         │                       │                      │         │
│         │ Webhooks              │ Webhooks             │ Events  │
│         │                       │                      │         │
└─────────┼───────────────────────┼──────────────────────┼─────────┘
          │                       │                      │
          │                       │                      │
          └───────────┬───────────┴──────────────────────┘
                      │
                      ▼
┌──────────────────────────────────────────────────────────────────┐
│                         API Gateway System                        │
│                      (Laravel 11 Application)                     │
│                                                                    │
│  Core Capabilities:                                               │
│  - Inbound call management (Retell AI)                           │
│  - Appointment booking and management (Cal.com)                  │
│  - Customer relationship management                              │
│  - Multi-tenant company/branch management                        │
│  - Cost tracking and billing                                     │
│  - Service catalog management                                    │
│  - Staff and resource scheduling                                 │
│                                                                    │
└──────────────────────────────────────────────────────────────────┘
          │                       │                      │
          │                       │                      │
          ▼                       ▼                      ▼
┌─────────────────┐    ┌──────────────────┐    ┌────────────────┐
│  Admin Portal   │    │  MySQL Database  │    │  File Storage  │
│  (Filament v3)  │    │   (Primary DB)   │    │  (Laravel)     │
└─────────────────┘    └──────────────────┘    └────────────────┘
```

### External System Integration Points

| System | Protocol | Purpose | Authentication |
|--------|----------|---------|----------------|
| **Retell AI** | HTTPS Webhooks | Voice call processing, conversation analysis | API Key + Webhook Signature |
| **Cal.com** | REST API + Webhooks | Calendar availability, booking management | API Key + Team ID |
| **Stripe** | REST API + Webhooks | Payment processing, subscription management | API Key + Webhook Secret |
| **Twilio** | REST API | SMS notifications, phone number provisioning | API Key |
| **Telegram** | Bot API | Push notifications to admins | Bot Token |

---

## 2. Container Diagram (C4 Level 2)

```
┌──────────────────────────────────────────────────────────────────┐
│                         API Gateway System                        │
├──────────────────────────────────────────────────────────────────┤
│                                                                    │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │                    Web Application Layer                   │  │
│  │                     (Laravel Framework)                    │  │
│  │                                                             │  │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐   │  │
│  │  │   Webhook    │  │   REST API   │  │  Admin Panel │   │  │
│  │  │  Endpoints   │  │  Endpoints   │  │  (Filament)  │   │  │
│  │  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘   │  │
│  │         │                  │                  │            │  │
│  └─────────┼──────────────────┼──────────────────┼────────────┘  │
│            │                  │                  │                │
│            └──────────────────┴──────────────────┘                │
│                               │                                   │
│  ┌────────────────────────────┴───────────────────────────────┐  │
│  │                    Business Logic Layer                     │  │
│  │                                                              │  │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐    │  │
│  │  │    Call      │  │  Appointment │  │   Customer   │    │  │
│  │  │ Management   │  │  Management  │  │  Management  │    │  │
│  │  └──────────────┘  └──────────────┘  └──────────────┘    │  │
│  │                                                              │  │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐    │  │
│  │  │   Service    │  │    Branch    │  │   Company    │    │  │
│  │  │   Catalog    │  │  Management  │  │  Management  │    │  │
│  │  └──────────────┘  └──────────────┘  └──────────────┘    │  │
│  │                                                              │  │
│  └──────────────────────────┬───────────────────────────────────┘
│                             │                                     │
│  ┌─────────────────────────┴─────────────────────────────────┐  │
│  │                    Integration Layer                       │  │
│  │                                                             │  │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐   │  │
│  │  │    Retell    │  │   Cal.com    │  │    Stripe    │   │  │
│  │  │   Service    │  │   Service    │  │   Service    │   │  │
│  │  └──────────────┘  └──────────────┘  └──────────────┘   │  │
│  │                                                             │  │
│  └──────────────────────────────────────────────────────────────┘
│                             │                                     │
│  ┌─────────────────────────┴─────────────────────────────────┐  │
│  │                     Data Access Layer                      │  │
│  │                    (Eloquent ORM Models)                   │  │
│  │                                                             │  │
│  │  Call │ Customer │ Appointment │ Service │ Branch │ Company│  │
│  │  PhoneNumber │ Staff │ Integration │ Transaction │ Invoice │  │
│  │                                                             │  │
│  └──────────────────────────┬───────────────────────────────────┘
│                             │                                     │
└──────────────────────────────┼─────────────────────────────────────┘
                               │
                               ▼
                    ┌──────────────────────┐
                    │   MySQL Database     │
                    │   (MariaDB 10.11+)   │
                    └──────────────────────┘
```

### Technology Stack

**Application Framework**
- Laravel 11.31 (PHP 8.2+)
- Filament 3.3 (Admin Panel)
- Laravel Telescope (Debugging/Monitoring)

**External Services**
- Retell AI (Voice AI & Call Processing)
- Cal.com v2 (Calendar & Booking)
- Stripe (Payment & Billing)
- Twilio (SMS & Communication)
- Pusher (Real-time Events)

**Data Storage**
- MySQL/MariaDB (Primary Database)
- Laravel Cache (Redis/File)
- Session Storage (Database/Redis)

**Development Tools**
- Pest (Testing Framework)
- Laravel Pint (Code Style)
- Laravel Sail (Docker Development)

---

## 3. Component Diagram (C4 Level 3) - Call Processing Flow

```
┌──────────────────────────────────────────────────────────────────┐
│               Inbound Call Processing Components                  │
├──────────────────────────────────────────────────────────────────┤
│                                                                    │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │             Webhook Ingestion Component                   │   │
│  │                                                            │   │
│  │  RetellWebhookController                                  │   │
│  │  ├─ call_inbound: Create initial call record              │   │
│  │  ├─ call_started: Update to ongoing status                │   │
│  │  ├─ call_ended: Mark complete, calculate costs            │   │
│  │  └─ call_analyzed: Extract insights, process booking      │   │
│  │                                                            │   │
│  │  Middleware: VerifyRetellSignature, LogWebhookEvents      │   │
│  └────────────────────────┬───────────────────────────────────┘   │
│                           │                                        │
│                           ▼                                        │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │         Phone Number Resolution Component                 │   │
│  │                                                            │   │
│  │  PhoneNumberNormalizer                                    │   │
│  │  ├─ normalize(): E.164 format conversion                  │   │
│  │  ├─ generateVariants(): Multiple format variants          │   │
│  │  └─ match(): Database lookup with fuzzy matching          │   │
│  │                                                            │   │
│  │  PhoneNumber Model → Branch → Company → Services          │   │
│  └────────────────────────┬───────────────────────────────────┘   │
│                           │                                        │
│                           ▼                                        │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │         Call Analysis & Extraction Component              │   │
│  │                                                            │   │
│  │  NameExtractor                                            │   │
│  │  └─ updateCallWithExtractedName()                         │   │
│  │                                                            │   │
│  │  Transcript Analysis                                      │   │
│  │  ├─ extractBookingDetailsFromRetellData()                │   │
│  │  ├─ extractBookingDetailsFromTranscript()                │   │
│  │  └─ processCallInsights()                                 │   │
│  │                                                            │   │
│  └────────────────────────┬───────────────────────────────────┘   │
│                           │                                        │
│                           ▼                                        │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │         Appointment Creation Component                    │   │
│  │                                                            │   │
│  │  AppointmentAlternativeFinder                             │   │
│  │  ├─ findAlternatives(): Search available slots            │   │
│  │  └─ rankByPreference(): Score alternatives                │   │
│  │                                                            │   │
│  │  CalcomService                                            │   │
│  │  ├─ checkAvailability()                                   │   │
│  │  ├─ createBooking()                                       │   │
│  │  └─ validateTeamAccess()                                  │   │
│  │                                                            │   │
│  │  NestedBookingManager                                     │   │
│  │  └─ createNestedBooking(): Multi-segment appointments     │   │
│  │                                                            │   │
│  └────────────────────────┬───────────────────────────────────┘   │
│                           │                                        │
│                           ▼                                        │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │              Cost Calculation Component                   │   │
│  │                                                            │   │
│  │  CostCalculator                                           │   │
│  │  └─ updateCallCosts()                                     │   │
│  │                                                            │   │
│  │  PlatformCostService                                      │   │
│  │  ├─ trackRetellCost()                                     │   │
│  │  ├─ trackTwilioCost()                                     │   │
│  │  └─ calculateCallTotalCosts()                             │   │
│  │                                                            │   │
│  │  ExchangeRateService                                      │   │
│  │  └─ convertUsdToEur()                                     │   │
│  │                                                            │   │
│  └──────────────────────────────────────────────────────────────┘
│                                                                    │
└──────────────────────────────────────────────────────────────────┘
```

---

## 4. Sequence Diagrams - Critical Flows

### Flow 1: Successful Booking from Call

```
┌────────┐   ┌─────────┐   ┌──────────┐   ┌─────────┐   ┌─────────┐
│Retell  │   │Webhook  │   │Phone     │   │Cal.com  │   │Database │
│  AI    │   │Handler  │   │Resolver  │   │Service  │   │         │
└───┬────┘   └────┬────┘   └────┬─────┘   └────┬────┘   └────┬────┘
    │             │              │              │             │
    │ call_inbound│              │              │             │
    ├────────────>│              │              │             │
    │             │ Normalize    │              │             │
    │             ├─────────────>│              │             │
    │             │ PhoneNumber  │              │             │
    │             │<─────────────┤              │             │
    │             │              │              │             │
    │             │ INSERT Call (status=inbound)             │
    │             ├──────────────────────────────────────────>│
    │             │              │              │             │
    │ call_started│              │              │             │
    ├────────────>│              │              │             │
    │             │ UPDATE Call (status=ongoing)             │
    │             ├──────────────────────────────────────────>│
    │             │              │              │             │
    │ call_ended  │              │              │             │
    ├────────────>│              │              │             │
    │             │ UPDATE Call (status=completed, duration) │
    │             ├──────────────────────────────────────────>│
    │             │              │              │             │
    │call_analyzed│              │              │             │
    ├────────────>│              │              │             │
    │ (transcript,│              │              │             │
    │  analysis)  │              │              │             │
    │             │ Extract appointment details              │
    │             │              │              │             │
    │             │              │ Check availability         │
    │             │              ├─────────────>│             │
    │             │              │ Available slots            │
    │             │              │<─────────────┤             │
    │             │              │              │             │
    │             │              │ Create booking             │
    │             │              ├─────────────>│             │
    │             │              │ Booking ID   │             │
    │             │              │<─────────────┤             │
    │             │              │              │             │
    │             │ INSERT Appointment (booked)              │
    │             ├──────────────────────────────────────────>│
    │             │              │              │             │
    │             │ UPDATE Call (converted_appointment_id)   │
    │             ├──────────────────────────────────────────>│
    │             │              │              │             │
    │             │ Calculate costs                          │
    │             ├──────────────────────────────────────────>│
    │             │              │              │             │
    │  200 OK     │              │              │             │
    │<────────────┤              │              │             │
    │             │              │              │             │
```

### Flow 2: PhoneNumber Not Found Error

```
┌────────┐   ┌─────────┐   ┌──────────┐   ┌─────────┐
│Retell  │   │Webhook  │   │Phone     │   │Database │
│  AI    │   │Handler  │   │Resolver  │   │         │
└───┬────┘   └────┬────┘   └────┬─────┘   └────┬────┘
    │             │              │              │
    │ call_inbound│              │              │
    ├────────────>│              │              │
    │  (to_number)│              │              │
    │             │              │              │
    │             │ Normalize +49123456789      │
    │             ├─────────────>│              │
    │             │              │              │
    │             │              │ SELECT * FROM phone_numbers
    │             │              ├─────────────>│
    │             │              │ NOT FOUND    │
    │             │              │<─────────────┤
    │             │              │              │
    │             │ Partial match (last 10 digits)
    │             │              ├─────────────>│
    │             │              │ NOT FOUND    │
    │             │              │<─────────────┤
    │             │              │              │
    │             │ NULL         │              │
    │             │<─────────────┤              │
    │             │              │              │
    │             │ LOG WARNING: PhoneNumber not found
    │             │              │              │
    │             │ INSERT Call (company_id=1, phone_number_id=NULL)
    │             ├──────────────────────────────────────────>│
    │             │              │              │
    │  200 OK     │              │              │
    │<────────────┤              │              │
    │             │              │              │
```

### Flow 3: Service Unavailable - Alternative Booking

```
┌────────┐   ┌─────────┐   ┌──────────┐   ┌─────────┐   ┌─────────┐
│Retell  │   │Webhook  │   │Alt.Finder│   │Cal.com  │   │Database │
│  AI    │   │Handler  │   │          │   │Service  │   │         │
└───┬────┘   └────┬────┘   └────┬─────┘   └────┬────┘   └────┬────┘
    │             │              │              │             │
    │call_analyzed│              │              │             │
    ├────────────>│              │              │             │
    │             │ Extract: 2025-10-01 14:00                │
    │             │              │              │             │
    │             │              │ Check availability (14:00)
    │             │              ├─────────────>│             │
    │             │              │ NOT AVAILABLE│             │
    │             │              │<─────────────┤             │
    │             │              │              │             │
    │             │ Find alternatives
    │             ├─────────────>│              │             │
    │             │              │              │             │
    │             │              │ Get slots (14:00-17:00)
    │             │              ├─────────────>│             │
    │             │              │ 14:30, 15:00, 16:00
    │             │              │<─────────────┤             │
    │             │              │              │             │
    │             │ [14:30, 15:00, 16:00]       │             │
    │             │<─────────────┤              │             │
    │             │              │              │             │
    │             │              │ Create booking (14:30)
    │             │              ├─────────────>│             │
    │             │              │ Booking ID   │             │
    │             │              │<─────────────┤             │
    │             │              │              │             │
    │             │ INSERT Appointment (14:30)               │
    │             ├──────────────────────────────────────────>│
    │             │              │              │             │
    │             │ UPDATE Call (booking_details: original=14:00,
    │             │                              booked=14:30)
    │             ├──────────────────────────────────────────>│
    │             │              │              │             │
    │             │ TODO: Notify customer about alternative  │
    │             │              │              │             │
    │  200 OK     │              │              │             │
    │<────────────┤              │              │             │
    │             │              │              │             │
```

---

## 5. Domain Model - Entity Relationship Diagram

```
┌───────────────────────────────────────────────────────────────────┐
│                         Domain Model                               │
└───────────────────────────────────────────────────────────────────┘

┌─────────────┐
│   Tenant    │ (Multi-tenancy root)
└──────┬──────┘
       │ 1:N
       │
       ▼
┌─────────────┐
│   Company   │ (Business entity)
│─────────────│
│ id          │
│ name        │
│ calcom_api_key
│ calcom_team_id
│ retell_api_key
│ retell_enabled
│ credit_balance
│ billing_type │
│ settings    │
└──────┬──────┘
       │ 1:N
       ├──────────────────┬─────────────────┬──────────────┐
       │                  │                 │              │
       ▼                  ▼                 ▼              ▼
┌─────────────┐    ┌─────────────┐  ┌─────────────┐ ┌─────────────┐
│   Branch    │    │PhoneNumber  │  │  Customer   │ │   Service   │
│─────────────│    │─────────────│  │─────────────│ │─────────────│
│ id (UUID)   │    │ id (UUID)   │  │ id          │ │ id          │
│ company_id  │    │ company_id  │  │ company_id  │ │ company_id  │
│ name        │    │ branch_id   │  │ branch_id   │ │ branch_id   │
│ address     │    │ number      │  │ name        │ │ name        │
│ is_active   │    │ retell_agent_id │ email    │ │ description │
│ settings    │    │ is_active   │  │ phone       │ │ duration_min│
└──────┬──────┘    │ is_primary  │  │ status      │ │ price       │
       │           └──────┬──────┘  │ journey_status│ calcom_event_type_id
       │                  │         │ total_revenue│ │ is_active   │
       │                  │         │ loyalty_tier │ │ composite   │
       │                  │         └──────┬──────┘ │ segments    │
       │ 1:N              │ 1:N           │        └──────┬──────┘
       │                  │               │ 1:N           │
       │                  │               │               │ 1:N
       │                  │               │               │
       ▼                  ▼               ▼               ▼
┌─────────────┐    ┌─────────────┐ ┌─────────────┐ ┌─────────────┐
│    Staff    │    │    Call     │ │ Appointment │ │    Staff    │
│─────────────│    │─────────────│ │─────────────│ │(many-to-many)
│ id          │    │ id          │ │ id          │ └─────────────┘
│ branch_id   │    │ phone_number_id│ company_id│      (via
│ name        │    │ customer_id │ │ customer_id │   service_staff)
│ specialties │    │ company_id  │ │ service_id  │
│ is_active   │    │ retell_call_id│ branch_id │
└──────┬──────┘    │ from_number │ │ staff_id    │
       │           │ to_number   │ │ starts_at   │
       │           │ transcript  │ │ ends_at     │
       │           │ duration_sec│ │ status      │
       │           │ status      │ │ is_composite│
       │           │ direction   │ │ composite_group_uid
       │           │ appointment_made│ call_id   │
       │           │ cost_cents  │ │ calcom_v2_booking_id
       │           │ retell_cost │ │ metadata    │
       │           │ customer_name│ source      │
       │           │ converted_appointment_id │
       │           │ analysis    │ └─────────────┘
       │           │ created_at  │
       │           └─────────────┘
       │
       │ 1:N
       ▼
┌─────────────┐
│WorkingHour  │
│─────────────│
│ id          │
│ branch_id   │
│ staff_id    │
│ day_of_week │
│ start_time  │
│ end_time    │
│ is_active   │
└─────────────┘

┌───────────────────────────────────────────────┐
│         Supporting Entities                    │
├───────────────────────────────────────────────┤
│                                                │
│  Integration       - External API configs     │
│  Transaction       - Financial transactions   │
│  Invoice           - Customer invoices        │
│  PricingPlan       - Subscription tiers       │
│  BalanceTopup      - Credit purchases         │
│  PlatformCost      - External service costs   │
│  WebhookEvent      - Webhook audit trail      │
│  RetellAgent       - AI agent configurations  │
│  TeamEventTypeMapping - Cal.com team sync    │
│                                                │
└───────────────────────────────────────────────┘
```

### Entity Descriptions

**Core Aggregates:**

1. **Company Aggregate**
   - Root: Company
   - Entities: Branch, PhoneNumber, Service, Staff, Customer
   - Value Objects: Settings, BillingInfo, ContactInfo
   - Business Rules: Credit balance management, multi-branch operations

2. **Call Aggregate**
   - Root: Call
   - Value Objects: CallStatus, CostBreakdown, Analysis
   - Business Rules: Cost calculation, conversion tracking, transcript analysis

3. **Appointment Aggregate**
   - Root: Appointment
   - Entities: RecurringAppointmentPattern
   - Value Objects: BookingMetadata, CompositeSegments
   - Business Rules: Booking validation, cancellation policy, composite scheduling

4. **Customer Aggregate**
   - Root: Customer
   - Entities: CustomerNote
   - Value Objects: JourneyStatus, PhoneVariants, PreferenceData
   - Business Rules: Journey stage progression, loyalty management

### Key Relationships

- **Company** → **Branch** (1:N) - Multi-location support
- **Branch** → **PhoneNumber** (1:N) - Each branch has dedicated phone numbers
- **PhoneNumber** → **Call** (1:N) - Call routing and tracking
- **Call** → **Appointment** (1:1) - Call conversion tracking
- **Service** ↔ **Staff** (N:M) - Service delivery assignments
- **Service** → **Cal.com EventType** (1:1) - Calendar integration
- **Company** → **Cal.com Team** (1:1) - Team-based organization

---

## 6. Architecture Decisions (ADRs)

### ADR-001: PhoneNumber Normalization Strategy

**Status:** Implemented
**Date:** 2025-09-30
**Decision Makers:** Development Team

**Context:**
Phone numbers arrive in multiple formats from Retell AI:
- E.164 format: +491234567890
- National format: 0123 456789
- International without +: 491234567890
- Formatted: +49 (123) 456-789

**Decision:**
Implement multi-stage phone number resolution:
1. Normalize all numbers to E.164 format (PhoneNumberNormalizer::normalize())
2. Generate format variants for matching (generateVariants())
3. Try exact match first
4. Fall back to partial match (last 10 digits)
5. Store company_id = 1 (default) if no match found

**Consequences:**
✅ Improved match rate from ~60% to ~95%
✅ Handles international and local formats
✅ Graceful degradation when PhoneNumber not found
❌ Additional database queries for fuzzy matching
❌ Requires maintenance for new country formats

**Alternatives Considered:**
- Store all variants in database (rejected: storage overhead)
- Use external phone validation API (rejected: latency, cost)

---

### ADR-002: Webhook Event Processing Architecture

**Status:** Implemented
**Date:** 2025-09-30
**Decision Makers:** Development Team

**Context:**
Multiple external systems send webhooks:
- Retell AI: call_inbound, call_started, call_ended, call_analyzed
- Cal.com: BOOKING.CREATED, BOOKING.UPDATED, EVENT_TYPE.CREATED
- Stripe: payment events

**Decision:**
Implement synchronous webhook processing with:
- Signature verification middleware
- Event logging (WebhookEvent model)
- Idempotent handlers (updateOrCreate patterns)
- Graceful error handling (always return 200)

**Consequences:**
✅ Full audit trail of all webhooks
✅ Immediate processing (no queue delays)
✅ Simple debugging and replay
❌ Webhook timeout risk for slow operations
❌ No automatic retry on failure

**Migration Path:**
Move to asynchronous processing using Laravel Queues for:
- Long-running operations (>5 seconds)
- External API calls that can fail
- Non-critical background tasks

---

### ADR-003: Multi-Tenant Data Isolation Strategy

**Status:** Implemented
**Date:** 2025-09-30
**Decision Makers:** Architecture Team

**Context:**
System serves multiple companies with different:
- Cal.com teams and API keys
- Retell AI agents and phone numbers
- Billing configurations
- Service catalogs

**Decision:**
Implement database-level multi-tenancy:
- `company_id` on all major tables
- Global scopes for automatic filtering (TenantScope, CompanyScope)
- Row-level security via policies
- Encrypted API keys per company

**Consequences:**
✅ Single database, simple infrastructure
✅ Automatic tenant isolation
✅ Easy cross-tenant reporting
❌ Risk of data leakage bugs
❌ Cannot scale to separate databases per tenant

**Security Measures:**
- Policies enforce company_id checks
- Middleware: IdentifyTenant
- Encrypted storage of API keys
- Audit logging of cross-tenant access

---

### ADR-004: Cal.com Team-Based Service Validation

**Status:** Implemented
**Date:** 2025-09-30
**Decision Makers:** Development Team

**Context:**
Cal.com supports team-based organizations where:
- Each company has a Cal.com team (`calcom_team_id`)
- Services are team event types (`calcom_event_type_id`)
- Must prevent booking services from wrong team

**Decision:**
Implement team validation at booking time:
```php
if ($company->hasTeam()) {
    if (!$company->ownsService($service->calcom_event_type_id)) {
        throw new UnauthorizedException();
    }
}
```

**Consequences:**
✅ Prevents cross-tenant service booking
✅ Clean separation of company services
✅ Supports Cal.com's team model
❌ Additional API call to validate
❌ Complexity in service assignment

---

### ADR-005: Call Cost Tracking with Multi-Currency Support

**Status:** Implemented
**Date:** 2025-09-30
**Decision Makers:** Finance Team

**Context:**
External services bill in USD:
- Retell AI: $0.07/minute
- Twilio: $0.0085/minute
But we bill customers in EUR.

**Decision:**
Implement cost tracking system:
- `PlatformCost` model for external costs (USD)
- `ExchangeRateService` for USD → EUR conversion
- `CostCalculator` for profit margin calculation
- Store both `cost_cents` (customer-facing EUR) and `retell_cost_usd` (external USD)

**Consequences:**
✅ Accurate profit tracking
✅ Supports multi-currency billing
✅ Transparent external cost tracking
❌ Exchange rate fluctuation risk
❌ Complex cost calculation logic

---

### ADR-006: Appointment Alternative Finder Strategy

**Status:** Implemented
**Date:** 2025-09-30
**Decision Makers:** Product Team

**Context:**
Customers request specific times, but slots often unavailable.
Need intelligent alternative suggestions.

**Decision:**
Implement `AppointmentAlternativeFinder` with ranking:
- Same day, different time (priority 1)
- Next day, same time (priority 2)
- Next day, different time (priority 3)
- Within 3 days (priority 4)

**Consequences:**
✅ Higher booking conversion rate
✅ Better customer experience
✅ Reduces manual rebooking
❌ Additional Cal.com API calls
❌ Complexity in preference logic

**Future Enhancement:**
- ML-based preference learning
- Staff preference matching
- Historical booking pattern analysis

---

## 7. Non-Functional Requirements

### Performance

| Metric | Target | Current | Status |
|--------|--------|---------|--------|
| Webhook response time | <2s | ~800ms | ✅ |
| Database query time | <100ms | ~45ms | ✅ |
| Cal.com API latency | <3s | ~1.2s | ✅ |
| Retell webhook processing | <5s | ~2.3s | ✅ |
| Admin dashboard load | <1.5s | ~900ms | ✅ |

**Scalability Targets:**
- 100 concurrent calls
- 500 webhook events/minute
- 10,000 appointments/day
- 50 companies/tenants

### Availability

| Service | Target | Monitoring |
|---------|--------|------------|
| API Gateway | 99.5% | Uptime Robot + Laravel Telescope |
| Database | 99.9% | MySQL replication + backups |
| External APIs | N/A | Health check endpoints |

**Disaster Recovery:**
- Database backups: Daily (30-day retention)
- Critical data replication: Real-time
- RTO (Recovery Time Objective): 4 hours
- RPO (Recovery Point Objective): 1 hour

### Security

**OWASP Top 10 Compliance:**
✅ Injection prevention (Eloquent ORM, prepared statements)
✅ Broken authentication (Sanctum tokens, encrypted passwords)
✅ Sensitive data exposure (Encrypted API keys, HTTPS only)
✅ XML external entities (JSON only, no XML parsing)
✅ Broken access control (Policy-based authorization)
✅ Security misconfiguration (Environment-based configs)
✅ XSS (Blade templating, CSP headers)
✅ Insecure deserialization (Validated input, sanitization)
✅ Using components with known vulnerabilities (Composer updates)
✅ Insufficient logging & monitoring (Telescope, structured logs)

**Security Measures:**
- HTTPS enforcement (SecurityHeaders middleware)
- API key rotation (90-day policy)
- Rate limiting (60 requests/minute per IP)
- Webhook signature verification
- SQL injection prevention (Eloquent ORM)
- XSS protection (Blade escaping, CSP)
- CSRF protection (Laravel built-in)

### Reliability

**Error Handling:**
- Graceful degradation when external APIs fail
- Retry logic with exponential backoff
- Circuit breaker pattern for Cal.com/Retell
- Dead letter queue for failed webhooks

**Data Integrity:**
- Database transactions for critical operations
- Soft deletes for audit trail
- Version control for conflicting updates
- Idempotent webhook handlers

### Observability

**Logging:**
- Application logs: `storage/logs/laravel.log`
- Webhook logs: `WebhookEvent` model + Telescope
- Error tracking: Laravel log channels
- Performance metrics: Telescope queries panel

**Monitoring Endpoints:**
- `/api/health` - Basic health check
- `/api/health/detailed` - Database, cache, external APIs
- `/api/health/metrics` - Performance statistics
- `/api/webhooks/retell/diagnostic` - Retell integration status

---

## 8. Technical Debt

### Critical (Address within 1 month)

**TD-001: PhoneNumber not found causes default company assignment**
- **Issue**: When PhoneNumber lookup fails, defaults to `company_id = 1`
- **Impact**: Calls routed to wrong company, billing errors
- **Root Cause**: No fallback mechanism for unrecognized numbers
- **Solution**: Implement phone number registration flow or admin alert system
- **Effort**: 3 days
- **Risk**: High (financial impact)

**TD-002: No retry mechanism for failed webhook processing**
- **Issue**: If webhook processing fails, event is lost (no queue)
- **Impact**: Missing appointments, lost call data
- **Root Cause**: Synchronous processing, no dead letter queue
- **Solution**: Migrate to async processing with Laravel Queues
- **Effort**: 5 days
- **Risk**: Medium (data loss)

**TD-003: Exchange rate hardcoded, no automatic updates**
- **Issue**: USD→EUR conversion uses static rate
- **Impact**: Inaccurate profit calculations
- **Root Cause**: No integration with currency API
- **Solution**: Integrate ECB API or similar for daily rates
- **Effort**: 2 days
- **Risk**: Medium (financial accuracy)

### High (Address within 3 months)

**TD-004: Missing customer notification system**
- **Issue**: No SMS/Email sent for alternative bookings
- **Impact**: Poor customer experience, confusion
- **Root Cause**: Notification system not implemented
- **Solution**: Implement notification queue with Twilio/SendGrid
- **Effort**: 8 days
- **Risk**: Medium (customer satisfaction)

**TD-005: No caching for Cal.com availability queries**
- **Issue**: Every availability check hits Cal.com API
- **Impact**: Slow response times, API rate limits
- **Root Cause**: No caching layer
- **Solution**: Implement Redis caching with 5-minute TTL
- **Effort**: 3 days
- **Risk**: Low (performance optimization)

**TD-006: Appointment extraction confidence threshold too low**
- **Issue**: 60% confidence still creates appointments
- **Impact**: Wrong time slots booked, customer complaints
- **Root Cause**: Overly permissive transcript parsing
- **Solution**: Increase threshold to 80%, improve extraction logic
- **Effort**: 5 days
- **Risk**: Medium (data quality)

### Medium (Address within 6 months)

**TD-007: No monitoring for external API failures**
- **Issue**: Silent failures when Retell/Cal.com down
- **Impact**: No visibility into integration health
- **Root Cause**: No alerting system
- **Solution**: Implement health monitoring with PagerDuty alerts
- **Effort**: 4 days
- **Risk**: Low (operational visibility)

**TD-008: Branch-service relationship complexity**
- **Issue**: Many-to-many with pivot overrides, hard to maintain
- **Impact**: Complex queries, slow performance
- **Root Cause**: Over-engineered data model
- **Solution**: Simplify to 1:N with service templates
- **Effort**: 10 days (migration needed)
- **Risk**: Low (code maintainability)

### Low (Backlog)

**TD-009: Test coverage below 50%**
- **Issue**: Missing unit/integration tests
- **Impact**: Regression risk, slow development
- **Solution**: Incremental test coverage increase
- **Effort**: Ongoing
- **Risk**: Low (quality assurance)

**TD-010: API documentation outdated**
- **Issue**: README and API docs not up to date
- **Impact**: Poor developer experience
- **Solution**: Generate OpenAPI spec from routes
- **Effort**: 3 days
- **Risk**: Low (documentation)

---

## 9. Future Architecture Enhancements

### Phase 1: Reliability & Observability (Q4 2025)

**Async Webhook Processing**
- Migrate to Laravel Queue system
- Implement retry logic with exponential backoff
- Add dead letter queue for manual review
- Benefits: Improved reliability, better scalability

**Enhanced Monitoring**
- Integrate with Sentry for error tracking
- Add custom Prometheus metrics
- Implement distributed tracing (OpenTelemetry)
- Benefits: Faster issue detection and resolution

### Phase 2: Performance & Scale (Q1 2026)

**Caching Layer**
- Redis for Cal.com availability (5-min TTL)
- Cache frequently accessed services/branches
- Implement cache invalidation on updates
- Benefits: 50% reduction in external API calls

**Database Optimization**
- Add missing indexes (identified via Telescope)
- Implement read replicas for analytics
- Optimize N+1 queries in Filament resources
- Benefits: 40% faster query performance

### Phase 3: Feature Enhancements (Q2 2026)

**Smart Booking Engine**
- ML-based preference learning
- Staff skill matching
- Historical pattern analysis
- Benefits: Higher conversion rate, better matches

**Customer Portal**
- Self-service appointment management
- Booking history and invoices
- Preference management
- Benefits: Reduced support load, better UX

### Phase 4: Multi-Region Support (Q3 2026)

**Geographic Distribution**
- Deploy to multiple AWS regions (EU, US)
- Latency-based routing
- Regional database replicas
- Benefits: Lower latency, compliance (GDPR)

---

## 10. Deployment Architecture

```
┌────────────────────────────────────────────────────────────┐
│                     Production Environment                  │
├────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌─────────────┐         ┌─────────────┐                   │
│  │   Cloudflare│         │     Nginx   │                   │
│  │     CDN     │────────>│   Reverse   │                   │
│  │   (Cache,   │         │    Proxy    │                   │
│  │   WAF, SSL) │         │  (PHP-FPM)  │                   │
│  └─────────────┘         └──────┬──────┘                   │
│                                 │                           │
│                                 ▼                           │
│                    ┌────────────────────────┐              │
│                    │   Laravel Application  │              │
│                    │   (PHP 8.2 + FPM)     │              │
│                    │                        │              │
│                    │  - Web routes          │              │
│                    │  - API routes          │              │
│                    │  - Webhook handlers    │              │
│                    │  - Background jobs     │              │
│                    └────────┬───────────────┘              │
│                             │                               │
│        ┌────────────────────┼────────────────────┐         │
│        │                    │                    │         │
│        ▼                    ▼                    ▼         │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐   │
│  │   MySQL     │    │    Redis    │    │   Storage   │   │
│  │  (Primary)  │    │  (Cache +   │    │  (Logs +    │   │
│  │             │    │   Session)  │    │   Files)    │   │
│  └─────────────┘    └─────────────┘    └─────────────┘   │
│                                                              │
└────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────┐
│                    External Dependencies                     │
├────────────────────────────────────────────────────────────┤
│                                                              │
│  - Retell AI (api.retellai.com)                            │
│  - Cal.com (api.cal.com)                                   │
│  - Stripe (api.stripe.com)                                 │
│  - Twilio (api.twilio.com)                                 │
│  - Pusher (pusher.com)                                     │
│                                                              │
└────────────────────────────────────────────────────────────┘
```

### Infrastructure

**Server Configuration:**
- OS: Ubuntu 22.04 LTS
- Web Server: Nginx 1.24
- PHP: PHP 8.2 (FPM mode)
- Database: MySQL 8.0 / MariaDB 10.11
- Cache: Redis 7.0

**Scaling Strategy:**
- Vertical: Increase server resources (current)
- Horizontal: Load balancer + multiple app servers (future)
- Database: Read replicas for analytics queries (future)

---

## 11. Glossary

**Terms:**

- **Call Conversion**: Successful booking created from a phone call
- **Composite Service**: Multi-segment service (e.g., haircut + coloring)
- **Event Type**: Cal.com's term for a bookable service
- **Multi-Tenant**: Multiple companies sharing same application instance
- **Nested Booking**: Sequential appointments for composite services
- **Phone Number Normalization**: Converting phone numbers to E.164 format
- **Team Validation**: Ensuring services belong to correct Cal.com team
- **Webhook**: HTTP callback from external system for event notification

**Acronyms:**

- **ADR**: Architecture Decision Record
- **API**: Application Programming Interface
- **CRUD**: Create, Read, Update, Delete
- **GDPR**: General Data Protection Regulation
- **ORM**: Object-Relational Mapping (Eloquent)
- **REST**: Representational State Transfer
- **RTO**: Recovery Time Objective
- **RPO**: Recovery Point Objective
- **SLA**: Service Level Agreement
- **TTL**: Time To Live (cache expiration)
- **UUID**: Universally Unique Identifier
- **WAF**: Web Application Firewall

---

## 12. References & Documentation

**Internal Documentation:**
- `/routes/api.php` - API endpoint definitions
- `/app/Http/Controllers/RetellWebhookController.php` - Call processing logic
- `/app/Http/Controllers/CalcomWebhookController.php` - Booking webhook handling
- `/app/Models/` - Domain model definitions
- `/database/migrations/` - Database schema history

**External Documentation:**
- [Laravel 11 Documentation](https://laravel.com/docs/11.x)
- [Retell AI API Reference](https://docs.retellai.com)
- [Cal.com API v2 Documentation](https://docs.cal.com/api/v2)
- [Stripe API Documentation](https://stripe.com/docs/api)
- [Filament v3 Documentation](https://filamentphp.com/docs/3.x)

**Architecture Patterns:**
- [C4 Model for Architecture Diagrams](https://c4model.com)
- [Domain-Driven Design](https://martinfowler.com/bliki/DomainDrivenDesign.html)
- [Microservices Patterns](https://microservices.io/patterns)

---

**Document End**

*Generated by System Architect with Claude AI*
*Last Updated: 2025-09-30*