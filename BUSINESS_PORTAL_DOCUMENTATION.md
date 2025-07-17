# 🚀 Business Portal Documentation

> **State-of-the-Art Documentation for AskProAI Business Portal**  
> Last Updated: 2025-07-10  
> Version: 2.0

## 📚 Table of Contents

1. [Overview](#overview)
2. [Architecture & Technology](#architecture--technology)
3. [Modules & Features](#modules--features)
4. [API Reference](#api-reference)
5. [Developer Guide](#developer-guide)
6. [Deployment & Operations](#deployment--operations)
7. [Security & Permissions](#security--permissions)
8. [Performance Guide](#performance-guide)
9. [Changelog & Roadmap](#changelog--roadmap)

---

## 🌟 Overview

The **AskProAI Business Portal** is a comprehensive B2B platform that enables companies to manage AI-powered phone services, appointments, customer relationships, and business analytics in real-time.

### Key Features
- 🤖 **AI Phone Integration** - Powered by Retell.ai for intelligent call handling
- 📅 **Smart Appointment Management** - Integrated with Cal.com for seamless scheduling
- 📊 **Real-time Analytics** - Advanced insights and performance metrics
- 👥 **Multi-tenant Architecture** - Complete data isolation for enterprise security
- 🌍 **Multi-language Support** - 30+ languages via AI translation
- 💰 **Flexible Billing** - Prepaid balance system with auto-topup
- 🔒 **Enterprise Security** - Role-based permissions and 2FA

### Quick Links
- **Production URL**: `https://business.askproai.de`
- **API Base**: `https://api.askproai.de/business/api`
- **Admin Panel**: `https://api.askproai.de/admin`
- **Documentation**: You're reading it! 😊

---

## 🏗️ Architecture & Technology

### Tech Stack

#### Frontend
- **Framework**: React 18.2 with TypeScript
- **UI Library**: Custom UI components based on shadcn/ui
- **Styling**: Tailwind CSS 3.x
- **State Management**: React Context API + Custom hooks
- **Charts**: Recharts for data visualization
- **Icons**: Lucide React
- **Date Handling**: Day.js with German locale
- **HTTP Client**: Axios with interceptors
- **Build Tool**: Vite 5.x

#### Backend
- **Framework**: Laravel 11.x (PHP 8.3)
- **Admin Panel**: Filament 3.x
- **Database**: MySQL 8.0 / MariaDB
- **Cache**: Redis
- **Queue**: Laravel Horizon
- **Auth**: Laravel Sanctum (multi-guard)
- **API**: RESTful with JSON responses

#### Infrastructure
- **Hosting**: Netcup VPS
- **Web Server**: Nginx
- **Process Manager**: PHP-FPM
- **SSL**: Let's Encrypt
- **CDN**: CloudFlare (optional)
- **Monitoring**: Laravel Telescope + Custom metrics

### Architecture Diagram

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
│  │             │  │              │  │                 │   │
│  │ - Web       │  │ - Auth       │  │ - Portal API    │   │
│  │ - API       │  │ - CORS       │  │ - Webhook       │   │
│  │ - Webhook   │  │ - Tenant     │  │ - Admin         │   │
│  └─────────────┘  └──────────────┘  └─────────────────┘   │
│                                                             │
│  ┌─────────────┐  ┌──────────────┐  ┌─────────────────┐   │
│  │  Services   │  │ Repositories │  │     Models      │   │
│  │             │  │              │  │                 │   │
│  │ - Retell    │  │ - Call       │  │ - Company       │   │
│  │ - Cal.com   │  │ - Customer   │  │ - Appointment   │   │
│  │ - Billing   │  │ - Appt       │  │ - Call          │   │
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

### Multi-Tenancy Design

```php
// Automatic tenant isolation via global scope
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if ($companyId = $this->getCurrentCompanyId()) {
            $builder->where($model->getTable() . '.company_id', $companyId);
        }
    }
}
```

---

## 📦 Modules & Features

### 1. Dashboard Module

**Purpose**: Real-time business metrics and insights at a glance

**Features**:
- 📊 Key Performance Indicators (KPIs)
- 📈 Interactive charts and graphs
- 🎯 Goal tracking widget
- ⚡ Quick actions panel
- 📱 Mobile-optimized view

**Technical Implementation**:
```jsx
// Dashboard Component Structure
<DashboardIndex>
  ├── StatsGrid (KPI Cards)
  ├── ChartsSection
  │   ├── CallVolumeChart (Area Chart)
  │   └── ConversionFunnel (Bar Chart)
  ├── PerformanceMetrics
  ├── GoalDashboard (Embedded)
  └── TabsSection
      ├── RecentCalls
      ├── UpcomingAppointments
      └── Insights
</DashboardIndex>
```

**API Endpoints**:
- `GET /business/api/dashboard` - Main dashboard data
- `GET /business/api/dashboard?range={today|week|month|year}` - Filtered data

**Database Schema**:
```sql
-- Key tables for dashboard metrics
calls (id, company_id, status, duration, created_at)
appointments (id, company_id, status, starts_at, created_at)
customers (id, company_id, created_at)
balance_topups (id, company_id, amount, created_at)
```

### 2. Calls Module

**Purpose**: Comprehensive call management and analysis

**Features**:
- 📞 Real-time call list with filters
- 🎙️ Call recordings and transcripts
- 📊 Call analytics and patterns
- 🏷️ Tag and categorize calls
- 📧 Send call summaries via email
- 📥 Export calls (CSV/PDF)

**Component Structure**:
```jsx
<CallsIndex>
  ├── CallFilters
  │   ├── DateRangePicker
  │   ├── StatusFilter
  │   └── SearchBar
  ├── CallsTable
  │   ├── CallRow
  │   └── BulkActions
  └── CallDetailModal
      ├── TranscriptViewer
      ├── AudioPlayer
      └── ActionButtons
</CallsIndex>
```

**API Endpoints**:
```javascript
// Call Management APIs
GET    /business/api/calls                    // List calls
GET    /business/api/calls/{id}              // Get call details
POST   /business/api/calls/{id}/status       // Update status
POST   /business/api/calls/{id}/notes        // Add notes
POST   /business/api/calls/{id}/send-summary // Email summary
GET    /business/api/calls/export-csv        // Export CSV
GET    /business/api/calls/{id}/export-pdf   // Export PDF
POST   /business/api/calls/export-batch      // Bulk export
```

### 3. Appointments Module

**Purpose**: Advanced appointment scheduling and management

**Features**:
- 📅 Calendar view (day/week/month)
- ➕ Create appointments manually
- 🔄 Reschedule with conflict detection
- 📧 Automated reminders
- 🏷️ Service and staff assignment
- 📊 Appointment analytics

**State Management**:
```javascript
// Appointment Context
const AppointmentContext = {
  appointments: [],
  filters: {
    dateRange: { start, end },
    status: 'all',
    staff: null,
    service: null
  },
  selectedAppointment: null,
  isCreating: false,
  isLoading: false
}
```

### 4. Team Module

**Purpose**: Staff and permission management

**Features**:
- 👥 Team member overview
- 📧 Invite new members
- 🔐 Role-based permissions
- 📊 Performance tracking
- 🗓️ Working hours management

**Permission System**:
```php
// Permission structure
portal_permissions:
  - calls.view_own / calls.view_all
  - appointments.create / appointments.edit
  - customers.view / customers.export
  - billing.view / billing.manage
  - team.view / team.manage
  - analytics.view / analytics.export
```

### 5. Analytics Module

**Purpose**: Deep business insights and reporting

**Features**:
- 📊 Custom date range analysis
- 🎯 Goal tracking and progress
- 📈 Trend analysis
- 🏆 Performance comparisons
- 📥 Export reports (PDF/Excel)
- 🔄 Real-time updates

**Key Metrics**:
```javascript
// Analytics Data Structure
{
  metrics: {
    totalCalls: { value, change, trend },
    conversionRate: { value, target, achievement },
    avgCallDuration: { value, optimal: "2-5min" },
    customerSatisfaction: { score, responses }
  },
  charts: {
    callVolume: [...],
    conversionFunnel: [...],
    hourlyDistribution: [...],
    goalProgress: [...]
  }
}
```

### 6. Billing Module

**Purpose**: Comprehensive billing and payment management

**Features**:
- 💳 Prepaid balance system
- 🔄 Auto-topup configuration
- 📊 Usage tracking
- 🧾 Transaction history
- 💰 Multiple payment methods
- 📥 Invoice downloads

**Billing Flow**:
```
User Action → Check Balance → Sufficient?
                                   ├─ Yes → Process Call
                                   └─ No → Auto-topup?
                                            ├─ Yes → Charge Card → Process
                                            └─ No → Block Service
```

### 7. Settings Module

**Purpose**: Company and user configuration

**Features**:
- 🏢 Company profile
- 👤 User preferences
- 🔔 Notification settings
- 🎨 Theme customization
- 🔐 Security settings (2FA)
- 🌍 Language preferences

---

## 🔌 API Reference

### Authentication

All API requests require authentication via session cookies or API tokens.

```javascript
// Request headers
{
  'Accept': 'application/json',
  'Content-Type': 'application/json',
  'X-CSRF-TOKEN': csrfToken // From meta tag or cookie
}
```

### Response Format

```javascript
// Success Response
{
  "success": true,
  "data": { ... },
  "message": "Operation successful"
}

// Error Response
{
  "success": false,
  "error": "Error message",
  "errors": { field: ["validation error"] }
}
```

### Common API Patterns

#### Pagination
```javascript
GET /api/resource?page=1&per_page=50
Response: {
  data: [...],
  meta: {
    current_page: 1,
    per_page: 50,
    total: 200,
    last_page: 4
  }
}
```

#### Filtering
```javascript
GET /api/resource?filter[status]=active&filter[date_from]=2025-01-01
```

#### Sorting
```javascript
GET /api/resource?sort=-created_at,name // -created_at DESC, name ASC
```

### API Endpoints Overview

#### Dashboard APIs
```javascript
GET  /business/api/dashboard
GET  /business/api/dashboard/stats
GET  /business/api/dashboard/charts
GET  /business/api/dashboard/alerts
```

#### Call Management APIs
```javascript
GET    /business/api/calls
GET    /business/api/calls/{id}
POST   /business/api/calls/{id}/status
POST   /business/api/calls/{id}/notes
POST   /business/api/calls/{id}/assign
POST   /business/api/calls/{id}/send-summary
DELETE /business/api/calls/{id}
```

#### Customer APIs
```javascript
GET    /business/api/customers
GET    /business/api/customers/{id}
POST   /business/api/customers
PUT    /business/api/customers/{id}
DELETE /business/api/customers/{id}
GET    /business/api/customers/tags
POST   /business/api/customers/{id}/tags
```

#### Appointment APIs
```javascript
GET    /business/api/appointments
GET    /business/api/appointments/{id}
POST   /business/api/appointments
PUT    /business/api/appointments/{id}
DELETE /business/api/appointments/{id}
POST   /business/api/appointments/{id}/reschedule
POST   /business/api/appointments/{id}/cancel
```

#### Analytics APIs
```javascript
GET  /business/api/analytics
GET  /business/api/analytics/metrics
GET  /business/api/analytics/charts
GET  /business/api/analytics/export
POST /business/api/analytics/custom-report
```

#### Goal APIs
```javascript
GET    /business/api/goals
GET    /business/api/goals/{id}
POST   /business/api/goals
PUT    /business/api/goals/{id}
DELETE /business/api/goals/{id}
GET    /business/api/goals/{id}/progress
POST   /business/api/goals/{id}/record-achievement
```

#### Billing APIs
```javascript
GET  /business/api/billing
GET  /business/api/billing/balance
POST /business/api/billing/topup
GET  /business/api/billing/transactions
GET  /business/api/billing/usage
PUT  /business/api/billing/auto-topup
```

#### Team APIs
```javascript
GET    /business/api/team
GET    /business/api/team/{id}
POST   /business/api/team/invite
PUT    /business/api/team/{id}
DELETE /business/api/team/{id}
POST   /business/api/team/{id}/permissions
```

#### Settings APIs
```javascript
GET  /business/api/settings/profile
PUT  /business/api/settings/profile
PUT  /business/api/settings/password
GET  /business/api/settings/company
PUT  /business/api/settings/company
PUT  /business/api/settings/notifications
POST /business/api/settings/2fa/enable
POST /business/api/settings/2fa/disable
```

---

## 👨‍💻 Developer Guide

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

### Code Structure

#### Frontend Structure
```
resources/js/
├── Pages/Portal/          # Page components
│   ├── Dashboard/
│   ├── Calls/
│   ├── Appointments/
│   └── ...
├── components/           # Reusable components
│   ├── ui/              # Base UI components
│   ├── Portal/          # Portal-specific
│   └── Mobile/          # Mobile components
├── hooks/               # Custom React hooks
├── services/            # API services
├── contexts/            # React contexts
└── lib/                 # Utilities
```

#### Backend Structure
```
app/
├── Http/
│   ├── Controllers/Portal/Api/  # API controllers
│   ├── Middleware/             # Custom middleware
│   └── Requests/              # Form requests
├── Models/                    # Eloquent models
├── Services/                  # Business logic
├── Repositories/              # Data access
└── Policies/                  # Authorization
```

### Development Patterns

#### Component Pattern
```jsx
// StandardComponent.jsx
import React, { useState, useEffect } from 'react';
import { useAuth } from '@/hooks/useAuth';
import { Card } from '@/components/ui/card';
import axiosInstance from '@/services/axiosInstance';

const StandardComponent = ({ prop1, prop2 }) => {
    const { user } = useAuth();
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        fetchData();
    }, []);

    const fetchData = async () => {
        try {
            const response = await axiosInstance.get('/endpoint');
            setData(response.data);
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    if (loading) return <LoadingSpinner />;
    if (error) return <ErrorAlert message={error} />;

    return (
        <Card>
            {/* Component content */}
        </Card>
    );
};

export default StandardComponent;
```

#### Service Pattern
```php
// app/Services/ExampleService.php
namespace App\Services;

use App\Models\Example;
use App\Repositories\ExampleRepository;
use Illuminate\Support\Facades\DB;

class ExampleService
{
    public function __construct(
        private ExampleRepository $repository
    ) {}

    public function process(array $data): Example
    {
        return DB::transaction(function () use ($data) {
            // Business logic here
            $example = $this->repository->create($data);
            
            // Additional processing
            event(new ExampleCreated($example));
            
            return $example;
        });
    }
}
```

#### API Controller Pattern
```php
// app/Http/Controllers/Portal/Api/ExampleController.php
namespace App\Http\Controllers\Portal\Api;

use App\Services\ExampleService;
use App\Http\Requests\ExampleRequest;
use Illuminate\Http\JsonResponse;

class ExampleController extends BaseApiController
{
    public function __construct(
        private ExampleService $service
    ) {}

    public function index(): JsonResponse
    {
        $company = $this->getCompany();
        $data = $this->service->getForCompany($company);
        
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function store(ExampleRequest $request): JsonResponse
    {
        try {
            $result = $this->service->create($request->validated());
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
```

### State Management

#### React Context Pattern
```jsx
// contexts/AppContext.jsx
import React, { createContext, useContext, useReducer } from 'react';

const AppContext = createContext();

const initialState = {
    user: null,
    company: null,
    preferences: {}
};

const appReducer = (state, action) => {
    switch (action.type) {
        case 'SET_USER':
            return { ...state, user: action.payload };
        case 'SET_COMPANY':
            return { ...state, company: action.payload };
        default:
            return state;
    }
};

export const AppProvider = ({ children }) => {
    const [state, dispatch] = useReducer(appReducer, initialState);
    
    return (
        <AppContext.Provider value={{ state, dispatch }}>
            {children}
        </AppContext.Provider>
    );
};

export const useApp = () => {
    const context = useContext(AppContext);
    if (!context) {
        throw new Error('useApp must be used within AppProvider');
    }
    return context;
};
```

### Real-time Features

#### WebSocket Integration
```javascript
// services/websocket.js
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.PUSHER_APP_KEY,
    cluster: process.env.PUSHER_APP_CLUSTER,
    forceTLS: true,
    auth: {
        headers: {
            Authorization: `Bearer ${token}`
        }
    }
});

// Listen for events
echo.private(`company.${companyId}`)
    .listen('CallReceived', (e) => {
        console.log('New call:', e.call);
        // Update UI
    })
    .listen('AppointmentCreated', (e) => {
        console.log('New appointment:', e.appointment);
        // Update UI
    });
```

### Testing Strategies

#### Frontend Testing
```javascript
// __tests__/Dashboard.test.jsx
import { render, screen, waitFor } from '@testing-library/react';
import { rest } from 'msw';
import { setupServer } from 'msw/node';
import Dashboard from '@/Pages/Portal/Dashboard';

const server = setupServer(
    rest.get('/business/api/dashboard', (req, res, ctx) => {
        return res(ctx.json({
            stats: { calls_today: 10 }
        }));
    })
);

beforeAll(() => server.listen());
afterEach(() => server.resetHandlers());
afterAll(() => server.close());

test('displays dashboard stats', async () => {
    render(<Dashboard />);
    
    await waitFor(() => {
        expect(screen.getByText('10')).toBeInTheDocument();
    });
});
```

#### Backend Testing
```php
// tests/Feature/Portal/DashboardTest.php
namespace Tests\Feature\Portal;

use Tests\TestCase;
use App\Models\PortalUser;
use App\Models\Company;

class DashboardTest extends TestCase
{
    public function test_dashboard_requires_authentication()
    {
        $response = $this->getJson('/business/api/dashboard');
        $response->assertStatus(401);
    }
    
    public function test_dashboard_returns_correct_data()
    {
        $company = Company::factory()->create();
        $user = PortalUser::factory()->for($company)->create();
        
        $response = $this->actingAs($user, 'portal')
            ->getJson('/business/api/dashboard');
            
        $response->assertStatus(200)
            ->assertJsonStructure([
                'stats' => ['calls_today', 'appointments_today'],
                'charts' => ['daily', 'hourly']
            ]);
    }
}
```

---

## 🚀 Deployment & Operations

### Production Deployment

#### Pre-deployment Checklist
```bash
# 1. Run tests
php artisan test
npm run test

# 2. Build assets
npm run build

# 3. Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# 4. Check environment
php artisan about

# 5. Backup database
php artisan backup:run
```

#### Deployment Script
```bash
#!/bin/bash
# deploy.sh

# Pull latest code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci

# Build assets
npm run build

# Run migrations
php artisan migrate --force

# Clear and rebuild caches
php artisan optimize:clear
php artisan optimize

# Restart services
sudo systemctl restart php8.3-fpm
sudo systemctl restart horizon
```

### Environment Configuration

#### Required Environment Variables
```env
# Application
APP_NAME="AskProAI Business Portal"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://business.askproai.de

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

# Queue
QUEUE_CONNECTION=redis
HORIZON_PREFIX=horizon:

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=noreply@askproai.de
MAIL_PASSWORD=secure_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@askproai.de
MAIL_FROM_NAME="${APP_NAME}"

# External Services
RETELL_API_KEY=your_retell_key
RETELL_WEBHOOK_SECRET=your_webhook_secret
CALCOM_API_KEY=your_calcom_key
STRIPE_KEY=your_stripe_key
STRIPE_SECRET=your_stripe_secret

# Sessions
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_DOMAIN=.askproai.de
SESSION_SECURE_COOKIE=true
```

### Monitoring & Logging

#### Application Monitoring
```php
// config/logging.php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['daily', 'slack'],
    ],
    'slack' => [
        'driver' => 'slack',
        'url' => env('LOG_SLACK_WEBHOOK_URL'),
        'username' => 'Business Portal',
        'emoji' => ':boom:',
        'level' => 'error',
    ],
]
```

#### Health Checks
```bash
# Horizon status
php artisan horizon:status

# Application health
curl https://business.askproai.de/health

# Database health
php artisan db:monitor
```

### Performance Optimization

#### Caching Strategy
```php
// Cache configuration
Cache::remember('dashboard:stats:' . $companyId, 300, function () {
    return $this->calculateStats();
});

// Query optimization
$calls = Call::with(['customer', 'appointments'])
    ->where('company_id', $companyId)
    ->whereDate('created_at', today())
    ->get();
```

#### Database Indexes
```sql
-- Critical indexes for performance
CREATE INDEX idx_calls_company_created ON calls(company_id, created_at);
CREATE INDEX idx_appointments_company_starts ON appointments(company_id, starts_at);
CREATE INDEX idx_customers_company_phone ON customers(company_id, phone_number);
```

---

## 🔒 Security & Permissions

### Authentication System

#### Multi-Guard Setup
```php
// config/auth.php
'guards' => [
    'web' => [          // Admin users
        'driver' => 'session',
        'provider' => 'users',
    ],
    'portal' => [       // Business portal users
        'driver' => 'session',
        'provider' => 'portal_users',
    ],
    'api' => [          // API access
        'driver' => 'sanctum',
        'provider' => 'portal_users',
    ],
]
```

#### Session Isolation
```php
// Separate session configurations
'portal' => [
    'driver' => 'redis',
    'connection' => 'portal_sessions',
    'table' => 'portal_sessions',
    'cookie' => 'portal_session',
    'path' => '/business',
]
```

### Permission System

#### Role-Based Access Control (RBAC)
```php
// Database schema
portal_permissions:
  - id
  - portal_user_id
  - permission_key
  - granted_at
  - granted_by

// Permission keys
permissions = [
    // Calls
    'calls.view_own',
    'calls.view_all',
    'calls.edit_own',
    'calls.edit_all',
    'calls.export',
    
    // Appointments
    'appointments.view_own',
    'appointments.view_all',
    'appointments.create',
    'appointments.edit',
    'appointments.delete',
    
    // Customers
    'customers.view',
    'customers.create',
    'customers.edit',
    'customers.delete',
    'customers.export',
    
    // Billing
    'billing.view',
    'billing.pay',
    'billing.manage',
    
    // Team
    'team.view',
    'team.manage',
    
    // Analytics
    'analytics.view',
    'analytics.export',
    
    // Settings
    'settings.view',
    'settings.edit',
    
    // Admin
    'admin.access',
    'admin.impersonate'
];
```

#### Permission Middleware
```php
// routes/business-portal.php
Route::get('/calls', [CallController::class, 'index'])
    ->middleware('portal.permission:calls.view_own');

// Custom middleware
class CheckPortalPermission
{
    public function handle($request, Closure $next, $permission)
    {
        $user = Auth::guard('portal')->user();
        
        if (!$user || !$user->hasPermission($permission)) {
            abort(403, 'Unauthorized action.');
        }
        
        return $next($request);
    }
}
```

### Security Best Practices

#### CSRF Protection
```javascript
// Frontend
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

axiosInstance.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
```

#### XSS Prevention
```jsx
// Always escape user input
<div dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(userContent) }} />
```

#### SQL Injection Prevention
```php
// Always use parameter binding
$calls = DB::select('
    SELECT * FROM calls 
    WHERE company_id = ? 
    AND created_at >= ?
', [$companyId, $startDate]);

// Or use Eloquent
$calls = Call::where('company_id', $companyId)
    ->where('created_at', '>=', $startDate)
    ->get();
```

#### API Rate Limiting
```php
// routes/api.php
Route::middleware(['throttle:api'])->group(function () {
    // API routes
});

// Custom rate limits
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});
```

---

## ⚡ Performance Guide

### Frontend Optimization

#### Code Splitting
```javascript
// Lazy load components
const Analytics = React.lazy(() => import('./Pages/Portal/Analytics'));

// Use Suspense
<Suspense fallback={<LoadingSpinner />}>
    <Analytics />
</Suspense>
```

#### Memoization
```jsx
// Memoize expensive calculations
const expensiveValue = useMemo(() => {
    return calculateExpensiveValue(data);
}, [data]);

// Memoize components
const MemoizedComponent = React.memo(Component, (prevProps, nextProps) => {
    return prevProps.id === nextProps.id;
});
```

#### Virtual Scrolling
```jsx
// For large lists
import { FixedSizeList } from 'react-window';

<FixedSizeList
    height={600}
    itemCount={1000}
    itemSize={50}
    width="100%"
>
    {Row}
</FixedSizeList>
```

### Backend Optimization

#### Query Optimization
```php
// N+1 query prevention
$calls = Call::with(['customer', 'appointments', 'tags'])
    ->where('company_id', $companyId)
    ->paginate(50);

// Use select to limit fields
$calls = Call::select(['id', 'from_number', 'duration', 'created_at'])
    ->where('company_id', $companyId)
    ->get();
```

#### Caching Strategies
```php
// Response caching
public function index()
{
    return Cache::remember("calls.index.{$companyId}", 300, function () {
        return $this->callService->getForCompany($companyId);
    });
}

// Cache invalidation
Cache::forget("calls.index.{$companyId}");
Cache::tags(['calls', "company.{$companyId}"])->flush();
```

#### Queue Optimization
```php
// Batch processing
Bus::batch([
    new ProcessCall($call1),
    new ProcessCall($call2),
    new ProcessCall($call3),
])->dispatch();

// Job chunking
Call::chunk(100, function ($calls) {
    foreach ($calls as $call) {
        ProcessCall::dispatch($call);
    }
});
```

### Database Optimization

#### Indexing Strategy
```sql
-- Composite indexes for common queries
CREATE INDEX idx_calls_company_status_created 
ON calls(company_id, status, created_at DESC);

-- Covering indexes
CREATE INDEX idx_appointments_covering 
ON appointments(company_id, starts_at, status, customer_id, staff_id);

-- Full-text search
ALTER TABLE customers ADD FULLTEXT(name, email, phone_number);
```

#### Query Analysis
```sql
-- Analyze slow queries
EXPLAIN SELECT * FROM calls WHERE company_id = 1 AND created_at >= '2025-01-01';

-- Check index usage
SHOW INDEX FROM calls;
```

---

## 📝 Changelog & Roadmap

### Version 2.0 (Current)
- ✅ Complete React migration
- ✅ Real-time dashboard
- ✅ Advanced analytics
- ✅ Goal tracking system
- ✅ Multi-language support
- ✅ Mobile optimization
- ✅ Enhanced security

### Version 1.0
- Initial release
- Basic call management
- Appointment booking
- Simple billing

### Roadmap 2025

#### Q1 2025
- [ ] Mobile app (React Native)
- [ ] Advanced AI insights
- [ ] WhatsApp integration
- [ ] Custom branding options

#### Q2 2025
- [ ] Video call support
- [ ] Advanced reporting builder
- [ ] API v2 with GraphQL
- [ ] Multi-location enhancements

#### Q3 2025
- [ ] AI-powered suggestions
- [ ] Automated workflows
- [ ] CRM integrations
- [ ] Voice analytics

#### Q4 2025
- [ ] International expansion
- [ ] Enterprise features
- [ ] Advanced security options
- [ ] Performance dashboard

---

## 🆘 Troubleshooting

### Common Issues

#### Authentication Problems
```bash
# Clear all caches
php artisan optimize:clear

# Reset sessions
php artisan session:table
php artisan migrate

# Check Redis
redis-cli
> FLUSHDB
```

#### Performance Issues
```bash
# Check slow queries
tail -f storage/logs/slow-queries.log

# Monitor Redis
redis-cli monitor

# Check Horizon
php artisan horizon:status
```

#### Frontend Build Issues
```bash
# Clear node modules
rm -rf node_modules package-lock.json
npm install

# Clear build cache
rm -rf public/build
npm run build
```

---

## 📚 Additional Resources

### Internal Documentation
- [API Gateway CLAUDE.md](./CLAUDE.md)
- [Deployment Checklist](./DEPLOYMENT_CHECKLIST.md)
- [Error Patterns](./ERROR_PATTERNS.md)
- [Troubleshooting Guide](./TROUBLESHOOTING_DECISION_TREE.md)

### External Resources
- [Laravel Documentation](https://laravel.com/docs)
- [React Documentation](https://react.dev)
- [Tailwind CSS](https://tailwindcss.com)
- [shadcn/ui](https://ui.shadcn.com)

### Support
- **Email**: support@askproai.de
- **Slack**: #business-portal
- **Issues**: GitHub Issues

---

<center>

**Built with ❤️ by the AskProAI Team**

</center>