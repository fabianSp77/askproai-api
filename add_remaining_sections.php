<?php

use App\Services\MCP\NotionMCPServer;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$notionServer = app(NotionMCPServer::class);

// Page ID to update
$pageId = '244aba11-76e2-812c-90a6-c055173ba565';

echo "📝 Adding remaining sections to Notion page...\n";

// Section 3: File-by-File Breakdown
$section3Content = <<<'MARKDOWN'

## 3. 📂 File-by-File Breakdown

### 🔧 Core Configuration Files

#### `composer.json` - Dependencies & Autoloading
```json
{
    "laravel/framework": "^10.0",
    "filament/filament": "^3.0",
    "stripe/stripe-php": "^13.0",
    "twilio/sdk": "^7.0"
}
```

#### `config/app.php` - Application Settings
- **Timezone**: 'Europe/Berlin'
- **Locale**: 'de' (German)
- **Providers**: Custom service providers for MCP integration

#### `config/database.php` - Multi-Environment DB Config
```php
'connections' => [
    'mysql' => [
        'host' => env('DB_HOST', '127.0.0.1'),
        'database' => env('DB_DATABASE', 'askproai_db'),
        'username' => env('DB_USERNAME', 'askproai_user'),
    ]
]
```

### 🛣️ Route Files Breakdown

#### `routes/web.php` - Web Routes (108 lines)
```php
// Business Portal Routes
Route::prefix('business')->group(function () {
    Route::get('/', [BusinessPortalController::class, 'dashboard'])
        ->middleware(['auth:portal']);
});

// Admin Routes
Route::prefix('admin')->group(function () {
    Route::middleware(['auth:admin'])->group(function () {
        // Filament admin panel
    });
});
```

#### `routes/api.php` - API Routes (287 lines)
```php
// Webhook Routes
Route::post('/retell/webhook-simple', [RetellWebhookController::class, 'handleWebhook']);
Route::post('/calcom/webhook', [CalcomWebhookController::class, 'handle']);
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);

// Business Portal API v2
Route::prefix('v2/portal')->group(function () {
    Route::post('auth/login', [PortalAuthController::class, 'login']);
    Route::middleware(['auth:portal'])->group(function () {
        Route::get('dashboard', [PortalDashboardController::class, 'index']);
        Route::get('calls', [PortalCallsController::class, 'index']);
        Route::get('appointments', [PortalAppointmentsController::class, 'index']);
    });
});
```

### 🎛️ Controllers Deep Dive

#### `RetellWebhookController.php` (456 lines)
**Purpose**: Processes all Retell.ai webhook events
```php
public function handleWebhook(Request $request)
{
    // 1. Verify webhook signature
    $this->verifySignature($request);
    
    // 2. Extract call data
    $callData = $request->all();
    
    // 3. Process based on event type
    switch ($callData['event_type']) {
        case 'call_started':
            return $this->handleCallStarted($callData);
        case 'call_ended':
            return $this->handleCallEnded($callData);
        case 'call_analyzed':
            return $this->handleCallAnalyzed($callData);
    }
}
```

**Key Methods**:
- `handleCallStarted()` - Initialize call record
- `handleCallAnalyzed()` - Extract appointment data, create customer
- `processAppointmentBooking()` - Book in Cal.com
- `deductPrepaidBalance()` - Charge for the call

#### `PortalAuthController.php` (234 lines)
**Purpose**: Business portal authentication
```php
public function login(Request $request)
{
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required'
    ]);
    
    if (Auth::guard('portal')->attempt($credentials)) {
        return response()->json([
            'token' => $user->createToken('portal')->plainTextToken,
            'user' => $user
        ]);
    }
    
    return response()->json(['error' => 'Invalid credentials'], 401);
}
```

### 🗃️ Models Architecture

#### `Company.php` (312 lines) - **Central Tenant Model**
```php
class Company extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'name', 'phone', 'email', 'address',
        'retell_agent_id', 'calcom_api_key',
        'stripe_customer_id', 'prepaid_balance'
    ];
    
    // Relationships
    public function branches() { return $this->hasMany(Branch::class); }
    public function staff() { return $this->hasMany(Staff::class); }
    public function customers() { return $this->hasMany(Customer::class); }
    public function appointments() { return $this->hasMany(Appointment::class); }
    public function calls() { return $this->hasMany(Call::class); }
    public function prepaidBalances() { return $this->hasMany(PrepaidBalance::class); }
}
```

#### `Call.php` (198 lines) - **Call Management**
```php
class Call extends Model
{
    protected $fillable = [
        'company_id', 'customer_id', 'retell_call_id',
        'phone_number', 'direction', 'status',
        'transcript', 'sentiment_score', 'cost'
    ];
    
    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'metadata' => 'json'
    ];
    
    // Scopes for multi-tenancy
    protected static function booted()
    {
        static::addGlobalScope(new CompanyScope);
    }
}
```

#### `PrepaidBalance.php` (156 lines) - **Credit System**
```php
class PrepaidBalance extends Model
{
    protected $fillable = [
        'company_id', 'balance', 'currency',
        'auto_topup_enabled', 'auto_topup_threshold',
        'auto_topup_amount'
    ];
    
    public function addBalance(float $amount, string $description)
    {
        DB::transaction(function () use ($amount, $description) {
            $this->increment('balance', $amount);
            
            PrepaidTransaction::create([
                'company_id' => $this->company_id,
                'amount' => $amount,
                'type' => 'credit',
                'description' => $description
            ]);
        });
    }
    
    public function deductBalance(float $amount, string $description)
    {
        if ($this->balance < $amount) {
            throw new InsufficientBalanceException();
        }
        
        DB::transaction(function () use ($amount, $description) {
            $this->decrement('balance', $amount);
            
            PrepaidTransaction::create([
                'company_id' => $this->company_id,
                'amount' => -$amount,
                'type' => 'debit',
                'description' => $description
            ]);
        });
    }
}
```

### 🔧 Services Architecture

#### `RetellV2Service.php` (567 lines) - **AI Phone Integration**
```php
class RetellV2Service
{
    public function createOutboundCall(array $callData): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.retell.api_key'),
            'Content-Type' => 'application/json'
        ])->post('https://api.retellai.com/v2/create-phone-call', [
            'from_number' => $callData['from_number'],
            'to_number' => $callData['to_number'],
            'agent_id' => $callData['agent_id'],
            'metadata' => $callData['metadata'] ?? []
        ]);
        
        if ($response->successful()) {
            return $response->json();
        }
        
        throw new RetellApiException($response->body());
    }
    
    public function processWebhookEvent(array $eventData): void
    {
        switch ($eventData['event']) {
            case 'call_started':
                $this->handleCallStarted($eventData);
                break;
            case 'call_ended':
                $this->handleCallEnded($eventData);
                break;
            case 'call_analyzed':
                $this->handleCallAnalyzed($eventData);
                break;
        }
    }
}
```

#### `AppointmentService.php` (423 lines) - **Booking Logic**
```php
class AppointmentService
{
    public function bookAppointment(array $appointmentData): Appointment
    {
        DB::transaction(function () use ($appointmentData) {
            // 1. Find or create customer
            $customer = $this->findOrCreateCustomer($appointmentData['customer']);
            
            // 2. Check availability
            $this->checkAvailability($appointmentData['datetime'], $appointmentData['service_id']);
            
            // 3. Book in Cal.com
            $calcomBooking = $this->calcomService->createBooking([
                'eventTypeId' => $appointmentData['service_id'],
                'start' => $appointmentData['datetime'],
                'attendee' => [
                    'email' => $customer->email,
                    'name' => $customer->name,
                    'phone' => $customer->phone
                ]
            ]);
            
            // 4. Create local appointment
            $appointment = Appointment::create([
                'company_id' => $appointmentData['company_id'],
                'customer_id' => $customer->id,
                'service_id' => $appointmentData['service_id'],
                'scheduled_at' => $appointmentData['datetime'],
                'calcom_booking_id' => $calcomBooking['id'],
                'status' => 'confirmed'
            ]);
            
            // 5. Deduct from prepaid balance
            $this->prepaidService->deductForAppointment($appointment);
            
            return $appointment;
        });
    }
}
```

### 🎨 Frontend Structure

#### Business Portal (resources/js/portal/)
```
portal/
├── components/
│   ├── Dashboard/
│   │   ├── StatsCards.jsx          # KPI cards
│   │   ├── CallsChart.jsx          # Call analytics
│   │   └── RecentActivity.jsx      # Latest activities
│   ├── Calls/
│   │   ├── CallsList.jsx           # Calls table
│   │   ├── CallDetails.jsx         # Individual call view
│   │   └── CallFilters.jsx         # Filter/search
│   ├── Appointments/
│   │   ├── Calendar.jsx            # Calendar view
│   │   ├── AppointmentForm.jsx     # Booking form
│   │   └── AppointmentList.jsx     # List view
│   └── Billing/
│       ├── BalanceCard.jsx         # Current balance
│       ├── TopupModal.jsx          # Add credits
│       └── TransactionHistory.jsx  # Payment history
├── pages/
│   ├── Dashboard.jsx               # Main dashboard
│   ├── Calls.jsx                  # Calls management
│   ├── Appointments.jsx           # Appointments page
│   ├── Billing.jsx                # Billing page
│   └── Settings.jsx               # Company settings
└── layouts/
    ├── PortalLayout.jsx           # Main layout
    └── AuthLayout.jsx             # Login layout
```

#### Admin Panel (Filament Resources)
```
app/Filament/Admin/Resources/
├── CompanyResource.php            # Company management
├── CallResource.php               # Call records
├── AppointmentResource.php        # Appointments
├── CustomerResource.php           # Customer management
├── PrepaidBalanceResource.php     # Balance management
└── UserResource.php               # Admin users
```

MARKDOWN;

// Section 4: API Endpoints Analysis
$section4Content = <<<'MARKDOWN'

## 4. 🔌 API Endpoints Analysis

### 📞 Webhook Endpoints (External → AskProAI)

#### Retell.ai Webhooks
```http
POST /api/retell/webhook-simple
Content-Type: application/json
X-Retell-Signature: sha256=...

# Handles all Retell.ai events:
# - call_started: Initialize call record
# - call_ended: Finalize call, calculate cost
# - call_analyzed: Extract data, book appointments
```

**Webhook Processing Flow**:
1. **Signature Verification**: Verify X-Retell-Signature header
2. **Event Routing**: Route to appropriate handler based on event_type
3. **Data Extraction**: Parse call transcript and metadata
4. **Business Logic**: Create customers, book appointments
5. **Balance Deduction**: Charge prepaid balance
6. **Response**: Return success/error status

#### Cal.com Webhooks
```http
POST /api/calcom/webhook
Content-Type: application/json
X-Cal-Signature: sha256=...

# Events:
# - booking.created: Appointment booked externally
# - booking.cancelled: Appointment cancelled
# - booking.rescheduled: Appointment time changed
```

#### Stripe Webhooks
```http
POST /api/stripe/webhook
Content-Type: application/json
Stripe-Signature: t=...,v1=...

# Payment events:
# - payment_intent.succeeded: Payment completed
# - invoice.payment_failed: Payment failed
# - customer.subscription.updated: Subscription changes
```

### 🏢 Business Portal API v2

#### Authentication Endpoints
```http
# Login
POST /api/v2/portal/auth/login
{
    "email": "demo@company.com",
    "password": "password123"
}
Response: {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "user": { "id": 1, "name": "Demo User", "company_id": 5 }
}

# Logout
POST /api/v2/portal/auth/logout
Authorization: Bearer {token}

# Refresh Token
POST /api/v2/portal/auth/refresh
Authorization: Bearer {token}
```

#### Dashboard & Analytics
```http
# Dashboard Overview
GET /api/v2/portal/dashboard
Authorization: Bearer {token}
Response: {
    "stats": {
        "total_calls": 150,
        "successful_bookings": 89,
        "conversion_rate": "59.3%",
        "prepaid_balance": 1250.50
    },
    "recent_calls": [...],
    "upcoming_appointments": [...]
}

# Detailed Analytics
GET /api/v2/portal/analytics?period=7d&metrics=calls,bookings,revenue
Response: {
    "period": "7d",
    "metrics": {
        "calls": { "total": 45, "trend": "+12%" },
        "bookings": { "total": 28, "trend": "+8%" },
        "revenue": { "total": 1420.00, "trend": "+15%" }
    },
    "charts": {
        "daily_calls": [...],
        "hourly_distribution": [...]
    }
}
```

#### Calls Management
```http
# List Calls with Filters
GET /api/v2/portal/calls?page=1&per_page=20&status=completed&date_from=2025-08-01
Response: {
    "data": [
        {
            "id": 123,
            "retell_call_id": "call_abc123",
            "phone_number": "+49 30 12345678",
            "direction": "inbound",
            "status": "completed",
            "duration": 180,
            "transcript": "Guten Tag, ich möchte einen Termin...",
            "sentiment_score": 0.75,
            "cost": 2.50,
            "created_at": "2025-08-03T10:30:00Z"
        }
    ],
    "meta": { "total": 150, "current_page": 1, "per_page": 20 }
}

# Get Call Details
GET /api/v2/portal/calls/123
Response: {
    "id": 123,
    "retell_call_id": "call_abc123",
    "customer": { "name": "Max Mustermann", "phone": "+49..." },
    "transcript_segments": [...],
    "extracted_data": {
        "appointment_request": true,
        "service_type": "Hausarzt Termin",
        "preferred_date": "2025-08-10"
    },
    "outcome": {
        "appointment_booked": true,
        "appointment_id": 456
    }
}

# Export Calls
POST /api/v2/portal/calls/export
{
    "format": "csv",
    "filters": { "date_from": "2025-08-01", "status": "completed" }
}
Response: {
    "download_url": "https://api.askproai.de/exports/calls_2025-08-03.csv",
    "expires_at": "2025-08-03T23:59:59Z"
}
```

#### Appointments Management
```http
# List Appointments
GET /api/v2/portal/appointments?date_from=2025-08-03&status=confirmed
Response: {
    "data": [
        {
            "id": 456,
            "customer": { "name": "Max Mustermann", "phone": "+49..." },
            "service": { "name": "Hausarzt Termin", "duration": 30 },
            "scheduled_at": "2025-08-10T14:00:00Z",
            "status": "confirmed",
            "calcom_booking_id": "booking_xyz789",
            "source": "ai_call"
        }
    ]
}

# Create Appointment (Manual)
POST /api/v2/portal/appointments
{
    "customer_id": 123,
    "service_id": 5,
    "scheduled_at": "2025-08-10T14:00:00Z",
    "notes": "Patient prefers afternoon appointments"
}

# Update Appointment
PUT /api/v2/portal/appointments/456
{
    "scheduled_at": "2025-08-10T15:00:00Z",
    "status": "rescheduled"
}

# Cancel Appointment
DELETE /api/v2/portal/appointments/456
```

#### Billing & Prepaid Management
```http
# Get Current Balance
GET /api/v2/portal/billing/balance
Response: {
    "current_balance": 1250.50,
    "currency": "EUR",
    "auto_topup": {
        "enabled": true,
        "threshold": 100.00,
        "amount": 500.00
    },
    "monthly_usage": 89.50
}

# Top-up Balance
POST /api/v2/portal/billing/topup
{
    "amount": 500.00,
    "payment_method": "stripe",
    "return_url": "https://portal.company.com/billing"
}
Response: {
    "payment_intent_id": "pi_abc123",
    "client_secret": "pi_abc123_secret_xyz",
    "stripe_url": "https://checkout.stripe.com/..."
}

# Transaction History
GET /api/v2/portal/billing/transactions?page=1&type=all
Response: {
    "data": [
        {
            "id": 789,
            "type": "debit",
            "amount": -2.50,
            "description": "Inbound call - 3 min",
            "created_at": "2025-08-03T10:33:00Z"
        },
        {
            "id": 788,
            "type": "credit",
            "amount": 500.00,
            "description": "Stripe payment",
            "created_at": "2025-08-03T09:00:00Z"
        }
    ]
}

# Configure Auto-Topup
POST /api/v2/portal/billing/auto-topup
{
    "enabled": true,
    "threshold": 100.00,
    "amount": 500.00
}
```

### 🔧 Admin API Endpoints

#### System Monitoring
```http
# Health Check
GET /api/health
Response: {
    "status": "healthy",
    "database": "connected",
    "redis": "connected",
    "queue": "processing",
    "external_apis": {
        "retell": "operational",
        "calcom": "operational",
        "stripe": "operational"
    }
}

# Prometheus Metrics
GET /api/metrics
Content-Type: text/plain

# app_requests_total{method="GET",endpoint="/api/v2/portal/dashboard"} 1250
# app_response_time_seconds{endpoint="/api/v2/portal/calls"} 0.145
# prepaid_balance_total{company_id="5"} 1250.50
```

#### Company Management
```http
# List Companies
GET /api/admin/companies?page=1&search=Demo
Response: {
    "data": [
        {
            "id": 5,
            "name": "Demo Hausarztpraxis Dr. Schmidt",
            "phone": "+49 30 12345678",
            "status": "active",
            "prepaid_balance": 1250.50,
            "total_calls": 150,
            "created_at": "2025-07-15T08:00:00Z"
        }
    ]
}

# Create Company
POST /api/admin/companies
{
    "name": "Neue Praxis GmbH",
    "phone": "+49 40 9876543",
    "email": "info@neuepraxis.de",
    "initial_balance": 100.00
}

# Company Statistics
GET /api/admin/companies/5/stats
Response: {
    "calls": { "total": 150, "this_month": 45 },
    "appointments": { "total": 89, "this_month": 28 },
    "revenue": { "total": 375.00, "this_month": 112.50 },
    "balance": { "current": 1250.50, "avg_monthly_usage": 89.50 }
}
```

### 🔄 MCP Integration Endpoints

#### MCP Command Execution
```http
# Execute MCP Command
POST /api/mcp/execute
{
    "server": "retell",
    "command": "create_outbound_call",
    "params": {
        "company_id": 5,
        "phone_number": "+49 30 87654321",
        "campaign_id": 12
    }
}
Response: {
    "success": true,
    "data": {
        "call_id": "call_def456",
        "status": "initiated"
    }
}

# MCP Health Status
GET /api/mcp/health
Response: {
    "servers": {
        "retell": { "status": "active", "last_ping": "2025-08-03T10:35:00Z" },
        "calcom": { "status": "active", "last_ping": "2025-08-03T10:35:00Z" },
        "stripe": { "status": "active", "last_ping": "2025-08-03T10:35:00Z" }
    }
}
```

MARKDOWN;

// Section 5: Architecture Deep Dive
$section5Content = <<<'MARKDOWN'

## 5. 🏗️ Architecture Deep Dive

### 🎯 Multi-Tenancy Architecture

#### Tenant Isolation Strategy
```php
// Global Scope für automatische Tenant-Isolation
class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if (auth()->check() && auth()->user()->company_id) {
            $builder->where('company_id', auth()->user()->company_id);
        }
    }
}

// Anwendung in Models
class Call extends Model
{
    protected static function booted()
    {
        static::addGlobalScope(new CompanyScope);
    }
}
```

#### Data Flow Architecture
```
Request → Middleware → Controller → Service → Repository → Model → Database
   ↓         ↓            ↓          ↓         ↓          ↓         ↓
Auth     TenantScope   Business   Data      Global    SQL       MySQL
Check    Applied       Logic      Access    Scope     Query     8.0
```

### 🔄 Event-Driven Architecture

#### Webhook Event Processing
```php
// Event Dispatcher Pattern
class WebhookEventDispatcher
{
    protected array $handlers = [
        'retell.call_started' => [HandleCallStarted::class],
        'retell.call_ended' => [HandleCallEnded::class, UpdateCallStatistics::class],
        'retell.call_analyzed' => [ProcessAppointmentData::class, DeductBalance::class],
        'calcom.booking_created' => [SyncAppointment::class],
        'stripe.payment_succeeded' => [UpdatePrepaidBalance::class]
    ];
    
    public function dispatch(string $event, array $payload): void
    {
        foreach ($this->handlers[$event] ?? [] as $handlerClass) {
            dispatch(new $handlerClass($payload));
        }
    }
}
```

#### Queue Architecture
```
High Priority Queue (retell-webhooks)
├── Call Processing Jobs
├── Appointment Booking Jobs
└── Balance Deduction Jobs

Normal Priority Queue (default)
├── Email Notifications
├── SMS Sending
└── Data Synchronization

Low Priority Queue (cleanup)
├── Log Cleanup
├── Cache Warming
└── Analytics Processing
```

### 💾 Database Architecture

#### Core Tables Structure
```sql
-- Companies (Tenants)
CREATE TABLE companies (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    email VARCHAR(255),
    retell_agent_id VARCHAR(255),
    calcom_api_key TEXT,
    stripe_customer_id VARCHAR(255),
    status ENUM('active', 'suspended', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Calls (Central entity)
CREATE TABLE calls (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    company_id BIGINT NOT NULL,
    customer_id BIGINT,
    retell_call_id VARCHAR(255) UNIQUE,
    phone_number VARCHAR(50) NOT NULL,
    direction ENUM('inbound', 'outbound') NOT NULL,
    status ENUM('initiated', 'in_progress', 'completed', 'failed') DEFAULT 'initiated',
    duration INTEGER DEFAULT 0, -- seconds
    transcript LONGTEXT,
    sentiment_score DECIMAL(3,2), -- 0.00 to 1.00
    cost DECIMAL(8,2) DEFAULT 0.00,
    metadata JSON,
    started_at TIMESTAMP NULL,
    ended_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    
    INDEX idx_company_status (company_id, status),
    INDEX idx_retell_call_id (retell_call_id),
    INDEX idx_phone_number (phone_number),
    INDEX idx_created_at (created_at),
    INDEX idx_direction_status (direction, status)
);

-- Prepaid Balance System
CREATE TABLE prepaid_balances (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    company_id BIGINT NOT NULL UNIQUE,
    balance DECIMAL(10,2) DEFAULT 0.00,
    currency VARCHAR(3) DEFAULT 'EUR',
    auto_topup_enabled BOOLEAN DEFAULT FALSE,
    auto_topup_threshold DECIMAL(8,2) DEFAULT 0.00,
    auto_topup_amount DECIMAL(8,2) DEFAULT 0.00,
    stripe_payment_method_id VARCHAR(255),
    last_topup_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    
    INDEX idx_balance (balance),
    INDEX idx_auto_topup (auto_topup_enabled, auto_topup_threshold)
);

-- Transaction History
CREATE TABLE prepaid_transactions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    company_id BIGINT NOT NULL,
    prepaid_balance_id BIGINT NOT NULL,
    amount DECIMAL(10,2) NOT NULL, -- positive for credit, negative for debit
    type ENUM('credit', 'debit') NOT NULL,
    description VARCHAR(500),
    reference_type VARCHAR(100), -- 'call', 'topup', 'adjustment'
    reference_id BIGINT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (prepaid_balance_id) REFERENCES prepaid_balances(id) ON DELETE CASCADE,
    
    INDEX idx_company_type (company_id, type),
    INDEX idx_reference (reference_type, reference_id),
    INDEX idx_created_at (created_at)
);
```

#### Performance Optimizations
```sql
-- Partitioning for large tables
CREATE TABLE calls_2025_08 PARTITION OF calls
FOR VALUES FROM ('2025-08-01') TO ('2025-09-01');

-- Optimized indexes for common queries
CREATE INDEX idx_calls_company_date ON calls (company_id, created_at DESC);
CREATE INDEX idx_calls_phone_lookup ON calls (phone_number, company_id);
CREATE INDEX idx_appointments_schedule ON appointments (company_id, scheduled_at);

-- Materialized views for analytics
CREATE MATERIALIZED VIEW daily_call_stats AS
SELECT 
    company_id,
    DATE(created_at) as call_date,
    COUNT(*) as total_calls,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_calls,
    SUM(cost) as total_cost,
    AVG(duration) as avg_duration
FROM calls 
GROUP BY company_id, DATE(created_at);
```

### 🔧 Service Layer Architecture

#### Service Pattern Implementation
```php
// Base Service Class
abstract class BaseService
{
    protected LoggerInterface $logger;
    protected CacheManager $cache;
    
    public function __construct(LoggerInterface $logger, CacheManager $cache)
    {
        $this->logger = $logger;
        $this->cache = $cache;
    }
    
    protected function logOperation(string $operation, array $context = []): void
    {
        $this->logger->info("Service operation: {$operation}", $context);
    }
}

// Retell Service with Circuit Breaker
class RetellV2Service extends BaseService
{
    private CircuitBreaker $circuitBreaker;
    
    public function createOutboundCall(array $callData): array
    {
        return $this->circuitBreaker->call(function() use ($callData) {
            $response = Http::timeout(30)
                ->retry(3, 1000)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . config('services.retell.api_key')
                ])
                ->post('https://api.retellai.com/v2/create-phone-call', $callData);
                
            if ($response->failed()) {
                throw new RetellApiException($response->body());
            }
            
            return $response->json();
        });
    }
}
```

#### MCP Server Architecture
```php
// MCP Server Registry
class MCPServerRegistry
{
    protected array $servers = [];
    
    public function register(string $name, MCPServerInterface $server): void
    {
        $this->servers[$name] = $server;
    }
    
    public function execute(string $serverName, string $command, array $params = []): array
    {
        if (!isset($this->servers[$serverName])) {
            throw new MCPServerNotFoundException($serverName);
        }
        
        return $this->servers[$serverName]->executeTool($command, $params);
    }
}

// Individual MCP Server
class RetellMCPServer implements MCPServerInterface
{
    protected RetellV2Service $retellService;
    
    public function executeTool(string $tool, array $params): array
    {
        $this->validateParams($tool, $params);
        
        switch ($tool) {
            case 'create_outbound_call':
                return $this->createOutboundCall($params);
            case 'get_call_details':
                return $this->getCallDetails($params);
            case 'list_agent_calls':
                return $this->listAgentCalls($params);
            default:
                throw new UnsupportedToolException($tool);
        }
    }
    
    protected function createOutboundCall(array $params): array
    {
        // Validate company access
        $this->ensureCompanyAccess($params['company_id']);
        
        // Check prepaid balance
        $this->ensureSufficientBalance($params['company_id']);
        
        // Create call via Retell API
        $result = $this->retellService->createOutboundCall($params);
        
        // Store call record
        $call = Call::create([
            'company_id' => $params['company_id'],
            'retell_call_id' => $result['call_id'],
            'phone_number' => $params['to_number'],
            'direction' => 'outbound',
            'status' => 'initiated'
        ]);
        
        return [
            'success' => true,
            'data' => [
                'call_id' => $call->id,
                'retell_call_id' => $result['call_id'],
                'status' => 'initiated'
            ]
        ];
    }
}
```

### 🔒 Security Architecture

#### Authentication & Authorization
```php
// Multi-Guard Configuration
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'admin' => [
        'driver' => 'session',
        'provider' => 'admin_users',
    ],
    'portal' => [
        'driver' => 'sanctum',
        'provider' => 'portal_users',
    ],
    'api' => [
        'driver' => 'sanctum',
        'provider' => 'users',
    ],
],

// RBAC Implementation
class Permission extends Model
{
    public const PERMISSIONS = [
        'calls.view' => 'View calls',
        'calls.create' => 'Create calls',
        'appointments.manage' => 'Manage appointments',
        'billing.view' => 'View billing',
        'settings.manage' => 'Manage settings'
    ];
}

class Role extends Model
{
    public const ROLES = [
        'company_admin' => ['calls.*', 'appointments.*', 'billing.*', 'settings.*'],
        'company_staff' => ['calls.view', 'appointments.manage'],
        'company_viewer' => ['calls.view', 'appointments.view']
    ];
}
```

#### Security Middleware Stack
```php
class SecurityMiddlewareStack
{
    protected array $middleware = [
        'threat.detection' => ThreatDetectionMiddleware::class,
        'rate.limiting' => RateLimitingMiddleware::class,
        'webhook.verification' => WebhookSignatureMiddleware::class,
        'tenant.isolation' => TenantIsolationMiddleware::class,
        'audit.logging' => AuditLoggingMiddleware::class,
    ];
}

// Rate Limiting Implementation
class RateLimitingMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->resolveRequestSignature($request);
        $maxAttempts = $this->getMaxAttempts($request);
        $decayMinutes = $this->getDecayMinutes($request);
        
        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return $this->buildTooManyAttemptsResponse($key, $maxAttempts);
        }
        
        $this->limiter->hit($key, $decayMinutes * 60);
        
        return $next($request);
    }
}
```

### 📊 Monitoring & Observability

#### Metrics Collection
```php
// Custom Metrics Collector
class MetricsCollector
{
    public function collectApplicationMetrics(): array
    {
        return [
            'app_requests_total' => $this->getRequestCount(),
            'app_response_time_seconds' => $this->getResponseTime(),
            'app_errors_total' => $this->getErrorCount(),
            'app_active_users' => $this->getActiveUsers(),
            'prepaid_balance_total' => $this->getTotalPrepaidBalance(),
            'calls_processed_total' => $this->getCallsProcessed(),
            'queue_jobs_pending' => $this->getQueueSize(),
            'external_api_calls_total' => $this->getExternalApiCalls(),
        ];
    }
    
    protected function getCallsProcessed(): array
    {
        return Call::selectRaw('
            company_id,
            COUNT(*) as total,
            COUNT(CASE WHEN status = "completed" THEN 1 END) as completed,
            COUNT(CASE WHEN status = "failed" THEN 1 END) as failed
        ')
        ->where('created_at', '>=', now()->subDay())
        ->groupBy('company_id')
        ->get()
        ->mapWithKeys(function ($item) {
            return [
                "calls_processed_total{company_id=\"{$item->company_id}\",status=\"total\"}" => $item->total,
                "calls_processed_total{company_id=\"{$item->company_id}\",status=\"completed\"}" => $item->completed,
                "calls_processed_total{company_id=\"{$item->company_id}\",status=\"failed\"}" => $item->failed,
            ];
        })
        ->toArray();
    }
}
```

#### Health Checks
```php
class HealthCheckService
{
    public function checkSystemHealth(): array
    {
        return [
            'status' => $this->getOverallStatus(),
            'checks' => [
                'database' => $this->checkDatabase(),
                'redis' => $this->checkRedis(),
                'queue' => $this->checkQueue(),
                'external_apis' => $this->checkExternalApis(),
                'storage' => $this->checkStorage(),
                'memory' => $this->checkMemoryUsage(),
            ],
            'timestamp' => now()->toISOString()
        ];
    }
    
    protected function checkExternalApis(): array
    {
        return [
            'retell' => $this->pingRetellApi(),
            'calcom' => $this->pingCalcomApi(),
            'stripe' => $this->pingStripeApi(),
            'twilio' => $this->pingTwilioApi(),
        ];
    }
}
```

MARKDOWN;

echo "📝 Adding Section 3: File-by-File Breakdown...\n";

$result1 = $notionServer->executeTool('add_content_to_page', [
    'page_id' => $pageId,
    'content' => $section3Content
]);

if ($result1['success']) {
    echo "✅ Section 3 added successfully!\n";
} else {
    echo "❌ Failed to add Section 3: " . $result1['error'] . "\n";
}

echo "\n📝 Adding Section 4: API Endpoints Analysis...\n";

$result2 = $notionServer->executeTool('add_content_to_page', [
    'page_id' => $pageId,
    'content' => $section4Content
]);

if ($result2['success']) {
    echo "✅ Section 4 added successfully!\n";
} else {
    echo "❌ Failed to add Section 4: " . $result2['error'] . "\n";
}

echo "\n📝 Adding Section 5: Architecture Deep Dive...\n";

$result3 = $notionServer->executeTool('add_content_to_page', [
    'page_id' => $pageId,
    'content' => $section5Content
]);

if ($result3['success']) {
    echo "✅ Section 5 added successfully!\n";
} else {
    echo "❌ Failed to add Section 5: " . $result3['error'] . "\n";
}

// Final summary
if ($result1['success'] && $result2['success'] && $result3['success']) {
    echo "\n🎉 All remaining sections uploaded successfully!\n";
    echo "📄 Complete documentation now available at:\n";
    echo "🔗 https://www.notion.so/AskProAI-Codebase-Analysis-Architecture-244aba1176e2812c90a6c055173ba565\n";
    
    echo "\n📋 Final Documentation Structure:\n";
    echo "=" . str_repeat("=", 50) . "\n";
    echo "✅ Section 1: Project Overview\n";
    echo "✅ Section 2: Directory Structure Analysis\n";
    echo "✅ Section 3: File-by-File Breakdown\n";
    echo "✅ Section 4: API Endpoints Analysis\n";
    echo "✅ Section 5: Architecture Deep Dive\n";
    echo "=" . str_repeat("=", 50) . "\n";
} else {
    echo "\n⚠️ Some sections failed to upload. Check individual results above.\n";
}