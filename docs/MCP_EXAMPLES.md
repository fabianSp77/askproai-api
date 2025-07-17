# MCP Practical Examples

## Real-World Usage Scenarios

This guide provides practical examples of using MCP servers in common development scenarios.

## 📅 Appointment Management

### Example 1: Book an Appointment

```bash
# Using shortcut
php artisan mcp book

# Direct execution
php artisan mcp exec --server=appointment --tool=create_appointment --params='{
  "customer_phone": "+49123456789",
  "service": "Haircut",
  "date": "2025-01-15",
  "time": "14:00",
  "branch_id": 1,
  "notes": "First time customer"
}'
```

### Example 2: Check Availability

```php
// In your code
$orchestrator = app(MCPOrchestrator::class);

$result = $orchestrator->execute('appointment', 'check_availability', [
    'date' => '2025-01-15',
    'service_id' => 123,
    'branch_id' => 1
]);

if ($result['success']) {
    $slots = $result['data']['available_slots'];
    // Display available time slots
}
```

### Example 3: Bulk Appointment Import

```php
// Import appointments from CSV
$appointments = [
    ['phone' => '+49111', 'date' => '2025-01-15', 'time' => '10:00'],
    ['phone' => '+49222', 'date' => '2025-01-15', 'time' => '11:00'],
];

foreach ($appointments as $apt) {
    $result = $orchestrator->execute('appointment', 'create_appointment', $apt);
    
    if (!$result['success']) {
        Log::error('Failed to import appointment', $apt);
    }
}
```

## 📞 Call Processing

### Example 1: Import Recent Calls

```bash
# Import last 50 calls
php artisan mcp calls

# Import with specific parameters
php artisan mcp exec --server=retell --tool=fetch_calls --params='{
  "limit": 100,
  "from_date": "2025-01-01",
  "status": "completed"
}'
```

### Example 2: Analyze Call Patterns

```php
// Analyze call patterns for optimization
$calls = $orchestrator->execute('retell', 'fetch_calls', [
    'limit' => 1000,
    'from_date' => now()->subMonth()
]);

$stats = [
    'total' => count($calls['data']),
    'by_hour' => [],
    'by_day' => [],
    'average_duration' => 0
];

foreach ($calls['data'] as $call) {
    $hour = Carbon::parse($call['created_at'])->hour;
    $stats['by_hour'][$hour] = ($stats['by_hour'][$hour] ?? 0) + 1;
}

// Find peak hours for staffing optimization
```

### Example 3: Call Follow-up Automation

```php
// Automatically create follow-up tasks for missed calls
$missedCalls = $orchestrator->execute('retell', 'fetch_calls', [
    'status' => 'missed',
    'from_date' => now()->subDay()
]);

foreach ($missedCalls['data'] as $call) {
    // Check if customer exists
    $customer = $orchestrator->execute('customer', 'search_customers', [
        'phone' => $call['from_number']
    ]);
    
    if ($customer['success'] && !empty($customer['data'])) {
        // Create follow-up task
        $orchestrator->execute('queue', 'dispatch_job', [
            'job' => 'SendFollowUpSMS',
            'data' => [
                'customer_id' => $customer['data'][0]['id'],
                'message' => 'We missed your call. How can we help?'
            ]
        ]);
    }
}
```

## 💰 Payment Processing

### Example 1: Create Invoice

```php
$invoice = $orchestrator->execute('stripe', 'create_invoice', [
    'customer_id' => 'cus_123456',
    'items' => [
        ['price' => 'price_haircut', 'quantity' => 1],
        ['price' => 'price_coloring', 'quantity' => 1]
    ],
    'due_date' => now()->addDays(14)
]);

if ($invoice['success']) {
    // Send invoice email
    Mail::to($customer->email)->send(new InvoiceCreated($invoice['data']));
}
```

### Example 2: Process Refund

```php
// Refund for cancelled appointment
$refund = $orchestrator->execute('stripe', 'create_refund', [
    'payment_intent' => 'pi_123456',
    'amount' => 5000, // in cents
    'reason' => 'requested_by_customer',
    'metadata' => [
        'appointment_id' => $appointment->id,
        'cancelled_at' => now()
    ]
]);
```

## 🔄 Synchronization Tasks

### Example 1: Full System Sync

```php
// Create a command for daily sync
class DailySyncCommand extends Command
{
    public function handle(MCPOrchestrator $orchestrator)
    {
        $this->info('Starting daily sync...');
        
        // Sync Cal.com
        $calcom = $orchestrator->execute('calcom', 'sync_calendars', [
            'full_sync' => true
        ]);
        $this->info('Cal.com: ' . ($calcom['success'] ? 'OK' : 'Failed'));
        
        // Import calls
        $calls = $orchestrator->execute('retell', 'fetch_calls', [
            'limit' => 500,
            'from_date' => now()->subDay()
        ]);
        $this->info('Calls imported: ' . count($calls['data'] ?? []));
        
        // Sync GitHub issues
        $github = $orchestrator->execute('github', 'sync_issues', [
            'repo' => 'askproai/api-gateway',
            'state' => 'open'
        ]);
        $this->info('GitHub issues: ' . ($github['success'] ? 'OK' : 'Failed'));
    }
}
```

### Example 2: Real-time Webhook Processing

```php
// In WebhookController
public function handleCalcomWebhook(Request $request)
{
    $event = $request->input('event');
    
    switch ($event['type']) {
        case 'booking.created':
            $result = $this->orchestrator->execute('webhook', 'process_event', [
                'source' => 'calcom',
                'event_type' => 'booking.created',
                'payload' => $event['data']
            ]);
            
            // Update local appointment
            if ($result['success']) {
                $this->orchestrator->execute('appointment', 'sync_from_calcom', [
                    'calcom_booking_id' => $event['data']['id']
                ]);
            }
            break;
    }
}
```

## 🧠 AI-Powered Features

### Example 1: Smart Task Discovery

```bash
# Let AI find the right tool
php artisan mcp discover
> Describe what you want to do: Find all customers who haven't booked in 30 days

# AI suggests:
# Server: customer
# Tool: find_inactive_customers
# Confidence: 95%
```

### Example 2: Code Generation from Business Requirements

```php
// Use developer assistant
$assistant = app(DeveloperAssistantService::class);

$code = $assistant->generateCode(
    "Create a service that sends appointment reminders 24 hours before",
    'service'
);

// Generated service includes:
// - Reminder scheduling logic
// - Customer notification preferences
// - Multi-channel support (SMS, Email, WhatsApp)
// - Error handling and retry logic
```

## 💾 Memory Bank Usage

### Example 1: Session Context Management

```php
// Store user session context
$memory = app(MemoryBankAutomationService::class);

// Beginning of conversation
$memory->startSession('session_123', [
    'user_id' => $user->id,
    'context' => 'booking_flow',
    'preferences' => [
        'language' => 'de',
        'preferred_branch' => 1
    ]
]);

// During conversation
$memory->addToSession('session_123', 'selected_service', 'Haircut');
$memory->addToSession('session_123', 'selected_date', '2025-01-15');

// Retrieve full context
$context = $memory->getSession('session_123');
```

### Example 2: Learning from Patterns

```php
// Track successful bookings to learn patterns
$memory->remember(
    'booking_pattern',
    [
        'day_of_week' => now()->dayOfWeek,
        'time_of_day' => now()->hour,
        'service' => $appointment->service,
        'lead_time' => now()->diffInHours($appointment->scheduled_at),
        'customer_age_group' => $this->getAgeGroup($customer->birth_date)
    ],
    'booking_patterns',
    ['analytics', 'patterns', 'bookings']
);

// Later: Analyze patterns
$patterns = $memory->search('', 'booking_patterns', ['bookings']);
$insights = $this->analyzeBookingPatterns($patterns['data']['results']);
```

## 🔍 Advanced Queries

### Example 1: Complex Customer Search

```php
// Find high-value customers
$result = $orchestrator->execute('database', 'execute_query', [
    'query' => '
        SELECT c.*, 
               COUNT(a.id) as appointment_count,
               SUM(a.price) as total_spent
        FROM customers c
        LEFT JOIN appointments a ON c.id = a.customer_id
        WHERE a.created_at >= ?
        GROUP BY c.id
        HAVING total_spent > ?
        ORDER BY total_spent DESC
    ',
    'params' => [now()->subMonths(6), 500],
    'timeout' => 30
]);
```

### Example 2: Cross-Service Data Aggregation

```php
// Get complete customer profile
public function getCustomerProfile($customerId)
{
    $profile = [];
    
    // Basic info
    $customer = $this->orchestrator->execute('customer', 'get_customer', [
        'id' => $customerId
    ]);
    $profile['info'] = $customer['data'];
    
    // Appointment history
    $appointments = $this->orchestrator->execute('appointment', 'get_customer_appointments', [
        'customer_id' => $customerId,
        'include_cancelled' => true
    ]);
    $profile['appointments'] = $appointments['data'];
    
    // Call history
    $calls = $this->orchestrator->execute('retell', 'get_customer_calls', [
        'customer_phone' => $customer['data']['phone']
    ]);
    $profile['calls'] = $calls['data'];
    
    // Payment history
    $payments = $this->orchestrator->execute('stripe', 'list_customer_payments', [
        'customer_id' => $customer['data']['stripe_id']
    ]);
    $profile['payments'] = $payments['data'];
    
    return $profile;
}
```

## 🚨 Error Handling Examples

### Example 1: Graceful Degradation

```php
public function bookAppointmentWithFallback($data)
{
    try {
        // Try primary calendar system
        $result = $this->orchestrator->execute('calcom', 'create_event', $data);
        
        if (!$result['success']) {
            // Fallback to local booking
            Log::warning('Cal.com booking failed, using local system', $result);
            
            $result = $this->orchestrator->execute('appointment', 'create_local', $data);
            
            // Queue sync for later
            $this->orchestrator->execute('queue', 'dispatch_job', [
                'job' => 'SyncAppointmentToCalcom',
                'data' => ['appointment_id' => $result['data']['id']],
                'delay' => 300 // 5 minutes
            ]);
        }
        
        return $result;
        
    } catch (\Exception $e) {
        // Last resort: save to memory bank for manual processing
        $this->memory->remember(
            'failed_booking_' . uniqid(),
            $data,
            'failed_bookings',
            ['urgent', 'manual_review']
        );
        
        throw $e;
    }
}
```

### Example 2: Retry Logic

```php
public function executeWithRetry($server, $tool, $params, $maxAttempts = 3)
{
    $attempt = 0;
    $lastError = null;
    
    while ($attempt < $maxAttempts) {
        $attempt++;
        
        try {
            $result = $this->orchestrator->execute($server, $tool, $params);
            
            if ($result['success']) {
                return $result;
            }
            
            $lastError = $result['error'] ?? 'Unknown error';
            
            // Don't retry on validation errors
            if (isset($result['code']) && $result['code'] === 'INVALID_PARAMS') {
                break;
            }
            
        } catch (\Exception $e) {
            $lastError = $e->getMessage();
        }
        
        if ($attempt < $maxAttempts) {
            sleep($attempt * 2); // Exponential backoff
        }
    }
    
    throw new MCPExecutionException(
        "Failed after {$maxAttempts} attempts: {$lastError}"
    );
}
```

## 🎯 Performance Optimization

### Example 1: Batch Operations

```php
// Instead of individual calls
foreach ($customerIds as $id) {
    $this->orchestrator->execute('customer', 'get_customer', ['id' => $id]);
}

// Use batch operation
$result = $this->orchestrator->execute('customer', 'get_customers_batch', [
    'ids' => $customerIds,
    'fields' => ['id', 'name', 'phone', 'email']
]);
```

### Example 2: Cached Aggregations

```php
public function getDashboardStats()
{
    return Cache::remember('dashboard_stats', 300, function () {
        $stats = [];
        
        // Parallel execution
        $promises = [
            'appointments' => fn() => $this->orchestrator->execute('appointment', 'get_stats'),
            'customers' => fn() => $this->orchestrator->execute('customer', 'get_stats'),
            'revenue' => fn() => $this->orchestrator->execute('stripe', 'get_revenue_stats')
        ];
        
        foreach ($promises as $key => $promise) {
            $stats[$key] = $promise();
        }
        
        return $stats;
    });
}
```

## 🧪 Testing with MCP

### Example 1: Unit Testing MCP Servers

```php
class AppointmentMCPServerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock external services
        $this->mock(CalcomService::class, function ($mock) {
            $mock->shouldReceive('createEvent')
                ->andReturn(['id' => 'test_123', 'status' => 'confirmed']);
        });
    }
    
    public function test_create_appointment()
    {
        $server = app(AppointmentMCPServer::class);
        
        $result = $server->executeTool('create_appointment', [
            'customer_phone' => '+49123456789',
            'service' => 'Test Service',
            'date' => '2025-01-15',
            'time' => '14:00'
        ]);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('appointment_id', $result['data']);
    }
}
```

### Example 2: Integration Testing

```php
public function test_full_booking_flow()
{
    // 1. Check availability
    $availability = $this->orchestrator->execute('appointment', 'check_availability', [
        'date' => '2025-01-15',
        'service_id' => 1
    ]);
    
    $this->assertTrue($availability['success']);
    $slot = $availability['data']['available_slots'][0];
    
    // 2. Create appointment
    $booking = $this->orchestrator->execute('appointment', 'create_appointment', [
        'customer_phone' => '+49123456789',
        'service_id' => 1,
        'date' => '2025-01-15',
        'time' => $slot
    ]);
    
    $this->assertTrue($booking['success']);
    
    // 3. Verify in calendar
    $calendar = $this->orchestrator->execute('calcom', 'get_event', [
        'event_id' => $booking['data']['calcom_event_id']
    ]);
    
    $this->assertTrue($calendar['success']);
}
```

---

These examples demonstrate real-world usage of MCP servers. For more specific use cases, consult the individual server documentation or use `php artisan mcp discover` to find the right tool for your needs.