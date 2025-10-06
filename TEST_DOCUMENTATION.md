# Test Documentation - API Gateway System

## Overview
This document provides comprehensive testing procedures and guidelines for the API Gateway system with Filament Admin Panel.

## Test Architecture

### Testing Framework
- **Framework**: Pest PHP 3.x with Laravel integration
- **Coverage Tools**: PHPUnit Code Coverage
- **Browser Testing**: Laravel Dusk (optional)
- **Mocking**: Mockery
- **Assertions**: Pest expectations + custom matchers

### Test Structure
```
tests/
├── Feature/
│   ├── Filament/
│   │   └── Resources/       # Resource CRUD tests
│   ├── Integration/
│   │   ├── CacheOptimizationTest.php
│   │   ├── DatabaseOptimizationTest.php
│   │   ├── NotificationWorkflowTest.php
│   │   ├── AutomatedProcessTest.php
│   │   └── PaymentIntegrationTest.php
│   └── Performance/
│       └── BenchmarkTest.php
├── Unit/
│   ├── Models/
│   ├── Services/
│   └── Helpers/
└── Pest.php                 # Global configuration

```

## Running Tests

### Full Test Suite
```bash
php artisan test
```

### Specific Test Files
```bash
php artisan test --filter=CompanyResourceTest
php artisan test tests/Feature/Integration/PaymentIntegrationTest.php
```

### Test Coverage
```bash
php artisan test --coverage
php artisan test --coverage --min=80  # Enforce minimum 80% coverage
```

### Parallel Testing
```bash
php artisan test --parallel
php artisan test --parallel --processes=4
```

### Performance Tests
```bash
php artisan test --filter=BenchmarkTest --stop-on-failure
```

## Test Categories

### 1. Feature Tests

#### Filament Resource Tests
Tests for all 25 Filament resources covering:
- CRUD operations
- Authorization and permissions
- Form validation
- Search and filtering
- Bulk operations
- Relationship management
- Multi-tenancy isolation

**Example Test**:
```php
it('can create company with valid data', function () {
    actingAsAdmin();

    $companyData = [
        'name' => 'Test Company GmbH',
        'email' => 'info@testcompany.de',
        'phone' => '+49 30 12345678'
    ];

    Livewire::test(CompanyResource\Pages\CreateCompany::class)
        ->fillForm($companyData)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('companies', $companyData);
});
```

### 2. Integration Tests

#### Cache Optimization Tests
- Model caching effectiveness
- Cache invalidation on updates
- Cache tag management
- Aggregate query caching
- Cache warming strategies
- Cache hit rate monitoring

#### Database Optimization Tests
- N+1 query prevention
- Index usage verification
- Query optimization with joins
- Efficient pagination
- Transaction integrity
- Bulk operation performance

#### Notification Workflow Tests
- Appointment reminder timing
- No-show processing
- Review request automation
- Multi-channel delivery
- Language template selection
- Duplicate prevention

#### Payment Integration Tests
- Stripe payment processing
- PayPal integration
- Balance management
- Refund processing
- Topup with bonuses
- Payment method management

#### Automated Process Tests
- Staff auto-assignment
- Recurring appointments
- Schedule optimization
- Auto-confirmation
- Waitlist management
- Intelligent rescheduling

### 3. Performance Benchmarks

#### Metrics Tracked
- Response times (<200ms target)
- Memory usage (<50MB for large datasets)
- Query performance
- Bulk operation speed
- Cache effectiveness
- Concurrent request handling

**Example Benchmark**:
```php
it('benchmarks customer listing performance', function () {
    Customer::factory(1000)->create();

    $start = microtime(true);
    Customer::with(['appointments', 'calls'])->paginate(50);
    $duration = microtime(true) - $start;

    expect($duration)->toBeLessThan(0.5); // 500ms max
});
```

## Test Data Management

### Factories
All models have corresponding factories for test data generation:
```php
$customer = Customer::factory()->create();
$appointment = Appointment::factory()
    ->for($customer)
    ->scheduled()
    ->create();
```

### Seeders
Test-specific seeders for complex scenarios:
```php
$this->seed(RolesAndPermissionsSeeder::class);
$setup = createCompanyWithFullSetup(); // Helper function
```

### Database Transactions
Tests use `RefreshDatabase` trait for isolation:
```php
uses(RefreshDatabase::class);
```

## Mocking External Services

### Payment Gateways
```php
Http::fake([
    'api.stripe.com/*' => Http::response([
        'id' => 'pi_test_123',
        'status' => 'succeeded'
    ], 200)
]);
```

### Notification Services
```php
Notification::fake();
// ... perform actions
Notification::assertSentTo($customer, AppointmentConfirmed::class);
```

### External APIs
```php
$this->mock(RetellAIService::class)
    ->shouldReceive('createAgent')
    ->once()
    ->andReturn($mockAgent);
```

## Custom Assertions

### Pest Expectations
```php
expect()->extend('toBePhoneNumber', function () {
    return $this->toMatch('/^\+?[1-9]\d{1,14}$/');
});

expect($phone)->toBePhoneNumber();
```

### Helper Functions
```php
function actingAsAdmin(): User
function actingAsCompanyOwner(Company $company = null): User
function actingAsStaff(Staff $staff = null): User
function createCompanyWithFullSetup(): array
```

## Continuous Integration

### GitHub Actions Workflow
```yaml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_DATABASE: testing
          MYSQL_ROOT_PASSWORD: password
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: mbstring, pdo_mysql
      - name: Install Dependencies
        run: composer install
      - name: Run Tests
        run: php artisan test --parallel
      - name: Generate Coverage Report
        run: php artisan test --coverage-html coverage
      - name: Upload Coverage
        uses: actions/upload-artifact@v2
        with:
          name: coverage-report
          path: coverage/
```

## Test Quality Standards

### Minimum Requirements
- **Code Coverage**: ≥80% for critical services
- **Performance**: All endpoints <200ms response time
- **Reliability**: Zero flaky tests
- **Documentation**: All test cases documented

### Best Practices
1. **Test Isolation**: Each test independent
2. **Clear Naming**: Descriptive test names
3. **Single Responsibility**: One assertion focus per test
4. **Fast Execution**: <10 seconds for unit tests
5. **Deterministic**: Same result every run

## Debugging Tests

### Verbose Output
```bash
php artisan test --verbose
php artisan test --debug
```

### Database Inspection
```php
$this->assertDatabaseHas('table', ['column' => 'value']);
$this->assertDatabaseCount('table', 5);
$this->assertDatabaseMissing('table', ['deleted_at' => null]);
```

### Query Logging
```php
DB::enableQueryLog();
// ... perform operations
$queries = DB::getQueryLog();
dd($queries);
```

### Pest Debugging
```php
test()->dd($variable); // Dump and die
test()->dump($variable); // Dump and continue
```

## Test Maintenance

### Regular Tasks
- **Weekly**: Review and update failing tests
- **Monthly**: Analyze coverage gaps
- **Quarterly**: Performance benchmark review
- **Yearly**: Framework version updates

### Test Refactoring
- Remove duplicate test logic
- Extract common setups to helpers
- Update deprecated assertions
- Optimize slow-running tests

## Security Testing

### Authentication Tests
```php
it('requires authentication for admin routes')
it('enforces role-based permissions')
it('validates multi-tenancy isolation')
```

### Input Validation
```php
it('sanitizes user input')
it('prevents SQL injection')
it('validates file uploads')
```

### API Security
```php
it('enforces rate limiting')
it('validates API tokens')
it('logs security events')
```

## Performance Monitoring

### Metrics to Track
- Test suite execution time
- Memory usage per test
- Database query count
- Cache hit rates
- API response times

### Optimization Strategies
1. Use database transactions
2. Minimize factory usage
3. Mock external services
4. Parallelize independent tests
5. Cache test dependencies

## Troubleshooting

### Common Issues

#### Database Connection Errors
```bash
# Check .env.testing file
DB_CONNECTION=mysql
DB_DATABASE=askproai_test
DB_USERNAME=testing
DB_PASSWORD=password
```

#### Memory Exhaustion
```bash
# Increase memory limit
php -d memory_limit=512M artisan test
```

#### Timeout Issues
```php
// Increase timeout for specific tests
$this->setTimeout(30); // 30 seconds
```

### Test Isolation Problems
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

## Reporting

### Coverage Reports
- HTML: `coverage/index.html`
- Console: `php artisan test --coverage`
- CI/CD: Upload to codecov.io

### Performance Reports
- Benchmark results in `tests/Performance/results/`
- Automated alerts for regression

### Quality Metrics
- Tests per feature
- Coverage percentage
- Average execution time
- Failure rate trends

## Conclusion

This testing infrastructure ensures:
- ✅ High code quality through comprehensive coverage
- ✅ Performance validation through benchmarking
- ✅ Integration reliability through mocking
- ✅ Security through authentication/authorization tests
- ✅ Maintainability through clear documentation

For questions or issues, refer to the Pest PHP documentation or Laravel testing guides.