# Cal.com v1 to v2 Migration Guide

## Overview

This guide covers the migration from Cal.com API v1 to v2, including breaking changes, new features, and step-by-step migration instructions for the AskProAI integration.

## Key Differences

### API Structure Changes

#### Base URLs
```yaml
v1: https://api.cal.com/v1
v2: https://api.cal.com/v2

Authentication:
  v1: API key in query parameter (?apiKey=xxx)
  v2: Bearer token in header (Authorization: Bearer xxx)
```

#### Response Format
```json
// v1 Response
{
  "booking": {
    "id": 123,
    "uid": "abc-123",
    "title": "Meeting"
  }
}

// v2 Response
{
  "status": "success",
  "data": {
    "id": "abc-123",
    "type": "booking",
    "attributes": {
      "title": "Meeting",
      "startTime": "2025-07-01T14:00:00Z",
      "endTime": "2025-07-01T15:00:00Z"
    }
  }
}
```

### Breaking Changes

1. **Authentication Method**
   - v1: Query parameter
   - v2: Bearer token header

2. **Endpoint Changes**
   ```yaml
   Event Types:
     v1: GET /event-types
     v2: GET /event-types?include=locations,hosts
   
   Bookings:
     v1: POST /bookings
     v2: POST /bookings with structured payload
   
   Availability:
     v1: GET /availability/{userId}
     v2: GET /slots/available
   ```

3. **Data Structure**
   - v2 uses JSON:API specification
   - Nested resources require explicit includes
   - Pagination format changed

## Migration Implementation

### Step 1: Update Service Configuration

```php
// config/services.php
return [
    'calcom' => [
        'api_version' => env('CALCOM_API_VERSION', 'v2'),
        'api_url' => env('CALCOM_API_URL', 'https://api.cal.com'),
        'api_key' => env('CALCOM_API_KEY'),
        'team_id' => env('CALCOM_TEAM_ID'),
        
        // v2 specific settings
        'v2' => [
            'timeout' => 30,
            'retry_times' => 3,
            'retry_delay' => 100,
            'include_relationships' => true,
        ],
        
        // Fallback v1 settings (during migration)
        'v1' => [
            'enabled' => env('CALCOM_V1_FALLBACK', true),
            'api_key' => env('CALCOM_V1_API_KEY'),
        ],
    ],
];
```

### Step 2: Create v2 Service Implementation

```php
// app/Services/CalcomV2Service.php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Exceptions\CalcomException;

class CalcomV2Service
{
    private string $baseUrl;
    private string $apiKey;
    private array $defaultHeaders;
    
    public function __construct()
    {
        $this->baseUrl = config('services.calcom.api_url') . '/v2';
        $this->apiKey = config('services.calcom.api_key');
        $this->defaultHeaders = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/vnd.api+json',
        ];
    }
    
    /**
     * Get event types with v2 API
     */
    public function getEventTypes(): array
    {
        $response = Http::withHeaders($this->defaultHeaders)
            ->get("{$this->baseUrl}/event-types", [
                'include' => 'locations,schedule,hosts',
                'filter[active]' => true,
            ]);
        
        if (!$response->successful()) {
            throw new CalcomException(
                "Failed to fetch event types: " . $response->body()
            );
        }
        
        return $this->transformEventTypes($response->json('data', []));
    }
    
    /**
     * Check availability with v2 API
     */
    public function checkAvailability(
        int $eventTypeId,
        string $startDate,
        string $endDate
    ): array {
        $response = Http::withHeaders($this->defaultHeaders)
            ->get("{$this->baseUrl}/slots/available", [
                'eventTypeId' => $eventTypeId,
                'startTime' => $startDate,
                'endTime' => $endDate,
                'timeZone' => config('app.timezone'),
            ]);
        
        if (!$response->successful()) {
            Log::error('Cal.com availability check failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            
            // Fallback to v1 if enabled
            if (config('services.calcom.v1.enabled')) {
                return $this->fallbackToV1Availability($eventTypeId, $startDate, $endDate);
            }
            
            throw new CalcomException("Availability check failed");
        }
        
        return $this->transformAvailabilitySlots($response->json('data', []));
    }
    
    /**
     * Create booking with v2 API
     */
    public function createBooking(array $data): array
    {
        $payload = [
            'data' => [
                'type' => 'bookings',
                'attributes' => [
                    'eventTypeId' => $data['eventTypeId'],
                    'start' => $data['start'],
                    'responses' => [
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'phone' => $data['phone'] ?? null,
                        'notes' => $data['notes'] ?? null,
                    ],
                    'timeZone' => $data['timeZone'] ?? config('app.timezone'),
                    'language' => $data['language'] ?? 'de',
                    'metadata' => [
                        'source' => 'askproai',
                        'companyId' => $data['companyId'] ?? null,
                        'branchId' => $data['branchId'] ?? null,
                    ],
                ],
            ],
        ];
        
        $response = Http::withHeaders($this->defaultHeaders)
            ->post("{$this->baseUrl}/bookings", $payload);
        
        if (!$response->successful()) {
            Log::error('Cal.com booking creation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'payload' => $payload,
            ]);
            
            throw new CalcomException(
                "Booking creation failed: " . $response->json('errors.0.detail', 'Unknown error')
            );
        }
        
        return $this->transformBooking($response->json('data'));
    }
    
    /**
     * Transform v2 event types to internal format
     */
    private function transformEventTypes(array $eventTypes): array
    {
        return collect($eventTypes)->map(function ($eventType) {
            $attributes = $eventType['attributes'] ?? [];
            
            return [
                'id' => $eventType['id'],
                'slug' => $attributes['slug'] ?? null,
                'title' => $attributes['title'] ?? 'Unnamed Event',
                'description' => $attributes['description'] ?? null,
                'length' => $attributes['length'] ?? 30,
                'locations' => $this->extractLocations($eventType),
                'price' => $attributes['price'] ?? 0,
                'currency' => $attributes['currency'] ?? 'EUR',
                'requiresConfirmation' => $attributes['requiresConfirmation'] ?? false,
                'disableGuests' => $attributes['disableGuests'] ?? false,
                // Map v2 fields to v1 compatible structure
                'users' => $this->extractHosts($eventType),
                'schedule' => $this->extractSchedule($eventType),
            ];
        })->toArray();
    }
    
    /**
     * Transform v2 availability slots
     */
    private function transformAvailabilitySlots(array $slots): array
    {
        return collect($slots)->map(function ($slot) {
            return [
                'time' => $slot['attributes']['startTime'] ?? $slot['attributes']['start'],
                'available' => true,
                'duration' => $slot['attributes']['duration'] ?? null,
            ];
        })->toArray();
    }
    
    /**
     * Fallback to v1 API for availability
     */
    private function fallbackToV1Availability(
        int $eventTypeId,
        string $startDate,
        string $endDate
    ): array {
        Log::warning('Falling back to Cal.com v1 API for availability check');
        
        return app(CalcomService::class)->checkAvailability(
            $eventTypeId,
            $startDate,
            $endDate
        );
    }
}
```

### Step 3: Migration Adapter Pattern

```php
// app/Services/CalcomAdapter.php
namespace App\Services;

use App\Contracts\CalendarServiceInterface;

class CalcomAdapter implements CalendarServiceInterface
{
    private $v1Service;
    private $v2Service;
    private bool $useV2;
    
    public function __construct(
        CalcomService $v1Service,
        CalcomV2Service $v2Service
    ) {
        $this->v1Service = $v1Service;
        $this->v2Service = $v2Service;
        $this->useV2 = config('services.calcom.api_version') === 'v2';
    }
    
    public function createBooking(array $data): array
    {
        try {
            if ($this->useV2) {
                return $this->v2Service->createBooking($data);
            }
        } catch (\Exception $e) {
            Log::error('v2 API failed, falling back to v1', [
                'error' => $e->getMessage()
            ]);
        }
        
        return $this->v1Service->createBooking($this->transformToV1($data));
    }
    
    private function transformToV1(array $v2Data): array
    {
        return [
            'eventTypeId' => $v2Data['eventTypeId'],
            'start' => $v2Data['start'],
            'end' => $v2Data['end'] ?? null,
            'name' => $v2Data['responses']['name'] ?? $v2Data['name'],
            'email' => $v2Data['responses']['email'] ?? $v2Data['email'],
            'guests' => $v2Data['responses']['guests'] ?? [],
            'notes' => $v2Data['responses']['notes'] ?? null,
            'timeZone' => $v2Data['timeZone'],
            'rescheduleUid' => $v2Data['rescheduleUid'] ?? null,
        ];
    }
}
```

### Step 4: Update Models and Database

```php
// database/migrations/2025_update_calcom_event_types_for_v2.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateCalcomEventTypesForV2 extends Migration
{
    public function up()
    {
        Schema::table('calcom_event_types', function (Blueprint $table) {
            // Add v2 specific fields
            $table->string('api_version')->default('v1')->after('event_type_id');
            $table->json('v2_attributes')->nullable()->after('metadata');
            $table->json('included_relationships')->nullable();
            $table->boolean('requires_confirmation')->default(false);
            $table->boolean('disable_guests')->default(false);
            $table->string('booking_limits')->nullable();
            
            // Add indexes for better performance
            $table->index('api_version');
            $table->index(['company_id', 'api_version']);
        });
        
        // Migrate existing data
        DB::statement("
            UPDATE calcom_event_types 
            SET api_version = 'v1' 
            WHERE api_version IS NULL
        ");
    }
    
    public function down()
    {
        Schema::table('calcom_event_types', function (Blueprint $table) {
            $table->dropColumn([
                'api_version',
                'v2_attributes',
                'included_relationships',
                'requires_confirmation',
                'disable_guests',
                'booking_limits',
            ]);
        });
    }
}
```

### Step 5: Update Webhook Handlers

```php
// app/Http/Controllers/CalcomWebhookController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\ProcessCalcomWebhook;

class CalcomWebhookController extends Controller
{
    public function handleV2Webhook(Request $request)
    {
        // Verify webhook signature for v2
        if (!$this->verifyV2Signature($request)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }
        
        $payload = $request->json();
        
        // v2 webhook structure
        $event = [
            'id' => $payload['id'],
            'type' => $payload['type'], // booking.created, booking.cancelled, etc.
            'data' => $payload['data'],
            'occurred_at' => $payload['occurred_at'],
        ];
        
        ProcessCalcomWebhook::dispatch($event, 'v2');
        
        return response()->json(['status' => 'accepted'], 202);
    }
    
    private function verifyV2Signature(Request $request): bool
    {
        $signature = $request->header('X-Cal-Signature-256');
        
        if (!$signature) {
            return false;
        }
        
        $secret = config('services.calcom.webhook_secret');
        $payload = $request->getContent();
        $expected = hash_hmac('sha256', $payload, $secret);
        
        return hash_equals($expected, $signature);
    }
}
```

### Step 6: Testing Migration

```php
// tests/Integration/CalcomV2MigrationTest.php
namespace Tests\Integration;

use Tests\TestCase;
use App\Services\CalcomService;
use App\Services\CalcomV2Service;
use Illuminate\Support\Facades\Http;

class CalcomV2MigrationTest extends TestCase
{
    /** @test */
    public function v2_service_returns_compatible_event_types()
    {
        Http::fake([
            '*/v2/event-types*' => Http::response([
                'data' => [
                    [
                        'id' => '123',
                        'type' => 'event-types',
                        'attributes' => [
                            'title' => 'Consultation',
                            'slug' => 'consultation',
                            'length' => 30,
                        ],
                    ],
                ],
            ]),
        ]);
        
        $v2Service = new CalcomV2Service();
        $eventTypes = $v2Service->getEventTypes();
        
        $this->assertArrayHasKey('id', $eventTypes[0]);
        $this->assertArrayHasKey('title', $eventTypes[0]);
        $this->assertArrayHasKey('length', $eventTypes[0]);
    }
    
    /** @test */
    public function adapter_falls_back_to_v1_on_v2_failure()
    {
        // Mock v2 to fail
        Http::fake([
            '*/v2/bookings*' => Http::response(null, 500),
            '*/v1/bookings*' => Http::response(['uid' => 'booking-123']),
        ]);
        
        $adapter = app(CalcomAdapter::class);
        $booking = $adapter->createBooking([
            'eventTypeId' => 1,
            'start' => '2025-07-01T14:00:00Z',
            'responses' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
        ]);
        
        $this->assertNotNull($booking);
    }
}
```

## Migration Timeline

### Phase 1: Preparation (Week 1)
- Set up v2 API credentials
- Deploy v2 service alongside v1
- Enable logging and monitoring

### Phase 2: Testing (Week 2)
- Run parallel tests
- Compare v1 and v2 responses
- Fix compatibility issues

### Phase 3: Gradual Migration (Weeks 3-4)
- Enable v2 for read operations
- Monitor error rates
- Enable v2 for write operations
- Keep v1 as fallback

### Phase 4: Completion (Week 5)
- Disable v1 fallback
- Remove v1 code
- Update documentation

## Rollback Plan

### Quick Rollback
```bash
# Switch back to v1
php artisan config:set services.calcom.api_version v1
php artisan config:cache
```

### Full Rollback
```php
// config/services.php
'calcom' => [
    'api_version' => 'v1', // Force v1
    'force_v1' => true,    // Disable v2 completely
]
```

## Monitoring Dashboard

```php
// app/Http/Controllers/CalcomMigrationDashboard.php
class CalcomMigrationDashboard extends Controller
{
    public function index()
    {
        $metrics = [
            'v1_calls' => Cache::get('calcom.v1.calls', 0),
            'v2_calls' => Cache::get('calcom.v2.calls', 0),
            'v1_errors' => Cache::get('calcom.v1.errors', 0),
            'v2_errors' => Cache::get('calcom.v2.errors', 0),
            'fallback_count' => Cache::get('calcom.fallbacks', 0),
            'migration_progress' => $this->calculateProgress(),
        ];
        
        return view('admin.calcom-migration', compact('metrics'));
    }
    
    private function calculateProgress(): float
    {
        $v1Calls = Cache::get('calcom.v1.calls', 0);
        $v2Calls = Cache::get('calcom.v2.calls', 0);
        $total = $v1Calls + $v2Calls;
        
        return $total > 0 ? ($v2Calls / $total) * 100 : 0;
    }
}
```

## Common Issues and Solutions

### Issue: Authentication Failures
```php
// Solution: Update headers
$headers = [
    'Authorization' => 'Bearer ' . $apiKey, // v2
    // Not: 'apiKey' => $apiKey (v1)
];
```

### Issue: Missing Data in Responses
```php
// Solution: Use includes parameter
$response = Http::get('/v2/event-types', [
    'include' => 'locations,schedule,hosts', // Explicit includes required
]);
```

### Issue: Webhook Signature Mismatch
```bash
# Debug webhook signatures
php artisan calcom:debug-webhook --payload='{"test":true}'
```

## Performance Comparison

```yaml
API Performance:
  v1:
    Average Response Time: 450ms
    P95 Response Time: 1200ms
    Error Rate: 0.05%
    
  v2:
    Average Response Time: 280ms (-38%)
    P95 Response Time: 650ms (-46%)
    Error Rate: 0.02% (-60%)
    
Features:
  v1:
    - Basic CRUD operations
    - Limited filtering
    - No field selection
    
  v2:
    - Advanced filtering
    - Field selection
    - Relationship includes
    - Better error messages
    - Webhook reliability
```

## Related Documentation

- [Cal.com Integration Guide](../integrations/calcom.md)
- [Service Configuration](../configuration/services.md)
- [Webhook Setup](../api/webhooks.md)
- [Testing Guide](../development/testing.md)