# Codebase Concerns

**Analysis Date:** 2026-01-19

## Tech Debt

**Massive Controller - RetellFunctionCallHandler (11,376 lines):**
- Issue: Single controller handles all Retell AI function calls with 11,376 lines
- Files: `app/Http/Controllers/RetellFunctionCallHandler.php`
- Impact: Difficult to maintain, test, and debug; high cognitive load; slow IDE performance
- Fix approach: Extract into dedicated service classes by function domain (availability, booking, customer, parsing)

**Database Schema Mismatch - Sept 21 Backup Compatibility:**
- Issue: Production code contains workarounds for missing columns from Sept 21 database backup
- Files:
  - `app/Models/PhoneNumber.php:14` - SoftDeletes removed
  - `app/Models/CallbackRequest.php:44-45` - SoftDeletes removed
  - `app/Models/Call.php:189,201,551` - Column mapping workarounds
  - `app/Models/Staff.php:164` - Column mapping workarounds
- Impact: Widgets disabled, features incomplete, code littered with backup compatibility comments
- Fix approach: Run pending migrations, restore full schema, remove workarounds

**Disabled Widgets and Resources (17+ components):**
- Issue: Numerous Filament widgets/resources disabled due to missing database tables/columns
- Files:
  - `app/Filament/Widgets/NotificationAnalyticsWidget.php:21-22`
  - `app/Filament/Widgets/PolicyEffectivenessWidget.php:21-22`
  - `app/Filament/Widgets/PolicyTrendWidget.php:20-21`
  - `app/Filament/Widgets/PolicyChartsWidget.php:23-24`
  - `app/Filament/Widgets/CustomerJourneyChart.php:22-23`
  - `app/Filament/Widgets/TimeBasedAnalyticsWidget.php:23-24`
  - `app/Filament/Resources/NotificationQueueResource.php:22-23`
  - `app/Filament/Resources/PricingPlanResource.php:30-31`
  - `app/Filament/Resources/TenantResource.php:34-35`
  - `app/Filament/Resources/WorkingHourResource.php:36`
- Impact: Missing analytics dashboards, incomplete admin functionality
- Fix approach: Create missing database migrations, enable resources

**Unimplemented TODO Comments (75+ instances):**
- Issue: Extensive TODO comments marking incomplete features
- Files:
  - `app/Jobs/SendNotificationJob.php:170,197,220` - SMS/WhatsApp/Push not implemented
  - `app/Jobs/SyncAppointmentToCalcomJob.php:1413-1414` - Monitoring alerts missing
  - `app/Services/BalanceService.php:380` - Email notification not implemented
  - `app/Filament/Pages/SettingsDashboard.php:1140-1210` - API tests not implemented
  - `app/Services/Booking/AvailabilityService.php:242,316` - Holiday checks, segment matching
  - `app/Http/Controllers/Api/CustomerPortal/AppointmentController.php:228,237` - Availability stub
- Impact: Incomplete features, placeholders in production code
- Fix approach: Prioritize TODOs by business impact, implement or remove dead code

**Backup Files in App Directory (45+ files):**
- Issue: Backup files (.backup, .bak, .pre-*) scattered throughout app directory
- Files:
  - `app/Filament/Resources/*.pre-caching-backup` (26 files)
  - `app/Http/Controllers/Api/API_backup/` (entire directory with .bak files)
  - `app/Livewire/AppointmentBookingFlow.php.backup-20251015-094720`
  - `app/Services/Billing/MonthlyBillingAggregator.php.backup_20260112_170811`
- Impact: Code pollution, confusion about current vs old code, git noise
- Fix approach: Archive to separate backup location, remove from codebase

**Deprecated Code Not Removed:**
- Issue: Deprecated methods and classes still present with @deprecated tags
- Files:
  - `app/Http/Controllers/RetellFunctionCallHandler.php:4738,4750,7118,7130,7143` - 5 deprecated methods
  - `app/Services/PhoneNumberNormalizer.php:308,318` - 2 deprecated methods
  - `app/Services/RelativeTimeParser.php:511` - 1 deprecated method
  - `app/Filament/Widgets/OngoingCallsWidget.php:21` - Deprecated widget
  - `app/Filament/Resources/InvoiceResource.php:43` - Legacy resource
- Impact: Maintenance burden, confusion about which code to use
- Fix approach: Complete migration to new implementations, remove deprecated code

## Known Bugs

**Cal.com Phone Number Format Unresolved:**
- Symptoms: Booking sync failures due to phone number format rejection
- Files:
  - `app/Jobs/SyncAppointmentToCalcomJob.php:289-291` - Phone field skipped
  - `app/Services/CalcomV2Client.php:148,175` - Format TODOs
- Trigger: Any appointment sync with phone number
- Workaround: Phone number field is skipped in Cal.com payloads

**Rate Limiter Disabled for Retell Calls:**
- Symptoms: Potential abuse of Retell API endpoints
- Files: `app/Http/Middleware/RetellCallRateLimiter.php:47`
- Trigger: Middleware was blocking legitimate function calls
- Workaround: Entire middleware disabled with comment

**Event Listeners Commented Out:**
- Symptoms: Missing reschedule notifications and manager alerts
- Files: `app/Providers/EventServiceProvider.php:85,105-106`
- Trigger: Appointment rescheduling, callback escalations
- Workaround: None - notifications simply don't fire

## Security Considerations

**Hardcoded env() Calls in Service Classes:**
- Risk: Direct env() calls bypass config caching, inconsistent values
- Files:
  - `app/Services/Retell/CustomerDataValidator.php:60,133` - Fallback email/phone from env()
  - `app/Http/Controllers/RetellFunctionCallHandler.php:3600` - ASYNC_CALCOM_SYNC from env()
- Current mitigation: None
- Recommendations: Move all env() calls to config files

**Model::unguard() Usage:**
- Risk: Mass assignment protection bypassed
- Files:
  - `app/Http/Controllers/TestChecklistController.php:529` - Call::unguard()
  - `app/Services/RetellApiClient.php:325` - unguard() for API sync
- Current mitigation: Only used in controlled contexts
- Recommendations: Use explicit fillable arrays, remove unguard() calls

**Cache Key Tenant Isolation:**
- Risk: Cross-tenant data leakage if cache keys don't include company_id
- Files:
  - `app/Http/Controllers/RetellFunctionCallHandler.php:84-99` - H-001 documented
  - `app/Services/ServiceDesk/IssueCapturingService.php:37-38,238` - H-001 pattern
  - `app/Filament/Resources/CallResource/Widgets/CallStatsOverview.php:44` - Missing tenant isolation
- Current mitigation: H-001 security pattern documented and partially implemented
- Recommendations: Audit all cache keys for tenant isolation, add automated checks

**Broad Exception Catching:**
- Risk: Swallowed exceptions hide errors, security issues masked
- Files: 40+ instances of `catch (Exception $e)` across services
  - `app/Services/PdfService.php` - 7 broad catches
  - `app/Services/ExportService.php` - 9 broad catches
  - `app/Services/Booking/CompositeBookingService.php` - 2 broad catches
- Current mitigation: Most log the error before continuing
- Recommendations: Catch specific exceptions, re-throw unexpected ones

## Performance Bottlenecks

**Sleep Statements in Sync Code:**
- Problem: Blocking sleep() calls in request paths and jobs
- Files:
  - `app/Jobs/SyncAppointmentToCalcomJob.php:1363` - sleep(2)
  - `app/Services/CalcomApiRateLimiter.php:66` - sleep(1)
  - `app/Services/Billing/StripeInvoicingService.php:125` - Retry with sleep
  - `app/Services/CalcomService.php:547,550` - usleep calls
  - `app/Services/CalcomV2Client.php:211` - Exponential backoff 2-8s
- Cause: Rate limiting, retry logic
- Improvement path: Use queue jobs for retries, implement proper async handling

**Large File Processing:**
- Problem: Multiple controllers/services exceed 1000 lines
- Files:
  - `app/Http/Controllers/RetellFunctionCallHandler.php` - 11,376 lines
  - `app/Filament/Resources/CallResource.php` - 3,564 lines
  - `app/Filament/Resources/ServiceResource.php` - 2,778 lines
  - `app/Http/Controllers/Api/RetellApiController.php` - 2,298 lines
  - `app/Jobs/SyncAppointmentToCalcomJob.php` - 2,067 lines
  - `app/Services/CalcomService.php` - 1,902 lines
- Cause: Feature accumulation without refactoring
- Improvement path: Extract into smaller service classes, use composition

**Raw DB Queries Without Optimization:**
- Problem: Multiple DB::raw() and whereRaw() calls
- Files:
  - `app/Traits/OptimizedAppointmentQueries.php:114-133` - Heavy raw SQL
  - `app/Http/Controllers/RetellFunctionCallHandler.php:3008,7185,7207` - LOWER() without index
- Cause: Complex aggregations, case-insensitive searches
- Improvement path: Add appropriate indexes, consider materialized views

## Fragile Areas

**Cal.com Sync Job (SyncAppointmentToCalcomJob):**
- Files: `app/Jobs/SyncAppointmentToCalcomJob.php`
- Why fragile: 2,067 lines, 20+ race condition fixes, complex state machine
- Safe modification: Test with mock Cal.com server, verify all booking scenarios
- Test coverage: Limited unit tests, relies heavily on manual testing

**RetellFunctionCallHandler:**
- Files: `app/Http/Controllers/RetellFunctionCallHandler.php`
- Why fragile: Monolithic controller, handles real-time AI voice calls, 5 deprecated methods still in use
- Safe modification: Never deploy during business hours, have rollback ready
- Test coverage: Partial - `tests/Unit/Services/Retell/` covers services but not controller

**Multi-Tenant Scoping (CompanyScope):**
- Files: `app/Scopes/CompanyScope.php`
- Why fragile: Global scope applied to all tenant-scoped queries, performance fixes added
- Safe modification: Test thoroughly across all tenant scenarios
- Test coverage: `tests/Performance/CompanyScopePerformanceTest.php` exists

## Scaling Limits

**Database Backup Compatibility:**
- Current capacity: Running on Sept 21 backup schema
- Limit: Many features disabled, schema drift from code
- Scaling path: Complete migration to production schema

**Test Coverage Ratio:**
- Current capacity: 219 test files for 1,095 source files (20% file coverage)
- Limit: Confidence for refactoring is low
- Scaling path: Add tests for critical paths before major changes

## Dependencies at Risk

**Cal.com API v1 Deprecation:**
- Risk: Cal.com v1 API deprecated end of 2025, v2 migration in progress
- Impact: All booking sync will break
- Migration plan: Code references v2 in 6+ places, migration underway
- Files:
  - `app/Console/Commands/VerifyTeamEventIds.php:52`
  - `app/Console/Commands/SyncCalcomBookings.php:73`
  - `app/Services/IntegrationService.php:497`

**Direct env() Dependencies:**
- Risk: Config caching incompatible, harder to test
- Impact: Inconsistent behavior between local and production
- Migration plan: Move to config() calls with defaults

## Missing Critical Features

**Notification System Incomplete:**
- Problem: SMS, WhatsApp, Push notifications not implemented
- Blocks: Customer communication, reminder system
- Files: `app/Jobs/SendNotificationJob.php:170-220`

**API Integration Testing:**
- Problem: Retell, Cal.com, OpenAI, Qdrant API tests return mock data
- Blocks: Integration verification in admin panel
- Files: `app/Filament/Pages/SettingsDashboard.php:1140-1210`

## Test Coverage Gaps

**Filament Resources:**
- What's not tested: 50+ Filament resource files
- Files: `app/Filament/Resources/*.php`
- Risk: Admin panel regressions undetected
- Priority: Medium

**Job Classes:**
- What's not tested: Most queue jobs lack unit tests
- Files: `app/Jobs/*.php` (partial coverage only)
- Risk: Background processing failures
- Priority: High

**Controllers:**
- What's not tested: API controllers beyond Retell
- Files: `app/Http/Controllers/Api/*.php`
- Risk: API contract breaks
- Priority: High

**Webhook Handlers:**
- What's not tested: Cal.com webhook handling
- Files: `app/Http/Controllers/CalcomWebhookController.php`
- Risk: Booking sync failures from external triggers
- Priority: High

---

*Concerns audit: 2026-01-19*
