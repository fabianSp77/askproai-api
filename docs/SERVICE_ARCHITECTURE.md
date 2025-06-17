# AskProAI Service Architecture Documentation

*Last Updated: 2025-06-17*

## Overview

This document provides a comprehensive overview of all service classes in the AskProAI platform, their responsibilities, and how they interact.

## Service Categories

### 1. Calendar Integration Services (Cal.com)

#### CalcomService.php
- **Purpose**: Primary V1 API integration with Cal.com
- **Key Methods**: 
  - `createBooking()` - Creates appointments in Cal.com
  - `getAvailability()` - Checks available time slots
  - `getEventTypes()` - Retrieves available event types
- **Used By**: ProcessRetellCallEndedJob, CalendarProviderFactory
- **Status**: Active, will be migrated to V2

#### CalcomV2Service.php ⭐ [RECOMMENDED]
- **Purpose**: Modern V2 API integration with Cal.com
- **Key Methods**:
  - `getEventTypes()` - Retrieves event types with enhanced data
  - `getAvailableSlots()` - Advanced availability checking
  - `createBooking()` - Creates bookings with V2 features
  - `getBookings()` - Retrieves booking list
- **Used By**: CalcomApiTest, future implementations
- **Status**: Active, target for all new features

#### CalcomImportService.php
- **Purpose**: Imports event types from Cal.com to local database
- **Key Methods**:
  - `importEventTypes()` - Bulk import of event types
  - `mapEventTypeData()` - Maps Cal.com data to local models
- **Used By**: ImportCalcomEventTypesController, Admin UI
- **Status**: Critical for onboarding

#### CalcomSyncService.php
- **Purpose**: Synchronizes data between Cal.com and local database
- **Key Methods**:
  - `checkAvailability()` - Real-time availability checks
  - `syncBookings()` - Two-way booking synchronization
  - `updateEventType()` - Syncs event type changes
- **Used By**: Multiple controllers, background jobs
- **Status**: Critical for real-time operations

#### CalcomEventTypeSyncService.php
- **Purpose**: Specialized service for event type synchronization
- **Key Methods**:
  - `syncEventTypesForCompany()` - Company-wide sync
  - `validateApiKey()` - API key validation
  - `fetchEventTypes()` - Retrieves and caches event types
- **Used By**: CompanyResource, setup wizards
- **Status**: Essential for multi-tenant operations

#### CalcomV2MigrationService.php
- **Purpose**: Handles migration from V1 to V2 API
- **Key Methods**:
  - `migrateEventTypes()` - Converts V1 event types to V2
  - `migrateBookings()` - Updates booking format
- **Used By**: Registered in AppServiceProvider
- **Status**: Transition service

### 2. Phone AI Services (Retell.ai)

#### RetellService.php
- **Purpose**: Primary integration with Retell.ai
- **Key Methods**:
  - `createCall()` - Initiates AI phone calls
  - `updateCall()` - Updates call status
  - `getCallDetails()` - Retrieves call information
- **Used By**: RetellWebhookController, various commands
- **Status**: Active, being migrated to V2

#### RetellV2Service.php ⭐ [RECOMMENDED]
- **Purpose**: Enhanced V2 integration with Retell.ai
- **Key Methods**:
  - `handleWebhook()` - Processes all webhook events
  - `processCallEnded()` - Handles completed calls
  - `extractAppointmentData()` - AI data extraction
- **Used By**: ProcessRetellCallEndedJob
- **Status**: Active, target for new features

#### RetellAgentService.php
- **Purpose**: Manages AI agents configuration
- **Key Methods**:
  - `createAgent()` - Creates new AI agents
  - `updateAgent()` - Updates agent settings
  - `assignAgentToBranch()` - Branch-agent mapping
- **Used By**: BranchResource
- **Status**: Critical for multi-branch setup

### 3. Core Business Services

#### AppointmentBookingService.php ⭐ [PRIMARY]
- **Purpose**: Central orchestrator for appointment bookings
- **Key Methods**:
  - `bookAppointment()` - Complete booking flow
  - `checkAvailability()` - Unified availability checking
  - `confirmBooking()` - Booking confirmation process
- **Used By**: Webhooks, API controllers, jobs
- **Status**: Primary booking service

#### SmartBookingService.php 🆕
- **Purpose**: AI-enhanced booking with smart features
- **Key Methods**:
  - `handleIncomingCall()` - End-to-end call processing
  - `findBestSlot()` - AI-powered slot recommendation
  - `autoReschedule()` - Intelligent rescheduling
- **Used By**: Future AI features
- **Status**: Next-generation service

#### PhoneNumberResolver.php
- **Purpose**: Maps phone numbers to companies/branches
- **Key Methods**:
  - `resolveBranch()` - Phone to branch mapping
  - `resolveCompany()` - Phone to company mapping
  - `cacheResolution()` - Performance optimization
- **Used By**: All incoming call handlers
- **Status**: Critical for multi-tenancy

#### CallDataRefresher.php
- **Purpose**: Updates call data from Retell.ai
- **Key Methods**:
  - `refreshCallData()` - Updates call information
  - `syncTranscripts()` - Retrieves call transcripts
  - `updateCallMetrics()` - Analytics data update
- **Used By**: Background jobs, admin UI
- **Status**: Essential for call tracking

### 4. Communication Services

#### NotificationService.php
- **Purpose**: Handles all notifications (email, SMS, WhatsApp)
- **Key Methods**:
  - `sendAppointmentConfirmation()` - Booking confirmations
  - `sendReminder()` - Appointment reminders
  - `sendCancellation()` - Cancellation notices
- **Used By**: All booking-related processes
- **Status**: Active, SMS/WhatsApp pending

#### EmailService.php
- **Purpose**: Email-specific functionality
- **Key Methods**:
  - `sendTransactionalEmail()` - Transactional emails
  - `sendBulkEmail()` - Marketing emails
  - `trackEmailStatus()` - Delivery tracking
- **Used By**: NotificationService
- **Status**: Fully functional

### 5. Payment & Billing Services

#### StripeService.php
- **Purpose**: Stripe payment integration
- **Key Methods**:
  - `createCustomer()` - Customer management
  - `createSubscription()` - Subscription handling
  - `processPayment()` - Payment processing
- **Used By**: Billing controllers
- **Status**: Basic implementation

#### PricingService.php
- **Purpose**: Dynamic pricing calculations
- **Key Methods**:
  - `calculatePrice()` - Price computation
  - `applyDiscounts()` - Discount logic
  - `getTierPricing()` - Tiered pricing
- **Used By**: Quote generation
- **Status**: Ready for expansion

## Service Interaction Patterns

### Appointment Booking Flow
```
1. RetellWebhookController receives call
   ↓
2. PhoneNumberResolver identifies branch
   ↓
3. RetellV2Service processes call data
   ↓
4. AppointmentBookingService orchestrates:
   - CalcomSyncService checks availability
   - Customer creation/lookup
   - CalcomV2Service creates booking
   ↓
5. NotificationService sends confirmation
```

### Event Type Synchronization
```
1. Admin triggers sync in UI
   ↓
2. CalcomEventTypeSyncService fetches from Cal.com
   ↓
3. CalcomImportService imports to database
   ↓
4. Staff assignments updated
   ↓
5. Cache refreshed
```

## Best Practices

1. **Use V2 Services**: Prefer CalcomV2Service and RetellV2Service for new features
2. **Service Boundaries**: Keep services focused on single responsibility
3. **Error Handling**: All services should implement comprehensive error handling
4. **Logging**: Use structured logging for debugging
5. **Caching**: Implement caching for frequently accessed data
6. **Testing**: Each service should have corresponding tests

## Migration Roadmap

### Short Term (1-2 weeks)
- Complete migration from CalcomService to CalcomV2Service
- Unify RetellService and RetellV2Service
- Implement comprehensive logging

### Medium Term (1 month)
- Add SMS/WhatsApp to NotificationService
- Enhance StripeService for full billing
- Build automated agent provisioning

### Long Term (3 months)
- Implement SmartBookingService AI features
- Add multi-language support
- Build customer self-service portal

## Monitoring & Maintenance

- All services log to `/storage/logs/services/`
- Performance metrics tracked in database
- Health checks available via `/api/health`
- Service status dashboard in admin panel

---

For questions or updates to this documentation, contact: dev@askproai.de