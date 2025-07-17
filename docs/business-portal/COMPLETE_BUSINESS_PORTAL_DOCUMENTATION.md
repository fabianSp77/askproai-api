# 🚀 AskProAI Business Portal - Complete Documentation

> **Version**: 2.0  
> **Last Updated**: 2025-07-10  
> **Status**: Production-Ready React SPA

## 📚 Table of Contents

1. [Executive Summary](#executive-summary)
2. [Architecture & Technology](#architecture--technology)
3. [Modules Overview](#modules-overview)
4. [API Reference](#api-reference)
5. [Developer Guide](#developer-guide)
6. [Security & Permissions](#security--permissions)
7. [Performance Guide](#performance-guide)
8. [Deployment & Operations](#deployment--operations)

---

## Executive Summary

The **AskProAI Business Portal** is a comprehensive B2B platform that enables companies to manage AI-powered phone services, appointments, customer relationships, and business analytics in real-time.

### Key Metrics
- **Technology**: React 18.2 + Laravel 11 + MySQL
- **Architecture**: SPA with RESTful API
- **Performance**: <200ms API response time
- **Availability**: 99.9% uptime SLA
- **Security**: Enterprise-grade with 2FA
- **Scalability**: Multi-tenant architecture

### Quick Links
- **Production**: https://business.askproai.de
- **API Base**: https://api.askproai.de/business/api
- **Admin Panel**: https://api.askproai.de/admin

---

## Architecture & Technology

### Tech Stack Overview

#### Frontend
- **Framework**: React 18.2 with TypeScript
- **UI Library**: Custom components based on shadcn/ui
- **Styling**: Tailwind CSS 3.x
- **State Management**: React Context API + Custom hooks
- **Charts**: Recharts for data visualization
- **Build Tool**: Vite 5.x

#### Backend
- **Framework**: Laravel 11.x (PHP 8.3)
- **Admin Panel**: Filament 3.x
- **Database**: MySQL 8.0 / MariaDB
- **Cache**: Redis
- **Queue**: Laravel Horizon
- **Auth**: Laravel Sanctum (multi-guard)

### System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                     Business Portal UI                       │
│                    (React SPA + Vite)                       │
└─────────────────────┬───────────────────────────────────────┘
                      │ HTTPS
┌─────────────────────▼───────────────────────────────────────┐
│                    Nginx (Reverse Proxy)                     │
│                   - SSL Termination                          │
│                   - Static Asset Serving                     │
└─────────────────────┬───────────────────────────────────────┘
                      │
┌─────────────────────▼───────────────────────────────────────┐
│                  Laravel Application                         │
│  ┌─────────────┐  ┌──────────────┐  ┌─────────────────┐   │
│  │   Routes    │  │  Middleware  │  │   Controllers   │   │
│  │ - Web       │  │ - Auth       │  │ - Portal API    │   │
│  │ - API       │  │ - CORS       │  │ - Webhook       │   │
│  │ - Webhook   │  │ - Tenant     │  │ - Admin         │   │
│  └─────────────┘  └──────────────┘  └─────────────────┘   │
└─────────────────────┬───────────────────────────────────────┘
                      │
         ┌────────────┴──────────┬────────────────┐
         │                       │                │
┌────────▼────────┐   ┌─────────▼──────┐  ┌─────▼─────┐
│     MySQL       │   │     Redis      │  │  Horizon  │
│   Database      │   │     Cache      │  │   Queue   │
└─────────────────┘   └────────────────┘  └───────────┘
```

### Database Schema (Key Tables)

```sql
-- Companies (Tenants)
companies (id, name, subdomain, settings, created_at)

-- Portal Users
portal_users (id, company_id, email, name, permissions)

-- Calls
calls (id, company_id, from_number, duration, transcript, status)

-- Appointments
appointments (id, company_id, customer_id, starts_at, status)

-- Customers
customers (id, company_id, name, email, phone, tags)

-- Billing
prepaid_balances (id, company_id, balance, auto_topup_settings)
balance_topups (id, company_id, amount, stripe_payment_id)
```

---

## Modules Overview

### 1. Dashboard Module 📊

**Purpose**: Real-time business metrics and insights

**Key Features**:
- 4 KPI cards (Calls, Appointments, Customers, Revenue)
- Interactive charts (Area & Bar charts)
- Goal tracking widget
- Performance metrics
- Real-time updates via WebSocket

**Component Structure**:
```
DashboardIndex
├── StatsGrid
├── ChartsSection
├── PerformanceMetrics
├── GoalDashboard
└── TabsSection
```

**API Endpoint**: `GET /business/api/dashboard?range={today|week|month|year}`

### 2. Calls Module 📞

**Purpose**: Comprehensive call management and analysis

**Key Features**:
- Real-time call list with filters
- Full transcript with speaker identification
- Audio playback with waveform
- AI-generated summaries
- Export functionality (CSV/PDF)

**Component Structure**:
```
CallsIndex
├── CallsFilters
├── CallsTable
└── CallDetailModal
    ├── TranscriptViewer
    ├── AudioPlayer
    └── CustomerPanel
```

**Key APIs**:
- `GET /business/api/calls` - List calls
- `GET /business/api/calls/{id}` - Call details
- `POST /business/api/calls/{id}/send-summary` - Email summary

### 3. Appointments Module 📅

**Purpose**: Advanced appointment scheduling and management

**Key Features**:
- Calendar views (day/week/month)
- Manual appointment creation
- Conflict detection
- Automated reminders
- Staff assignment

**APIs**:
- `GET /business/api/appointments`
- `POST /business/api/appointments`
- `PUT /business/api/appointments/{id}/reschedule`

### 4. Team Module 👥

**Purpose**: Staff and permission management

**Key Features**:
- Team member management
- Role-based permissions
- Performance tracking
- Working hours setup

**Permission Keys**:
```
- calls.view_own / calls.view_all
- appointments.create / appointments.edit
- customers.view / customers.export
- billing.view / billing.manage
- team.view / team.manage
```

### 5. Analytics Module 📊

**Purpose**: Deep business insights and reporting

**Key Features**:
- Custom date ranges
- Goal tracking
- Trend analysis
- Export reports (PDF/Excel)
- Real-time updates

### 6. Billing Module 💰

**Purpose**: Comprehensive billing and payment management

**Key Features**:
- Prepaid balance system
- Auto-topup configuration
- Transaction history
- Multiple payment methods
- Invoice downloads

### 7. Settings Module ⚙️

**Purpose**: Company and user configuration

**Key Features**:
- Company profile
- User preferences
- Notification settings
- Theme customization
- 2FA security

---

## API Reference

### Authentication

All API requests require authentication via session cookies or API tokens.

```javascript
// Request headers
{
  'Accept': 'application/json',
  'Content-Type': 'application/json',
  'X-CSRF-TOKEN': csrfToken,
  'Authorization': 'Bearer {token}' // For API token auth
}
```

### Response Format

```javascript
// Success Response
{
  "success": true,
  "data": { ... },
  "message": "Operation successful",
  "meta": {
    "request_id": "unique_id",
    "response_time": 125
  }
}

// Error Response
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid",
    "details": { ... }
  }
}

// Paginated Response
{
  "success": true,
  "data": [...],
  "pagination": {
    "current_page": 1,
    "per_page": 50,
    "total": 245,
    "last_page": 5
  }
}
```

### Core API Endpoints

#### Dashboard
- `GET /business/api/dashboard` - Main dashboard data
- `GET /business/api/dashboard/stats` - Statistics only
- `GET /business/api/dashboard/charts` - Chart data

#### Calls
- `GET /business/api/calls` - List calls (paginated)
- `GET /business/api/calls/{id}` - Get call details
- `POST /business/api/calls/{id}/status` - Update status
- `POST /business/api/calls/{id}/send-summary` - Email summary
- `GET /business/api/calls/export-csv` - Export to CSV

#### Appointments
- `GET /business/api/appointments` - List appointments
- `POST /business/api/appointments` - Create appointment
- `PUT /business/api/appointments/{id}` - Update appointment
- `POST /business/api/appointments/{id}/reschedule` - Reschedule
- `DELETE /business/api/appointments/{id}` - Cancel appointment

#### Customers
- `GET /business/api/customers` - List customers
- `GET /business/api/customers/{id}` - Customer details
- `POST /business/api/customers` - Create customer
- `PUT /business/api/customers/{id}` - Update customer

#### Analytics
- `GET /business/api/analytics` - Analytics dashboard
- `GET /business/api/analytics/metrics` - Key metrics
- `GET /business/api/analytics/export` - Export report

#### Team
- `GET /business/api/team` - List team members
- `POST /business/api/team/invite` - Invite member
- `PUT /business/api/team/{id}/permissions` - Update permissions

#### Billing
- `GET /business/api/billing/balance` - Current balance
- `POST /business/api/billing/topup` - Add credit
- `GET /business/api/billing/transactions` - Transaction history

---

## Developer Guide

### Getting Started

#### Prerequisites
- PHP 8.3+
- Node.js 18+
- MySQL 8.0+
- Redis
- Composer
- NPM/Yarn

#### Local Setup
```bash
# Clone repository
git clone https://github.com/askproai/api-gateway.git
cd api-gateway

# Install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database setup
php artisan migrate --seed

# Build assets
npm run dev

# Start servers
php artisan serve
php artisan horizon
npm run dev
```

### Development Patterns

#### React Component Pattern
```jsx
import React, { useState, useEffect } from 'react';
import { useAuth } from '@/hooks/useAuth';
import { Card } from '@/components/ui/card';
import axiosInstance from '@/services/axiosInstance';

const StandardComponent = ({ prop1, prop2 }) => {
    const { user } = useAuth();
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    
    useEffect(() => {
        fetchData();
    }, []);
    
    const fetchData = async () => {
        try {
            const response = await axiosInstance.get('/endpoint');
            setData(response.data);
        } catch (err) {
            console.error(err);
        } finally {
            setLoading(false);
        }
    };
    
    if (loading) return <LoadingSpinner />;
    
    return (
        <Card>
            {/* Component content */}
        </Card>
    );
};
```

#### Laravel Service Pattern
```php
namespace App\Services\Portal;

class ExampleService
{
    public function __construct(
        private ExampleRepository $repository
    ) {}
    
    public function process(array $data): Example
    {
        return DB::transaction(function () use ($data) {
            $example = $this->repository->create($data);
            event(new ExampleCreated($example));
            return $example;
        });
    }
}
```

### Testing

#### Frontend Testing
```javascript
import { render, screen, waitFor } from '@testing-library/react';

test('displays dashboard stats', async () => {
    render(<Dashboard />);
    
    await waitFor(() => {
        expect(screen.getByText('25')).toBeInTheDocument();
    });
});
```

#### Backend Testing
```php
class DashboardTest extends TestCase
{
    public function test_dashboard_returns_correct_data()
    {
        $user = PortalUser::factory()->create();
        
        $response = $this->actingAs($user, 'portal')
            ->getJson('/business/api/dashboard');
            
        $response->assertStatus(200)
            ->assertJsonStructure(['stats', 'charts']);
    }
}
```

---

## Security & Permissions

### Authentication System

#### Multi-Guard Setup
- `web` - Admin users
- `portal` - Business portal users
- `api` - API access with Sanctum

#### Session Isolation
```php
'portal_session' => [
    'driver' => 'redis',
    'connection' => 'portal_sessions',
    'cookie' => 'portal_session',
    'path' => '/business',
    'secure' => true
]
```

### Permission Matrix

| Module | Permissions |
|--------|------------|
| Calls | view_own, view_all, edit, export |
| Appointments | view, create, edit, delete |
| Customers | view, create, edit, export |
| Billing | view, pay, manage |
| Team | view, manage |
| Analytics | view, export |
| Settings | view, edit |

### Security Best Practices
- CSRF protection on all forms
- XSS prevention with content sanitization
- SQL injection prevention via Eloquent ORM
- API rate limiting (60 req/min default)
- 2FA authentication available

---

## Performance Guide

### Frontend Optimization

#### Code Splitting
```javascript
const Analytics = React.lazy(() => import('./Pages/Portal/Analytics'));

<Suspense fallback={<LoadingSpinner />}>
    <Analytics />
</Suspense>
```

#### Memoization
```javascript
const expensiveValue = useMemo(() => {
    return calculateExpensiveValue(data);
}, [data]);
```

### Backend Optimization

#### Query Optimization
```php
// Eager loading
$calls = Call::with(['customer', 'appointments'])
    ->where('company_id', $companyId)
    ->paginate(50);

// Caching
Cache::remember("dashboard:{$companyId}", 300, function () {
    return $this->calculateStats();
});
```

### Performance Targets
- API Response: < 200ms (p95)
- Page Load: < 1s
- Database Queries: < 100ms
- Memory Usage: < 512MB per request

---

## Deployment & Operations

### Production Deployment

#### Pre-deployment Checklist
```bash
# Run tests
php artisan test
npm run test

# Build assets
npm run build

# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### Deployment Script
```bash
#!/bin/bash
# Pull latest code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci

# Build assets
npm run build

# Run migrations
php artisan migrate --force

# Clear caches
php artisan optimize:clear
php artisan optimize

# Restart services
sudo systemctl restart php8.3-fpm
sudo systemctl restart horizon
```

### Environment Variables

```env
# Application
APP_NAME="AskProAI Business Portal"
APP_ENV=production
APP_URL=https://business.askproai.de

# Database
DB_CONNECTION=mysql
DB_DATABASE=askproai_db

# Redis
REDIS_HOST=127.0.0.1

# External Services
RETELL_API_KEY=your_key
CALCOM_API_KEY=your_key
STRIPE_KEY=your_key

# Sessions
SESSION_DRIVER=redis
SESSION_SECURE_COOKIE=true
```

### Monitoring & Logging

#### Health Checks
```bash
# Horizon status
php artisan horizon:status

# Application health
curl https://business.askproai.de/health
```

#### Log Channels
- `stack` - Combined logging
- `slack` - Error notifications
- `daily` - Rotating log files

### Performance Monitoring
- Response time tracking
- Memory usage monitoring
- Slow query logging
- Real-time metrics dashboard

---

## 📝 Additional Resources

### Internal Documentation
- [CLAUDE.md](../../CLAUDE.md) - AI assistant guidelines
- [DEPLOYMENT_CHECKLIST.md](../../DEPLOYMENT_CHECKLIST.md)
- [ERROR_PATTERNS.md](../../ERROR_PATTERNS.md)

### External Resources
- [Laravel Documentation](https://laravel.com/docs)
- [React Documentation](https://react.dev)
- [Tailwind CSS](https://tailwindcss.com)
- [shadcn/ui](https://ui.shadcn.com)

### Support
- **Email**: support@askproai.de
- **GitHub**: Issues & PRs
- **Monitoring**: 24/7 automated alerts

---

<center>

**Built with ❤️ by the AskProAI Team**

</center>