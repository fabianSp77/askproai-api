# Cal.com API Migration Guide

## Quick Start

### 1. Update Environment Variables

Add these to your `.env` file:

```env
# Existing
CALCOM_API_KEY=cal_live_e9aa2c4d18e0fd79cf4f8dddb90903da
CALCOM_WEBHOOK_SECRET=your_webhook_secret_here
CALCOM_TEAM_SLUG=askproai

# New settings for v2
CALCOM_API_VERSION=v2
CALCOM_V2_API_VERSION=2024-08-13
CALCOM_FALLBACK_V1=false
```

### 2. Update Service Configuration

Update `config/services.php`:

```php
'calcom' => [
    'api_key' => env('CALCOM_API_KEY'),
    'webhook_secret' => env('CALCOM_WEBHOOK_SECRET'),
    'team_slug' => env('CALCOM_TEAM_SLUG', 'askproai'),
    'api_version' => env('CALCOM_API_VERSION', 'v2'),
    'v2_api_version' => env('CALCOM_V2_API_VERSION', '2024-08-13'),
    'enable_fallback' => env('CALCOM_FALLBACK_V1', false),
],
```

### 3. Test Your Configuration

Run the test script to verify your setup:

```bash
php test-calcom-unified.php
```

## Code Migration

### Replace CalcomService with CalcomUnifiedService

#### Before (Old Code)
```php
use App\Services\CalcomService;

class SomeController extends Controller
{
    private $calcomService;
    
    public function __construct(CalcomService $calcomService)
    {
        $this->calcomService = $calcomService;
    }
}
```

#### After (New Code)
```php
use App\Services\CalcomUnifiedService;

class SomeController extends Controller
{
    private $calcomService;
    
    public function __construct(CalcomUnifiedService $calcomService)
    {
        $this->calcomService = $calcomService;
    }
}
```

### Update Service Container Binding

In `app/Providers/AppServiceProvider.php`:

```php
public function register()
{
    // Add this binding
    $this->app->bind(
        \App\Services\CalcomService::class,
        \App\Services\CalcomUnifiedService::class
    );
}
```

This allows existing code to work without changes while using the new unified service.

## API Usage Examples

### 1. Get Event Types
```php
$service = new CalcomUnifiedService();
$eventTypes = $service->getEventTypes();

// Response is normalized regardless of API version
foreach ($eventTypes as $eventType) {
    echo $eventType['id'] . ': ' . $eventType['title'] . "\n";
}
```

### 2. Check Availability
```php
$eventTypeId = 2026302;
$dateFrom = Carbon::now()->addDay()->setHour(9)->toIso8601String();
$dateTo = Carbon::now()->addDay()->setHour(18)->toIso8601String();

$availability = $service->checkAvailability($eventTypeId, $dateFrom, $dateTo);

// Normalized response structure
if ($availability && isset($availability['slots'])) {
    foreach ($availability['slots'] as $slot) {
        echo "Available: " . $slot['time'] . "\n";
    }
}
```

### 3. Create Booking
```php
$customerData = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'phone' => '+491234567890'
];

$booking = $service->bookAppointment(
    $eventTypeId,
    '2024-06-15T14:00:00Z',
    null, // endTime is calculated automatically
    $customerData,
    'Additional notes here'
);

// Normalized response
if ($booking) {
    echo "Booking ID: " . $booking['id'] . "\n";
    echo "Booking UID: " . $booking['uid'] . "\n";
    echo "Status: " . $booking['status'] . "\n";
}
```

### 4. Get Booking Details
```php
$bookingId = 'abc123xyz'; // Use UID for v2, ID for v1
$booking = $service->getBooking($bookingId);
```

### 5. Cancel Booking
```php
$result = $service->cancelBooking($bookingId, 'Customer requested cancellation');
```

## Webhook Handling

### Update Webhook Controller

The webhook payload structure may differ between v1 and v2. Update your webhook handler:

```php
public function handle(Request $request)
{
    $payload = $request->all();
    
    // Check for v2 structure
    if (isset($payload['data']) && isset($payload['data']['id'])) {
        // V2 webhook
        $bookingData = $payload['data'];
        $bookingId = $bookingData['id'];
        $bookingUid = $bookingData['uid'];
    } else {
        // V1 webhook
        $bookingData = $payload;
        $bookingId = $bookingData['id'] ?? null;
        $bookingUid = $bookingData['uid'] ?? null;
    }
    
    // Process webhook...
}
```

## Database Updates

Run the migration to add API version tracking:

```bash
php artisan make:migration add_api_version_to_calcom_bookings --table=calcom_bookings
```

Migration content:
```php
public function up()
{
    Schema::table('calcom_bookings', function (Blueprint $table) {
        $table->string('api_version')->default('v1')->after('booking_uid');
        $table->json('v2_response_data')->nullable()->after('api_version');
    });
}
```

## Testing

### 1. Run Unit Tests
```bash
php artisan test --filter=CalcomUnifiedServiceTest
```

### 2. Manual Testing Checklist
- [ ] Test API connectivity with `php test-calcom-unified.php`
- [ ] Verify event types are retrieved correctly
- [ ] Check availability returns proper slots
- [ ] Create a test booking
- [ ] Verify webhook is received for the booking
- [ ] Cancel the test booking
- [ ] Check logs for any errors

### 3. Integration Testing
```php
// Create a test route for integration testing
Route::get('/test/calcom-integration', function () {
    $service = app(CalcomUnifiedService::class);
    
    // Test connection
    $connection = $service->testConnection();
    
    // Get event types
    $eventTypes = $service->getEventTypes();
    
    // Check availability
    $availability = $service->checkAvailability(
        2026302,
        now()->addDay()->toIso8601String(),
        now()->addDays(2)->toIso8601String()
    );
    
    return response()->json([
        'connection' => $connection,
        'event_types_count' => is_array($eventTypes) ? count($eventTypes) : 0,
        'has_availability' => !empty($availability['slots'])
    ]);
});
```

## Troubleshooting

### Common Issues

1. **403 Forbidden Error**
   - Your API key only works with v2
   - Set `CALCOM_API_VERSION=v2` in `.env`
   - Disable v1 fallback: `CALCOM_FALLBACK_V1=false`

2. **No Slots Available**
   - Check the event type ID is correct
   - Verify the date range is in the future
   - Ensure timezone is set correctly (Europe/Berlin)

3. **Booking Fails**
   - Verify all required fields are provided
   - Check the slot time is still available
   - Ensure customer email is valid

### Debug Mode

Enable detailed logging by adding to your controller:

```php
use Illuminate\Support\Facades\Log;

Log::channel('calcom')->info('API Request', [
    'endpoint' => $endpoint,
    'method' => $method,
    'data' => $data
]);
```

Add a custom log channel in `config/logging.php`:

```php
'channels' => [
    'calcom' => [
        'driver' => 'single',
        'path' => storage_path('logs/calcom.log'),
        'level' => 'debug',
    ],
],
```

## Rollback Plan

If issues occur, you can quickly rollback:

1. Set `CALCOM_API_VERSION=v1` in `.env`
2. Enable fallback: `CALCOM_FALLBACK_V1=true`
3. Or switch back to old service:
   ```php
   // In AppServiceProvider, comment out the binding
   // $this->app->bind(CalcomService::class, CalcomUnifiedService::class);
   ```

## Support

For issues or questions:
1. Check the logs in `storage/logs/laravel.log`
2. Run the test script: `php test-calcom-unified.php`
3. Review the API documentation at https://cal.com/docs/api-reference/v2

## Next Steps

1. Update all controllers to use `CalcomUnifiedService`
2. Run tests to ensure functionality
3. Monitor logs during initial deployment
4. Remove old `CalcomService` and `CalcomV2Service` once stable