# Portal Controller Test Implementation Strategy

## Overview
This document outlines the comprehensive testing strategy for Portal controllers to achieve 60% test coverage efficiently by targeting high-value tests that cover critical business logic, security, and user workflows.

## Current Test Coverage Analysis

### Tested Controllers (6/67)
- **AuthenticationTest.php** ✅ - Basic login/logout functionality
- **Emergency tests** ✅ - Critical path, API contracts, performance

### Priority Controllers Needing Tests (5/67)
1. **DashboardController** ✅ - COMPLETED (comprehensive test created)
2. **BillingController** 🔄 - IN PROGRESS
3. **AppointmentController** ⏳ - PENDING
4. **CallController** ⏳ - PENDING
5. **CustomerController** ⏳ - PENDING (need to identify actual controller)

## Test Architecture & Patterns

### 1. Test Class Structure
```php
class {Controller}Test extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Company $company;
    protected User $user;
    protected User $adminUser;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->createTestData();
        $this->mockExternalServices();
    }
    
    protected function createTestData(): void
    {
        // Create realistic test data
    }
    
    protected function mockExternalServices(): void
    {
        // Mock MCP services, external APIs
    }
}
```

### 2. Authentication & Authorization Setup
```php
// Regular user with limited permissions
$this->user = User::factory()->create([
    'company_id' => $this->company->id,
    'permissions' => [
        'dashboard.view' => true,
        'calls.view_own' => true,
        'billing.view' => false
    ]
]);

// Admin user with full permissions
$this->adminUser = User::factory()->create([
    'company_id' => $this->company->id,
    'permissions' => [
        'dashboard.view' => true,
        'calls.view_all' => true,
        'billing.view' => true,
        'analytics.view_team' => true
    ]
]);
```

### 3. MCP Service Mocking Strategy
```php
protected function mockMCPServices(): void
{
    $this->mock('alias:' . UsesMCPServers::class, function ($mock) {
        $mock->shouldReceive('executeMCPTask')
            ->with('getDashboardStatistics', Mockery::any())
            ->andReturn([
                'success' => true,
                'result' => ['data' => [...]]
            ]);
    });
}
```

## Test Categories & Coverage Targets

### 1. Authentication & Authorization Tests (Critical - 100% Coverage)
- ✅ Authentication required for protected routes
- ✅ Role-based access control
- ✅ Admin impersonation mode
- ✅ Tenant isolation
- ✅ Session persistence

### 2. Business Logic Tests (High Priority - 80% Coverage)
- ✅ Core controller methods (index, show, store, update, destroy)
- ✅ API endpoints with correct data structures
- ✅ Permission-based data filtering
- ✅ Status transitions and workflow logic

### 3. Integration Tests (Medium Priority - 60% Coverage)
- ✅ MCP service integration
- ✅ External API interactions (mocked)
- ✅ Database operations with transactions
- ✅ Event dispatching

### 4. Error Handling Tests (Medium Priority - 60% Coverage)
- ✅ Service failure graceful degradation
- ✅ Invalid input validation
- ✅ 404/403 error responses
- ✅ Timeout handling

### 5. Performance Tests (Low Priority - 40% Coverage)
- ✅ Response time benchmarks
- ✅ Database query optimization
- ✅ Caching effectiveness
- ✅ Memory usage limits

## Controller-Specific Test Plans

### 1. DashboardController ✅ COMPLETED
**Coverage**: 95% (20 test methods)
- Authentication & authorization
- MCP service integration
- Admin impersonation mode
- Performance benchmarks
- Error handling
- Tenant isolation
- API endpoints

### 2. BillingController 🔄 IN PROGRESS
**Target Coverage**: 85%
**Key Test Areas**:
- Payment processing (Stripe integration)
- Topup functionality
- Invoice downloads
- Auto-topup settings
- Payment method management
- Usage statistics and exports
- Admin viewing restrictions

**Test Methods** (Planned):
```php
// Authentication & Authorization
public function billing_index_requires_authentication()
public function billing_requires_view_permission()
public function admin_viewing_prevents_payment_actions()

// Payment Processing  
public function process_topup_validates_input()
public function process_topup_creates_stripe_session()
public function topup_success_updates_balance()
public function topup_cancel_redirects_with_message()

// Invoice Management
public function download_invoice_requires_permission()
public function download_invoice_returns_pdf()
public function download_invoice_handles_missing_invoice()

// Usage & Statistics
public function usage_report_respects_date_filters()
public function usage_export_generates_csv()
public function usage_stats_are_tenant_isolated()

// Auto-topup
public function auto_topup_settings_update_validation()
public function auto_topup_requires_payment_method()
public function auto_topup_has_daily_limits()

// Payment Methods
public function add_payment_method_creates_setup_intent()
public function store_payment_method_validates_stripe_pm()
public function delete_payment_method_removes_from_stripe()
```

### 3. AppointmentController ⏳ PENDING
**Target Coverage**: 80%
**Key Test Areas**:
- Appointment listing with filters
- Appointment details view
- API endpoints for React components
- Permission-based appointment access
- Search and pagination
- Statistics generation

### 4. CallController ⏳ PENDING  
**Target Coverage**: 80%
**Key Test Areas**:
- Call listing and filtering
- Call status updates
- Call assignment workflow
- Note management
- Callback scheduling
- Export functionality
- Bulk operations

### 5. CustomerController ⏳ PENDING
**Note**: Need to identify the actual customer controller(s) in the Portal namespace.
Potential candidates:
- `Portal/Api/CustomersApiController.php`
- Related customer management endpoints

## Test Data Builders & Factories

### Enhanced Factory Usage
```php
// Company with realistic settings
$company = Company::factory()->withBilling()->create();

// User with specific permissions
$user = User::factory()->withPermissions(['billing.view', 'calls.manage'])->create();

// Call with complete data
$call = Call::factory()->completed()->withCustomer()->create();

// Appointment with dependencies
$appointment = Appointment::factory()->confirmed()->withStaff()->create();
```

### Test Data Builders
```php
class DashboardTestDataBuilder
{
    public static function withCalls(int $count = 5): self
    {
        // Create calls with varied statuses
    }
    
    public static function withAppointments(int $count = 10): self
    {
        // Create appointments for different dates
    }
    
    public static function withBillingData(): self
    {
        // Create invoices, transactions, etc.
    }
}
```

## Common Test Patterns

### 1. Authentication Test Pattern
```php
/** @test */
public function {action}_requires_authentication()
{
    $response = $this->get('/business/{endpoint}');
    $response->assertRedirect('/business/login');
}
```

### 2. Permission Test Pattern
```php
/** @test */
public function {action}_requires_{permission}_permission()
{
    $user = User::factory()->withoutPermission('{permission}')->create();
    
    $response = $this->actingAs($user, 'portal')
        ->get('/business/{endpoint}');
        
    $response->assertStatus(403);
}
```

### 3. API Response Test Pattern
```php
/** @test */
public function api_{endpoint}_returns_structured_data()
{
    $response = $this->actingAs($this->user, 'portal')
        ->getJson('/business/api/{endpoint}');

    $response->assertStatus(200);
    $response->assertJsonStructure([...]);
}
```

### 4. Performance Benchmark Pattern
```php
/** @test */
public function {action}_performance_is_acceptable()
{
    $startTime = microtime(true);
    
    $response = $this->actingAs($this->user, 'portal')
        ->get('/business/{endpoint}');
    
    $endTime = microtime(true);
    $responseTime = ($endTime - $startTime) * 1000;

    $response->assertStatus(200);
    $this->assertLessThan(1000, $responseTime);
}
```

### 5. Tenant Isolation Test Pattern
```php
/** @test */
public function {action}_data_is_tenant_isolated()
{
    $otherCompany = Company::factory()->create();
    $otherData = Model::factory()->create(['company_id' => $otherCompany->id]);

    $response = $this->actingAs($this->user, 'portal')
        ->getJson('/business/api/{endpoint}');

    $response->assertStatus(200);
    // Assert user only sees their company's data
}
```

## Mock Strategies

### 1. MCP Service Mocking
```php
protected function mockMCPSuccess(string $task, array $data): void
{
    $this->mock('alias:' . UsesMCPServers::class, function ($mock) use ($task, $data) {
        $mock->shouldReceive('executeMCPTask')
            ->with($task, Mockery::any())
            ->andReturn(['success' => true, 'result' => ['data' => $data]]);
    });
}

protected function mockMCPFailure(string $task, string $error = 'Service unavailable'): void
{
    $this->mock('alias:' . UsesMCPServers::class, function ($mock) use ($task, $error) {
        $mock->shouldReceive('executeMCPTask')
            ->with($task, Mockery::any())
            ->andReturn(['success' => false, 'error' => $error]);
    });
}
```

### 2. External Service Mocking
```php
// Already available via TestsWithMocks trait
$this->mockStripe(); // Mocks Stripe payment processing
$this->mockCalcom(); // Mocks Cal.com calendar integration
$this->mockRetell(); // Mocks Retell.ai call service
$this->mockEmail();  // Mocks email sending
```

## Performance Benchmarks

### Response Time Targets
- **Dashboard loading**: < 1000ms
- **API endpoints**: < 500ms
- **List pages**: < 800ms
- **Detail views**: < 400ms
- **Form submissions**: < 2000ms

### Memory Usage Targets
- **Per test**: < 50MB
- **Test suite**: < 500MB total
- **Database operations**: < 10MB per test

## CI/CD Integration

### GitHub Actions Workflow
```yaml
name: Portal Controller Tests

on: 
  push:
    paths:
      - 'app/Http/Controllers/Portal/**'
      - 'tests/Feature/Portal/**'

jobs:
  portal-tests:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: pdo, pdo_sqlite, mbstring
          
      - name: Install Dependencies
        run: composer install --no-dev --optimize-autoloader
        
      - name: Setup Test Database
        run: |
          php artisan config:cache
          php artisan migrate --force --env=testing
          
      - name: Run Portal Controller Tests
        run: |
          php artisan test --testsuite=Feature \
            --group=portal \
            --coverage-clover=coverage.xml
            
      - name: Upload Coverage
        uses: codecov/codecov-action@v3
        with:
          file: ./coverage.xml
          flags: portal-controllers
```

### Test Grouping
```php
/**
 * @group portal
 * @group dashboard
 * @group billing
 * @group appointments
 * @group calls
 */
class DashboardControllerTest extends TestCase
```

## Quality Metrics & Targets

### Coverage Targets
- **Overall Portal Controllers**: 60%
- **Critical Controllers** (Dashboard, Billing, Auth): 85%
- **High-value Methods**: 90%
- **Edge Cases**: 40%

### Test Quality Metrics
- **Test Speed**: Average < 100ms per test
- **Test Reliability**: < 1% flaky test rate
- **Test Maintainability**: Clear naming, DRY principles
- **Documentation**: All test methods have descriptive names

## Implementation Timeline

### Week 1: Foundation (COMPLETED)
- ✅ DashboardController comprehensive test
- ✅ Test infrastructure and patterns
- ✅ MCP mocking strategy

### Week 2: Core Controllers
- 🔄 BillingController tests
- ⏳ AppointmentController tests
- ⏳ CallController tests

### Week 3: Remaining Controllers
- ⏳ Customer-related controller tests
- ⏳ Additional API controller tests
- ⏳ Edge case coverage

### Week 4: Integration & Optimization
- ⏳ CI/CD pipeline setup
- ⏳ Performance optimization
- ⏳ Documentation and training

## Success Criteria

1. **Coverage**: Achieve 60% overall Portal controller test coverage
2. **Quality**: All tests pass consistently with < 1% flaky rate
3. **Performance**: Test suite runs in < 5 minutes
4. **Maintainability**: Tests follow established patterns and are easy to understand
5. **Integration**: CI/CD pipeline catches regressions automatically

## Next Steps

1. **Complete BillingController tests** (priority 1)
2. **Implement AppointmentController tests** (priority 2)
3. **Create CallController tests** (priority 3)
4. **Set up CI/CD integration** (priority 4)
5. **Document test patterns for team** (priority 5)

This strategy focuses on high-value testing that covers critical business functionality while maintaining efficiency and avoiding over-testing of low-risk areas.