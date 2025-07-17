# Retell.ai Developer Guide

> **For developers working on AskProAI's Retell.ai integration**  
> Version 1.0 - July 2025

## Table of Contents
1. [Getting Started](#getting-started)
2. [Architecture Overview](#architecture-overview)
3. [Working with Webhooks](#working-with-webhooks)
4. [Creating Custom Functions](#creating-custom-functions)
5. [Agent Management](#agent-management)
6. [Testing & Debugging](#testing--debugging)
7. [Best Practices](#best-practices)
8. [Code Examples](#code-examples)

---

## Getting Started

### Prerequisites
- PHP 8.1+
- Laravel 10.x
- Redis for queues
- MySQL 8.0+
- Retell.ai account with API access

### Local Development Setup

```bash
# 1. Clone and setup
git clone https://github.com/askproai/api-gateway.git
cd api-gateway
composer install
npm install

# 2. Configure environment
cp .env.example .env
# Add Retell credentials:
# RETELL_TOKEN=key_xxx
# RETELL_WEBHOOK_SECRET=key_xxx  (same as token)
# RETELL_BASE=https://api.retellai.com

# 3. Database setup
php artisan migrate
php artisan db:seed --class=DevelopmentSeeder

# 4. Start services
php artisan horizon
npm run dev
php artisan serve

# 5. Setup ngrok for webhooks
ngrok http 8000
# Update Retell webhook URL to: https://xxx.ngrok.io/api/retell/webhook-simple
```

---

## Architecture Overview

### Key Components

```
┌─────────────────────────────────────────────────────────────┐
│                     Retell.ai Cloud Service                  │
└─────────────────────────────────────────────────────────────┘
                              │
                              │ Webhooks & API
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    AskProAI API Gateway                      │
├─────────────────────────────────────────────────────────────┤
│  Controllers           │  Services              │  Jobs      │
│  ├─ WebhookController  │  ├─ RetellV2Service   │  ├─ Call*  │
│  └─ CustomFunctions    │  └─ WebhookHandler    │  └─ Analyze│
├─────────────────────────────────────────────────────────────┤
│                         Helpers                              │
│                    RetellDataExtractor                       │
├─────────────────────────────────────────────────────────────┤
│                    Database (MySQL)                          │
│              calls, customers, appointments                  │
└─────────────────────────────────────────────────────────────┘
```

### Service Layer

**RetellV2Service** - Main API client
```php
// app/Services/RetellV2Service.php
$retell = new RetellV2Service($apiKey);
$agent = $retell->getAgent($agentId);
$calls = $retell->listCalls(50);
```

**RetellWebhookHandler** - Processes webhooks
```php
// app/Services/Webhooks/RetellWebhookHandler.php
$handler->handleCallStarted($data);
$handler->handleCallEnded($data);
```

**RetellDataExtractor** - Normalizes data
```php
// app/Helpers/RetellDataExtractor.php
$callData = RetellDataExtractor::extractCallData($webhookData);
$updateData = RetellDataExtractor::extractUpdateData($webhookData);
```

---

## Working with Webhooks

### Webhook Flow

1. **Retell.ai** sends POST request to webhook endpoint
2. **Middleware** verifies signature (optional)
3. **Controller** receives and validates data
4. **Job** queued for async processing
5. **Handler** processes business logic
6. **Database** updated with results

### Implementing Webhook Handler

```php
// app/Http/Controllers/Api/MyRetellWebhookController.php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Jobs\ProcessRetellWebhookJob;

class MyRetellWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Extract event type
        $event = $request->input('event') ?? $request->input('event_type');
        
        // Handle nested structure
        $data = $request->all();
        if (isset($data['call']) && is_array($data['call'])) {
            $callData = $data['call'];
            $data = array_merge($callData, ['event' => $event]);
        }
        
        // Queue for processing
        ProcessRetellWebhookJob::dispatch($data)
            ->onQueue('webhooks');
            
        return response()->json(['success' => true]);
    }
}
```

### Processing Job

```php
// app/Jobs/ProcessRetellWebhookJob.php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Call;
use App\Helpers\RetellDataExtractor;

class ProcessRetellWebhookJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;
    
    protected $data;
    
    public function __construct(array $data)
    {
        $this->data = $data;
    }
    
    public function handle()
    {
        $event = $this->data['event'] ?? 'unknown';
        
        switch ($event) {
            case 'call_started':
                $this->handleCallStarted();
                break;
            case 'call_ended':
                $this->handleCallEnded();
                break;
        }
    }
    
    protected function handleCallStarted()
    {
        $callData = RetellDataExtractor::extractCallData($this->data);
        
        Call::create([
            'call_id' => $callData['call_id'],
            'from_number' => $callData['from_number'],
            'to_number' => $callData['to_number'],
            'status' => 'in_progress',
            'company_id' => $this->resolveCompany($callData['to_number'])
        ]);
    }
    
    protected function handleCallEnded()
    {
        $call = Call::where('call_id', $this->data['call_id'])->first();
        if (!$call) return;
        
        $updateData = RetellDataExtractor::extractUpdateData($this->data);
        $call->update($updateData);
        
        // Trigger post-processing
        if ($call->hasAppointmentData()) {
            ProcessAppointmentJob::dispatch($call);
        }
    }
}
```

---

## Creating Custom Functions

### Function Structure

Custom functions allow the AI agent to execute business logic during calls.

```php
// routes/api.php
Route::prefix('retell')->group(function () {
    Route::post('/my-custom-function', 
        [RetellCustomFunctionsController::class, 'myFunction'])
        ->middleware(['verify.retell.signature']);
});

// app/Http/Controllers/RetellCustomFunctionsController.php
public function myFunction(Request $request)
{
    // Log for debugging
    $this->logRetellRequest('my_function', $request);
    
    try {
        // Extract parameters
        $args = $request->input('args', []);
        $callId = $request->input('call.call_id');
        
        // Your business logic
        $result = $this->processFunction($args);
        
        // Return structured response
        return response()->json([
            'success' => true,
            'message' => 'Function executed successfully',
            'data' => $result
        ]);
        
    } catch (\Exception $e) {
        Log::error('Custom function error', [
            'function' => 'my_function',
            'error' => $e->getMessage()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'An error occurred'
        ]);
    }
}
```

### Available Custom Functions

1. **check_availability**
```json
{
  "name": "check_availability",
  "description": "Check available appointment slots",
  "parameters": {
    "type": "object",
    "properties": {
      "date": {
        "type": "string",
        "description": "Date to check (e.g., 'tomorrow', '2025-07-15')"
      }
    }
  }
}
```

2. **collect_appointment_data**
```json
{
  "name": "collect_appointment_data",
  "description": "Collect and book appointment",
  "parameters": {
    "type": "object",
    "properties": {
      "datum": {"type": "string"},
      "uhrzeit": {"type": "string"},
      "name": {"type": "string"},
      "telefonnummer": {"type": "string"},
      "dienstleistung": {"type": "string"}
    }
  }
}
```

### Configuring in Retell Dashboard

1. Navigate to Agent Configuration
2. Add Custom Function
3. Set endpoint: `https://api.askproai.de/api/retell/your-function`
4. Define parameters schema
5. Add to agent prompt:
```
When someone wants to book an appointment, use the collect_appointment_data function.
```

---

## Agent Management

### Creating an Agent

```php
$retell = new RetellV2Service();

$agentConfig = [
    'agent_name' => 'My Business Assistant',
    'voice_id' => 'elevenlabs_voice_id',
    'voice_speed' => 1.0,
    'response_engine' => [
        'type' => 'retell_llm',
        'llm_id' => 'llm_xxx'
    ],
    'language' => 'de',
    'agent_prompt' => $this->buildPrompt(),
    'webhook_url' => 'https://api.askproai.de/api/retell/webhook-simple',
    'enable_voicemail' => false,
    'end_call_after_silence_ms' => 10000
];

$agent = $retell->createAgent($agentConfig);
```

### Updating Agent Configuration

```php
// Update prompt
$retell->updateAgent($agentId, [
    'agent_prompt' => $newPrompt
]);

// Update voice settings
$retell->updateAgent($agentId, [
    'voice_id' => 'new_voice_id',
    'voice_speed' => 0.95
]);

// Add custom functions
$llmConfig = $retell->getRetellLLM($llmId);
$llmConfig['general_tools'] = [
    [
        'type' => 'end_call',
        'name' => 'end_call'
    ],
    [
        'type' => 'custom',
        'name' => 'check_availability',
        'url' => 'https://api.askproai.de/api/retell/check-availability',
        'method' => 'POST',
        'parameters' => $functionSchema
    ]
];
$retell->updateRetellLLM($llmId, $llmConfig);
```

### Phone Number Configuration

```php
// Update phone number to use specific agent
$retell->updatePhoneNumber('+491234567890', [
    'agent_id' => $agentId,
    'inbound_agent_id' => $agentId
]);
```

---

## Testing & Debugging

### Local Testing with Ngrok

```bash
# 1. Start ngrok
ngrok http 8000

# 2. Update .env
RETELL_WEBHOOK_URL=https://abc123.ngrok.io/api/retell/webhook-simple

# 3. Monitor ngrok traffic
# Visit http://localhost:4040
```

### Unit Testing Webhooks

```php
// tests/Feature/RetellWebhookTest.php
public function test_webhook_creates_call_record()
{
    $payload = [
        'event' => 'call_started',
        'call' => [
            'call_id' => 'test_123',
            'from_number' => '+491234567890',
            'to_number' => '+493012345678'
        ]
    ];
    
    $response = $this->postJson('/api/retell/webhook-simple', $payload);
    
    $response->assertStatus(200);
    $this->assertDatabaseHas('calls', [
        'call_id' => 'test_123'
    ]);
}
```

### Integration Testing

```php
// tests/Integration/RetellServiceTest.php
public function test_can_fetch_agent_details()
{
    $retell = new RetellV2Service();
    $agent = $retell->getAgent('agent_123');
    
    $this->assertNotNull($agent);
    $this->assertEquals('agent_123', $agent['agent_id']);
}
```

### Debugging Tools

```bash
# Monitor real-time logs
tail -f storage/logs/laravel.log | grep -i retell

# Check webhook payloads
tail -f storage/logs/retell-functions-*.log

# Database queries
mysql -u askproai_user -p askproai_db -e "
SELECT * FROM calls 
WHERE created_at >= NOW() - INTERVAL 1 HOUR 
ORDER BY created_at DESC"

# Test custom function
curl -X POST http://localhost:8000/api/retell/check-availability \
  -H "Content-Type: application/json" \
  -d '{"date": "tomorrow", "call": {"call_id": "test"}}'
```

### Common Debug Scenarios

1. **Webhook not received**
```php
// Add to controller
Log::info('Webhook received', [
    'headers' => $request->headers->all(),
    'body' => $request->all()
]);
```

2. **Function timeout**
```php
// Increase timeout in function
set_time_limit(30);
// Add progress logging
Log::info('Function step 1 completed');
```

3. **Data not saving**
```php
// Check model fillable
protected $fillable = ['all', 'your', 'fields'];
// Log SQL queries
\DB::enableQueryLog();
// ... your code
dd(\DB::getQueryLog());
```

---

## Best Practices

### 1. Error Handling

```php
// Always wrap in try-catch
try {
    $result = $this->riskyOperation();
} catch (\Exception $e) {
    Log::error('Operation failed', [
        'error' => $e->getMessage(),
        'context' => $contextData
    ]);
    
    // Return graceful response
    return response()->json([
        'success' => false,
        'message' => 'Service temporarily unavailable'
    ], 503);
}
```

### 2. Performance

```php
// Cache expensive operations
$agents = Cache::remember('retell_agents', 3600, function () {
    return $this->retell->listAgents();
});

// Use queues for heavy processing
ProcessCallAnalyticsJob::dispatch($call)->onQueue('low');

// Batch operations
$calls = Call::whereIn('call_id', $callIds)
    ->update(['status' => 'processed']);
```

### 3. Security

```php
// Validate all inputs
$validated = $request->validate([
    'date' => 'required|date',
    'time' => 'required|regex:/^\d{2}:\d{2}$/'
]);

// Sanitize phone numbers
$phone = preg_replace('/[^0-9+]/', '', $input);

// Rate limiting
Route::middleware(['throttle:60,1'])->group(function () {
    // Your routes
});
```

### 4. Logging

```php
// Use structured logging
Log::channel('retell')->info('Call processed', [
    'call_id' => $call->id,
    'duration' => $call->duration_sec,
    'cost' => $call->cost,
    'correlation_id' => $correlationId
]);

// Create custom log channel
// config/logging.php
'retell' => [
    'driver' => 'daily',
    'path' => storage_path('logs/retell.log'),
    'level' => 'debug',
    'days' => 14,
]
```

---

## Code Examples

### Complete Webhook Handler

```php
<?php

namespace App\Services;

use App\Models\Call;
use App\Models\Company;
use App\Jobs\ProcessAppointmentJob;
use App\Helpers\RetellDataExtractor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CompleteRetellWebhookHandler
{
    public function handle(array $data): void
    {
        $event = $data['event'] ?? $data['event_type'] ?? 'unknown';
        
        Log::info('Processing Retell webhook', [
            'event' => $event,
            'call_id' => $data['call_id'] ?? 'unknown'
        ]);
        
        DB::beginTransaction();
        
        try {
            switch ($event) {
                case 'call_started':
                    $this->handleCallStarted($data);
                    break;
                    
                case 'call_ended':
                    $this->handleCallEnded($data);
                    break;
                    
                case 'call_analyzed':
                    $this->handleCallAnalyzed($data);
                    break;
                    
                default:
                    Log::warning('Unknown Retell event', ['event' => $event]);
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'event' => $event,
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
    
    protected function handleCallStarted(array $data): void
    {
        $callData = RetellDataExtractor::extractCallData($data);
        
        // Resolve company and branch
        $resolved = $this->resolveCompanyContext($callData['to_number']);
        
        Call::create([
            'call_id' => $callData['call_id'],
            'retell_call_id' => $callData['call_id'],
            'from_number' => $callData['from_number'],
            'to_number' => $callData['to_number'],
            'call_status' => 'in_progress',
            'start_timestamp' => $callData['start_timestamp'],
            'company_id' => $resolved['company_id'],
            'branch_id' => $resolved['branch_id'],
            'agent_id' => $callData['agent_id'],
            'direction' => $callData['direction'],
            'webhook_data' => $data
        ]);
        
        Log::info('Call started', [
            'call_id' => $callData['call_id'],
            'company' => $resolved['company_name'] ?? 'Unknown'
        ]);
    }
    
    protected function handleCallEnded(array $data): void
    {
        $callId = $data['call_id'] ?? null;
        if (!$callId) {
            throw new \Exception('No call_id in webhook data');
        }
        
        $call = Call::where('call_id', $callId)->firstOrFail();
        
        $updateData = RetellDataExtractor::extractUpdateData($data);
        $call->update($updateData);
        
        // Check for appointment data
        if ($this->hasAppointmentData($call)) {
            ProcessAppointmentJob::dispatch($call)
                ->onQueue('high');
        }
        
        // Send call summary if enabled
        if ($call->company->send_call_summaries) {
            SendCallSummaryJob::dispatch($call)
                ->delay(now()->addMinutes(5));
        }
        
        Log::info('Call ended', [
            'call_id' => $callId,
            'duration' => $updateData['duration_sec'] ?? 0,
            'cost' => $updateData['cost'] ?? 0
        ]);
    }
    
    protected function handleCallAnalyzed(array $data): void
    {
        // Additional analysis data
        $callId = $data['call_id'] ?? null;
        if (!$callId) return;
        
        $call = Call::where('call_id', $callId)->first();
        if (!$call) return;
        
        $analysis = $data['call_analysis'] ?? [];
        $call->update([
            'analysis' => array_merge($call->analysis ?? [], $analysis)
        ]);
    }
    
    protected function resolveCompanyContext(string $phoneNumber): array
    {
        // Implementation details...
        return [
            'company_id' => 1,
            'branch_id' => 1,
            'company_name' => 'Test Company'
        ];
    }
    
    protected function hasAppointmentData(Call $call): bool
    {
        $webhookData = $call->webhook_data;
        
        return isset($webhookData['call']['retell_llm_dynamic_variables']['datum']) ||
               isset($webhookData['call']['custom_analysis_data']['appointment_made']);
    }
}
```

### Custom Function with MCP Integration

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MCP\AppointmentManagementMCPServer;
use App\Models\Call;
use Carbon\Carbon;

class AdvancedCustomFunctionController extends Controller
{
    protected $appointmentMCP;
    
    public function __construct(AppointmentManagementMCPServer $appointmentMCP)
    {
        $this->appointmentMCP = $appointmentMCP;
    }
    
    public function smartBooking(Request $request)
    {
        $args = $request->input('args', []);
        $callId = $request->input('call.call_id');
        
        // Get call context
        $call = Call::where('call_id', $callId)->first();
        if (!$call) {
            return response()->json([
                'success' => false,
                'message' => 'Call context not found'
            ]);
        }
        
        // Extract customer preferences
        $preferences = $this->extractPreferences($call);
        
        // Find best available slot
        $slot = $this->findOptimalSlot(
            $args['preferred_date'] ?? 'this week',
            $args['preferred_time'] ?? 'morning',
            $preferences
        );
        
        if (!$slot) {
            return response()->json([
                'success' => false,
                'message' => 'Keine passenden Termine verfügbar',
                'alternatives' => $this->suggestAlternatives()
            ]);
        }
        
        // Book the appointment
        $result = $this->appointmentMCP->create([
            'date' => $slot['date'],
            'time' => $slot['time'],
            'duration' => $args['duration'] ?? 30,
            'service' => $args['service'],
            'customer_name' => $args['name'],
            'phone' => $call->from_number,
            'notes' => $args['notes'] ?? '',
            'staff_id' => $slot['staff_id'] ?? null,
            'branch_id' => $call->branch_id
        ]);
        
        return response()->json([
            'success' => true,
            'message' => sprintf(
                'Perfekt! Termin gebucht für %s um %s Uhr bei %s',
                Carbon::parse($slot['date'])->format('d.m.Y'),
                $slot['time'],
                $slot['staff_name'] ?? 'uns'
            ),
            'appointment_id' => $result['appointment']['id'] ?? null
        ]);
    }
    
    protected function extractPreferences(Call $call): array
    {
        // Extract from previous calls, customer record, etc.
        $customer = $call->customer;
        
        return [
            'preferred_staff' => $customer->preferred_staff_id ?? null,
            'preferred_times' => $customer->preferred_times ?? ['morning'],
            'avoid_days' => $customer->avoid_days ?? [],
            'is_vip' => $customer->is_vip ?? false
        ];
    }
    
    protected function findOptimalSlot($dateRange, $timePreference, $preferences): ?array
    {
        // Complex availability logic
        // This would integrate with Cal.com or your booking system
        
        return [
            'date' => '2025-07-15',
            'time' => '10:00',
            'staff_id' => 1,
            'staff_name' => 'Dr. Schmidt'
        ];
    }
    
    protected function suggestAlternatives(): array
    {
        return [
            ['date' => '2025-07-16', 'time' => '14:00'],
            ['date' => '2025-07-17', 'time' => '09:00'],
            ['date' => '2025-07-18', 'time' => '16:00']
        ];
    }
}
```

---

## Additional Resources

- [Retell.ai API Documentation](https://docs.retellai.com)
- [Laravel Queue Documentation](https://laravel.com/docs/queues)
- [Webhook Best Practices](https://webhooks.dev)
- [AskProAI Internal Wiki](https://wiki.askproai.de)

---

**Last Updated**: July 10, 2025  
**Maintainer**: AskProAI Development Team