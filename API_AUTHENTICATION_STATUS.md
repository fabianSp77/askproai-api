# API Authentication Status

## Overview
This document outlines the authentication status of all API endpoints in the AskProAI platform as of June 17, 2025.

## Authentication Methods

### 1. Sanctum Authentication (`auth:sanctum`)
Used for all admin and management APIs. Requires Bearer token in Authorization header.

### 2. Signature Verification
Used for webhook endpoints to verify authenticity of external service callbacks.

### 3. Public Access
Some endpoints are intentionally public for health checks and metrics.

## Protected Endpoints (Require Authentication)

### Admin API Endpoints
All endpoints under these controllers require `auth:sanctum`:

- **CustomerController** (`/api/customers/*`)
  - GET /api/customers - List all customers
  - POST /api/customers - Create customer
  - GET /api/customers/{id} - Show customer
  - PUT /api/customers/{id} - Update customer
  - DELETE /api/customers/{id} - Delete customer

- **AppointmentController** (`/api/appointments/*`)
  - GET /api/appointments - List appointments
  - POST /api/appointments - Create appointment
  - GET /api/appointments/{id} - Show appointment
  - PUT /api/appointments/{id} - Update appointment
  - DELETE /api/appointments/{id} - Delete appointment

- **StaffController** (`/api/staff/*`)
  - GET /api/staff - List staff members
  - POST /api/staff - Create staff member
  - GET /api/staff/{id} - Show staff member
  - PUT /api/staff/{id} - Update staff member
  - DELETE /api/staff/{id} - Delete staff member

- **ServiceController** (`/api/services/*`)
  - GET /api/services - List services
  - POST /api/services - Create service
  - GET /api/services/{id} - Show service
  - PUT /api/services/{id} - Update service
  - DELETE /api/services/{id} - Delete service

- **BusinessController** (`/api/businesses/*`)
  - GET /api/businesses - List businesses
  - POST /api/businesses - Create business
  - GET /api/businesses/{id} - Show business
  - PUT /api/businesses/{id} - Update business
  - DELETE /api/businesses/{id} - Delete business

- **CallController** (`/api/calls/*`)
  - GET /api/calls - List calls
  - POST /api/calls - Create call record
  - GET /api/calls/{id} - Show call
  - PUT /api/calls/{id} - Update call
  - DELETE /api/calls/{id} - Delete call

- **BillingController**
  - GET /api/billing/checkout - Create checkout session (requires auth)

### Event Management API
All endpoints under `/api/event-management/*` require authentication:
- GET /api/event-management/sync/event-types/{company}
- GET /api/event-management/sync/team/{company}
- POST /api/event-management/check-availability
- GET /api/event-management/event-types/{company}/branch/{branch?}
- POST /api/event-management/staff-event-assignments
- GET /api/event-management/staff-event-matrix/{company}

### Mobile API
Protected endpoints under `/api/mobile/*`:
- GET /api/mobile/event-types
- POST /api/mobile/availability/check
- POST /api/mobile/bookings
- GET /api/mobile/appointments
- DELETE /api/mobile/appointments/{id}

### Session Management
- GET /api/session/health
- POST /api/session/refresh

### Validation API
- GET /api/validation/last-test/{entityId}
- POST /api/validation/run-test/{entityId}

## Webhook Endpoints (Signature Verification Only)

These endpoints use signature verification instead of user authentication:

### Retell.ai Webhooks
- POST /api/retell/webhook - Uses WebhookProcessor with signature verification
- POST /api/retell/function-call - Uses `verify.retell.signature` middleware

### Cal.com Webhooks
- POST /api/calcom/webhook - Uses WebhookProcessor with signature verification
- GET /api/calcom/webhook - Ping endpoint (no auth required)

### Stripe Webhooks
- POST /api/stripe/webhook - Stripe payment webhooks
- POST /api/billing/webhook - Legacy billing webhook endpoint

### Unified Webhook Handler
- POST /api/webhook - Auto-detects and routes webhooks from any source
- GET /api/webhook/health - Health check for webhook system

## Public Endpoints (No Authentication Required)

### Health & Monitoring
- GET /api/metrics - Prometheus metrics endpoint (rate limited: 100/min)
- GET /api/metrics-test - Metrics system test endpoint
- GET /api/health/calcom - Cal.com integration health check
- POST /api/log-frontend-error - Frontend error logging (rate limited: 10/min)

### Mobile API Public
- POST /api/mobile/device/register - Device registration
- GET /api/mobile/test - Mobile API test endpoint

### Hybrid Booking API
- GET /api/hybrid/slots - Check available slots
- POST /api/hybrid/book - Book appointment
- POST /api/hybrid/book-next - Book next available slot

### Test Endpoints (Development Only)
- POST /api/calcom/book-test - Cal.com booking test
- GET /api/test/calcom-v2/event-types - Test Cal.com V2 event types
- GET /api/test/calcom-v2/slots - Test Cal.com V2 slots
- POST /api/test/calcom-v2/book - Test Cal.com V2 booking

## Security Considerations

1. **API Keys**: All protected endpoints require a valid Bearer token obtained through Sanctum authentication.

2. **Webhook Security**: Webhook endpoints verify signatures to ensure requests come from legitimate sources (Retell.ai, Cal.com, Stripe).

3. **Rate Limiting**: Public endpoints have rate limiting to prevent abuse:
   - Metrics: 100 requests per minute
   - Error logging: 10 requests per minute

4. **CORS**: Handled by Laravel's CORS middleware for all API routes.

5. **Tenant Isolation**: All authenticated requests are automatically scoped to the user's company/tenant.

## Implementation Notes

- Controllers now include `$this->middleware('auth:sanctum')` in their constructors
- Webhook controllers use signature verification through WebhookProcessor service
- The new `ApiAuthMiddleware` can be used for custom API authentication logic
- Routes are grouped by authentication requirements in `routes/api.php`

## Future Considerations

1. Consider implementing API versioning (v1, v2) for better backward compatibility
2. Add API key authentication as an alternative to Bearer tokens
3. Implement more granular permissions/scopes for API access
4. Add request logging for security audit trails
5. Consider implementing rate limiting per user/API key