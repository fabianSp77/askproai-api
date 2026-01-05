# Architecture Overview

AskPro API Gateway is a multi-tenant SaaS platform for AI-powered appointment management and service desk operations.

## System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        Frontend Layer                           │
├─────────────────────────────────────────────────────────────────┤
│  Filament Admin    │  Customer Portal   │   API Clients        │
│  (Laravel/Livewire)│  (Blade/Alpine.js) │   (REST/Webhooks)    │
└─────────────────────────────────────────────────────────────────┘
                              │
┌─────────────────────────────────────────────────────────────────┐
│                      Application Layer                          │
├─────────────────────────────────────────────────────────────────┤
│  Controllers  │  Services  │  Jobs  │  Events  │  Policies     │
└─────────────────────────────────────────────────────────────────┘
                              │
┌─────────────────────────────────────────────────────────────────┐
│                        Data Layer                               │
├─────────────────────────────────────────────────────────────────┤
│     MySQL (Primary)    │    Redis (Cache/Queue)                 │
└─────────────────────────────────────────────────────────────────┘
                              │
┌─────────────────────────────────────────────────────────────────┐
│                    External Integrations                        │
├─────────────────────────────────────────────────────────────────┤
│   Retell.ai     │   Cal.com      │   Email (SMTP)   │  Webhooks│
│   (Voice AI)    │   (Scheduling) │   (Notifications)│  (Output)│
└─────────────────────────────────────────────────────────────────┘
```

## Core Components

### Multi-Tenant Architecture

Every tenant (Company) has isolated data:

- **CompanyScope**: Automatic query scoping via Laravel Global Scope
- **Row-Level Security**: Database-enforced isolation
- **Tenant Context**: Set via middleware on each request

### Service Layer Pattern

```php
app/Services/
├── Retell/                 # Voice AI integration
│   ├── RetellService.php
│   └── DateTimeParser.php
├── CalcomService.php       # Scheduling integration
├── Gateway/                # Service Gateway logic
│   ├── GatewayModeResolver.php
│   ├── IntentDetectionService.php
│   └── OutputHandlers/
└── ServiceDesk/            # Ticket management
```

### Job Queue Architecture

```
Queue Workers
├── default         # General jobs
├── notifications   # Email/SMS delivery
├── sync           # Cal.com synchronization
└── gateway        # Service case processing
```

### Event-Driven Design

```php
// Events trigger listeners for decoupled processing
ServiceCaseCreated → EnrichServiceCaseJob
                   → SendNotificationJob
                   → LogActivityJob
```

## Database Schema

### Core Tables

| Table | Purpose |
|-------|---------|
| companies | Tenant configuration |
| users | Admin/Staff accounts |
| customers | End-user data |
| calls | Voice call records |
| appointments | Booking data |
| service_cases | Support tickets |
| service_case_categories | Ticket categories with SLA |

### Multi-Tenancy Keys

All tenant-scoped tables include:
- `company_id` - Foreign key to companies
- Composite indexes for query performance

## Security Architecture

- **Authentication**: Laravel Sanctum (API) + Session (Web)
- **Authorization**: Spatie Permissions + Policies
- **CSRF**: Token validation on all forms
- **XSS**: Blade auto-escaping
- **SQL Injection**: Eloquent parameterized queries

## Caching Strategy

```
Redis Keys:
├── company:{id}:config     # Company settings (1hr TTL)
├── availability:{staff}    # Cal.com slots (5min TTL)
├── categories:{company}    # Service categories (30min TTL)
└── session:*              # User sessions
```

## Deployment Architecture

```
Production Environment
├── Nginx (Reverse Proxy + SSL)
├── PHP-FPM 8.3 (Application)
├── MySQL 8.0 (Database)
├── Redis (Cache + Queue)
└── Supervisor (Queue Workers)
```
