# API Reference

Generated on: 2025-06-23 16:14:16

## Mcp Endpoints

### `POST` /api/mcp/retell/webhook

**Middleware**: api

---

### `POST` /api/mcp/retell/events

**Controller**: `App\Http\Controllers\RetellWebhookMCPController@processWebhook`

**Description**:
```
Process Retell webhook using MCP architecture
```

**Middleware**: api, throttle:webhook, App\Http\Middleware\VerifyRetellSignature

---

### `POST` /api/mcp/execute

**Controller**: `App\Http\Controllers\Api\MCPController@execute`

**Description**:
```
Execute MCP request through orchestrator
```

**Middleware**: api, throttle:1000,1

---

### `POST` /api/mcp/batch

**Controller**: `App\Http\Controllers\Api\MCPController@batch`

**Description**:
```
Execute batch MCP requests
```

**Middleware**: api, throttle:1000,1

---

### `GET|HEAD` /api/mcp/health

**Controller**: `App\Http\Controllers\Api\MCPController@orchestratorHealth`

**Description**:
```
Get orchestrator health status
```

**Middleware**: api, throttle:1000,1

---

### `GET|HEAD` /api/mcp/metrics

**Controller**: `App\Http\Controllers\Api\MCPController@orchestratorMetrics`

**Description**:
```
Get orchestrator metrics
```

**Middleware**: api, throttle:1000,1

---

### `GET|HEAD` /api/mcp/info

**Controller**: `App\Http\Controllers\Api\MCPController@info`

**Description**:
```
Get MCP server information
```

**Middleware**: api, auth:sanctum

---

### `POST` /api/mcp/webhook/retell

**Controller**: `App\Http\Controllers\Api\MCPWebhookController@handleRetell`

**Description**:
```
Handle Retell webhook through MCP
This bypasses signature verification for easier integration
```

**Middleware**: api, throttle:1000,1

---

### `GET|HEAD` /api/mcp/webhook/test

**Controller**: `App\Http\Controllers\Api\MCPWebhookController@test`

**Description**:
```
Test endpoint for MCP webhook
```

**Middleware**: api, throttle:1000,1

---

### `GET|HEAD` /api/mcp/database/schema

**Controller**: `App\Http\Controllers\Api\MCPController@databaseSchema`

**Middleware**: api, auth:sanctum

---

### `POST` /api/mcp/database/query

**Controller**: `App\Http\Controllers\Api\MCPController@databaseQuery`

**Middleware**: api, auth:sanctum

---

### `POST` /api/mcp/database/search

**Controller**: `App\Http\Controllers\Api\MCPController@databaseSearch`

**Middleware**: api, auth:sanctum

---

### `GET|HEAD` /api/mcp/database/failed-appointments

**Controller**: `App\Http\Controllers\Api\MCPController@databaseFailedAppointments`

**Middleware**: api, auth:sanctum

---

### `GET|HEAD` /api/mcp/database/call-stats

**Controller**: `App\Http\Controllers\Api\MCPController@databaseCallStats`

**Middleware**: api, auth:sanctum

---

### `GET|HEAD` /api/mcp/database/tenant-stats

**Controller**: `App\Http\Controllers\Api\MCPController@databaseTenantStats`

**Middleware**: api, auth:sanctum

---

### `GET|HEAD` /api/mcp/calcom/event-types

**Controller**: `App\Http\Controllers\Api\MCPController@calcomEventTypes`

**Middleware**: api, auth:sanctum

---

### `POST` /api/mcp/calcom/availability

**Controller**: `App\Http\Controllers\Api\MCPController@calcomAvailability`

**Middleware**: api, auth:sanctum

---

### `GET|HEAD` /api/mcp/calcom/bookings

**Controller**: `App\Http\Controllers\Api\MCPController@calcomBookings`

**Middleware**: api, auth:sanctum

---

### `GET|HEAD` /api/mcp/calcom/assignments/{companyId}

**Controller**: `App\Http\Controllers\Api\MCPController@calcomAssignments`

**Middleware**: api, auth:sanctum

---

### `POST` /api/mcp/calcom/sync

**Controller**: `App\Http\Controllers\Api\MCPController@calcomSync`

**Middleware**: api, auth:sanctum

---

### `GET|HEAD` /api/mcp/calcom/test/{companyId}

**Controller**: `App\Http\Controllers\Api\MCPController@calcomTest`

**Middleware**: api, auth:sanctum

---

### `GET|HEAD` /api/mcp/retell/agent/{companyId}

**Controller**: `App\Http\Controllers\Api\MCPController@retellAgent`

**Middleware**: api, auth:sanctum

---

### `GET|HEAD` /api/mcp/retell/agents/{companyId}

**Controller**: `App\Http\Controllers\Api\MCPController@retellAgents`

**Middleware**: api, auth:sanctum

---

### `POST` /api/mcp/retell/call-stats

**Controller**: `App\Http\Controllers\Api\MCPController@retellCallStats`

**Middleware**: api, throttle:1000,1

---

### `POST` /api/mcp/retell/recent-calls

**Controller**: `App\Http\Controllers\Api\MCPController@retellRecentCalls`

**Middleware**: api, throttle:1000,1

---

### `GET|HEAD` /api/mcp/retell/call/{callId}

**Controller**: `App\Http\Controllers\Api\MCPController@retellCallDetails`

**Middleware**: api, auth:sanctum

---

### `POST` /api/mcp/retell/search-calls

**Controller**: `App\Http\Controllers\Api\MCPController@retellSearchCalls`

**Middleware**: api, auth:sanctum

---

### `GET|HEAD` /api/mcp/retell/phone-numbers/{companyId}

**Controller**: `App\Http\Controllers\Api\MCPController@retellPhoneNumbers`

**Middleware**: api, auth:sanctum

---

### `GET|HEAD` /api/mcp/retell/test/{companyId}

**Controller**: `App\Http\Controllers\Api\MCPController@retellTest`

**Middleware**: api, auth:sanctum

---

### `GET|HEAD` /api/mcp/queue/overview

**Controller**: `App\Http\Controllers\Api\MCPController@queueOverview`

**Middleware**: api, auth:sanctum

---

### `GET|HEAD` /api/mcp/queue/failed-jobs

**Controller**: `App\Http\Controllers\Api\MCPController@queueFailedJobs`

**Middleware**: api, auth:sanctum

---

### `GET|HEAD` /api/mcp/queue/recent-jobs

**Controller**: `App\Http\Controllers\Api\MCPController@queueRecentJobs`

**Middleware**: api, auth:sanctum

---

### `GET|HEAD` /api/mcp/queue/job/{jobId}

**Controller**: `App\Http\Controllers\Api\MCPController@queueJobDetails`

**Middleware**: api, auth:sanctum

---

### `POST` /api/mcp/queue/job/{jobId}/retry

**Controller**: `App\Http\Controllers\Api\MCPController@queueRetryJob`

**Middleware**: api, auth:sanctum

---

### `GET|HEAD` /api/mcp/queue/metrics

**Controller**: `App\Http\Controllers\Api\MCPController@queueMetrics`

**Middleware**: api, auth:sanctum

---

### `GET|HEAD` /api/mcp/queue/workers

**Controller**: `App\Http\Controllers\Api\MCPController@queueWorkers`

**Middleware**: api, auth:sanctum

---

### `POST` /api/mcp/queue/search

**Controller**: `App\Http\Controllers\Api\MCPController@queueSearchJobs`

**Middleware**: api, auth:sanctum

---

### `DELETE` /api/mcp/cache/{service}

**Controller**: `App\Http\Controllers\Api\MCPController@clearCache`

**Middleware**: api, throttle:1000,1

---

### `GET|HEAD` /api/mcp/stream

**Controller**: `App\Http\Controllers\Api\MCPStreamController@stream`

**Description**:
```
Stream real-time MCP updates via Server-Sent Events
```

**Middleware**: api, throttle:1000,1, auth:sanctum

---

### `GET|HEAD` /api/mcp/retell/call-stats

**Controller**: `App\Http\Controllers\Api\MCPController@retellCallStats`

**Middleware**: api, auth:sanctum

---

### `GET|HEAD` /api/mcp/retell/recent-calls

**Controller**: `App\Http\Controllers\Api\MCPController@retellRecentCalls`

**Middleware**: api, auth:sanctum

---

### `GET|HEAD` /api/mcp/sentry/issues

**Controller**: `App\Http\Controllers\Api\MCPController@sentryIssues`

**Middleware**: api, auth:sanctum

---

### `GET|HEAD` /api/mcp/sentry/issues/{issueId}

**Controller**: `App\Http\Controllers\Api\MCPController@sentryIssueDetails`

**Middleware**: api, auth:sanctum

---

### `GET|HEAD` /api/mcp/sentry/issues/{issueId}/latest-event

**Controller**: `App\Http\Controllers\Api\MCPController@sentryLatestEvent`

**Middleware**: api, auth:sanctum

---

### `POST` /api/mcp/sentry/issues/search

**Controller**: `App\Http\Controllers\Api\MCPController@sentrySearchIssues`

**Middleware**: api, auth:sanctum

---

### `GET|HEAD` /api/mcp/sentry/performance

**Controller**: `App\Http\Controllers\Api\MCPController@sentryPerformance`

**Middleware**: api, auth:sanctum

---

### `POST` /api/mcp/{service}/cache/clear

**Controller**: `App\Http\Controllers\Api\MCPController@clearCache`

**Middleware**: api, auth:sanctum

---

## Metrics Endpoints

### `GET|HEAD` /api/metrics

**Controller**: `App\Http\Controllers\Api\MCPHealthCheckController@metrics`

**Description**:
```
Prometheus metrics endpoint
```

**Middleware**: api, api

---

## Zeitinfo Endpoints

### `GET|HEAD` /api/zeitinfo

**Controller**: `App\Http\Controllers\ZeitinfoController@jetzt`

**Middleware**: api

---

## Test Endpoints

### `GET|POST|HEAD` /api/test/webhook

**Controller**: `App\Http\Controllers\Api\TestWebhookController@test`

**Description**:
```
Test endpoint to verify webhook connectivity
```

**Middleware**: api

---

### `POST` /api/test/webhook/simulate-retell

**Controller**: `App\Http\Controllers\Api\TestWebhookController@simulateRetellWebhook`

**Description**:
```
Simulate a Retell webhook for testing
```

**Middleware**: api

---

### `POST` /api/test/webhook

**Controller**: `App\Http\Controllers\TestWebhookController@test`

**Description**:
```
Test webhook endpoint - FOR DEVELOPMENT ONLY
```

**Middleware**: api

---

### `POST` /api/test/mcp-webhook

**Middleware**: api

---

### `GET|HEAD` /api/test/calcom-v2/event-types

**Middleware**: api

---

### `GET|HEAD` /api/test/calcom-v2/slots

**Middleware**: api

---

### `POST` /api/test/calcom-v2/book

**Middleware**: api

---

## Retell Endpoints

### `POST` /api/retell/collect-appointment

**Controller**: `App\Http\Controllers\Api\RetellAppointmentCollectorController@collect`

**Description**:
```
Collect appointment data from Retell.ai custom function
This endpoint is called during the conversation to collect structured data
```

**Middleware**: api

---

### `GET|HEAD` /api/retell/collect-appointment/test

**Controller**: `App\Http\Controllers\Api\RetellAppointmentCollectorController@test`

**Description**:
```
Test endpoint to verify the collector is working
```

**Middleware**: api

---

### `POST` /api/retell/webhook-debug

**Controller**: `App\Http\Controllers\Api\RetellWebhookDebugController@handle`

**Description**:
```
Handle Retell webhook WITHOUT signature verification (TEMPORARY FOR DEBUGGING)
WARNING: This bypasses security! Only use for testing!
```

**Middleware**: api

---

### `POST` /api/retell/webhook-nosig

**Controller**: `App\Http\Controllers\Api\RetellWebhookDebugController@handle`

**Description**:
```
Handle Retell webhook WITHOUT signature verification (TEMPORARY FOR DEBUGGING)
WARNING: This bypasses security! Only use for testing!
```

**Middleware**: api

---

### `POST` /api/retell/webhook

**Middleware**: api, verify.retell.signature

---

### `POST` /api/retell/optimized-webhook

**Middleware**: api, verify.retell.signature

---

### `POST` /api/retell/debug-webhook

**Middleware**: api, ip.whitelist

---

### `POST` /api/retell/enhanced-webhook

**Middleware**: api, verify.retell.signature

---

### `POST` /api/retell/mcp-webhook

**Controller**: `App\Http\Controllers\MCPWebhookController@handleRetellWebhook`

**Description**:
```
Handle Retell webhook using MCP services
```

**Middleware**: api

---

### `GET|HEAD` /api/retell/mcp-webhook/stats

**Controller**: `App\Http\Controllers\MCPWebhookController@getWebhookStats`

**Description**:
```
Get webhook processing statistics
```

**Middleware**: api

---

### `GET|HEAD` /api/retell/mcp-webhook/health

**Controller**: `App\Http\Controllers\MCPWebhookController@health`

**Description**:
```
Health check for MCP webhook processor
```

**Middleware**: api

---

### `POST` /api/retell/function-call

**Controller**: `App\Http\Controllers\RetellRealtimeController@handleFunctionCall`

**Description**:
```
Handle real-time function calls from Retell.ai during active calls
This is called when the agent uses collect_appointment_data with verfuegbarkeit_pruefen=true
```

**Middleware**: api, verify.retell.signature

---

## Docs-data Endpoints

### `GET|HEAD` /api/docs-data/metrics

**Controller**: `App\Http\Controllers\Api\DocumentationDataController@metrics`

**Description**:
```
Get live metrics for documentation
```

**Middleware**: api

---

### `GET|HEAD` /api/docs-data/performance

**Controller**: `App\Http\Controllers\Api\DocumentationDataController@performance`

**Description**:
```
Get API endpoint performance data
```

**Middleware**: api

---

### `GET|HEAD` /api/docs-data/workflows

**Controller**: `App\Http\Controllers\Api\DocumentationDataController@workflows`

**Description**:
```
Get workflow status data
```

**Middleware**: api

---

### `GET|HEAD` /api/docs-data/health

**Controller**: `App\Http\Controllers\Api\DocumentationDataController@health`

**Description**:
```
Get system health data
```

**Middleware**: api

---

## Health Endpoints

### `GET|HEAD` /api/health

**Controller**: `App\Http\Controllers\Api\MCPHealthCheckController@health`

**Description**:
```
Overall system health check
```

**Middleware**: api, api

---

### `GET|HEAD` /api/health/comprehensive

**Controller**: `App\Http\Controllers\Api\HealthController@comprehensive`

**Description**:
```
Comprehensive health check endpoint
```

**Middleware**: api

---

### `GET|HEAD` /api/health/service/{service}

**Controller**: `App\Http\Controllers\Api\MCPHealthCheckController@service`

**Description**:
```
Service-specific health check
```

**Middleware**: api, api

---

### `GET|HEAD` /api/health/calcom

**Controller**: `App\Http\Controllers\Api\HealthController@calcomHealth`

**Description**:
```
Cal.com integration health check
```

**Middleware**: api

---

### `GET|HEAD` /api/health/detailed

**Controller**: `App\Http\Controllers\Api\MCPHealthCheckController@detailed`

**Description**:
```
Detailed health check with all services
```

**Middleware**: api, api

---

### `GET|HEAD` /api/health/ready

**Controller**: `App\Http\Controllers\Api\MCPHealthCheckController@ready`

**Description**:
```
Readiness probe for Kubernetes
```

**Middleware**: api, api

---

### `GET|HEAD` /api/health/live

**Controller**: `App\Http\Controllers\Api\MCPHealthCheckController@live`

**Description**:
```
Liveness probe for Kubernetes
```

**Middleware**: api, api

---

## Cookie-consent Endpoints

### `GET|HEAD` /api/cookie-consent/status

**Controller**: `App\Http\Controllers\Api\CookieConsentController@status`

**Description**:
```
Get current consent status
```

**Middleware**: api

---

### `POST` /api/cookie-consent/save

**Controller**: `App\Http\Controllers\Api\CookieConsentController@save`

**Description**:
```
Save cookie consent via AJAX
```

**Middleware**: api

---

### `POST` /api/cookie-consent/accept-all

**Controller**: `App\Http\Controllers\Api\CookieConsentController@acceptAll`

**Description**:
```
Accept all cookies
```

**Middleware**: api

---

### `POST` /api/cookie-consent/reject-all

**Controller**: `App\Http\Controllers\Api\CookieConsentController@rejectAll`

**Description**:
```
Reject all non-essential cookies
```

**Middleware**: api

---

### `POST` /api/cookie-consent/withdraw

**Controller**: `App\Http\Controllers\Api\CookieConsentController@withdraw`

**Description**:
```
Withdraw consent
```

**Middleware**: api

---

## Metrics-test Endpoints

### `GET|HEAD` /api/metrics-test

**Middleware**: api

---

## Calcom Endpoints

### `GET|HEAD` /api/calcom/webhook

**Controller**: `App\Http\Controllers\CalcomWebhookController@ping`

**Description**:
```
Handle ping request from Cal.com
```

**Middleware**: api

---

### `POST` /api/calcom/webhook

**Middleware**: api, calcom.signature

---

### `POST` /api/calcom/book-test

**Middleware**: api

---

## Stripe Endpoints

### `POST` /api/stripe/webhook

**Controller**: `App\Http\Controllers\Api\StripeWebhookController@handle`

**Description**:
```
Handle Stripe webhook using WebhookProcessor.
```

**Middleware**: api, verify.stripe.signature, webhook.replay.protection

---

## Billing Endpoints

### `POST` /api/billing/webhook

**Controller**: `App\Http\Controllers\BillingController@webhook`

**Middleware**: api

---

### `GET|HEAD` /api/billing/checkout

**Controller**: `App\Http\Controllers\BillingController@checkout`

**Middleware**: api, auth:sanctum, input.validation

---

## Webhook Endpoints

### `POST` /api/webhook

**Controller**: `App\Http\Controllers\UnifiedWebhookController@handle`

**Description**:
```
Handle incoming webhook from any source
```

**Middleware**: api

---

### `GET|HEAD` /api/webhook/health

**Controller**: `App\Http\Controllers\UnifiedWebhookController@health`

**Description**:
```
Health check endpoint for webhook processing
```

**Middleware**: api

---

## Log-frontend-error Endpoints

### `POST` /api/log-frontend-error

**Controller**: `App\Http\Controllers\FrontendErrorController@log`

**Middleware**: api, web

---

## Hybrid Endpoints

### `GET|HEAD` /api/hybrid/slots

**Controller**: `App\Http\Controllers\HybridBookingController@getAvailableSlots`

**Description**:
```
Verfügbare Slots abrufen (V1)
```

**Middleware**: api, input.validation

---

### `POST` /api/hybrid/book

**Controller**: `App\Http\Controllers\HybridBookingController@bookAppointment`

**Description**:
```
Termin buchen (V2)
```

**Middleware**: api, input.validation

---

### `POST` /api/hybrid/book-next

**Controller**: `App\Http\Controllers\HybridBookingController@bookNextAvailable`

**Description**:
```
Automatisch nächsten verfügbaren Slot buchen
```

**Middleware**: api, input.validation

---

## Session Endpoints

### `GET|HEAD` /api/session/health

**Controller**: `App\Http\Controllers\SessionHealthController@check`

**Description**:
```
Check session health
```

**Middleware**: api, auth:sanctum

---

### `POST` /api/session/refresh

**Controller**: `App\Http\Controllers\SessionHealthController@refresh`

**Description**:
```
Force session refresh
```

**Middleware**: api, auth:sanctum

---

## Customers Endpoints

### `GET|HEAD` /api/customers

**Controller**: `App\Http\Controllers\API\CustomerController@index`

**Description**:
```
Alle Kunden anzeigen.
```

**Middleware**: api, auth:sanctum, input.validation

---

### `POST` /api/customers

**Controller**: `App\Http\Controllers\API\CustomerController@store`

**Description**:
```
Neuen Kunden anlegen.
```

**Middleware**: api, auth:sanctum, input.validation

---

### `GET|HEAD` /api/customers/{customer}

**Controller**: `App\Http\Controllers\API\CustomerController@show`

**Description**:
```
Bestimmten Kunden anzeigen.
```

**Middleware**: api, auth:sanctum, input.validation

---

### `PUT|PATCH` /api/customers/{customer}

**Controller**: `App\Http\Controllers\API\CustomerController@update`

**Description**:
```
Kundendaten aktualisieren.
```

**Middleware**: api, auth:sanctum, input.validation

---

### `DELETE` /api/customers/{customer}

**Controller**: `App\Http\Controllers\API\CustomerController@destroy`

**Description**:
```
Kunden löschen.
```

**Middleware**: api, auth:sanctum, input.validation

---

## Appointments Endpoints

### `GET|HEAD` /api/appointments

**Controller**: `App\Http\Controllers\API\AppointmentController@index`

**Description**:
```
Alle Termine anzeigen.
```

**Middleware**: api, auth:sanctum, input.validation

---

### `POST` /api/appointments

**Controller**: `App\Http\Controllers\API\AppointmentController@store`

**Description**:
```
Neuen Termin anlegen.
```

**Middleware**: api, auth:sanctum, input.validation

---

### `GET|HEAD` /api/appointments/{appointment}

**Controller**: `App\Http\Controllers\API\AppointmentController@show`

**Description**:
```
Bestimmten Termin anzeigen.
```

**Middleware**: api, auth:sanctum, input.validation

---

### `PUT|PATCH` /api/appointments/{appointment}

**Controller**: `App\Http\Controllers\API\AppointmentController@update`

**Description**:
```
Termindaten aktualisieren.
```

**Middleware**: api, auth:sanctum, input.validation

---

### `DELETE` /api/appointments/{appointment}

**Controller**: `App\Http\Controllers\API\AppointmentController@destroy`

**Description**:
```
Termin löschen.
```

**Middleware**: api, auth:sanctum, input.validation

---

## Staff Endpoints

### `GET|HEAD` /api/staff

**Controller**: `App\Http\Controllers\API\StaffController@index`

**Description**:
```
Alle Mitarbeiter anzeigen.
```

**Middleware**: api, auth:sanctum, input.validation

---

### `POST` /api/staff

**Controller**: `App\Http\Controllers\API\StaffController@store`

**Description**:
```
Neuen Mitarbeiter anlegen.
```

**Middleware**: api, auth:sanctum, input.validation

---

### `GET|HEAD` /api/staff/{staff}

**Controller**: `App\Http\Controllers\API\StaffController@show`

**Description**:
```
Bestimmten Mitarbeiter anzeigen.
```

**Middleware**: api, auth:sanctum, input.validation

---

### `PUT|PATCH` /api/staff/{staff}

**Controller**: `App\Http\Controllers\API\StaffController@update`

**Description**:
```
Mitarbeiterdaten aktualisieren.
```

**Middleware**: api, auth:sanctum, input.validation

---

### `DELETE` /api/staff/{staff}

**Controller**: `App\Http\Controllers\API\StaffController@destroy`

**Description**:
```
Mitarbeiter löschen.
```

**Middleware**: api, auth:sanctum, input.validation

---

## Services Endpoints

### `GET|HEAD` /api/services

**Controller**: `App\Http\Controllers\API\ServiceController@index`

**Description**:
```
Alle Dienstleistungen anzeigen.
```

**Middleware**: api, auth:sanctum, input.validation

---

### `POST` /api/services

**Controller**: `App\Http\Controllers\API\ServiceController@store`

**Description**:
```
Neue Dienstleistung anlegen.
```

**Middleware**: api, auth:sanctum, input.validation

---

### `GET|HEAD` /api/services/{service}

**Controller**: `App\Http\Controllers\API\ServiceController@show`

**Description**:
```
Bestimmte Dienstleistung anzeigen.
```

**Middleware**: api, auth:sanctum, input.validation

---

### `PUT|PATCH` /api/services/{service}

**Controller**: `App\Http\Controllers\API\ServiceController@update`

**Description**:
```
Dienstleistungsdaten aktualisieren.
```

**Middleware**: api, auth:sanctum, input.validation

---

### `DELETE` /api/services/{service}

**Controller**: `App\Http\Controllers\API\ServiceController@destroy`

**Description**:
```
Dienstleistung löschen.
```

**Middleware**: api, auth:sanctum, input.validation

---

## Businesses Endpoints

### `GET|HEAD` /api/businesses

**Controller**: `App\Http\Controllers\API\BusinessController@index`

**Description**:
```
Alle Unternehmen anzeigen.
```

**Middleware**: api, auth:sanctum, input.validation

---

### `POST` /api/businesses

**Controller**: `App\Http\Controllers\API\BusinessController@store`

**Description**:
```
Neues Unternehmen anlegen.
```

**Middleware**: api, auth:sanctum, input.validation

---

### `GET|HEAD` /api/businesses/{business}

**Controller**: `App\Http\Controllers\API\BusinessController@show`

**Description**:
```
Bestimmtes Unternehmen anzeigen.
```

**Middleware**: api, auth:sanctum, input.validation

---

### `PUT|PATCH` /api/businesses/{business}

**Controller**: `App\Http\Controllers\API\BusinessController@update`

**Description**:
```
Unternehmensdaten aktualisieren.
```

**Middleware**: api, auth:sanctum, input.validation

---

### `DELETE` /api/businesses/{business}

**Controller**: `App\Http\Controllers\API\BusinessController@destroy`

**Description**:
```
Unternehmen löschen.
```

**Middleware**: api, auth:sanctum, input.validation

---

## Calls Endpoints

### `GET|HEAD` /api/calls

**Controller**: `App\Http\Controllers\API\CallController@index`

**Description**:
```
Alle Anrufe anzeigen.
```

**Middleware**: api, auth:sanctum, input.validation

---

### `POST` /api/calls

**Controller**: `App\Http\Controllers\API\CallController@store`

**Description**:
```
Neuen Anruf anlegen.
```

**Middleware**: api, auth:sanctum, input.validation

---

### `GET|HEAD` /api/calls/{call}

**Controller**: `App\Http\Controllers\API\CallController@show`

**Description**:
```
Bestimmten Anruf anzeigen.
```

**Middleware**: api, auth:sanctum, input.validation

---

### `PUT|PATCH` /api/calls/{call}

**Controller**: `App\Http\Controllers\API\CallController@update`

**Description**:
```
Anrufdaten aktualisieren.
```

**Middleware**: api, auth:sanctum, input.validation

---

### `DELETE` /api/calls/{call}

**Controller**: `App\Http\Controllers\API\CallController@destroy`

**Description**:
```
Anruf löschen.
```

**Middleware**: api, auth:sanctum, input.validation

---

## Event-management Endpoints

### `GET|HEAD` /api/event-management/sync/event-types/{company}

**Controller**: `App\Http\Controllers\API\EventManagementController@syncEventTypes`

**Description**:
```
Synchronisiere Event-Types für ein Unternehmen
```

**Middleware**: api, auth:sanctum

---

### `GET|HEAD` /api/event-management/sync/team/{company}

**Controller**: `App\Http\Controllers\API\EventManagementController@syncTeamMembers`

**Description**:
```
Synchronisiere Team-Mitglieder für ein Unternehmen
```

**Middleware**: api, auth:sanctum

---

### `POST` /api/event-management/check-availability

**Controller**: `App\Http\Controllers\API\EventManagementController@checkAvailability`

**Description**:
```
Prüfe Verfügbarkeit
```

**Middleware**: api, auth:sanctum

---

### `GET|HEAD` /api/event-management/event-types/{company}/branch/{branch?}

**Controller**: `App\Http\Controllers\API\EventManagementController@getEventTypes`

**Description**:
```
Hole Event-Types für ein Unternehmen
```

**Middleware**: api, auth:sanctum

---

### `POST` /api/event-management/staff-event-assignments

**Controller**: `App\Http\Controllers\API\EventManagementController@manageStaffEventAssignments`

**Description**:
```
Verwalte Mitarbeiter-Event-Type Zuordnungen
```

**Middleware**: api, auth:sanctum

---

### `GET|HEAD` /api/event-management/staff-event-matrix/{company}

**Controller**: `App\Http\Controllers\API\EventManagementController@getStaffEventMatrix`

**Description**:
```
Hole Staff-Event-Type Matrix für ein Unternehmen
```

**Middleware**: api, auth:sanctum

---

## Validation Endpoints

### `GET|HEAD` /api/validation/last-test/{entityId}

**Middleware**: api, auth:sanctum

---

### `POST` /api/validation/run-test/{entityId}

**Middleware**: api, auth:sanctum

---

## Dashboard Endpoints

### `GET|HEAD` /api/dashboard/metrics/operational

**Controller**: `App\Http\Controllers\Api\DashboardMetricsController@operational`

**Description**:
```
Get operational metrics
  "timestamp": "2025-06-18T10:00:00Z",
  "active_calls": 5,
  "queue": {
    "depth": 3,
    "average_wait_time": 45,
    "longest_wait_time": 120,
    "abandoned_rate": 0.05
  },
  "today": {
    "calls": {
      "total": 150,
      "booked": 75,
      "conversion_rate": 50.0
    },
    "appointments": {
      "total": 80,
      "completed": 65,
      "completion_rate": 81.3
    }
  },
  "system_health": {
    "status": "operational",
    "services": {
      "calcom": {
        "status": "operational",
        "uptime": 99.9,
        "response_time": 45
      }
    }
  },
  "conversion_funnel": {
    "stages": [...],
    "overall_conversion": 50.0
  }
}
```

**Middleware**: api, auth:sanctum

---

### `GET|HEAD` /api/dashboard/metrics/financial

**Controller**: `App\Http\Controllers\Api\DashboardMetricsController@financial`

**Description**:
```
Get financial metrics
  "period": "month",
  "date_range": {
    "start": "2025-06-01",
    "end": "2025-06-18"
  },
  "acquisition": {
    "new_customers": 150,
    "marketing_spend": 5000,
    "cac": 33.33,
    "channels": {
      "phone": 105,
      "web": 30,
      "referral": 15
    }
  },
  "revenue": {
    "total_revenue": 45000,
    "appointment_count": 900,
    "average_booking_value": 50,
    "mrr": 45000
  },
  "unit_economics": {
    "ltv": 500,
    "cac": 33.33,
    "ltv_cac_ratio": 15.0,
    "payback_months": 0.8,
    "health_score": "excellent"
  },
  "trends": [...]
}
```

**Middleware**: api, auth:sanctum

---

### `GET|HEAD` /api/dashboard/metrics/branch-comparison

**Controller**: `App\Http\Controllers\Api\DashboardMetricsController@branchComparison`

**Description**:
```
Get branch comparison
  "period": "week",
  "date_range": {
    "start": "2025-06-12",
    "end": "2025-06-18"
  },
  "branches": [
    {
      "branch": {
        "id": 1,
        "name": "Berlin Mitte",
        "location": "Berlin"
      },
      "metrics": {
        "calls": 234,
        "bookings": 156,
        "conversion_rate": 66.7,
        "revenue": 7020,
        "revenue_change": 12
      },
      "rank": 1
    }
  ]
}
```

**Middleware**: api, auth:sanctum

---

### `GET|HEAD` /api/dashboard/metrics/anomalies

**Controller**: `App\Http\Controllers\Api\DashboardMetricsController@anomalies`

**Description**:
```
Get anomalies
  "count": 2,
  "alerts": [
    {
      "type": "conversion_rate",
      "severity": "warning",
      "message": "Conversion rate is 25.5% (normal: 50.0% ± 5.0%)",
      "current_value": 25.5,
      "expected_range": {
        "min": 40.0,
        "max": 60.0
      },
      "detected_at": "2025-06-18T10:00:00Z"
    }
  ],
  "last_check": "2025-06-18T10:00:00Z"
}
```

**Middleware**: api, auth:sanctum

---

### `GET|HEAD` /api/dashboard/metrics/all

**Controller**: `App\Http\Controllers\Api\DashboardMetricsController@all`

**Description**:
```
Get all dashboard metrics in one call
  "operational": {...},
  "financial": {...},
  "branch_comparison": {...},
  "anomalies": {...}
}
```

**Middleware**: api, auth:sanctum

---

## Monitoring Endpoints

### `GET|HEAD` /api/monitoring/dashboard

**Controller**: `App\Http\Controllers\MonitoringController@dashboard`

**Description**:
```
Monitoring dashboard data
```

**Middleware**: api, auth:sanctum, can:view-monitoring

---

### `GET|HEAD` /api/monitoring/alerts

**Controller**: `App\Http\Controllers\Api\MCPHealthCheckController@alerts`

**Description**:
```
Get current alerts
```

**Middleware**: api, api, auth:sanctum

---

### `GET|HEAD` /api/monitoring/service/{service}/metrics

**Controller**: `App\Http\Controllers\Api\MCPHealthCheckController@serviceMetrics`

**Description**:
```
Get service metrics
```

**Middleware**: api, api, auth:sanctum

---

## Mobile Endpoints

### `POST` /api/mobile/device/register

**Controller**: `App\Http\Controllers\API\MobileAppController@registerDevice`

**Description**:
```
Update push notification token
    path="/api/mobile/device/register",
    summary="Register device for push notifications",
    tags={"Mobile API"},
    security={{"bearerAuth":{}}},
    @OA\RequestBody(
        required=true,
        @OA\JsonContent(
            required={"token", "platform"},
            @OA\Property(property="token", type="string"),
            @OA\Property(property="platform", type="string", enum={"ios", "android"}),
            @OA\Property(property="device_id", type="string")
        )
    ),
    @OA\Response(
        response=200,
        description="Device registered successfully"
    )
)
```

**Middleware**: api

---

### `GET|HEAD` /api/mobile/test

**Middleware**: api

---

### `GET|HEAD` /api/mobile/event-types

**Controller**: `App\Http\Controllers\API\MobileAppController@getEventTypes`

**Description**:
```
Get available event types
    path="/api/mobile/event-types",
    summary="Get available event types",
    tags={"Mobile API"},
    security={{"bearerAuth":{}}},
    @OA\Parameter(
        name="company_id",
        in="query",
        description="Filter by company",
        required=false,
        @OA\Schema(type="integer")
    ),
    @OA\Parameter(
        name="branch_id",
        in="query",
        description="Filter by branch",
        required=false,
        @OA\Schema(type="string")
    ),
    @OA\Response(
        response=200,
        description="Successful operation",
        @OA\JsonContent(
            type="object",
            @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/EventType"))
        )
    )
)
```

**Middleware**: api, auth:sanctum

---

### `POST` /api/mobile/availability/check

**Controller**: `App\Http\Controllers\API\MobileAppController@checkAvailability`

**Description**:
```
Check availability for an event type
    path="/api/mobile/availability/check",
    summary="Check availability for event type",
    tags={"Mobile API"},
    security={{"bearerAuth":{}}},
    @OA\RequestBody(
        required=true,
        @OA\JsonContent(
            required={"event_type_id", "date"},
            @OA\Property(property="event_type_id", type="integer"),
            @OA\Property(property="date", type="string", format="date"),
            @OA\Property(property="staff_id", type="string", description="Optional specific staff member")
        )
    ),
    @OA\Response(
        response=200,
        description="Availability information",
        @OA\JsonContent(
            type="object",
            @OA\Property(property="available", type="boolean"),
            @OA\Property(property="slots", type="array", @OA\Items(
                @OA\Property(property="start", type="string"),
                @OA\Property(property="end", type="string"),
                @OA\Property(property="staff_id", type="string"),
                @OA\Property(property="staff_name", type="string")
            ))
        )
    )
)
```

**Middleware**: api, auth:sanctum

---

### `POST` /api/mobile/bookings

**Controller**: `App\Http\Controllers\API\MobileAppController@createBooking`

**Description**:
```
Create a booking
    path="/api/mobile/bookings",
    summary="Create a new booking",
    tags={"Mobile API"},
    security={{"bearerAuth":{}}},
    @OA\RequestBody(
        required=true,
        @OA\JsonContent(
            required={"event_type_id", "staff_id", "customer_data", "start_time"},
            @OA\Property(property="event_type_id", type="integer"),
            @OA\Property(property="staff_id", type="string"),
            @OA\Property(property="start_time", type="string", format="date-time"),
            @OA\Property(property="customer_data", type="object",
                @OA\Property(property="name", type="string"),
                @OA\Property(property="email", type="string"),
                @OA\Property(property="phone", type="string")
            ),
            @OA\Property(property="notes", type="string"),
            @OA\Property(property="send_notifications", type="boolean", default=true)
        )
    ),
    @OA\Response(
        response=201,
        description="Booking created successfully",
        @OA\JsonContent(
            type="object",
            @OA\Property(property="success", type="boolean"),
            @OA\Property(property="booking", ref="#/components/schemas/Appointment")
        )
    )
)
```

**Middleware**: api, auth:sanctum

---

### `GET|HEAD` /api/mobile/appointments

**Controller**: `App\Http\Controllers\API\MobileAppController@getAppointments`

**Description**:
```
Get customer appointments
    path="/api/mobile/appointments",
    summary="Get customer appointments",
    tags={"Mobile API"},
    security={{"bearerAuth":{}}},
    @OA\Parameter(
        name="status",
        in="query",
        description="Filter by status",
        required=false,
        @OA\Schema(type="string", enum={"upcoming", "past", "cancelled"})
    ),
    @OA\Response(
        response=200,
        description="List of appointments"
    )
)
```

**Middleware**: api, auth:sanctum

---

### `DELETE` /api/mobile/appointments/{id}

**Controller**: `App\Http\Controllers\API\MobileAppController@cancelAppointment`

**Description**:
```
Cancel appointment
    path="/api/mobile/appointments/{id}",
    summary="Cancel an appointment",
    tags={"Mobile API"},
    security={{"bearerAuth":{}}},
    @OA\Parameter(
        name="id",
        in="path",
        description="Appointment ID",
        required=true,
        @OA\Schema(type="integer")
    ),
    @OA\RequestBody(
        @OA\JsonContent(
            @OA\Property(property="reason", type="string")
        )
    ),
    @OA\Response(
        response=200,
        description="Appointment cancelled successfully"
    )
)
```

**Middleware**: api, auth:sanctum

---

### `GET|HEAD` /api/mobile/dashboard

**Controller**: `App\Http\Controllers\Api\DashboardMetricsController@all`

**Description**:
```
Get all dashboard metrics in one call
  "operational": {...},
  "financial": {...},
  "branch_comparison": {...},
  "anomalies": {...}
}
```

**Middleware**: api, auth:sanctum

---

## Webhooks Endpoints

### `POST` /api/webhooks

**Controller**: `App\Http\Controllers\UnifiedWebhookController@handle`

**Description**:
```
Handle incoming webhook from any source
```

**Middleware**: api

---

### `POST` /api/webhooks/calcom

**Controller**: `App\Http\Controllers\UnifiedWebhookController@handle`

**Description**:
```
Handle incoming webhook from any source
```

**Middleware**: api

---

### `POST` /api/webhooks/retell

**Controller**: `App\Http\Controllers\UnifiedWebhookController@handle`

**Description**:
```
Handle incoming webhook from any source
```

**Middleware**: api

---

### `POST` /api/webhooks/stripe

**Controller**: `App\Http\Controllers\UnifiedWebhookController@handle`

**Description**:
```
Handle incoming webhook from any source
```

**Middleware**: api

---

### `GET|HEAD` /api/webhooks/health

**Controller**: `App\Http\Controllers\UnifiedWebhookController@health`

**Description**:
```
Health check endpoint for webhook processing
```

**Middleware**: api

---

