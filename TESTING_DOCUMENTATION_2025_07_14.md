# 📚 Testing Documentation - AskProAI

## 🎯 Test Suite Overview

### Current Status (Stand: 2025-07-14)
- **Total Tests**: 203
- **Working Tests**: ~140
- **Test Assertions**: 465
- **Code Coverage**: Not available (no coverage driver)

### Test Categories

#### 1. Unit Tests (137 Tests)
- **Basic Tests** (7): DatabaseConnection, Simple, Example, BasicPHPUnit
- **Mock Tests** (5): MockRetellService, MockServices
- **Model Tests** (8): BranchRelationship, SchemaFixValidation
- **Service Tests** (38): Context7, WebhookDeduplication, AppointmentLockUnit, CriticalFixes
- **Repository Tests** (76): Appointment (25), Call (26), Customer (25)
- **Security Tests** (9): SensitiveDataMasker
- **MCP Tests** (15): MCPGateway
- **Cache Tests** (11): CacheManager
- **Middleware Tests** (9): VerifyStripeSignature

#### 2. Feature Tests (25 Tests)
- **Simple Tests** (2): Basic feature tests
- **API Tests** (13): Authentication endpoints
- **Webhook Tests** (10): Integration tests

#### 3. Integration Tests (20 Tests)
- Service integration tests
- External API mock tests

#### 4. E2E Tests (21 Tests)
- PhoneToAppointmentFlow
- CustomerPortalLogin
- AppointmentManagement
- BookingFlow

## 🚀 Running Tests

### Basic Test Execution
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
php artisan test --testsuite=Integration
php artisan test --testsuite=E2E

# Run with parallel execution
php artisan test --parallel --processes=4

# Run specific test file
./vendor/bin/phpunit tests/Unit/Services/Context7ServiceTest.php

# Run with filter
php artisan test --filter CustomerRepository
```

### Running Working Tests Only
```bash
# All currently working tests (85 tests)
./vendor/bin/phpunit \
  tests/Unit/DatabaseConnectionTest.php \
  tests/Unit/SimpleTest.php \
  tests/Unit/ExampleTest.php \
  tests/Unit/BasicPHPUnitTest.php \
  tests/Unit/MockRetellServiceTest.php \
  tests/Unit/Mocks/MockServicesTest.php \
  tests/Unit/Models/BranchRelationshipTest.php \
  tests/Unit/SchemaFixValidationTest.php \
  tests/Unit/Services/Context7ServiceTest.php \
  tests/Unit/Services/Webhook/WebhookDeduplicationServiceTest.php \
  tests/Unit/Repositories/AppointmentRepositoryTest.php \
  tests/Unit/Services/AppointmentBookingServiceLockUnitTest.php \
  tests/Unit/CriticalFixesUnitTest.php \
  tests/Feature/SimpleTest.php \
  --no-coverage
```

### Coverage Reports (requires PCOV/Xdebug)
```bash
# Text coverage
php artisan test --coverage

# HTML coverage report
php artisan test --coverage-html=coverage-report

# Coverage with minimum threshold
php artisan test --coverage --min=70

# Clover XML for CI/CD
php artisan test --coverage-clover=coverage.xml
```

## 🏗️ Test Infrastructure

### Test Database
- **Default**: SQLite in-memory (`:memory:`)
- **Integration Tests**: MySQL test database
- **Isolation**: Each test runs in transaction

### Test Environment
- Configuration: `.env.testing`
- Migrations: Run fresh for each test
- Seeders: None by default

### Mock Services
All external services are mocked in tests:
- **CalcomServiceMock**: Calendar API operations
- **StripeServiceMock**: Payment processing
- **EmailServiceMock**: Email notifications
- **RetellServiceMock**: AI phone service

## 📝 Writing Tests

### Test Structure
```php
<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\MyService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyServiceTest extends TestCase
{
    use RefreshDatabase;
    
    protected MyService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new MyService();
    }
    
    /** @test */
    public function it_performs_expected_action()
    {
        // Arrange
        $input = 'test';
        
        // Act
        $result = $this->service->process($input);
        
        // Assert
        $this->assertEquals('expected', $result);
    }
}
```

### Best Practices
1. **Use RefreshDatabase trait** for database tests
2. **Mock external dependencies** to avoid API calls
3. **Test one thing per test** for clarity
4. **Use descriptive test names** that explain the scenario
5. **Follow AAA pattern**: Arrange, Act, Assert
6. **Isolate tests** - no test should depend on another

### Factory Usage
```php
// Create single model
$customer = Customer::factory()->create();

// Create with specific attributes
$customer = Customer::factory()->create([
    'company_id' => $company->id,
    'email' => 'test@example.com'
]);

// Create multiple
$customers = Customer::factory()->count(3)->create();

// Create without persisting
$customer = Customer::factory()->make();
```

## 🔧 Common Issues & Solutions

### Issue: "main.kunden" table not found
**Solution**: Update migrations referencing old 'kunden' table to 'customers'
```php
// Change from:
->constrained('kunden')
// To:
->constrained('customers')
```

### Issue: Event broadcasting in tests
**Solution**: Disable events in TestCase setup
```php
protected function setUp(): void
{
    parent::setUp();
    Event::fake();
}
```

### Issue: TenantScope filtering test data
**Solution**: Set proper tenant context
```php
app()->instance('current_company_id', $this->company->id);
app()->instance('current_company', $this->company);
```

### Issue: Type hints for UUID support
**Solution**: Use string|int type hints
```php
public function find(string|int $id): ?Model
```

## 🔍 Test Quality Metrics

### Coverage Goals
- **Unit Tests**: 80% coverage
- **Service Layer**: 90% coverage
- **Repository Layer**: 95% coverage
- **Controllers**: 70% coverage
- **Overall**: 75% coverage

### Test Performance
- Unit tests: < 100ms per test
- Feature tests: < 500ms per test
- E2E tests: < 2s per test
- Total suite: < 5 minutes

## 🚨 CI/CD Integration

### GitHub Actions Workflows
1. **ci.yml**: Basic test pipeline
2. **ci-advanced.yml**: Comprehensive pipeline with quality checks
3. **test-coverage.yml**: Coverage report generation

### Pipeline Steps
1. Code quality checks (Pint, PHPStan)
2. Security scanning
3. Unit tests (parallel)
4. Feature tests
5. Integration tests
6. E2E tests (on PR only)
7. Coverage report
8. Performance benchmarks

### Required Secrets
```
STAGING_SSH_HOST
STAGING_SSH_USER
STAGING_SSH_KEY
PRODUCTION_SSH_HOST
PRODUCTION_SSH_USER
PRODUCTION_SSH_KEY
SLACK_WEBHOOK
CODECOV_TOKEN
```

## 📊 Test Reports

### PHPUnit Reports
- **JUnit XML**: For CI/CD integration
- **TeamCity**: For TeamCity CI
- **TestDox**: Human-readable test names
- **Coverage**: HTML, Clover, Cobertura formats

### Custom Reports
```bash
# Generate test summary
php artisan test:summary

# Generate detailed report
php artisan test:report --format=json > test-report.json

# Performance report
php artisan test:benchmark
```

## 🎯 Next Steps

1. **Install PCOV** for code coverage
   ```bash
   pecl install pcov
   echo "extension=pcov.so" >> php.ini
   ```

2. **Fix remaining test failures**
   - Repository tests with TenantScope
   - Event broadcasting issues
   - Mock service improvements

3. **Increase test coverage**
   - Add controller tests
   - Add API endpoint tests
   - Add validation tests

4. **Performance optimization**
   - Implement test parallelization
   - Optimize database queries in tests
   - Cache test dependencies

5. **Documentation**
   - Add inline documentation
   - Create testing guidelines
   - Document test data builders

## 🏆 Testing Philosophy

"A test suite is only as good as the bugs it catches and the confidence it provides."

Our testing strategy focuses on:
- **Fast feedback** - Quick test execution
- **High confidence** - Comprehensive coverage
- **Easy maintenance** - Clear, simple tests
- **Continuous improvement** - Regular updates

Remember: Tests are documentation for how your code should work!