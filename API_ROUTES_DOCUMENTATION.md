# API Routes Documentation

## Overview
This document provides a comprehensive overview of all API routes in the AskProAI system, including authentication requirements, rate limits, and webhook signature verification.

## Route Categories

### 1. Public Routes (No Authentication Required)

#### Health Check Endpoints
- `GET /api/health` - System health check
- `GET /api/health/calcom` - Cal.com integration health check
- `GET /api/metrics` - Prometheus metrics endpoint (Rate limit: 100/min)
- `GET /api/metrics-test` - Debug endpoint for metrics

#### Webhook Endpoints (Signature Verification Required)
All webhook endpoints use the unified `WebhookProcessor` service for signature verification and deduplication.

- `GET /api/calcom/webhook` - Cal.com webhook ping (no signature required)
- `POST /api/calcom/webhook` - Cal.com webhook handler
- `POST /api/retell/webhook` - Retell.ai webhook handler
- `POST /api/retell/function-call` - Retell.ai real-time function calls (uses verify.retell.signature middleware)
- `POST /api/stripe/webhook` - Stripe webhook handler
- `POST /api/billing/webhook` - Billing webhook handler (Stripe)
- `POST /api/webhook` - Unified webhook handler (auto-detects provider)
- `GET /api/webhook/health` - Webhook system health check

#### Frontend Support
- `POST /api/log-frontend-error` - Frontend error logging (Rate limit: 10/min)

#### Test Endpoints (Development Only)
- `POST /api/calcom/book-test` - Cal.com booking test
- `GET /api/test/calcom-v2/event-types` - Cal.com V2 event types test
- `GET /api/test/calcom-v2/slots` - Cal.com V2 availability test
- `POST /api/test/calcom-v2/book` - Cal.com V2 booking test

### 2. Hybrid Booking Routes (No Authentication)
These routes support the booking flow without requiring authentication:
- `GET /api/hybrid/slots` - Get available time slots
- `POST /api/hybrid/book` - Book an appointment
- `POST /api/hybrid/book-next` - Book next available slot

### 3. Protected Routes (Authentication Required)

All routes below require `auth:sanctum` middleware.

#### Core API Resources
- `GET|POST|PUT|DELETE /api/customers` - Customer management
- `GET|POST|PUT|DELETE /api/appointments` - Appointment management
- `GET|POST|PUT|DELETE /api/staff` - Staff management
- `GET|POST|PUT|DELETE /api/services` - Service management
- `GET|POST|PUT|DELETE /api/businesses` - Business management
- `GET|POST|PUT|DELETE /api/calls` - Call history management

#### Billing
- `GET /api/billing/checkout` - Billing checkout page

#### Session Management
- `GET /api/session/health` - Session health check
- `POST /api/session/refresh` - Refresh session

#### Event Management
- `GET /api/event-management/sync/event-types/{company}` - Sync Cal.com event types
- `GET /api/event-management/sync/team/{company}` - Sync team members
- `POST /api/event-management/check-availability` - Check availability
- `GET /api/event-management/event-types/{company}/branch/{branch?}` - Get event types
- `POST /api/event-management/staff-event-assignments` - Manage staff assignments
- `GET /api/event-management/staff-event-matrix/{company}` - Get assignment matrix

#### Validation
- `GET /api/validation/last-test/{entityId}` - Get last validation result
- `POST /api/validation/run-test/{entityId}` - Run validation test (SSE stream)

### 4. Mobile App API Routes

#### Public Mobile Routes
- `POST /api/mobile/device/register` - Register mobile device
- `GET /api/mobile/test` - Mobile API test endpoint

#### Protected Mobile Routes (auth:sanctum required)
- `GET /api/mobile/event-types` - Get event types for mobile
- `POST /api/mobile/availability/check` - Check availability
- `POST /api/mobile/bookings` - Create booking
- `GET /api/mobile/appointments` - Get appointments
- `DELETE /api/mobile/appointments/{id}` - Cancel appointment

## Authentication Methods

### 1. Sanctum Authentication
Used for all protected routes. Requires Bearer token in Authorization header:
```
Authorization: Bearer {token}
```

### 2. Webhook Signature Verification
Each webhook provider uses specific signature verification:

#### Retell.ai
- Header: `x-retell-signature`
- Method: HMAC-SHA256
- Verified by: `WebhookProcessor` service

#### Cal.com
- Header: `x-cal-signature-256`
- Method: HMAC-SHA256
- Verified by: `WebhookProcessor` service

#### Stripe
- Header: `stripe-signature`
- Method: Stripe SDK verification
- Verified by: `WebhookProcessor` service

## Rate Limiting

- **Metrics endpoint**: 100 requests per minute
- **Frontend error logging**: 10 requests per minute
- **Other endpoints**: Default Laravel rate limiting (60/min)

## Migration Notes

### Completed Migrations
1. ✅ All webhook controllers now use `WebhookProcessor` for unified handling
2. ✅ Signature verification moved from middleware to service layer
3. ✅ Deduplication implemented via Redis SETNX
4. ✅ Response formats standardized across all webhooks

### Remaining Cleanup Tasks
1. Remove `verify.retell.signature` middleware from `/api/retell/function-call`
2. Consider consolidating test routes under feature flag
3. Review public vs protected route decisions

## Security Considerations

1. **Webhook Security**: All webhooks verify signatures before processing
2. **Multi-tenancy**: All protected routes automatically scope to company via middleware
3. **Rate Limiting**: Critical endpoints have specific rate limits
4. **CORS**: Configured in `config/cors.php`
5. **API Versioning**: Currently no versioning, consider for future

## Best Practices

1. Always use the unified webhook endpoint `/api/webhook` for new integrations
2. Protected routes should use resource controllers when possible
3. Test endpoints should be disabled in production
4. Health checks should not expose sensitive information
5. Rate limits should be monitored and adjusted based on usage

---
*Last Updated: 2025-06-17*