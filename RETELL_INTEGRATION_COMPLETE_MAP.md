# Retell AI Integration - Complete File Map

**Last Updated**: 2025-11-06  
**Codebase**: AskPro API Gateway (Laravel 11 + Filament 3)  
**Integration Type**: Voice AI Agent for Appointment Booking

---

## OVERVIEW

The Retell AI integration is a comprehensive system that enables voice-based appointment booking through an AI agent. It includes webhook handlers, function call processors, services, models, middleware, configurations, and extensive testing infrastructure.

---

## 1. CONFIGURATION FILES

### 1.1 Services Configuration
**File**: `/var/www/api-gateway/config/services.php`
**Lines**: 62-76
**Purpose**: Core Retell API configuration including:
- API key management
- Base URL configuration
- Agent ID
- Webhook secret
- Function secret (for function call validation)
- Test mode fallback configuration
- Webhook logging toggle
- Unsigned webhook allowance

**Key Configuration Keys**:
```
retellai.api_key
retellai.base_url
retellai.agent_id
retellai.webhook_secret
retellai.function_secret
retellai.log_webhooks
retellai.allow_unsigned_webhooks
retellai.test_mode_company_id
retellai.test_mode_branch_id
```

---

## 2. WEBHOOK HANDLING & CONTROLLERS

### 2.1 Main Webhook Controller
**File**: `/var/www/api-gateway/app/Http/Controllers/RetellWebhookController.php`
**Type**: Controller (Invokable)
**Purpose**: Handles Retell call completion webhooks
**Key Methods**:
- `__invoke()` - Primary webhook handler for call completion events
- `diagnostic()` - Diagnostic endpoint for webhook verification

**Key Dependencies**:
- PhoneNumberResolutionService
- ServiceSelectionService
- WebhookResponseService
- CallLifecycleService
- CallTrackingService
- AppointmentCreationService
- BookingDetailsExtractor

**Routes**:
- POST `/webhooks/retell` - Main webhook endpoint
- GET `/webhooks/retell/diagnostic` - Webhook diagnostic endpoint
- POST `/webhook` (legacy) - Backward compatibility route

---

### 2.2 Function Call Handler
**File**: `/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php`
**Type**: Controller
**Purpose**: Handles real-time function calls from Retell AI during active calls (availability checks, service selections, appointment data collection)
**Key Methods**:
- `extractCanonicalCallId()` - RCA 2025-11-03: Extracts canonical call_id from multiple sources
- `handleFunctionCall()` - Generic function call handler
- `collectAppointment()` - Collects appointment info from caller
- `handleAvailabilityCheck()` - Checks availability in real-time
- `checkAvailabilityV17()` - V17 endpoint for availability
- `bookAppointmentV17()` - V17 endpoint for booking
- `initializeCallV4()` - V4 initialization endpoint
- `getCustomerAppointmentsV4()` - V4 get appointments
- `cancelAppointmentV4()` - V4 cancel appointment
- `rescheduleAppointmentV4()` - V4 reschedule appointment
- `getAvailableServicesV4()` - V4 get services
- `getAvailableServices()` - Get available services

**Routes** (multiple versions):
- POST `/webhooks/retell/function` - Main function handler
- POST `/webhooks/retell/function-call` - Alias for function handler
- POST `/webhooks/retell/collect-appointment` - Specific collect appointment endpoint
- POST `/webhooks/retell/check-availability` - Specific availability check endpoint
- POST `/retell/v17/check-availability` - V17 check availability
- POST `/retell/v17/book-appointment` - V17 book appointment
- POST `/retell/v4/initialize-call` - V4 initialize
- POST `/retell/v4/get-appointments` - V4 get appointments
- POST `/retell/v4/cancel-appointment` - V4 cancel
- POST `/retell/v4/reschedule-appointment` - V4 reschedule
- POST `/retell/v4/get-services` - V4 services
- POST `/retell/get-available-services` - Get services

---

### 2.3 API Endpoints Controller
**File**: `/var/www/api-gateway/app/Http/Controllers/Api/RetellApiController.php`
**Type**: API Controller
**Purpose**: Handles API endpoints called by Retell Agent configuration
**Key Methods**:
- `checkCustomer()` - POST `/api/retell/check-customer`
- `initializeCall()` - POST `/api/retell/initialize-call` (V16: combined init)
- `checkAvailability()` - POST `/api/retell/check-availability`
- `collectAppointment()` - POST `/api/retell/collect-appointment`
- `bookAppointment()` - POST `/api/retell/book-appointment`
- `cancelAppointment()` - POST `/api/retell/cancel-appointment`
- `rescheduleAppointment()` - POST `/api/retell/reschedule-appointment`

---

### 2.4 Additional API Controllers
**File**: `/var/www/api-gateway/app/Http/Controllers/Api/RetellGetAppointmentsController.php`
**Purpose**: Dedicated handler for retrieving customer appointments
**Route**: POST `/api/retell/get-customer-appointments`

**File**: `/var/www/api-gateway/app/Http/Controllers/Api/Retell/DateTimeInfoController.php`
**Purpose**: Provides German datetime interpretation
**Route**: POST `/webhooks/retell/datetime`

**File**: `/var/www/api-gateway/app/Http/Controllers/Api/Retell/CurrentContextController.php`
**Purpose**: Provides current date/time context dynamically for agent
**Route**: POST `/webhooks/retell/current-context`

---

## 3. MIDDLEWARE (SECURITY & VALIDATION)

### 3.1 Webhook Signature Verification
**File**: `/var/www/api-gateway/app/Http/Middleware/VerifyRetellWebhookSignature.php`
**Purpose**: Validates webhook signatures to prevent webhook forgery
**Coverage**: Main webhook events
**Security Level**: CVSS 9.3 mitigation

**File**: `/var/www/api-gateway/app/Http/Middleware/VerifyRetellSignature.php`
**Purpose**: Alias extending VerifyRetellWebhookSignature

---

### 3.2 Function Call Signature Verification
**File**: `/var/www/api-gateway/app/Http/Middleware/VerifyRetellFunctionSignature.php`
**Purpose**: Validates function call request signatures
**Scope**: Real-time function calls from Retell

**File**: `/var/www/api-gateway/app/Http/Middleware/VerifyRetellFunctionSignatureWithWhitelist.php`
**Purpose**: Enhanced signature verification with IP whitelist
**Status**: Optional, more restrictive variant

---

### 3.3 Call ID Validation
**File**: `/var/www/api-gateway/app/Http/Middleware/ValidateRetellCallId.php`
**Purpose**: RCA 2025-11-03: Defense-in-depth validation of call_id
**Ensures**: Canonical call_id extraction and validation

**File**: `/var/www/api-gateway/app/Http/Middleware/RetellCallRateLimiter.php`
**Purpose**: Rate limiting for Retell function calls by call_id
**Prevents**: Abuse of real-time function calls

---

## 4. SERVICES (BUSINESS LOGIC)

### 4.1 Core Integration Service
**File**: `/var/www/api-gateway/app/Services/RetellApiClient.php`
**Purpose**: HTTP client for Retell API communication
**Key Methods**:
- `getAllCalls()` - Fetch all calls from Retell API
- Call fetching with pagination, filtering, sorting
- Handles authentication and error handling

---

### 4.2 Retell Service Subdirectory
**Location**: `/var/www/api-gateway/app/Services/Retell/`

#### 4.2.1 Appointment & Booking Services
**File**: `AppointmentCreationService.php`
- Creates appointments from collected information
- Validates booking data
- Handles calendar integration

**File**: `AppointmentQueryService.php`
- Queries existing appointments
- Retrieves appointment details

**File**: `AppointmentCustomerResolver.php`
- Resolves customer identity from call context
- Customer matching logic

**File**: `BookingDetailsExtractor.php`
- Extracts booking info from collected data
- Data transformation and validation

**File**: `BookingDetailsExtractorInterface.php`
- Interface contract for booking detail extraction

---

#### 4.2.2 Service Selection & Resolution
**File**: `ServiceSelectionService.php`
- Selects appropriate service from available options
- Matches customer intent to services

**File**: `ServiceSelectionInterface.php`
- Interface contract for service selection

**File**: `ServiceNameExtractor.php`
- Extracts service names from transcripts
- NLP processing for service matching

---

#### 4.2.3 Phone Number Resolution
**File**: `PhoneNumberResolutionService.php`
- Resolves phone numbers from calls
- Phone number normalization and validation

**File**: `PhoneNumberResolutionInterface.php`
- Interface contract for phone resolution

---

#### 4.2.4 Data Validation & Processing
**File**: `CustomerDataValidator.php`
- Validates customer information
- Data integrity checks

**File**: `DateTimeParser.php`
- Parses German time expressions
- Handles datetime validation and conversion

---

#### 4.2.5 Response Formatting
**File**: `WebhookResponseService.php`
- Formats API responses for Retell
- Structures function call returns

**File**: `WebhookResponseInterface.php`
- Interface contract for webhook responses

---

#### 4.2.6 Call Lifecycle Management
**File**: `CallLifecycleService.php`
- Manages call state throughout lifecycle
- Call initialization, tracking, completion

**File**: `CallLifecycleInterface.php`
- Interface contract for call lifecycle

**File**: `CallTrackingService.php`
- Tracks call events and metrics
- Logging and monitoring

---

#### 4.2.7 Agent Management & Prompt Services
**File**: `RetellAgentManagementService.php`
- Manages Retell agent configuration
- Agent updates and synchronization

**File**: `RetellPromptTemplateService.php`
- Manages agent prompt templates
- Prompt generation and updates

**File**: `RetellPromptValidationService.php`
- Validates prompt configurations
- Syntax and content validation

---

#### 4.2.8 Legacy & Query Services
**File**: `QueryAppointmentByNameFunction.php`
- Legacy function for querying appointments by name
- Backward compatibility handler

---

## 5. MODELS (DATA STRUCTURES)

### 5.1 Core Models
**File**: `/var/www/api-gateway/app/Models/RetellAgent.php`
**Purpose**: Represents Retell AI agent configuration
**Key Fields**:
- `agent_id` - Retell agent ID
- `agent_name` - Agent display name
- `voice_id` - Voice configuration
- `language` - Agent language
- `prompt` - Agent instructions
- `is_active` - Agent status
- `call_count` - Total calls made
- Metrics: max_call_duration, interruption_sensitivity, backchannel_frequency, etc.
**Relations**: belongsTo Company, hasMany Calls

---

### 5.2 Call & Session Models
**File**: `/var/www/api-gateway/app/Models/RetellCallSession.php`
**Purpose**: Represents a single Retell call session
**Usage**: Track individual call context and metadata

**File**: `/var/www/api-gateway/app/Models/RetellCallEvent.php`
**Purpose**: Records events during a call
**Usage**: Call event logging and analysis

**File**: `/var/www/api-gateway/app/Models/Call.php`
**Purpose**: Core call model (shared with other systems)
**Integration**: Links to RetellAgent via agent_id

---

### 5.3 Logging & Monitoring Models
**File**: `/var/www/api-gateway/app/Models/RetellErrorLog.php`
**Purpose**: Logs errors and failures in Retell calls

**File**: `/var/www/api-gateway/app/Models/RetellFunctionTrace.php`
**Purpose**: Traces function call execution
**Usage**: Debugging and performance analysis

**File**: `/var/www/api-gateway/app/Models/RetellTranscriptSegment.php`
**Purpose**: Stores transcript segments
**Usage**: Call analysis and compliance

---

### 5.4 Prompt Management
**File**: `/var/www/api-gateway/app/Models/RetellAgentPrompt.php`
**Purpose**: Versioned prompt storage
**Fields**: prompt content, version, status, metadata

---

## 6. DATABASE MIGRATIONS

### 6.1 Core Integration Migrations
**File**: `/var/www/api-gateway/database/migrations/2025_09_30_090000_add_retell_agent_id_to_calls_table.php`
**Purpose**: Add retell_agent_id column to calls table
**Links calls to Retell agents**

**File**: `/var/www/api-gateway/database/migrations/2025_09_25_000000_create_calls_table.php`
**Purpose**: Main calls table creation
**Stores**: Call metadata, duration, status

---

### 6.2 Agent Configuration Migrations
**File**: `/var/www/api-gateway/database/migrations/2025_10_27_000001_add_retell_agent_id_to_companies.php`
**Purpose**: Add retell_agent_id to companies table
**Links companies to their Retell agent**

**File**: `/var/www/api-gateway/database/migrations/2025_10_21_131415_create_retell_agent_prompts_table.php`
**Purpose**: Create table for versioned prompts
**Stores**: Prompt history and versions

---

### 6.3 Session & Monitoring Migrations
**File**: `/var/www/api-gateway/database/migrations/2025_10_23_000001_create_retell_monitoring_tables.php`
**Purpose**: Create monitoring infrastructure tables
**Includes**: RetellCallSession, RetellCallEvent, RetellFunctionTrace, etc.

**File**: `/var/www/api-gateway/database/migrations/2025_10_25_132525_add_phone_and_branch_to_retell_call_sessions.php`
**Purpose**: Add phone number and branch tracking
**Enables**: Multi-branch call routing

---

## 7. REQUESTS & VALIDATION

**File**: `/var/www/api-gateway/app/Http/Requests/RetellWebhookRequest.php`
**Purpose**: Validates incoming Retell webhook payloads
**Fields Validated**: call_id, status, duration, transcript, etc.

---

## 8. JOBS & ASYNC PROCESSING

**File**: `/var/www/api-gateway/app/Jobs/ProcessRetellCallJob.php`
**Purpose**: Asynchronous processing of Retell calls
**Uses**: Queue workers for call analysis and data sync
**Triggers**: After call completion webhook

---

## 9. CONSOLE COMMANDS

### 9.1 Webhook & Configuration
**File**: `/var/www/api-gateway/app/Console/Commands/ConfigureRetellWebhook.php`
**Purpose**: Setup webhook configuration in Retell
**Registers**: Webhook endpoints and settings

**File**: `/var/www/api-gateway/app/Console/Commands/ValidateRetellCostsCommand.php`
**Purpose**: Validate cost calculations
**Usage**: Cost accuracy verification

**File**: `/var/www/api-gateway/app/Console/Commands/RollbackRetellCostsCommand.php`
**Purpose**: Rollback cost calculations
**Usage**: Error correction and cleanup

**File**: `/var/www/api-gateway/app/Console/Commands/RecalculateRetellCostsCommand.php`
**Purpose**: Recalculate call costs
**Usage**: Cost audit and reconciliation

---

### 9.2 Data Sync & Import
**File**: `/var/www/api-gateway/app/Console/Commands/SyncRetellCalls.php`
**Purpose**: Synchronize calls from Retell API
**Fetches**: Recent calls from Retell
**Updates**: Local database with call data

**File**: `/var/www/api-gateway/app/Console/Commands/RetellImportCommand.php`
**Purpose**: Bulk import of Retell data
**Usage**: Initial setup or backfill

---

### 9.3 Testing & Monitoring
**File**: `/var/www/api-gateway/app/Console/Commands/TestRetellIntegration.php`
**Purpose**: Test Retell integration health
**Checks**: API connectivity, webhook configuration, function calls

**File**: `/var/www/api-gateway/app/Console/Commands/MonitorRetellHealth.php`
**Purpose**: Monitor Retell integration health
**Metrics**: Call success rates, latency, errors

---

## 10. FILAMENT ADMIN RESOURCES

### 10.1 Retell Agent Management
**File**: `/var/www/api-gateway/app/Filament/Resources/RetellAgentResource.php`
**Purpose**: Admin UI for managing Retell agents
**Pages**:
- `ListRetellAgents.php` - List all agents
- `CreateRetellAgent.php` - Create new agent
- `EditRetellAgent.php` - Edit existing agent
- `ViewRetellAgent.php` - View agent details

**Features**:
- Agent configuration management
- Voice settings
- LLM model selection
- Prompt management
- Performance metrics

---

### 10.2 Call Session Management
**File**: `/var/www/api-gateway/app/Filament/Resources/RetellCallSessionResource.php`
**Purpose**: Admin UI for viewing call sessions
**Pages**:
- `ListRetellCallSessions.php` - List sessions
- `ViewRetellCallSession.php` - View session details

**Features**:
- Session metadata
- Timeline view
- Call transcript
- Function traces
- Error logs

---

## 11. POLICIES & AUTHORIZATION

**File**: `/var/www/api-gateway/app/Policies/RetellCallSessionPolicy.php`
**Purpose**: Authorization rules for RetellCallSession
**Controls**: Who can view/edit/delete call sessions
**Context**: Multi-tenant company isolation

---

## 12. TESTING

### 12.1 Feature Tests
**File**: `/var/www/api-gateway/tests/Feature/RetellPolicyIntegrationTest.php`
**Purpose**: Tests Retell policy integration
**Covers**: Policy engine with Retell appointments

---

### 12.2 Unit Tests
**File**: `/var/www/api-gateway/tests/Unit/Middleware/VerifyRetellWebhookSignatureTest.php`
**Purpose**: Tests webhook signature verification
**Ensures**: Security of webhook validation

**File**: `/var/www/api-gateway/tests/Unit/Controllers/RetellFunctionCallHandlerCanonicalCallIdTest.php`
**Purpose**: Tests canonical call_id extraction
**Validates**: RCA 2025-11-03 fix for call_id handling

---

## 13. API ROUTES SUMMARY

### Webhook Routes
```
POST  /webhook                              [Legacy compatibility]
POST  /webhooks/retell                      [Main webhook]
GET   /webhooks/retell/diagnostic           [Diagnostic]
POST  /webhooks/retell/function             [Function calls]
POST  /webhooks/retell/function-call        [Function alias]
POST  /webhooks/retell/collect-appointment  [Specific function]
POST  /webhooks/retell/check-availability   [Specific function]
POST  /webhooks/retell/datetime             [DateTime context]
POST  /webhooks/retell/current-context      [Dynamic context]
```

### API Endpoints (Agent Function Calls)
```
POST  /api/retell/check-customer
POST  /api/retell/initialize-call           [V16: combined]
POST  /api/retell/check-availability
POST  /api/retell/collect-appointment
POST  /api/retell/book-appointment
POST  /api/retell/cancel-appointment
POST  /api/retell/reschedule-appointment
POST  /api/retell/get-customer-appointments

POST  /api/retell/v17/check-availability    [V17]
POST  /api/retell/v17/book-appointment      [V17]

POST  /api/retell/v4/initialize-call        [V4]
POST  /api/retell/v4/get-appointments       [V4]
POST  /api/retell/v4/cancel-appointment     [V4]
POST  /api/retell/v4/reschedule-appointment [V4]
POST  /api/retell/v4/get-services           [V4]

POST  /api/retell/get-available-services
POST  /api/retell/current-time-berlin
POST  /api/retell/function-call             [Legacy fallback]

GET   /api/zeitinfo                         [German timezone info]
```

---

## 14. ROUTE MIDDLEWARE CONFIGURATION

### Signature Verification
- Webhook: `retell.signature` → VerifyRetellWebhookSignature
- Function: `retell.function.signature` → VerifyRetellFunctionSignature
- Whitelist variant: `retell.function.whitelist` → VerifyRetellFunctionSignatureWithWhitelist

### Rate Limiting
- Webhooks: `throttle:60,1` (60 requests/min)
- Function calls: `throttle:100,1` (100 requests/min)
- Booking operations: `throttle:30,60` (30 requests/min)

### Validation
- Call ID: `retell.validate.callid` → ValidateRetellCallId
- Rate limiting by call: RetellCallRateLimiter

---

## 15. KEY ARCHITECTURAL PATTERNS

### 15.1 Call ID Extraction (RCA 2025-11-03)
**Defense-in-Depth Pattern**:
```
Priority Order:
1. call.call_id (server webhook context) - CANONICAL
2. args.call_id (from Retell agent) - VALIDATION
3. Fallback sources - LAST RESORT
```

### 15.2 Multi-Version Support
**Versioning Strategy**:
- V4: Comprehensive conversation flow
- V17: Explicit function nodes
- V16: Combined initialization endpoint
- Legacy: Function-call fallback

### 15.3 Service Injection Pattern
Heavy use of dependency injection for testability:
```php
ServiceSelectionService
ServiceNameExtractor
WebhookResponseService
CallLifecycleService
CallTrackingService
AppointmentCreationService
DateTimeParser
CustomerDataValidator
PhoneNumberResolutionService
AppointmentCustomerResolver
BookingDetailsExtractor
```

### 15.4 Interfaces for Extension
Multiple interfaces for maintainability:
```
BookingDetailsExtractorInterface
ServiceSelectionInterface
PhoneNumberResolutionInterface
WebhookResponseInterface
CallLifecycleInterface
AppointmentCreationInterface
```

---

## 16. CONFIGURATION NOTES

### Test Mode Support
**Feature**: Test mode fallback (2025-11-05)
- `retellai.test_mode_company_id` - Default company for test calls
- `retellai.test_mode_branch_id` - Default branch for test calls
- Prevents errors when test calls don't sync to database

### Webhook Logging
- `retellai.log_webhooks` - Enable/disable webhook event logging
- Configurable per environment

### Unsigned Webhooks
- `retellai.allow_unsigned_webhooks` - Allow unsigned webhooks (development only)

---

## 17. INTEGRATION WITH OTHER SYSTEMS

### Cal.com Integration
- `AppointmentAlternativeFinder` - Find alternative slots
- `CalcomService` - Calendar synchronization
- Availability checking in real-time during calls

### Appointment Management
- `AppointmentCreationService` - Creates appointments after booking
- Links to Appointment model
- Triggers appointment notifications

### Policy Engine
- `AppointmentPolicyEngine` - Enforces cancellation/reschedule policies
- Validates customer permissions

---

## 18. LOGGING & MONITORING

### Log Sanitization
- `LogSanitizer` - Removes PII before logging
- Protects sensitive data in logs

### Call Tracking
- `CallTrackingService` - Metrics collection
- Success rates, latency, errors

### Error Handling
- `RetellErrorLog` - Error logging
- `RetellFunctionTrace` - Function execution tracing

---

## 19. CRITICAL FILES BY FUNCTION

### Webhook Handling
1. RetellWebhookController.php (Primary)
2. VerifyRetellWebhookSignature.php (Security)
3. RetellWebhookRequest.php (Validation)

### Real-time Processing
1. RetellFunctionCallHandler.php (Primary)
2. VerifyRetellFunctionSignature.php (Security)
3. ValidateRetellCallId.php (Defense-in-depth)

### API Integration
1. RetellApiController.php (Primary endpoints)
2. RetellApiClient.php (HTTP communication)
3. RetellGetAppointmentsController.php (Appointments)

### Business Logic
1. AppointmentCreationService.php
2. ServiceSelectionService.php
3. CallLifecycleService.php
4. DateTimeParser.php

### Data Management
1. RetellAgent.php (Model)
2. RetellCallSession.php (Model)
3. Database migrations (5 files)

---

## 20. SECURITY CONSIDERATIONS

### CVSS 9.3 Mitigations
- Webhook signature verification
- Function call signature validation
- Call ID validation (defense-in-depth)
- Rate limiting by call_id
- IP whitelist option
- PII log sanitization

### Multi-tenant Isolation
- BelongsToCompany trait
- Row-level security via company_id
- Policy-based authorization

---

## SUMMARY STATISTICS

- **Total Files**: 94+ files
- **Controllers**: 5 main + 2 API
- **Services**: 1 + 21 in Retell subdirectory
- **Models**: 7 Retell-specific
- **Middleware**: 6 Retell-specific
- **Migrations**: 5 Retell-related
- **Console Commands**: 8 Retell commands
- **Tests**: 3 Retell-specific test files
- **Admin Resources**: 2 (Agent + CallSession)
- **API Routes**: 30+ endpoints
- **Lines of Code**: ~10,000+ (estimated)

---

## QUICK REFERENCE - BY TASK

### Setting up new Retell integration
1. Configure `config/services.php`
2. Run migrations
3. Create RetellAgent record
4. Deploy webhook endpoints
5. Run `ConfigureRetellWebhook` command

### Debugging a failed call
1. Check `RetellCallSession` table
2. Review `RetellErrorLog`
3. Check `RetellFunctionTrace`
4. Review middleware: `ValidateRetellCallId`, signature verification
5. Check `CallTrackingService` logs

### Adding new function call
1. Add method to `RetellFunctionCallHandler`
2. Add route in `routes/api.php`
3. Create/update service in `app/Services/Retell/`
4. Add tests
5. Update Retell agent configuration

### Monitoring health
1. Run `MonitorRetellHealth` command
2. Check Filament dashboard
3. Review call metrics
4. Check error logs

