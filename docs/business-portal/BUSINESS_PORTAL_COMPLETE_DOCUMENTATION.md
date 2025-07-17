# Business Portal - Complete Documentation

> **Version**: 2.0.0  
> **Last Updated**: 2025-01-10  
> **Status**: Production Ready with Advanced Features

## Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Features](#features)
4. [API Reference](#api-reference)
5. [MCP Server Integration](#mcp-server-integration)
6. [Goal System](#goal-system)
7. [Customer Journey](#customer-journey)
8. [Security & Compliance](#security--compliance)
9. [Deployment](#deployment)
10. [Troubleshooting](#troubleshooting)
11. [Environment Configuration](#environment-configuration)

## Overview

The Business Portal is a comprehensive React-based web application that provides businesses with full control over their AI-powered appointment booking system. It includes advanced features like goal tracking, customer journey management, and detailed analytics.

### Key Components

- **Frontend**: React 18 with TypeScript
- **Backend**: Laravel 11 API
- **Real-time**: WebSocket support (configured, not active)
- **Analytics**: Integrated goal tracking and KPI monitoring
- **Security**: 2FA, audit logging, role-based permissions

### Feature Status Matrix

| Feature | Status | Notes |
|---------|--------|-------|
| Dashboard | ✅ Implemented | Real-time metrics |
| Call Management | ✅ Implemented | Full CRUD + analytics |
| Appointment System | ✅ Implemented | Cal.com integration |
| Team Management | ✅ Implemented | Multi-role support |
| Billing & Payments | ✅ Implemented | Stripe integration |
| Goal System | ✅ Implemented | KPI tracking |
| Customer Journey | ✅ Implemented | Stage tracking |
| MCP Servers | ✅ Implemented | 15+ integrations |
| WebSocket | ⚠️ Configured | Not active in production |
| Mobile App | 🚧 In Progress | API ready |
| WhatsApp Integration | 📅 Planned | Q2 2025 |

## Architecture

### System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Business Portal UI (React)                │
├─────────────────────────────────────────────────────────────┤
│                         API Gateway                          │
│  ┌─────────────┐  ┌──────────────┐  ┌─────────────────┐   │
│  │   Auth API  │  │ Business API │  │ Analytics API   │   │
│  └─────────────┘  └──────────────┘  └─────────────────┘   │
├─────────────────────────────────────────────────────────────┤
│                      MCP Server Layer                        │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐  │
│  │ Database │  │  Retell  │  │ Cal.com  │  │  Stripe  │  │
│  │   MCP    │  │   MCP    │  │   MCP    │  │   MCP    │  │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘  │
├─────────────────────────────────────────────────────────────┤
│                    External Services                         │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐  │
│  │ MariaDB  │  │Retell.ai │  │ Cal.com  │  │  Stripe  │  │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘  │
└─────────────────────────────────────────────────────────────┘
```

### Database Schema Overview

```sql
-- Core Tables
portal_users          -- Business portal users with 2FA
companies            -- Multi-tenant organizations
branches             -- Company locations
staff                -- Employees per branch
customers            -- Customer records
calls                -- Phone call logs from Retell.ai
appointments         -- Booking records

-- Goal System Tables
company_goals        -- Strategic goals per company
goal_metrics         -- KPI measurements
goal_funnel_steps    -- Conversion funnel tracking
goal_achievements    -- Achievement history

-- Customer Journey Tables
customer_journey_stages    -- Journey stage definitions
customer_relationships     -- Customer-company relationships

-- Security & Compliance Tables
audit_logs           -- Complete audit trail
portal_permissions   -- Granular permissions
portal_sessions      -- Session management
```

## Features

### 1. Dashboard

The dashboard provides real-time insights with:

- **Key Metrics**: Calls, appointments, conversion rates
- **Goal Progress**: Visual KPI tracking
- **Recent Activity**: Live feed of events
- **Performance Charts**: Trend analysis

```javascript
// Dashboard API endpoint
GET /api/v2/portal/dashboard
Authorization: Bearer {token}

Response:
{
  "stats": {
    "total_calls": 1250,
    "total_appointments": 450,
    "conversion_rate": 36.0,
    "revenue": 125000
  },
  "goals": [...],
  "recent_activity": [...]
}
```

### 2. Call Management

Complete call tracking and analysis:

- **Call List**: Filterable, sortable with export
- **Call Details**: Transcript, audio, metadata
- **Analytics**: Duration, outcome, patterns
- **AI Insights**: Sentiment analysis, key topics

### 3. Goal System

Strategic goal management with:

- **Goal Creation**: Set targets with timelines
- **Metric Tracking**: Automatic KPI calculation
- **Funnel Analysis**: Conversion tracking
- **Achievement History**: Progress over time

### 4. Customer Journey

Track customer lifecycle:

- **Journey Stages**: Customizable stages
- **Relationship Tracking**: Interaction history
- **Engagement Metrics**: Activity scoring
- **Predictive Analytics**: Churn risk

### 5. Team Management

Multi-level access control:

- **Roles**: Admin, Manager, Staff
- **Permissions**: Granular access control
- **Activity Tracking**: User audit logs
- **Performance Metrics**: Staff KPIs

## API Reference

### Authentication

```bash
# Login
POST /api/v2/portal/auth/login
{
  "email": "user@company.com",
  "password": "password",
  "two_factor_code": "123456"  // If 2FA enabled
}

# Response
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {...},
  "company": {...},
  "permissions": [...]
}
```

### Core Endpoints

```bash
# Dashboard
GET /api/v2/portal/dashboard

# Calls
GET /api/v2/portal/calls
GET /api/v2/portal/calls/{id}
POST /api/v2/portal/calls/{id}/export

# Appointments
GET /api/v2/portal/appointments
POST /api/v2/portal/appointments
PUT /api/v2/portal/appointments/{id}
DELETE /api/v2/portal/appointments/{id}

# Customers
GET /api/v2/portal/customers
GET /api/v2/portal/customers/{id}
PUT /api/v2/portal/customers/{id}

# Team
GET /api/v2/portal/team
POST /api/v2/portal/team/invite
DELETE /api/v2/portal/team/{id}
```

### Goal System Endpoints

```bash
# Goals
GET /api/v2/portal/goals
POST /api/v2/portal/goals
PUT /api/v2/portal/goals/{id}
DELETE /api/v2/portal/goals/{id}

# Goal Metrics
GET /api/v2/portal/goals/{id}/metrics
POST /api/v2/portal/goals/{id}/metrics

# Goal Achievements
GET /api/v2/portal/goals/{id}/achievements
```

### Customer Journey Endpoints

```bash
# Journey Stages
GET /api/v2/portal/customer-journey/stages
POST /api/v2/portal/customer-journey/stages

# Customer Journey Status
GET /api/v2/portal/customers/{id}/journey
PUT /api/v2/portal/customers/{id}/journey/stage
```

### Analytics Endpoints

```bash
# Reports
GET /api/v2/portal/analytics/overview
GET /api/v2/portal/analytics/calls
GET /api/v2/portal/analytics/appointments
GET /api/v2/portal/analytics/revenue

# Export
POST /api/v2/portal/analytics/export
```

## MCP Server Integration

The Business Portal leverages 15+ MCP (Model Context Protocol) servers for seamless integration:

### Available MCP Servers

1. **DatabaseMCP** - Direct database operations
2. **RetellMCP** - AI phone system integration
3. **CalcomMCP** - Calendar management
4. **StripeMCP** - Payment processing
5. **CustomerMCP** - Customer data management
6. **AppointmentMCP** - Booking workflows
7. **CompanyMCP** - Multi-tenant management
8. **BranchMCP** - Location management
9. **GoalMCP** - Goal tracking system
10. **AuditMCP** - Compliance logging
11. **WebhookMCP** - Event processing
12. **QueueMCP** - Background jobs
13. **SentryMCP** - Error tracking
14. **KnowledgeMCP** - Knowledge base
15. **NotificationMCP** - Multi-channel notifications

### MCP Usage Example

```php
// In a service class
use App\Traits\UsesMCPServers;

class GoalService 
{
    use UsesMCPServers;
    
    public function calculateMetrics($goalId)
    {
        // Automatically discovers and uses the best MCP server
        return $this->executeMCPTask('calculate goal metrics', [
            'goal_id' => $goalId,
            'period' => 'current_month'
        ]);
    }
}
```

### MCP Discovery

```bash
# Find the best MCP server for a task
php artisan mcp:discover "calculate monthly revenue"

# Execute directly
php artisan mcp:discover "fetch customer data" --execute

# List all available MCP servers
php artisan mcp:list

# Check MCP health
php artisan mcp:health
```

## Goal System

### Overview

The Goal System enables businesses to set and track strategic objectives with automatic KPI calculation.

### Goal Types

1. **Revenue Goals** - Track monetary targets
2. **Volume Goals** - Count-based objectives
3. **Conversion Goals** - Percentage targets
4. **Custom Goals** - Flexible metrics

### Implementation

```php
// Create a goal
$goal = CompanyGoal::create([
    'company_id' => $company->id,
    'name' => 'Q1 Revenue Target',
    'type' => 'revenue',
    'target_value' => 100000,
    'current_value' => 0,
    'start_date' => '2025-01-01',
    'end_date' => '2025-03-31',
    'status' => 'active'
]);

// Track metrics
GoalMetric::create([
    'goal_id' => $goal->id,
    'metric_name' => 'monthly_revenue',
    'metric_value' => 35000,
    'recorded_at' => now()
]);

// Funnel tracking
GoalFunnelStep::create([
    'goal_id' => $goal->id,
    'step_name' => 'Call Received',
    'step_order' => 1,
    'conversion_rate' => 100
]);
```

### Goal Templates

Pre-configured templates for common business objectives:

```javascript
// Available templates
const goalTemplates = [
  {
    name: "Monthly Revenue Target",
    type: "revenue",
    metrics: ["daily_revenue", "appointment_value"]
  },
  {
    name: "Call Conversion Rate",
    type: "conversion",
    metrics: ["calls_received", "appointments_booked"]
  },
  {
    name: "Customer Acquisition",
    type: "volume",
    metrics: ["new_customers", "repeat_customers"]
  }
];
```

## Customer Journey

### Journey Stages

Track customers through their lifecycle:

```php
// Default stages
$stages = [
    'prospect' => 'Initial contact',
    'lead' => 'Expressed interest',
    'customer' => 'First appointment booked',
    'regular' => 'Multiple appointments',
    'vip' => 'High-value customer',
    'at_risk' => 'No recent activity',
    'churned' => 'Lost customer'
];
```

### Stage Transitions

Automatic and manual stage updates:

```php
// Automatic transition on appointment booking
event(new AppointmentBooked($appointment));
// Triggers: CustomerJourneyService::updateStage()

// Manual stage update
$customer->updateJourneyStage('vip', 'High lifetime value');
```

### Journey Analytics

```sql
-- Customer distribution by stage
SELECT 
    stage,
    COUNT(*) as customer_count,
    AVG(lifetime_value) as avg_value
FROM customer_relationships
GROUP BY stage;

-- Stage conversion funnel
SELECT 
    from_stage,
    to_stage,
    COUNT(*) as transitions,
    AVG(days_in_stage) as avg_days
FROM customer_journey_transitions
GROUP BY from_stage, to_stage;
```

## Security & Compliance

### Authentication & Authorization

#### Two-Factor Authentication (2FA)

```php
// Enable 2FA for user
$user->enableTwoFactorAuthentication();

// Verify 2FA code
if ($user->verifyTwoFactorCode($request->code)) {
    // Grant access
}
```

#### Role-Based Permissions

```php
// Permission structure
$permissions = [
    'admin' => ['*'], // Full access
    'manager' => [
        'view_calls',
        'manage_appointments',
        'view_analytics',
        'manage_team'
    ],
    'staff' => [
        'view_own_calls',
        'manage_own_appointments'
    ]
];
```

### Audit Logging

Complete audit trail for compliance:

```php
// Automatic logging via AuditLogService
AuditLog::create([
    'company_id' => $company->id,
    'user_id' => $user->id,
    'action' => 'appointment.created',
    'model_type' => 'Appointment',
    'model_id' => $appointment->id,
    'changes' => $changes,
    'ip_address' => $request->ip(),
    'user_agent' => $request->userAgent()
]);
```

### Data Privacy

GDPR-compliant data handling:

```php
// Data export
$exporter = new CustomerDataExporter($customer);
$data = $exporter->export(); // All customer data

// Data deletion
$customer->anonymize(); // Soft delete with data scrambling
$customer->purge(); // Hard delete
```

### Session Security

```php
// Session configuration
'portal' => [
    'driver' => env('SESSION_DRIVER', 'database'),
    'lifetime' => 120, // 2 hours
    'expire_on_close' => false,
    'encrypt' => true,
    'table' => 'portal_sessions',
    'lottery' => [2, 100],
    'cookie' => 'portal_session',
    'path' => '/',
    'domain' => env('SESSION_DOMAIN', null),
    'secure' => true, // HTTPS only
    'http_only' => true,
    'same_site' => 'lax',
]
```

## Deployment

### Prerequisites

- PHP 8.3+
- Node.js 18+
- MariaDB 10.6+
- Redis 6.2+
- Nginx 1.21+

### Deployment Process

```bash
# 1. Pull latest code
git pull origin main

# 2. Install dependencies
composer install --no-dev --optimize-autoloader
npm ci

# 3. Build assets
npm run build

# 4. Run migrations
php artisan migrate --force

# 5. Clear and cache
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Restart services
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx
sudo systemctl restart horizon
```

### Environment Variables

```env
# Core Settings
APP_NAME="AskProAI Business Portal"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://portal.askproai.de

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=askproai_db
DB_USERNAME=askproai_user
DB_PASSWORD=secure_password

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Portal Settings
PORTAL_DOMAIN=portal.askproai.de
PORTAL_SESSION_LIFETIME=120
PORTAL_2FA_ENABLED=true
PORTAL_AUDIT_ENABLED=true

# API Keys
RETELL_API_KEY=key_xxx
CALCOM_API_KEY=cal_xxx
STRIPE_KEY=sk_xxx
STRIPE_SECRET=whsec_xxx

# MCP Servers
MCP_DATABASE_ENABLED=true
MCP_RETELL_ENABLED=true
MCP_CALCOM_ENABLED=true
MCP_STRIPE_ENABLED=true

# Features
FEATURE_GOALS_ENABLED=true
FEATURE_JOURNEY_ENABLED=true
FEATURE_WEBSOCKET_ENABLED=false
FEATURE_MOBILE_API_ENABLED=true
```

### Health Checks

```bash
# System health
curl https://portal.askproai.de/api/health

# Component status
php artisan health:check

# MCP status
php artisan mcp:health

# Queue status
php artisan horizon:status
```

## Troubleshooting

### Common Issues

#### 1. Login Issues

**Symptom**: Can't log in to portal
```bash
# Check sessions
php artisan session:table
php artisan migrate

# Clear sessions
php artisan session:clear

# Check 2FA
php check-portal-user.php
```

#### 2. API Response Issues

**Symptom**: API returns 419 or 401
```bash
# Check CSRF token
curl -X POST https://portal.askproai.de/api/v2/portal/test \
  -H "X-CSRF-TOKEN: {token}" \
  -H "Authorization: Bearer {token}"

# Regenerate API token
php artisan portal:regenerate-token user@example.com
```

#### 3. Goal Calculations

**Symptom**: Goals not updating
```bash
# Recalculate goals
php artisan goals:recalculate --company=1

# Check goal metrics
php artisan goals:debug 1
```

#### 4. MCP Server Errors

**Symptom**: MCP tasks failing
```bash
# Check MCP health
php artisan mcp:health

# Test specific MCP
php artisan mcp:test DatabaseMCP

# Reset MCP connections
php artisan mcp:reset
```

### Debug Commands

```bash
# Portal user debugging
php debug-portal-user.php user@example.com

# API testing
php test-portal-api.php

# Session debugging
php debug-business-portal-session.php

# Goal system testing
php test-goal-system.php

# Journey tracking test
php test-customer-journey.php
```

### Log Locations

```bash
# Application logs
tail -f storage/logs/laravel.log

# API logs
tail -f storage/logs/api-*.log

# Audit logs
tail -f storage/logs/audit-*.log

# MCP logs
tail -f storage/logs/mcp-*.log
```

### Performance Monitoring

```bash
# Database queries
php artisan debugbar:clear
php artisan telescope:prune

# API performance
php artisan api:performance --route=/api/v2/portal/dashboard

# Cache hit rates
php artisan cache:stats
```

## Advanced Features

### WebSocket Configuration (Configured but not active)

```javascript
// WebSocket configuration ready for activation
const wsConfig = {
  host: 'wss://portal.askproai.de',
  port: 6001,
  encrypted: true,
  auth: {
    headers: {
      'Authorization': 'Bearer ' + token
    }
  }
};

// Event subscriptions (when activated)
Echo.channel('company.' + companyId)
  .listen('CallReceived', (e) => {
    console.log('New call:', e.call);
  })
  .listen('AppointmentBooked', (e) => {
    console.log('New appointment:', e.appointment);
  });
```

### Batch Operations

```php
// Batch appointment creation
$appointments = AppointmentBatchService::createMultiple([
    ['customer_id' => 1, 'date' => '2025-01-15', 'time' => '10:00'],
    ['customer_id' => 2, 'date' => '2025-01-15', 'time' => '11:00'],
]);

// Batch customer import
$importer = new CustomerBatchImporter($csvFile);
$results = $importer->import();
```

### Custom Reporting

```php
// Create custom report
$report = new CustomReport();
$report->setDateRange('2025-01-01', '2025-01-31')
       ->addMetric('total_revenue')
       ->addMetric('conversion_rate')
       ->addDimension('branch')
       ->generate();
```

## Integration Examples

### Retell.ai Webhook Processing

```php
// Webhook controller
public function handleRetellWebhook(Request $request)
{
    $validator = new RetellWebhookValidator();
    if (!$validator->validate($request)) {
        return response('Unauthorized', 401);
    }
    
    ProcessRetellWebhook::dispatch($request->all());
    
    return response('OK', 200);
}
```

### Cal.com Sync

```php
// Sync appointments
$syncer = new CalcomSyncService();
$syncer->syncAppointments($branch);
$syncer->syncAvailability($staff);
```

### Stripe Integration

```php
// Process payment
$payment = StripeService::createPayment([
    'amount' => 5000, // $50.00
    'currency' => 'eur',
    'customer' => $customer->stripe_id,
    'description' => 'Appointment booking'
]);
```

## Best Practices

### API Usage

1. **Always use API versioning**: `/api/v2/portal/...`
2. **Include proper headers**:
   ```bash
   Authorization: Bearer {token}
   Accept: application/json
   Content-Type: application/json
   ```
3. **Handle rate limiting**: 60 requests/minute
4. **Implement retry logic** for failed requests

### Security

1. **Enable 2FA** for all admin accounts
2. **Rotate API keys** regularly
3. **Monitor audit logs** for suspicious activity
4. **Use HTTPS** for all communications
5. **Implement CSRF protection** for forms

### Performance

1. **Cache API responses** where possible
2. **Use pagination** for large datasets
3. **Implement lazy loading** for UI components
4. **Optimize database queries** with indexes
5. **Use queue workers** for heavy operations

### Data Management

1. **Regular backups** of customer data
2. **Data retention policies** for compliance
3. **Anonymization** for test environments
4. **Export capabilities** for GDPR
5. **Version control** for data schemas

## Support & Resources

### Documentation

- [API Documentation](https://portal.askproai.de/docs/api)
- [MCP Server Guide](./MCP_SERVER_GUIDE.md)
- [Goal System Guide](./GOAL_SYSTEM_GUIDE.md)
- [Security Guide](./SECURITY_GUIDE.md)

### Quick Links

- Admin Panel: https://admin.askproai.de
- Business Portal: https://portal.askproai.de
- API Status: https://status.askproai.de
- Support: support@askproai.de

### Community

- GitHub: https://github.com/askproai
- Discord: https://discord.gg/askproai
- Forum: https://forum.askproai.de

---

*This documentation is automatically updated. Last sync: 2025-01-10*