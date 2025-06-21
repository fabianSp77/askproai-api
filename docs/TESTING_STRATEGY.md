# AskProAI Testing Strategy

## Overview
This document outlines the comprehensive testing strategy for the AskProAI platform, covering unit tests, integration tests, E2E tests, and performance testing.

## Test Levels

### 1. Unit Tests
Focus on testing individual components in isolation.

**Coverage Areas:**
- Services (CalcomV2Service, RetellV2Service, etc.)
- Models and their relationships
- Utilities and helpers
- Validators and formatters

**Key Test Files:**
- `tests/Unit/CalcomV2ServiceTest.php`
- `tests/Unit/PhoneNumberValidatorTest.php`
- `tests/Unit/MockRetellServiceTest.php`
- `tests/Unit/CriticalFixesUnitTest.php`

**Running Unit Tests:**
```bash
php artisan test --testsuite=Unit
php artisan test --testsuite=Unit --parallel
```

### 2. Integration Tests
Test interaction between multiple components.

**Coverage Areas:**
- Service interactions
- Database transactions
- External API mocking
- Queue job processing

**Key Test Files:**
- `tests/Integration/BookingServiceIntegrationTest.php`
- `tests/Integration/WebhookProcessingTest.php`
- `tests/Integration/CalcomIntegrationTest.php`

**Running Integration Tests:**
```bash
php artisan test --testsuite=Integration
```

### 3. End-to-End (E2E) Tests
Test complete user workflows from start to finish.

**Coverage Areas:**
- Complete booking flow (phone → appointment)
- Webhook processing chains
- Multi-step processes
- Error scenarios

**Key Test Files:**
- `tests/E2E/BookingFlowCalcomV2E2ETest.php`
- `tests/E2E/PhoneToAppointmentFlowTest.php`
- `tests/E2E/ConcurrentBookingStressTest.php`

**Running E2E Tests:**
```bash
php artisan test --testsuite=E2E
```

### 4. Performance Tests
Ensure system meets performance requirements.

**Coverage Areas:**
- Concurrent request handling
- Database query performance
- API response times
- Memory usage

**Key Test Files:**
- `tests/E2E/ConcurrentBookingStressTest.php`
- Performance assertions in E2E tests

**Running Performance Tests:**
```bash
php artisan test --filter=performance
```

## Test Data Strategy

### 1. Factories
Use Laravel factories for consistent test data generation.

```php
// Create test company with branches
$company = Company::factory()
    ->has(Branch::factory()->count(3))
    ->has(Staff::factory()->count(5))
    ->create();
```

### 2. Seeders
Special seeders for test environments:
- `TestDataSeeder` - Basic test data
- `PerformanceTestSeeder` - Large datasets
- `E2ETestSeeder` - Complete scenarios

### 3. Mock Services
Replace external services with mocks:
- `MockCalcomV2Client` - Cal.com API mock
- `MockRetellService` - Retell.ai API mock
- `Mail::fake()` - Email testing
- `Queue::fake()` - Queue testing

## Test Environment Configuration

### PHPUnit Configuration
```xml
<phpunit>
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
        <testsuite name="E2E">
            <directory>tests/E2E</directory>
        </testsuite>
    </testsuites>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="CACHE_DRIVER" value="array"/>
    </php>
</phpunit>
```

### Test Database
- SQLite in-memory for speed
- Migrations run before each test
- Transaction rollback after each test

## Mock Strategy

### External APIs
All external APIs should be mocked in tests:

```php
// Mock Cal.com API
$this->app->bind(CalcomV2Client::class, function () {
    return new MockCalcomV2Client();
});

// Mock Retell API
$this->app->bind(RetellV2Service::class, function () {
    return new MockRetellService();
});
```

### Time-based Testing
Use Carbon's test helpers:

```php
// Freeze time
Carbon::setTestNow('2024-01-15 10:00:00');

// Travel to future
$this->travel(5)->minutes();
```

## Continuous Integration

### GitHub Actions Workflow
```yaml
name: Tests
on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
      - name: Install Dependencies
        run: composer install
      - name: Run Tests
        run: php artisan test --parallel
      - name: Upload Coverage
        uses: codecov/codecov-action@v1
```

### Pre-commit Hooks
```bash
#!/bin/sh
# .git/hooks/pre-commit

# Run tests before commit
php artisan test --testsuite=Unit --stop-on-failure

# Check code style
./vendor/bin/pint --test
```

## Test Scenarios

### Critical Business Flows

#### 1. Appointment Booking Flow
```
1. Receive Retell webhook (call ended)
2. Extract customer information
3. Check availability via Cal.com
4. Create appointment
5. Send confirmation email
```

#### 2. Webhook Deduplication
```
1. Receive duplicate webhook
2. Check Redis for existing key
3. Return cached response
4. Verify no duplicate processing
```

#### 3. Multi-Tenancy Isolation
```
1. Create data for Company A
2. Create data for Company B
3. Verify Company A cannot access Company B data
4. Test with different user contexts
```

### Error Scenarios

#### 1. External API Failures
- Cal.com timeout
- Retell.ai rate limit
- Network errors
- Invalid responses

#### 2. Data Integrity
- Concurrent bookings
- Race conditions
- Transaction rollbacks
- Lock timeouts

#### 3. Security
- Invalid signatures
- Unauthorized access
- SQL injection attempts
- XSS prevention

## Performance Benchmarks

### Response Time Targets
- API endpoints: < 200ms (p95)
- Webhook processing: < 100ms
- Database queries: < 50ms
- External API calls: < 2s with timeout

### Load Testing
```bash
# Using Apache Bench
ab -n 1000 -c 10 https://api.askproai.de/api/health

# Using Locust
locust -f tests/LoadTests/booking_flow.py --host=https://api.askproai.de
```

## Test Maintenance

### Best Practices
1. **Keep tests fast**: Use mocks, avoid sleep()
2. **Keep tests isolated**: No dependencies between tests
3. **Keep tests readable**: Clear names, arrange-act-assert
4. **Keep tests maintainable**: Use factories, avoid hardcoded values

### Regular Tasks
- Weekly: Review failing tests
- Monthly: Update test data
- Quarterly: Performance baseline update
- Yearly: Test strategy review

## Debugging Failed Tests

### Local Debugging
```bash
# Run specific test with details
php artisan test --filter=test_can_book_appointment -vvv

# Stop on first failure
php artisan test --stop-on-failure

# With Xdebug
XDEBUG_MODE=debug php artisan test
```

### CI Debugging
- Check GitHub Actions logs
- Download artifacts
- Re-run with SSH access
- Use `ray()` for debugging

## Coverage Requirements

### Minimum Coverage
- Overall: 80%
- Critical paths: 95%
- New code: 90%

### Coverage Reports
```bash
# Generate HTML report
php artisan test --coverage-html coverage

# Generate Clover report
php artisan test --coverage-clover coverage.xml
```

## Testing Checklist

### Before Deployment
- [ ] All tests passing
- [ ] Coverage meets requirements
- [ ] Performance tests pass
- [ ] Security tests pass
- [ ] E2E tests pass
- [ ] No console.log or dd() statements
- [ ] Mocks disabled for production

### After Deployment
- [ ] Smoke tests pass
- [ ] Health checks green
- [ ] No error spike in logs
- [ ] Performance metrics normal

---
*Last Updated: 2025-06-17*