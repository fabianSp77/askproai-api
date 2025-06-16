# Service Layer Integration Tests

This directory contains comprehensive integration tests for the service layer of the AskProAI platform.

## Test Files

### AppointmentServiceTest.php
Tests the complete appointment workflow including:
- Creating appointments with customer creation
- Cal.com integration
- Appointment rescheduling
- Appointment cancellation
- Availability checking
- Time slot generation
- No-show handling
- Statistics calculation
- Transaction handling and rollbacks

### CustomerServiceTest.php
Tests customer management including:
- Customer creation with validation
- Duplicate detection and merging
- Customer history tracking
- Tag management
- Blocking/unblocking customers
- Data export
- Phone number normalization
- Integration with appointments and calls

### CallServiceTest.php
Tests call processing workflows including:
- Webhook processing for different events
- Customer creation from calls
- Appointment creation from calls
- Call analysis and structured data extraction
- Retell.ai API integration
- Statistics calculation
- Error handling and retries
- Transaction rollbacks

## Running the Tests

### Run all integration tests:
```bash
php artisan test tests/Integration/Services
```

### Run a specific test file:
```bash
php artisan test tests/Integration/Services/AppointmentServiceTest.php
php artisan test tests/Integration/Services/CustomerServiceTest.php
php artisan test tests/Integration/Services/CallServiceTest.php
```

### Run a specific test method:
```bash
php artisan test --filter=test_creates_appointment_with_customer_and_fires_event
```

### Run with coverage:
```bash
php artisan test tests/Integration/Services --coverage
```

## Key Testing Patterns

1. **Event Testing**: Uses `Event::fake()` to verify events are dispatched correctly
2. **Mail Testing**: Uses `Mail::fake()` to verify email notifications
3. **External Service Mocking**: Uses Mockery to mock Cal.com and Retell.ai services
4. **Database Transactions**: Tests verify transaction rollbacks on failures
5. **Complete Workflows**: Tests cover entire business flows, not just individual methods

## Test Database

These tests use the RefreshDatabase trait, which:
- Runs migrations before each test class
- Wraps each test in a database transaction
- Rolls back changes after each test
- Uses SQLite in-memory database for speed

## Mocking External Services

### Cal.com Service Mock
```php
$calcomServiceMock = Mockery::mock(CalcomService::class);
$calcomServiceMock->shouldReceive('createBooking')
    ->once()
    ->with(Mockery::on(function ($data) {
        // Validate the data structure
        return isset($data['eventTypeId']);
    }))
    ->andReturn(['id' => 12345]);
```

### Retell.ai Service Mock
```php
$retellServiceMock = Mockery::mock(RetellService::class);
$retellServiceMock->shouldReceive('getCall')
    ->once()
    ->with('call_id_123')
    ->andReturn(['status' => 'completed']);
```

## Common Assertions

- Database state: `$this->assertDatabaseHas('table', ['field' => 'value'])`
- Event dispatching: `Event::assertDispatched(EventClass::class)`
- Model relationships: `$this->assertTrue($model->relationLoaded('relation'))`
- Collection counts: `$this->assertCount(3, $collection)`
- Exception handling: `$this->expectException(ExceptionClass::class)`

## Tips for Writing More Tests

1. **Test Edge Cases**: Empty data, null values, invalid inputs
2. **Test Concurrent Operations**: Multiple appointments at same time
3. **Test Permission Scenarios**: Different user roles and permissions
4. **Test API Rate Limits**: External service throttling
5. **Test Data Consistency**: Verify related data stays in sync
6. **Test Business Rules**: No-show policies, cancellation rules, etc.

## Debugging Failed Tests

1. Check test database migrations are up to date
2. Verify factory definitions match current schema
3. Check for hardcoded dates that may expire
4. Review mock expectations vs actual calls
5. Use `$this->withoutExceptionHandling()` to see full errors
6. Add `dd()` or `Log::info()` to debug test flow