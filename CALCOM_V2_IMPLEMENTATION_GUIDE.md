# Cal.com V2 API Implementation Guide

## Quick Start Implementation

### Step 1: Create Pure V2 Client

Create a new file: `app/Services/Calcom/CalcomV2Client.php`

```php
<?php

namespace App\Services\Calcom;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Exceptions\CalcomApiException;

class CalcomV2Client
{
    private string $apiKey;
    private string $baseUrl = 'https://api.cal.com/v2';
    private string $apiVersion = '2024-08-13';
    private array $config;
    
    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? config('services.calcom.api_key');
        $this->config = config('calcom-v2', []);
    }
    
    /**
     * Make authenticated request to Cal.com V2 API
     */
    private function request(string $method, string $endpoint, array $data = []): array
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'cal-api-version' => $this->apiVersion,
            'Content-Type' => 'application/json',
        ];
        
        try {
            $response = Http::withHeaders($headers)
                ->timeout($this->config['timeout']['request'] ?? 30)
                ->$method($this->baseUrl . $endpoint, $data);
            
            // Log request/response if enabled
            if ($this->config['logging']['log_requests'] ?? false) {
                Log::channel('calcom')->info('Cal.com V2 Request', [
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'data' => $data,
                    'response_status' => $response->status(),
                ]);
            }
            
            if (!$response->successful()) {
                throw new CalcomApiException(
                    "Cal.com API error: {$response->body()}",
                    $response->status()
                );
            }
            
            return $response->json() ?? [];
            
        } catch (\Exception $e) {
            Log::error('Cal.com V2 API Error', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    /**
     * Get current user information
     */
    public function getMe(): array
    {
        return $this->request('get', '/me');
    }
    
    /**
     * Get all event types
     */
    public function getEventTypes(): array
    {
        $response = $this->request('get', '/event-types');
        
        // Extract event types from nested structure
        $eventTypes = [];
        if (isset($response['data']['eventTypeGroups'])) {
            foreach ($response['data']['eventTypeGroups'] as $group) {
                if (isset($group['eventTypes'])) {
                    $eventTypes = array_merge($eventTypes, $group['eventTypes']);
                }
            }
        }
        
        return ['event_types' => $eventTypes];
    }
    
    /**
     * Get available slots for an event type
     */
    public function getAvailableSlots(
        int $eventTypeId,
        string $startDate,
        string $endDate,
        string $timeZone = 'Europe/Berlin'
    ): array {
        $params = [
            'eventTypeId' => $eventTypeId,
            'startTime' => $startDate . 'T00:00:00.000Z',
            'endTime' => $endDate . 'T23:59:59.999Z',
            'timeZone' => $timeZone,
        ];
        
        $response = $this->request('get', '/slots/available', $params);
        
        // Flatten nested slots structure
        $slots = [];
        if (isset($response['data']['slots'])) {
            foreach ($response['data']['slots'] as $date => $daySlots) {
                foreach ($daySlots as $slot) {
                    $slots[] = is_array($slot) ? ($slot['time'] ?? $slot) : $slot;
                }
            }
        }
        
        return [
            'success' => true,
            'data' => ['slots' => $slots]
        ];
    }
    
    /**
     * Create a booking (V2 format)
     */
    public function createBooking(array $bookingData): array
    {
        // Transform to V2 structure
        $v2Data = [
            'eventTypeId' => (int)$bookingData['eventTypeId'],
            'start' => $bookingData['start'],
            'attendee' => [
                'name' => $bookingData['attendee']['name'] ?? 'Unknown',
                'email' => $bookingData['attendee']['email'] ?? 'kunde@example.com',
                'timeZone' => $bookingData['attendee']['timeZone'] ?? 'Europe/Berlin',
            ],
            'language' => $bookingData['language'] ?? 'de',
        ];
        
        // Add optional fields
        if (isset($bookingData['attendee']['phone'])) {
            $v2Data['attendee']['phone'] = $bookingData['attendee']['phone'];
        }
        
        if (isset($bookingData['metadata'])) {
            $v2Data['metadata'] = $bookingData['metadata'];
        }
        
        if (isset($bookingData['notes'])) {
            $v2Data['notes'] = $bookingData['notes'];
        }
        
        $response = $this->request('post', '/bookings', $v2Data);
        
        // Return normalized response
        return [
            'success' => true,
            'booking' => $response['data'] ?? $response,
            'id' => $response['data']['id'] ?? null,
            'uid' => $response['data']['uid'] ?? null,
        ];
    }
    
    /**
     * Get all bookings with optional filters
     */
    public function getBookings(array $filters = []): array
    {
        $params = array_merge([
            'limit' => 100,
            'page' => 1,
        ], $filters);
        
        $response = $this->request('get', '/bookings', $params);
        
        return [
            'success' => true,
            'bookings' => $response['data'] ?? [],
            'pagination' => $response['pagination'] ?? null,
        ];
    }
    
    /**
     * Cancel a booking
     */
    public function cancelBooking(int $bookingId, ?string $reason = null): array
    {
        $data = [];
        if ($reason) {
            $data['cancellationReason'] = $reason;
        }
        
        return $this->request('post', "/bookings/{$bookingId}/cancel", $data);
    }
    
    /**
     * Reschedule a booking
     */
    public function rescheduleBooking(int $bookingId, string $newStart, ?string $reason = null): array
    {
        $data = ['start' => $newStart];
        if ($reason) {
            $data['rescheduleReason'] = $reason;
        }
        
        return $this->request('post', "/bookings/{$bookingId}/reschedule", $data);
    }
}
```

### Step 2: Create Backward Compatibility Adapter

Create `app/Services/Calcom/CalcomV2Adapter.php`:

```php
<?php

namespace App\Services\Calcom;

use App\Services\CalcomV2Service;

/**
 * Adapter to make V2 client compatible with existing V1 interface
 */
class CalcomV2Adapter extends CalcomV2Service
{
    private CalcomV2Client $v2Client;
    
    public function __construct(?string $apiKey = null)
    {
        parent::__construct($apiKey);
        $this->v2Client = new CalcomV2Client($apiKey);
    }
    
    /**
     * Override bookAppointment to use V2 API
     */
    public function bookAppointment($eventTypeId, $startTime, $endTime, $customerData, $notes = null)
    {
        try {
            // Transform V1 format to V2
            $bookingData = [
                'eventTypeId' => $eventTypeId,
                'start' => $startTime,
                'attendee' => [
                    'name' => $customerData['name'] ?? 'Unknown',
                    'email' => $customerData['email'] ?? 'kunde@example.com',
                    'phone' => $customerData['phone'] ?? null,
                    'timeZone' => $customerData['timeZone'] ?? 'Europe/Berlin',
                ],
                'language' => 'de',
                'metadata' => $customerData['metadata'] ?? [],
                'notes' => $notes,
            ];
            
            $result = $this->v2Client->createBooking($bookingData);
            
            // Transform V2 response to V1 format for compatibility
            if ($result['success']) {
                return [
                    'id' => $result['booking']['id'] ?? null,
                    'uid' => $result['booking']['uid'] ?? null,
                    'status' => $result['booking']['status'] ?? 'ACCEPTED',
                    'startTime' => $result['booking']['startTime'] ?? $startTime,
                    'endTime' => $result['booking']['endTime'] ?? $endTime,
                ];
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('V2 Booking failed, falling back to V1', [
                'error' => $e->getMessage()
            ]);
            
            // Fall back to V1 if V2 fails
            return parent::bookAppointment($eventTypeId, $startTime, $endTime, $customerData, $notes);
        }
    }
    
    /**
     * Override getEventTypes to use V2 API
     */
    public function getEventTypes()
    {
        try {
            return $this->v2Client->getEventTypes();
        } catch (\Exception $e) {
            // Fall back to V1
            return parent::getEventTypes();
        }
    }
    
    /**
     * Override checkAvailability to ensure V2 usage
     */
    public function checkAvailability($eventTypeId, $date, $timezone = 'Europe/Berlin')
    {
        return $this->v2Client->getAvailableSlots($eventTypeId, $date, $date, $timezone);
    }
}
```

### Step 3: Update Service Provider

Update `app/Providers/AppServiceProvider.php`:

```php
public function register()
{
    // ... existing code ...
    
    // Cal.com service binding with feature flag
    $this->app->bind(CalcomV2Service::class, function ($app) {
        // Check feature flag
        if (config('calcom.use_pure_v2', false)) {
            // Use pure V2 implementation
            return new \App\Services\Calcom\CalcomV2Adapter();
        }
        
        // Use existing mixed implementation
        return new \App\Services\CalcomV2Service();
    });
}
```

### Step 4: Add Feature Flags

Update `.env`:

```bash
# Cal.com V2 Migration Flags
CALCOM_USE_PURE_V2=false
CALCOM_V2_BOOKING_ENABLED=false
CALCOM_V2_LOG_REQUESTS=true
CALCOM_V2_LOG_RESPONSES=true
```

### Step 5: Create Migration Command

Create `app/Console/Commands/MigrateCalcomToV2.php`:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Calcom\CalcomV2Client;
use App\Services\CalcomV2Service;

class MigrateCalcomToV2 extends Command
{
    protected $signature = 'calcom:migrate-v2 
                            {--test : Run in test mode}
                            {--company= : Specific company ID}';
    
    protected $description = 'Test Cal.com V2 API migration';
    
    public function handle()
    {
        $this->info('Testing Cal.com V2 API Migration...');
        
        $v1Service = new CalcomV2Service();
        $v2Client = new CalcomV2Client();
        
        // Test authentication
        $this->info('Testing V2 Authentication...');
        try {
            $me = $v2Client->getMe();
            $this->info('✓ V2 Authentication successful: ' . ($me['data']['email'] ?? 'Unknown'));
        } catch (\Exception $e) {
            $this->error('✗ V2 Authentication failed: ' . $e->getMessage());
            return 1;
        }
        
        // Compare event types
        $this->info("\nComparing Event Types...");
        try {
            $v1EventTypes = $v1Service->getEventTypes();
            $v2EventTypes = $v2Client->getEventTypes();
            
            $this->info('V1 Event Types: ' . count($v1EventTypes['event_types'] ?? []));
            $this->info('V2 Event Types: ' . count($v2EventTypes['event_types'] ?? []));
            
        } catch (\Exception $e) {
            $this->error('Event type comparison failed: ' . $e->getMessage());
        }
        
        // Test booking creation (if not in test mode)
        if (!$this->option('test')) {
            $this->info("\nTesting Booking Creation...");
            // Implementation here
        }
        
        $this->info("\nMigration test complete!");
        return 0;
    }
}
```

### Step 6: Gradual Migration Strategy

1. **Phase 1 - Preparation** (Current)
   ```bash
   # Set all flags to false
   CALCOM_USE_PURE_V2=false
   CALCOM_V2_BOOKING_ENABLED=false
   ```

2. **Phase 2 - Testing** (Week 1)
   ```bash
   # Enable for specific companies
   CALCOM_V2_COMPANY_IDS=1,2,3
   CALCOM_V2_LOG_REQUESTS=true
   ```

3. **Phase 3 - Partial Rollout** (Week 2)
   ```bash
   # Enable V2 for non-critical operations
   CALCOM_V2_EVENTYPES_ENABLED=true
   CALCOM_V2_AVAILABILITY_ENABLED=true
   ```

4. **Phase 4 - Full Migration** (Week 3-4)
   ```bash
   # Enable V2 for booking
   CALCOM_V2_BOOKING_ENABLED=true
   CALCOM_USE_PURE_V2=true
   ```

### Step 7: Monitoring & Rollback

Add monitoring to track migration progress:

```php
// app/Services/Monitoring/CalcomMigrationMonitor.php
class CalcomMigrationMonitor
{
    public static function trackApiCall(string $version, string $method, bool $success, float $duration)
    {
        // Log to metrics
        Metrics::increment("calcom.api.{$version}.{$method}.calls");
        Metrics::histogram("calcom.api.{$version}.{$method}.duration", $duration);
        
        if (!$success) {
            Metrics::increment("calcom.api.{$version}.{$method}.errors");
        }
        
        // Log to database for analysis
        DB::table('calcom_api_metrics')->insert([
            'version' => $version,
            'method' => $method,
            'success' => $success,
            'duration_ms' => $duration * 1000,
            'created_at' => now(),
        ]);
    }
}
```

## Testing Checklist

- [ ] V2 Authentication works
- [ ] Event types fetch correctly
- [ ] Availability check returns slots
- [ ] Booking creation succeeds
- [ ] Booking cancellation works
- [ ] Booking rescheduling works
- [ ] Webhooks receive V2 format
- [ ] Error handling works properly
- [ ] Performance is acceptable
- [ ] No breaking changes for API consumers

## Rollback Plan

If issues arise during migration:

1. **Immediate Rollback**
   ```bash
   # Disable V2 flags
   CALCOM_USE_PURE_V2=false
   CALCOM_V2_BOOKING_ENABLED=false
   ```

2. **Clear Cache**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

3. **Monitor Errors**
   ```bash
   tail -f storage/logs/calcom.log
   ```

4. **Revert Code** (if needed)
   ```bash
   git revert <migration-commit>
   ```

This implementation guide provides a safe, gradual migration path from V1 to V2 with proper testing and rollback capabilities.