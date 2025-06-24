# Service Layer Documentation

Generated on: 2025-06-23 16:14:16

## Service Architecture

!!! info "Service Count"
    Found **69 services** in the codebase.

## Integration Services

### CalcomEventSyncService

**Public Methods**:

- `syncAllEventTypes()`
  
  Synchronisiert alle Event-Types aus Cal.com
- `linkStaffToEventTypes()`
  
  Verknüpft Mitarbeiter mit Event-Types basierend auf Cal.com Daten

---

### CalcomEventTypeImportService

**Public Methods**:

- `importEventTypes(): array`
- `getEventTypeById($eventTypeId): ?array`

---

### CalcomEventTypeSyncService

**Public Methods**:

- `validateApiKey($apiKey): array`
- `fetchEventTypes($apiKey, $useCache = false): array`
- `checkWebhookStatus($eventTypeId, $apiKey): bool`
- `clearCache($apiKey): void`
- `debugApiCall($apiKey): array`

---

### CalcomImportService

**Public Methods**:

- `importEventTypes()`
- `resolveDuplicate($eventTypeId, $action = "keep_local")`

---

### CalcomMigrationService

Service to handle gradual migration from Cal.com V1 to V2 API
This service provides a unified interface that can switch between
V1 and V2 implementations based on configuration or feature flags.

**Public Methods**:

- `getEventTypes(?string $teamSlug = null)`
  
  Get event types with migration support
- `getAvailableSlots(int $eventTypeId, string $startDate, string $endDate, string $timezone = "Europe\/Berlin")`
  
  Get available slots with migration support
- `bookAppointment(int $eventTypeId, string $startTime, ?string $endTime = null, array $customerData = [], ?string $notes = null, array $metadata = [])`
  
  Create booking with migration support
- `cancelBooking(string $bookingId, ?string $reason = null)`
  
  Cancel booking with migration support
- `getBooking(string $bookingId)`
  
  Get booking by ID with migration support
- `enableV2ForMethod(string $method, int $ttl = 3600): void`
  
  Enable V2 for specific method (for gradual rollout)
- `disableV2ForMethod(string $method): void`
  
  Disable V2 for specific method (for rollback)
- `getMigrationStatus(): array`
  
  Get migration status

---

### CalcomService

**Public Methods**:

- `setApiKey(string $apiKey): self`
  
  Set API key (for dynamic configuration)
- `checkAvailability($eventTypeId, $dateFrom, $dateTo)`
- `bookAppointment($eventTypeId, $startTime, $endTime, $customerData, $notes = null)`
- `getEventTypes($companyId = null)`

---

### CalcomServiceV1Legacy

**Public Methods**:

- `checkAvailability($eventTypeId, $dateFrom, $dateTo)`
- `bookAppointment($eventTypeId, $startTime, $endTime, $customerData, $notes = null)`
- `getEventTypes()`
- `getBookings($params = [])`
- `cancelBooking($bookingId, $reason = null)`

---

### CalcomSyncService

**Public Methods**:

- `syncEventTypesForCompany($companyId)`
  
  Synchronisiere Event-Types für ein Unternehmen
- `syncTeamMembers($companyId)`
  
  Synchronisiere Team-Mitglieder für ein Unternehmen
- `checkAvailability($eventTypeId, $dateFrom, $dateTo, $staffId = null)`
  
  Prüfe Verfügbarkeit für einen Event-Type
- `invalidateEventTypeCache($eventTypeId)`
  
  Cache für einen Event-Type invalidieren
- `invalidateCompanyCache($companyId)`
  
  Cache für alle Event-Types einer Company invalidieren

---

### CalcomV2MigrationService

Cal.com V2 Migration Service
This service provides v2 API compatibility while maintaining
backward compatibility with existing v1 implementations.

**Public Methods**:

- `getEventTypes(string $apiKey): ?array`
  
  Fetch event types using v2 API
- `checkAvailability(string $apiKey, int $eventTypeId, string $startDate, string $endDate, ?string $timezone = null): ?array`
  
  Check availability using v2 API
- `createBooking(string $apiKey, array $bookingData): ?array`
  
  Create a booking using v2 API
- `testConnection(string $apiKey): array`
  
  Test API connection and permissions

---

### CalcomV2Service

Cal.com V2 API Integration Service
Provides comprehensive integration with Cal.com's V2 API for calendar management.
This service handles:
- Event type management and synchronization
- Availability checking with real-time slot calculation
- Booking creation and management
- Circuit breaker pattern for fault tolerance
- Rate limiting to respect API quotas
- Automatic retries with exponential backoff

**Public Methods**:

- `getMe()`
  
  Get current user info (v2)
- `getUsers()`
  
  V1 API für Users nutzen
- `getEventTypes()`
  
  V1 API für Event-Types nutzen
- `getEventTypeDetails($eventTypeId)`
  
  Get detailed information for a specific event type
- `checkAvailability($eventTypeId, $date, $timezone = "Europe\/Berlin")`
  
  V2 API für Verfügbarkeiten - mit korrektem Slot-Flattening
- `bookAppointment($eventTypeId, $startTime, $endTime, $customerData, $notes = null)`
  
  Create a booking in Cal.com calendar
  Books an appointment slot in Cal.com using the V1 API for compatibility.
  This method handles customer data validation, time slot verification,
  and creates the booking with proper error handling.
    name: string,
    email: string,
    phone?: string,
    metadata?: array
  } $customerData Customer information
    success: bool,
    booking_id?: int,
    booking_uid?: string,
    error?: string,
    details?: array
  }
- `getBookings($params = [])`
  
  Hole alle Bookings mit Paginierung
- `getBooking($bookingId)`
  
  Hole ein einzelnes Booking
- `getSchedules()`
  
  Get all schedules - Note: V2 doesn't have schedules endpoint, use V1
- `getTeams()`
  
  Get teams (v2)
- `getTeamEventTypes($teamId)`
  
  Get team event types (v2)
- `getWebhooks()`
  
  Get webhooks (v2)
- `createWebhook($subscriberUrl, $triggers = ["BOOKING_CREATED","BOOKING_CANCELLED","BOOKING_RESCHEDULED"])`
  
  Create webhook (v2)
- `cancelBooking($bookingId, $reason = null)`
  
  Cancel booking (v2)
- `rescheduleBooking($bookingId, $start, $reason = null)`
  
  Reschedule booking (v2)
- `getSlots($eventTypeId, $startDate, $endDate, $timeZone = "Europe\/Berlin")`
  
  Get available time slots for an event type
  Uses V2 API
- `updateBooking($bookingId, array $updateData)`
  
  Update an existing booking

---

### RetellAgentService

**Public Methods**:

- `getAgentDetails($agentId)`
- `getAgentStatistics($agentId, $days = 7)`
- `listAgents()`
- `validateAgentConfiguration($agentId)`

---

### RetellService

**Public Methods**:

- `getAgents()`
  
  Alle Agenten abrufen
- `getAgent($agentId)`
  
  Einzelnen Agenten abrufen
- `updateAgent($agentId, array $data)`
  
  Update agent metadata
- `clearCache()`
  
  Cache leeren
- `buildInboundResponse($agentId, $fromNumber = null, $dynamicVariables = [])`
  
  Build response for inbound calls with dynamic variables

---

### RetellV2Service

**Public Methods**:

- `createPhoneCall(array $payload): array`
  
   Einen Anruf starten.
   Erforderlich   : from_number  (+E.164)
   Entweder ODER  : to_number    (+E.164)  **oder**  agent_id
- `createAgent(array $config): array`
  
  Create a new agent
- `updateAgent(string $agentId, array $config): array`
  
  Update existing agent
- `getAgent(string $agentId): ?array`
  
  Get agent details
- `listAgents(): array`
  
  List all agents
- `deleteAgent(string $agentId): bool`
  
  Delete an agent
- `updatePhoneNumber(string $phoneNumber, array $config): array`
  
  Update phone number configuration
- `getCall(string $callId): ?array`
  
  Get call details
- `listCalls(int $limit = 50): array`
  
  List recent calls
- `listPhoneNumbers(): array`
  
  List all phone numbers
- `getPhoneNumber(string $phoneNumberId): ?array`
  
  Get phone number details
- `getAgentPrompt(string $agentId): ?string`
  
  Get agent prompt only

---

### StripeInvoiceService

**Public Methods**:

- `ensureStripeCustomer(App\Models\Company $company): ?string`
  
  Create or update Stripe customer for a company.
- `createInvoiceForBillingPeriod(App\Models\BillingPeriod $billingPeriod): ?App\Models\Invoice`
  
  Create invoice for a billing period.
- `processWebhook(array $event): void`
  
  Process Stripe webhook for invoice events.
- `createManualInvoice(App\Models\Company $company, array $items, ?int $branchId = null): ?App\Models\Invoice`
  
  Create manual invoice for additional services.

---

## Business Services

### AppointmentBookingService

Central service for managing appointment bookings across all channels
This service orchestrates the complete appointment booking flow, including:
- Customer management (find/create)
- Service and staff validation
- Availability checking with time slot locking
- Calendar integration (Cal.com)
- Notification sending
- Transaction management

**Public Methods**:

- `bookFromPhoneCall($callOrData, ?array $appointmentData = null): array`
  
  Book an appointment from a phone call with AI-extracted data
  Handles the complete phone-to-appointment flow, supporting both legacy
  format and the new collect_appointment_data format from Retell.ai
    success: bool,
    appointment: Appointment|null,
    message: string,
    confirmation_number?: string,
    errors?: array
  }
- `cleanupExpiredLocks(): int`
  
  Clean up expired locks (delegates to TimeSlotLockManager)
- `extendLock(string $lockToken, int $additionalMinutes = 5): bool`
  
  Extend an existing lock (useful for long-running operations)
- `isSlotLocked($staff, $startTime, $endTime): bool`
  
  Check if a time slot is currently locked

---

### AppointmentService

**Public Methods**:

- `create(array $data): App\Models\Appointment`
  
  Create new appointment
- `update(int $appointmentId, array $data): App\Models\Appointment`
  
  Update appointment
- `cancel(int $appointmentId, ?string $reason = null): bool`
  
  Cancel appointment
- `checkAvailability(int $staffId, $startTime, $endTime, ?int $excludeAppointmentId = null): bool`
  
  Check availability
- `getAvailableSlots(int $staffId, Carbon\Carbon $date, int $duration = 30): array`
  
  Get available time slots
- `complete(int $appointmentId, array $data = []): bool`
  
  Complete appointment
- `markAsNoShow(int $appointmentId): bool`
  
  Mark as no-show
- `getStatistics(Carbon\Carbon $startDate, Carbon\Carbon $endDate): array`
  
  Get appointment statistics

---

### CustomerPortalService

**Public Methods**:

- `enablePortalAccess(App\Models\CustomerAuth $customer, ?string $password = null): bool`
  
  Enable portal access for a customer.
- `disablePortalAccess(App\Models\CustomerAuth $customer): bool`
  
  Disable portal access for a customer.
- `sendMagicLink(App\Models\CustomerAuth $customer, string $token): bool`
  
  Send magic link to customer.
- `getPortalUrl(App\Models\Company $company): string`
  
  Generate portal URL for company.
- `canAccessPortal(App\Models\CustomerAuth $customer): bool`
  
  Check if customer can access portal.
- `getPortalFeatures(App\Models\Company $company): array`
  
  Get portal features for company.
- `getCustomerStats(App\Models\CustomerAuth $customer): array`
  
  Get portal statistics for customer.
- `bulkEnablePortalAccess(App\Models\Company $company, array $customerIds = []): array`
  
  Bulk enable portal access.

---

### CustomerService

**Public Methods**:

- `findByPhone(string $phone, int $companyId): ?App\Models\Customer`
  
  Find customer by phone with caching
- `create(array $data): App\Models\Customer`
  
  Create new customer
- `update(int $customerId, array $data): App\Models\Customer`
  
  Update customer
- `mergeDuplicates(int $primaryId, int $duplicateId): App\Models\Customer`
  
  Merge duplicate customers
- `getHistory(int $customerId): array`
  
  Get customer history
- `findPotentialDuplicates(int $customerId): Illuminate\Support\Collection`
  
  Search for potential duplicates
- `block(int $customerId, string $reason): void`
  
  Block customer
- `unblock(int $customerId): void`
  
  Unblock customer
- `addTag(int $customerId, string $tag): void`
  
  Add tag to customer
- `removeTag(int $customerId, string $tag): void`
  
  Remove tag from customer
- `getByTag(string $tag): Illuminate\Support\Collection`
  
  Get customers by tag
- `export(int $customerId): array`
  
  Export customer data

---

### SmartBookingService

SmartBookingService - Der zentrale Service für alle Terminbuchungen
Konsolidiert die Funktionalität von:
- AppointmentService
- BookingService
- Teile von CallService

**Public Methods**:

- `handleIncomingCall(array $webhookData): ?App\Models\Appointment`
  
  Hauptmethode: Verarbeitet einen eingehenden Anruf und bucht einen Termin

---

## Other Services

### AnomalyDetectionService

**Public Methods**:

- `detectSystemAnomalies(): array`
  
  Detect anomalies across the entire system
- `getSeverityScore(string $severity): int`
  
  Get severity score for prioritization
- `getSystemRecommendations(array $anomalies): array`
  
  Get recommendations based on current anomalies

---

### AvailabilityChecker

**Public Methods**:

- `checkAvailabilityFromRequest($request)`
  
  Parse eine Anfrage und prüfe Verfügbarkeiten intelligent
- `checkAvailability($eventTypeId, $dateFrom, $dateTo, $staffId = null, $branchId = null)`
  
  Prüfe Verfügbarkeit mit allen Business-Regeln
- `findNextAvailableSlot($eventTypeId, $staffId = null, $branchId = null)`
  
  Finde nächsten verfügbaren Termin

---

### AvailabilityService

**Public Methods**:

- `checkRealTimeAvailability(string $staffId, int $eventTypeId, Carbon\Carbon $date): array`
  
  Prüfe Echtzeit-Verfügbarkeit für einen Mitarbeiter
- `checkMultipleStaffAvailability(array $staffIds, int $eventTypeId, Carbon\Carbon $date): Illuminate\Support\Collection`
  
  Prüfe Verfügbarkeit für mehrere Mitarbeiter gleichzeitig
- `getNextAvailableSlot(string $staffId, int $eventTypeId, Carbon\Carbon $fromDate): ?array`
  
  Finde nächsten verfügbaren Slot

---

### CacheService

**Public Methods**:

- `getEventTypes(int $companyId, callable $callback)`
  
  Get Cal.com event types with caching
- `getCustomerByPhone(string $phone, int $companyId, callable $callback)`
  
  Get customer by phone with caching
- `getAvailability(int $staffId, string $date, callable $callback)`
  
  Get appointment availability with caching
- `getCompanySettings(int $companyId, callable $callback)`
  
  Get company settings with caching
- `getStaffSchedules(int $staffId, callable $callback)`
  
  Get staff schedules with caching
- `getServiceLists(int $companyId, ?int $branchId, callable $callback)`
  
  Get service lists with caching
- `clearCompanyCache(int $companyId): void`
  
  Clear cache by company
- `clearStaffCache(int $staffId): void`
  
  Clear cache by staff
- `clearCustomerCache(string $identifier): void`
  
  Clear cache by customer
- `clearAppointmentsCache(string $date): void`
  
  Clear appointments cache by date
- `clearEventTypesCache(): void`
  
  Clear all event types cache

---

### CacheWarmer

**Public Methods**:

- `warmInvoiceCaches(): void`
  
  Warm invoice-related caches
- `isUsageBasedInvoice(App\Models\Invoice $invoice): bool`
  
  Check if an invoice is usage-based using cache

---

### CalService

**Public Methods**:

- `getEventTypes()`
- `createBooking($data)`

---

### CallDataRefresher

**Public Methods**:

- `refresh(App\Models\Call $call): bool`

---

### CallService

**Public Methods**:

- `processWebhook(array $webhookData): App\Models\Call`
  
  Process incoming call webhook
- `getStatistics(Carbon\Carbon $startDate, Carbon\Carbon $endDate): array`
  
  Get call statistics
- `refreshCallData(int $callId): bool`
  
  Refresh call data from Retell
- `markAsFailed(int $callId, string $reason): void`
  
  Mark call as failed

---

### CallbackService

**Public Methods**:

- `createFromFailedBooking(App\Models\Call $call, string $reason, array $bookingData, ?string $errorDetails = null): App\Models\CallbackRequest`
  
  Create callback request from failed booking
- `sendDailySummary(App\Models\Company $company, string $email): void`
  
  Send daily summary email
- `autoCloseOldCallbacks(): int`
  
  Auto-close old callbacks
- `getStatistics(App\Models\Company $company, ?Carbon\Carbon $from = null, ?Carbon\Carbon $to = null): array`
  
  Get callback statistics for company

---

### CompanyService

**Public Methods**:

- `getSettings(int $companyId): ?array`
  
  Get company settings with caching
- `updateSettings(int $companyId, array $settings): bool`
  
  Update company settings
- `updateConfiguration(int $companyId, array $data): bool`
  
  Update company configuration
- `getCompany(int $companyId): ?App\Models\Company`
  
  Get company by ID with caching
- `isActive(int $companyId): bool`
  
  Check if company is active
- `isInTrial(int $companyId): bool`
  
  Check if company is in trial
- `getNotificationEmails(int $companyId): array`
  
  Get company notification emails
- `getCalendarConfig(int $companyId): array`
  
  Get company calendar configuration

---

### ConflictDetectionService

**Public Methods**:

- `detectConflicts(string $staffId, Carbon\Carbon $startTime, Carbon\Carbon $endTime, ?int $excludeAppointmentId = null): array`
  
  Erkenne Konflikte für eine neue Buchung
- `batchConflictCheck(array $appointments): array`
  
  Batch-Konfliktprüfung für mehrere Termine

---

### CookieConsentService

**Public Methods**:

- `hasConsent(?string $category = null): bool`
  
  Check if user has given consent
- `getCurrentConsent(): ?array`
  
  Get current consent status
- `saveConsent(array $preferences): App\Models\CookieConsent`
  
  Save consent preferences
- `withdrawConsent(): void`
  
  Withdraw consent
- `getCookieCategories(): array`
  
  Get cookie categories with descriptions
- `shouldShowBanner(): bool`
  
  Check if cookie banner should be shown
- `getConsentStatistics(int $companyId): array`
  
  Get consent statistics for a company

---

### CurrencyConverter

**Public Methods**:

- `centsToEuros(float $cents): float`
  
  Convert cents to euros
- `dollarsToEuros(float $dollars): float`
  
  Convert dollars to euros
- `getExchangeRate($date = null): float`
  
  Get current exchange rate (cached)
- `convertRetellCostToEuros($costData): float`
  
  Convert Retell cost structure to euros
- `formatCostBreakdown(array $costBreakdown): array`
  
  Format cost breakdown for storage
  Konvertiert alle Cent-Werte zu Euro-Werten

---

### DashboardRouteResolver

**Public Methods**:

- `resolve(string $slug): ?string`
  
  Resolve a dashboard route by its slug
- `getDefault(): string`
  
  Get the default dashboard route
- `exists(string $slug): bool`
  
  Check if a dashboard exists
- `getAllDashboards(): array`
  
  Get all available dashboards

---

### DatevExportService

**Public Methods**:

- `exportInvoices(App\Models\Company $company, Carbon\Carbon $startDate, Carbon\Carbon $endDate, string $format = "EXTF"): array`
  
  Exportiert Rechnungen im DATEV-Format

---

### EagerLoadingAnalyzer

**Public Methods**:

- `startAnalysis(): void`
  
  Start analyzing queries for N+1 problems
- `stopAnalysis(): array`
  
  Stop analyzing and return results
- `analyzeModel(string $modelClass): array`
  
  Analyze a specific model for potential N+1 issues

---

### EncryptionService

**Public Methods**:

- `encrypt(?string $value): ?string`
  
  Encrypt sensitive data
- `decrypt(?string $value): ?string`
  
  Decrypt sensitive data
- `isEncrypted(string $value): bool`
  
  Check if a value is encrypted
- `rotateKey(array $models, array $fields): void`
  
  Rotate encryption key (requires APP_KEY rotation)

---

### EventTypeMatchingService

**Public Methods**:

- `findMatchingEventType(string $serviceRequest, App\Models\Branch $branch, ?string $staffName = null, ?array $timePreference = null): ?array`
  
  Find matching event type based on service request and optional staff preference
- `createMapping(App\Models\Service $service, App\Models\CalcomEventType $eventType, ?array $keywords = null, int $priority = 0): void`
  
  Create or update service to event type mapping

---

### EventTypeNameParser

**Public Methods**:

- `parseEventTypeName(string $eventTypeName): array`
  
  Parse einen Event-Type Namen nach dem Schema: "Filial-Unternehmen-Dienstleistung"
- `validateBranchMatch(string $parsedBranchName, App\Models\Branch $selectedBranch): bool`
  
  Validiere ob der geparste Filialname zur ausgewählten Filiale passt
- `generateEventTypeName(App\Models\Branch $branch, string $serviceName): string`
  
  Generiere den korrekten Event-Type Namen nach Schema
- `analyzeEventTypesForImport(array $eventTypes, App\Models\Branch $targetBranch): array`
  
  Analysiere eine Liste von Event-Types und schlage Zuordnungen vor
- `extractServiceName(string $eventTypeName): string`
  
  Extrahiere nur den Service-Namen aus einem vollständigen Event-Type Namen

---

### FeatureFlagService

**Public Methods**:

- `isEnabled(string $key, ?string $companyId = null, bool $trackUsage = true): bool`
  
  Check if a feature is enabled
- `createOrUpdate(array $data): void`
  
  Create or update a feature flag
- `setOverride(string $key, string $companyId, bool $enabled, ?string $reason = null): void`
  
  Set company override
- `removeOverride(string $key, string $companyId): void`
  
  Remove company override
- `getAllFlags(): array`
  
  Get all feature flags
- `getCompanyOverrides(string $companyId): array`
  
  Get company overrides
- `getUsageStats(string $key, int $hours = 24): array`
  
  Get usage statistics
- `areEnabled(array $keys, ?string $companyId = null): array`
  
  Batch check multiple flags
- `emergencyDisableAll(string $reason): void`
  
  Emergency kill switch - disable all features

---

### FileWatcherService

**Public Methods**:

- `checkForChanges(): array`
  
  Check for file changes and re-index if needed
- `forceReindex(): array`
  
  Force re-index all files
- `getStatus(): array`
  
  Get watcher status
- `setLastCheck(): void`
  
  Set last check time

---

### GdprService

**Public Methods**:

- `exportCustomerData(App\Models\Customer $customer): array`
  
  Export all customer data in a machine-readable format
- `createExportFile(App\Models\Customer $customer): string`
  
  Create a downloadable export file
- `deleteCustomerData(App\Models\Customer $customer, bool $anonymize = true): void`
  
  Delete all customer data (right to be forgotten)

---

### HealthCheckService

**Public Methods**:

- `setCompany(App\Models\Company $company): self`
  
  Set the company context
- `runAll(): App\Contracts\HealthReport`
  
  Run all health checks
- `runCheck(App\Contracts\IntegrationHealthCheck $check): App\Contracts\HealthCheckResult`
  
  Run a specific health check
- `runCheckByName(string $name): ?App\Contracts\HealthCheckResult`
  
  Run check by name
- `getCriticalChecks(): array`
  
  Get all critical checks
- `getChecksByStatus(string $status): array`
  
  Get checks by status
- `attemptAutoFix(): array`
  
  Attempt auto-fix for all checks with issues
- `getSuggestedFixes(): array`
  
  Get suggested fixes for all issues
- `getHealthStatusForBadge(): array`
  
  Get health status for admin badge
- `clearCache(): void`
  
  Clear all health check caches
- `getHistory(string $checkName, int $days = 7): Illuminate\Support\Collection`
  
  Get historical health check results

---

### HorizonHealth

**Public Methods**:

- `ok(): bool`
  
   true = queues healthy, false = stalled/offline */

---

### ImprovedEventTypeNameParser

**Public Methods**:

- `extractCleanServiceName(string $eventTypeName, ?App\Models\Branch $branch = null, ?App\Models\Company $company = null): string`
  
  Extract a clean service name from a marketing-style event type name
- `analyzeEventTypesForImport(array $eventTypes, App\Models\Branch $targetBranch): array`
  
  Improved analyze method that handles marketing-style names better
- `generateEventTypeName(App\Models\Branch $branch, string $serviceName, string $format = "standard"): string`
  
  Generate a standardized event type name with optional format
- `parseEventTypeName(string $eventTypeName): array`
  
  Parse einen Event-Type Namen nach dem Schema: "Filial-Unternehmen-Dienstleistung"
- `validateBranchMatch(string $parsedBranchName, App\Models\Branch $selectedBranch): bool`
  
  Validiere ob der geparste Filialname zur ausgewählten Filiale passt
- `extractServiceName(string $eventTypeName): string`
  
  Extrahiere nur den Service-Namen aus einem vollständigen Event-Type Namen

---

### IntegrationTestService

**Public Methods**:

- `createTestBooking(App\Models\Branch $branch): array`
  
  Create a test booking for a branch
- `testCalcomConnection($apiKey)`
  
  Test Cal.com connection
- `testRetellConnection($apiKey, $agentId = null)`
  
  Test Retell.ai connection

---

### InvoiceComplianceService

**Public Methods**:

- `generateCompliantInvoiceNumber(App\Models\Company $company): string`
  
  Generiert eine GoBD-konforme Rechnungsnummer
  Format: [Mandantenkürzel]-[Jahr]-[Laufende Nr]-[Prüfziffer]
  Beispiel: ASK-2024-00001-7
- `finalizeInvoice(App\Models\Invoice $invoice): void`
  
  Finalisiert eine Rechnung (macht sie unveränderbar)
- `archiveInvoice(App\Models\Invoice $invoice): void`
  
  Archiviert eine Rechnung GoBD-konform
- `createAuditLog(App\Models\Invoice $invoice, string $action, ?array $changes = null): void`
  
  Erstellt Audit-Log Eintrag
- `validateInvoiceCompliance(App\Models\Invoice $invoice): array`
  
  Validiert Rechnungspflichtangaben nach §14 UStG
- `createCancellationInvoice(App\Models\Invoice $originalInvoice): App\Models\Invoice`
  
  Generiert eine Storno-Rechnung
- `canModifyInvoice(App\Models\Invoice $invoice): bool`
  
  Prüft ob eine Rechnung geändert werden darf
- `generateDatevExport(App\Models\Company $company, Carbon\Carbon $startDate, Carbon\Carbon $endDate): array`
  
  Generiert DATEV-kompatible Buchungssätze

---

### KnowledgeBaseService

**Public Methods**:

- `discoverAndIndexDocuments(array $paths = []): array`
  
  Discover and index all documentation files
- `indexFile(string $filePath): ?App\Models\KnowledgeDocument`
  
  Index a single documentation file
- `search(string $query, array $filters = []): Illuminate\Support\Collection`
  
  Search documents

---

### MasterServiceManager

**Public Methods**:

- `getEffectiveServicesForBranch(App\Models\Branch $branch): array`
- `deployServiceToBranches(App\Models\MasterService $service, array $branchIds): void`

---

### MobileDetector

**Public Methods**:

- `setRequest(Illuminate\Http\Request $request): self`
- `isMobile(): bool`
- `isTablet(): bool`
- `isDesktop(): bool`
- `getDeviceType(): string`
- `getViewport(): array`
- `getUserAgent(): ?string`
- `isIOS(): bool`
- `isAndroid(): bool`
- `isTouchDevice(): bool`
- `getOS(): string`
- `getBrowser(): string`
- `supportsWebP(): bool`
- `supportsPWA(): bool`
- `getDevicePixelRatio(): float`

---

### NavigationService

**Public Methods**:

- `getResourceGroup(string $resource): ?string`
  
  Get navigation group for a resource
- `getGroupLabel(string $group): string`
  
  Get navigation group label
- `getGroupSort(string $group): int`
  
  Get navigation group sort order
- `getGroupIcon(string $group): string`
  
  Get navigation group icon
- `getResourceSort(string $resource, int $default = 10): int`
  
  Get sort order for a resource within its group
- `canViewGroup(string $group, $user = null): bool`
  
  Check if user has permission to view a navigation group
- `getVisibleGroups($user = null): array`
  
  Get all visible groups for a user
- `getNavigationForResource(string $resourceClass): array`
  
  Get navigation structure for use in Filament resources
- `registerWithFilament(): void`
  
  Register navigation groups with Filament
  Call this in a service provider
- `getBreadcrumbs(string $resourceClass, array $additionalCrumbs = []): array`
  
  Get breadcrumb structure for a resource
- `getActionLabel(string $action): string`
  
  Get a consistent label for common actions

---

### NotificationService

**Public Methods**:

- `sendAppointmentReminders(): void`
  
  Sende Terminerinnerungen
- `sendAppointmentConfirmation(App\Models\Appointment $appointment): void`
  
  Sende Terminbestätigung

---

### OnboardingService

**Public Methods**:

- `getProgress(App\Models\Company $company, App\Models\User $user): array`
  
  Get or create onboarding progress for a company/user
- `updateProgress(App\Models\Company $company, App\Models\User $user, string $step, array $data = []): array`
  
  Update onboarding progress
- `validateStep(App\Models\Company $company, string $step): bool`
  
  Validate a specific step
- `getSampleData(string $step): array`
  
  Get sample data for a step
- `getChecklist(App\Models\Company $company, string $type = "getting_started"): array`
  
  Get checklist for a company
- `updateChecklistItem(App\Models\Company $company, string $type, string $itemKey, bool $completed): void`
  
  Update checklist item status
- `awardAchievement(App\Models\Company $company, string $achievementKey): void`
  
  Award achievement to company
- `getAchievements(App\Models\Company $company): Illuminate\Support\Collection`
  
  Get company achievements
- `getTutorialsForPage(string $pageRoute): Illuminate\Support\Collection`
  
  Get available tutorials for a page
- `markTutorialProgress(App\Models\User $user, int $tutorialId, bool $completed = false): void`
  
  Mark tutorial as viewed/completed
- `getSteps(): array`
  
  Get all defined steps
- `getReadinessScore(App\Models\Company $company): int`
  
  Calculate overall readiness score

---

### PhoneNumberResolver

**Public Methods**:

- `resolve(string $phoneNumber): array`
  
  Simple resolve method for MCPContextResolver compatibility
- `resolveFromWebhook(array $webhookData): array`
  
  Resolve branch and agent from phone number or metadata
  Enhanced for multi-location support
- `normalize(string $phoneNumber): string`
  
  Public method to normalize phone numbers

---

### PricingService

**Public Methods**:

- `calculateCallPrice(App\Models\Call $call): array`
  
  Calculate the customer price for a call
- `getCurrentMonthMinutes($companyId, $branchId = null): float`
  
  Get total minutes used in current month
- `calculateBillingPeriod($companyId, Carbon\Carbon $startDate, Carbon\Carbon $endDate)`
  
  Calculate billing for a period
- `saveBillingPeriod(array $billingData): App\Models\BillingPeriod`
  
  Create or update billing period record

---

### PromptTemplateService

**Public Methods**:

- `getAvailableTemplates(): array`
  
  Get available industry templates
- `renderPrompt(App\Models\Branch $branch, string $industry = "generic", array $additionalData = []): string`
  
  Render a prompt template for a branch
- `generateRetellPrompt(App\Models\Branch $branch, array $config = []): array`
  
  Generate prompt for Retell agent

---

### QueryCache

**Public Methods**:

- `getAppointmentStats($companyId, $dateRange = "month")`
  
  Cache appointment statistics
- `getCustomerMetrics($companyId)`
  
  Cache customer metrics
- `getCallStats($companyId, $days = 30)`
  
  Cache call statistics
- `getStaffPerformance($companyId, $staffId = null, $days = 30)`
  
  Cache staff performance metrics
- `getBranchComparison($companyId, $dateRange = "month")`
  
  Cache branch comparison data
- `clearCompanyCache($companyId)`
  
  Clear cache for a specific company
- `clearAllCaches()`
  
  Clear all query caches
- `getCacheStats()`
  
  Get cache statistics

---

### QueryMonitor

**Public Methods**:

- `enable(): void`
  
  Enable query monitoring
- `getStats(): array`
  
  Get query statistics
- `getRecentQueries(int $limit = 50): array`
  
  Get recent queries
- `getSlowQueries(int $limit = 50): array`
  
  Get slow queries
- `clearStats(): void`
  
  Clear query statistics
- `analyzePatterns(): array`
  
  Analyze query patterns
- `setSlowQueryThreshold(int $milliseconds): void`
  
  Set slow query threshold

---

### QueryOptimizer

**Public Methods**:

- `optimizeEagerLoading(Illuminate\Database\Eloquent\Builder $query, array $relationships): Illuminate\Database\Eloquent\Builder`
  
  Eagerly load relationships to prevent N+1 queries
- `applyIndexHints(Illuminate\Database\Eloquent\Builder $query, string $table, array $indexes): Illuminate\Database\Eloquent\Builder`
  
  Apply index hints for better query performance
- `optimizeAppointmentQuery(Illuminate\Database\Eloquent\Builder $query): Illuminate\Database\Eloquent\Builder`
  
  Optimize appointment queries
- `optimizeCustomerQuery(Illuminate\Database\Eloquent\Builder $query): Illuminate\Database\Eloquent\Builder`
  
  Optimize customer queries
- `optimizeCallQuery(Illuminate\Database\Eloquent\Builder $query): Illuminate\Database\Eloquent\Builder`
  
  Optimize call queries
- `optimizeStaffQuery(Illuminate\Database\Eloquent\Builder $query): Illuminate\Database\Eloquent\Builder`
  
  Optimize staff queries
- `cacheAggregation(string $key, Closure $callback, int $ttl = 300)`
  
  Cache complex aggregation queries
- `analyzeQuery(Illuminate\Database\Eloquent\Builder $query): array`
  
  Analyze query performance
- `getQueryStats(): array`
  
  Get query statistics
- `optimizePagination(Illuminate\Database\Eloquent\Builder $query, int $perPage = 15): Illuminate\Database\Eloquent\Builder`
  
  Optimize pagination queries
- `forceIndex(Illuminate\Database\Eloquent\Builder $query, string $table, string $index): Illuminate\Database\Eloquent\Builder`
  
  Force use of specific index
- `addQueryHint(Illuminate\Database\Eloquent\Builder $query, string $hint): Illuminate\Database\Eloquent\Builder`
  
  Add query hints

---

### ScreenshotAuthService

**Public Methods**:

- `getAuthCookies(): array`
  
  Get authentication cookies for screenshot service
- `authenticateViaHttp(string $email, string $password): ?array`
  
  Authenticate via HTTP request (for testing)

---

### ScreenshotService

**Public Methods**:

- `capture(string $urlOrRoute, array $options = []): string`
  
  Capture a screenshot of a given URL or route
- `captureSync(string $url, string $savePath, array $options = []): string`
  
  Capture screenshot synchronously
- `captureBatch(array $routes, array $options = []): array`
  
  Capture multiple screenshots in batch
- `getLatest(string $route): ?string`
  
  Get the latest screenshot for a route
- `cleanup(int $daysToKeep = 7): int`
  
  Clean up old screenshots

---

### ServiceService

**Public Methods**:

- `getServicesList(int $companyId, ?int $branchId = null): Illuminate\Support\Collection`
  
  Get services list with caching
- `getServicesByCategory(int $companyId, string $category): Illuminate\Support\Collection`
  
  Get services by category
- `getOnlineBookableServices(int $companyId, ?int $branchId = null): Illuminate\Support\Collection`
  
  Get online bookable services
- `getService(int $serviceId): ?App\Models\Service`
  
  Get service by ID with caching
- `create(array $data): App\Models\Service`
  
  Create new service
- `update(int $serviceId, array $data): bool`
  
  Update service
- `delete(int $serviceId): bool`
  
  Delete service
- `getServicesGroupedByCategory(int $companyId, ?int $branchId = null): array`
  
  Get services grouped by category
- `getServiceStatistics(int $serviceId): array`
  
  Get service statistics

---

### SmartEventTypeNameParser

**Public Methods**:

- `extractCleanServiceName(string $eventTypeName): string`
  
  Extrahiere einen sauberen Service-Namen aus Marketing-Text
- `generateNameFormats(App\Models\Branch $branch, string $serviceName): array`
  
  Generiere verschiedene Namensformate für den Import
- `analyzeEventTypesForImport(array $eventTypes, App\Models\Branch $targetBranch): array`
  
  Analysiere Event-Types für Import mit intelligenter Namensgebung

---

### SmartMigrationService

**Public Methods**:

- `analyzeMigration(string $migrationPath): array`
  
  Analyze migration impact before execution
- `executeSafeMigration(string $migrationClass, array $options = []): bool`
  
  Execute migration with zero downtime strategies
- `performOnlineSchemaChange(string $table, callable $schemaChange): bool`
  
  Perform online schema change for large tables

---

### StaffService

**Public Methods**:

- `getSchedule(int $staffId): array`
  
  Get staff schedules with caching
- `getWeeklySchedule(int $staffId, ?Carbon\Carbon $weekStart = null): array`
  
  Get weekly schedule for staff
- `updateWorkingHours(int $staffId, array $workingHours): bool`
  
  Update staff working hours
- `getCompanyStaff(int $companyId): Illuminate\Support\Collection`
  
  Get all staff members for a company
- `getAvailableStaff(int $serviceId, int $branchId, Carbon\Carbon $date): Illuminate\Support\Collection`
  
  Get available staff for a specific service and branch

---

### TaxService

**Public Methods**:

- `getDeterminedTaxRate(App\Models\Company $company, ?string $taxType = "standard"): array`
  
  Ermittelt den anzuwendenden Steuersatz basierend auf Unternehmenskonfiguration
- `getOrCreateTaxRate(App\Models\Company $company, string $name, float $rate)`
  
  Erstellt oder holt einen Steuersatz
- `checkSmallBusinessThreshold(App\Models\Company $company): array`
  
  Überprüft und aktualisiert Kleinunternehmer-Status
- `validateVatId(string $vatId, string $countryCode = "DE"): array`
  
  Validiert eine USt-ID über VIES
- `shouldApplyReverseCharge(App\Models\Company $company, ?string $customerVatId, string $customerCountry): bool`
  
  Prüft ob Reverse-Charge anzuwenden ist
- `generateTaxNote(App\Models\Company $company, App\Models\Invoice $invoice): ?string`
  
  Generiert Steuerhinweis für Rechnung
- `calculateInvoiceTaxes(App\Models\Invoice $invoice): array`
  
  Berechnet Steuern für eine Rechnung
- `syncStripeEUtaxRate(App\Models\Company $company, float $rate, string $name): ?string`
  
  Erstellt Stripe Tax Rate

---

### TutorialService

**Public Methods**:

- `initializeDefaultTutorials(): void`
  
  Initialize default tutorials
- `getTutorialsForCurrentPage(string $currentRoute, App\Models\User $user): Illuminate\Support\Collection`
  
  Get tutorials for current page
- `markAsViewed(App\Models\User $user, int $tutorialId): void`
  
  Mark tutorial as viewed
- `markAsCompleted(App\Models\User $user, int $tutorialId): void`
  
  Mark tutorial as completed
- `getUserProgress(App\Models\User $user): array`
  
  Get user's tutorial progress summary
- `resetProgress(App\Models\User $user): void`
  
  Reset user's tutorial progress
- `getNextTutorial(App\Models\User $user, string $currentRoute): ?object`
  
  Get next unviewed tutorial for user

---

### ValidationService

**Public Methods**:

- `validateBranch(App\Models\Branch $branch): array`

---

### WebhookProcessor

**Public Methods**:

- `process(string $provider, array $payload, array $headers = [], ?string $correlationId = null): array`
  
  Process an incoming webhook request
- `retry(int $webhookEventId): array`
  
  Retry a failed webhook

---

