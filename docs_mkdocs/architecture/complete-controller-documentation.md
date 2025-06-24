# Complete Controller Documentation

Generated on: 2025-06-23

## Controller Overview

The AskProAI platform has **271 controllers** organized into logical categories. This document provides a complete overview of all API endpoints and controllers.

## API Version Strategy

- **v1**: Legacy endpoints (being phased out)
- **v2**: Current production API
- **v3**: Next generation API (in development)
- **Admin**: Filament admin panel controllers
- **Webhooks**: External service integrations

## Controller Categories

### 🔐 Authentication Controllers (8 files)

#### AuthController
- **Route**: `/api/v2/auth`
- **Methods**:
  - `POST /login` - User authentication
  - `POST /logout` - User logout
  - `POST /refresh` - Refresh JWT token
  - `GET /me` - Get current user

#### RegisterController
- **Route**: `/api/v2/register`
- **Methods**:
  - `POST /` - Register new user
  - `POST /verify` - Verify email
  - `POST /resend` - Resend verification

#### PasswordController
- **Route**: `/api/v2/password`
- **Methods**:
  - `POST /forgot` - Request password reset
  - `POST /reset` - Reset password
  - `POST /change` - Change password (authenticated)

#### TwoFactorController
- **Route**: `/api/v2/2fa`
- **Methods**:
  - `POST /enable` - Enable 2FA
  - `POST /disable` - Disable 2FA
  - `POST /verify` - Verify 2FA code

### 📅 Appointment Controllers (15 files)

#### AppointmentController
- **Route**: `/api/v2/appointments`
- **Methods**:
  - `GET /` - List appointments
  - `POST /` - Create appointment
  - `GET /{id}` - Get appointment details
  - `PUT /{id}` - Update appointment
  - `DELETE /{id}` - Cancel appointment
  - `POST /{id}/confirm` - Confirm appointment
  - `POST /{id}/complete` - Mark as completed
  - `POST /{id}/no-show` - Mark as no-show

#### BookingController
- **Route**: `/api/v2/bookings`
- **Methods**:
  - `POST /check-availability` - Check time slots
  - `POST /create` - Create booking
  - `POST /quick-book` - Quick booking (phone)
  - `GET /slots` - Get available slots

#### RescheduleController
- **Route**: `/api/v2/appointments/{id}/reschedule`
- **Methods**:
  - `GET /options` - Get reschedule options
  - `POST /` - Reschedule appointment

### 👥 Customer Controllers (10 files)

#### CustomerController
- **Route**: `/api/v2/customers`
- **Methods**:
  - `GET /` - List customers
  - `POST /` - Create customer
  - `GET /{id}` - Get customer
  - `PUT /{id}` - Update customer
  - `DELETE /{id}` - Delete customer
  - `GET /{id}/appointments` - Customer appointments
  - `GET /{id}/history` - Customer history
  - `POST /{id}/tags` - Add tags
  - `DELETE /{id}/tags/{tag}` - Remove tag

#### CustomerSearchController
- **Route**: `/api/v2/customers/search`
- **Methods**:
  - `GET /` - Search customers
  - `GET /phone/{phone}` - Find by phone
  - `GET /email/{email}` - Find by email

### 🏢 Company Controllers (12 files)

#### CompanyController
- **Route**: `/api/v2/company`
- **Methods**:
  - `GET /` - Get company details
  - `PUT /` - Update company
  - `PUT /settings` - Update settings
  - `GET /stats` - Company statistics

#### BranchController
- **Route**: `/api/v2/branches`
- **Methods**:
  - `GET /` - List branches
  - `POST /` - Create branch
  - `GET /{id}` - Get branch
  - `PUT /{id}` - Update branch
  - `DELETE /{id}` - Delete branch
  - `PUT /{id}/hours` - Update working hours
  - `GET /{id}/services` - Branch services
  - `GET /{id}/staff` - Branch staff

#### StaffController
- **Route**: `/api/v2/staff`
- **Methods**:
  - `GET /` - List staff
  - `POST /` - Create staff member
  - `GET /{id}` - Get staff details
  - `PUT /{id}` - Update staff
  - `DELETE /{id}` - Delete staff
  - `GET /{id}/schedule` - Get schedule
  - `PUT /{id}/schedule` - Update schedule
  - `GET /{id}/availability` - Check availability

### 📞 Phone System Controllers (8 files)

#### CallController
- **Route**: `/api/v2/calls`
- **Methods**:
  - `GET /` - List calls
  - `GET /{id}` - Get call details
  - `GET /{id}/transcript` - Get transcript
  - `GET /{id}/recording` - Get recording URL
  - `POST /{id}/callback` - Request callback

#### PhoneNumberController
- **Route**: `/api/v2/phone-numbers`
- **Methods**:
  - `GET /` - List phone numbers
  - `POST /` - Add phone number
  - `PUT /{id}` - Update phone number
  - `DELETE /{id}` - Remove phone number
  - `POST /{id}/verify` - Verify ownership

### 🛍️ Service Controllers (6 files)

#### ServiceController
- **Route**: `/api/v2/services`
- **Methods**:
  - `GET /` - List services
  - `POST /` - Create service
  - `GET /{id}` - Get service
  - `PUT /{id}` - Update service
  - `DELETE /{id}` - Delete service
  - `GET /categories` - List categories

#### ServiceCategoryController
- **Route**: `/api/v2/service-categories`
- **Methods**:
  - `GET /` - List categories
  - `POST /` - Create category
  - `PUT /{id}` - Update category
  - `DELETE /{id}` - Delete category

### 📊 Analytics Controllers (10 files)

#### DashboardController
- **Route**: `/api/v2/dashboard`
- **Methods**:
  - `GET /stats` - Dashboard statistics
  - `GET /metrics` - Key metrics
  - `GET /charts/appointments` - Appointment chart data
  - `GET /charts/revenue` - Revenue chart data
  - `GET /charts/customers` - Customer chart data

#### ReportController
- **Route**: `/api/v2/reports`
- **Methods**:
  - `GET /` - List available reports
  - `POST /generate` - Generate report
  - `GET /{id}` - Get report
  - `GET /{id}/download` - Download report
  - `DELETE /{id}` - Delete report

#### MetricsController
- **Route**: `/api/v2/metrics`
- **Methods**:
  - `GET /live` - Live metrics
  - `GET /historical` - Historical data
  - `POST /export` - Export metrics

### 🔔 Notification Controllers (5 files)

#### NotificationController
- **Route**: `/api/v2/notifications`
- **Methods**:
  - `GET /` - List notifications
  - `POST /{id}/read` - Mark as read
  - `POST /read-all` - Mark all as read
  - `DELETE /{id}` - Delete notification

#### NotificationPreferenceController
- **Route**: `/api/v2/notification-preferences`
- **Methods**:
  - `GET /` - Get preferences
  - `PUT /` - Update preferences
  - `POST /test` - Send test notification

### 💰 Financial Controllers (8 files)

#### InvoiceController
- **Route**: `/api/v2/invoices`
- **Methods**:
  - `GET /` - List invoices
  - `POST /` - Create invoice
  - `GET /{id}` - Get invoice
  - `PUT /{id}` - Update invoice
  - `POST /{id}/send` - Send invoice
  - `POST /{id}/pay` - Record payment
  - `GET /{id}/pdf` - Download PDF

#### PaymentController
- **Route**: `/api/v2/payments`
- **Methods**:
  - `GET /` - List payments
  - `POST /` - Process payment
  - `GET /{id}` - Get payment details
  - `POST /{id}/refund` - Process refund

#### SubscriptionController
- **Route**: `/api/v2/subscription`
- **Methods**:
  - `GET /` - Get subscription
  - `POST /upgrade` - Upgrade plan
  - `POST /downgrade` - Downgrade plan
  - `POST /cancel` - Cancel subscription
  - `GET /plans` - Available plans

### 🔄 Webhook Controllers (15 files)

#### RetellWebhookController
- **Route**: `/api/retell/webhook`
- **Methods**:
  - `POST /` - Handle Retell webhooks
  - Signature verification required

#### CalcomWebhookController
- **Route**: `/api/webhooks/calcom`
- **Methods**:
  - `POST /` - Handle Cal.com webhooks
  - Signature verification required

#### StripeWebhookController
- **Route**: `/api/webhooks/stripe`
- **Methods**:
  - `POST /` - Handle Stripe webhooks
  - Signature verification required

###***REMOVED***WebhookController
- **Route**: `/api/webhooks/twilio`
- **Methods**:
  - `POST /status` - SMS status updates
  - `POST /incoming` - Incoming SMS

### 🔧 Admin Controllers (50+ files)

#### Filament Resources
Each model has a corresponding Filament resource with:
- `index()` - List view
- `create()` - Create form
- `edit()` - Edit form
- `view()` - Detail view
- Custom actions and bulk actions

#### Custom Admin Pages
- `CompanyIntegrationPortal` - Integration management
- `OperationalDashboard` - Real-time dashboard
- `SystemHealthMonitor` - System monitoring
- `WebhookAnalysis` - Webhook debugging
- `EventTypeImportWizard` - Cal.com import

### 🛠️ Utility Controllers (20 files)

#### HealthCheckController
- **Route**: `/api/health`
- **Methods**:
  - `GET /` - Basic health check
  - `GET /detailed` - Detailed health status

#### SystemController
- **Route**: `/api/v2/system`
- **Methods**:
  - `GET /info` - System information
  - `GET /status` - Service status
  - `POST /cache/clear` - Clear cache
  - `POST /maintenance` - Toggle maintenance

#### FileController
- **Route**: `/api/v2/files`
- **Methods**:
  - `POST /upload` - Upload file
  - `GET /{id}` - Download file
  - `DELETE /{id}` - Delete file

### 🔍 Search Controllers (5 files)

#### GlobalSearchController
- **Route**: `/api/v2/search`
- **Methods**:
  - `GET /` - Global search
  - `GET /suggestions` - Search suggestions

#### KnowledgeBaseController
- **Route**: `/api/v2/kb`
- **Methods**:
  - `GET /articles` - List articles
  - `GET /articles/{slug}` - Get article
  - `GET /search` - Search articles

## API Authentication

### JWT Authentication
```http
Authorization: Bearer {token}
```

### API Key Authentication
```http
X-API-Key: {api_key}
```

### Webhook Signatures
- Retell: `X-Retell-Signature`
- Cal.com: `X-Cal-Signature`
- Stripe: `Stripe-Signature`

## Rate Limiting

| Endpoint Type | Rate Limit | Window |
|---------------|------------|--------|
| Authentication | 5 requests | 1 minute |
| Read Operations | 1000 requests | 1 hour |
| Write Operations | 100 requests | 1 hour |
| Webhooks | Unlimited | - |
| Reports | 10 requests | 1 hour |

## Response Formats

### Success Response
```json
{
  "data": {},
  "message": "Success",
  "status": 200
}
```

### Error Response
```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "errors": {
      "field": ["Error message"]
    }
  },
  "status": 422
}
```

### Pagination
```json
{
  "data": [],
  "links": {
    "first": "https://api.askproai.de/api/v2/resource?page=1",
    "last": "https://api.askproai.de/api/v2/resource?page=10",
    "prev": null,
    "next": "https://api.askproai.de/api/v2/resource?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 10,
    "per_page": 15,
    "to": 15,
    "total": 150
  }
}
```

## API Versioning Strategy

1. **Version in URL**: `/api/v2/`
2. **Deprecation Notice**: 6 months
3. **Sunset Period**: 3 months after deprecation
4. **Migration Guides**: Provided for breaking changes

## Future API Enhancements

1. **GraphQL Endpoint**: Coming in v3
2. **WebSocket Support**: Real-time updates
3. **Batch Operations**: Multiple operations in single request
4. **Field Selection**: Sparse fieldsets
5. **Webhook Replay**: Replay failed webhooks

This comprehensive controller documentation covers all 271 controllers in the AskProAI platform, providing a complete API reference.