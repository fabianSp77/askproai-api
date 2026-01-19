# Testing Patterns

**Analysis Date:** 2026-01-19

## Test Framework

**Runner:**
- Pest PHP ^3.0 (primary)
- PHPUnit ^11.0.1 (underlying)
- Config: `phpunit.xml`

**Assertion Library:**
- Pest expectations (`expect()->toBe()`, `expect()->toBeNull()`)
- PHPUnit assertions (`$this->assertEquals()`, `$this->assertNotNull()`)

**Run Commands:**
```bash
vendor/bin/pest              # Run all tests
vendor/bin/pest --filter=Unit    # Run unit tests only
vendor/bin/pest --filter=Feature # Run feature tests only
vendor/bin/pest --parallel   # Parallel execution
vendor/bin/pest --coverage   # With coverage report
```

**Test Suites (from `phpunit.xml`):**
```xml
<testsuites>
    <testsuite name="Unit">
        <directory>tests/Unit</directory>
    </testsuite>
    <testsuite name="Feature">
        <directory>tests/Feature</directory>
    </testsuite>
</testsuites>
```

**Excluded Groups:**
```xml
<groups>
    <exclude>
        <group>slow</group>
        <group>circuit-breaker</group>
        <group>requires-redis</group>
    </exclude>
</groups>
```

## Test File Organization

**Location:**
- Pattern: Separate test directories mirroring app structure
- Unit tests: `tests/Unit/`
- Feature tests: `tests/Feature/`
- E2E tests: `tests/E2E/`
- Performance tests: `tests/Performance/`

**Naming:**
- `{Subject}Test.php`
- Examples: `AppointmentCreationServiceTest.php`, `MultiTenantIsolationTest.php`

**Structure:**
```
tests/
├── Pest.php                     # Pest configuration & helpers
├── TestCase.php                 # Base test case with safeguards
├── Unit/
│   ├── ExampleTest.php
│   ├── Services/
│   │   ├── Retell/
│   │   │   ├── AppointmentCreationServiceTest.php
│   │   │   ├── BookingDetailsExtractorTest.php
│   │   │   └── DuplicateBookingPreventionTest.php
│   │   └── CostCalculatorTest.php
│   └── Requests/
│       └── CollectAppointmentRequestTest.php
├── Feature/
│   ├── Security/
│   │   ├── MultiTenantIsolationTest.php
│   │   ├── CrossTenantDataLeakageTest.php
│   │   └── MassAssignmentTest.php
│   ├── CalcomV2/
│   │   ├── CalcomV2IntegrationTest.php
│   │   └── CalcomV2PerformanceTest.php
│   ├── Filament/
│   │   └── Resources/
│   │       ├── AppointmentResourceTest.php
│   │       └── CustomerResourceTest.php
│   └── Integration/
│       └── AutomatedProcessTest.php
├── Performance/
│   ├── SystemPerformanceTest.php
│   └── CompanyScopePerformanceTest.php
└── Mocks/
    └── CalcomV2MockServer.php
```

## Test Structure

**PHPUnit Style (Class-based):**
```php
<?php

namespace Tests\Unit\Services\Retell;

use Tests\TestCase;
use App\Services\Retell\AppointmentCreationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AppointmentCreationServiceTest extends TestCase
{
    use RefreshDatabase;

    private AppointmentCreationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Create service dependencies
        $this->callLifecycle = $this->createMock(CallLifecycleService::class);
        $this->service = new AppointmentCreationService($this->callLifecycle);
    }

    /** @test */
    public function it_returns_existing_customer_when_already_linked_to_call()
    {
        // Arrange
        $company = Company::create(['name' => 'Test Company', 'status' => 'active']);
        $customer = Customer::create([...]);

        // Act
        $result = $this->service->ensureCustomer($call);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($customer->id, $result->id);
    }
}
```

**Pest Style (Closure-based):**
```php
<?php

use App\Services\AutomatedProcessService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->appointmentService = $this->mock(AppointmentService::class);
    $this->automatedService = new AutomatedProcessService($this->appointmentService);

    // Create test data
    $this->company = Company::factory()->create();
    $this->staff = Staff::factory(3)->create(['company_id' => $this->company->id]);
});

it('auto-assigns staff to appointments based on availability', function () {
    $appointment = Appointment::factory()->create([
        'staff_id' => null
    ]);

    $assignedStaff = $this->automatedService->autoAssignStaff($appointment);

    expect($assignedStaff)->not->toBeNull();
    expect($appointment->fresh()->staff_id)->toBe($assignedStaff->id);
});
```

**Test Helpers (from `tests/Pest.php`):**
```php
function actingAsAdmin(): User
{
    $user = User::factory()->create();
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $user->assignRole($adminRole);
    test()->actingAs($user);
    return $user;
}

function actingAsCompanyOwner(Company $company = null): User
{
    $company = $company ?? Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $ownerRole = Role::firstOrCreate(['name' => 'company_owner']);
    $user->assignRole($ownerRole);
    test()->actingAs($user);
    return $user;
}

function createCompanyWithFullSetup(): array
{
    $company = Company::factory()->create();
    $branch = Branch::factory()->create(['company_id' => $company->id]);
    $service = Service::factory()->create(['company_id' => $company->id]);
    $staff = Staff::factory()->create(['company_id' => $company->id]);
    return compact('company', 'branch', 'service', 'staff');
}
```

**Custom Expectations:**
```php
expect()->extend('toBeValidEmail', function () {
    return $this->toMatch('/^[^\s@]+@[^\s@]+\.[^\s@]+$/');
});

expect()->extend('toBePhoneNumber', function () {
    return $this->toMatch('/^\+?[1-9]\d{1,14}$/');
});

expect()->extend('toBeUuid', function () {
    return $this->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
});
```

## Mocking

**Framework:** Mockery ^1.6

**Patterns:**
```php
// PHPUnit createMock
$this->callLifecycle = $this->createMock(CallLifecycleService::class);
$this->callLifecycle->expects($this->once())
    ->method('linkCustomer')
    ->with($call, $existingCustomer)
    ->willReturn($call);

// Callback matchers
$this->calcomService->expects($this->once())
    ->method('createBooking')
    ->with($this->callback(function ($bookingData) {
        return $bookingData['responses']['email'] === 'john@example.com';
    }))
    ->willReturn(['id' => 'booking_123']);

// Multiple returns
$this->calcomService->expects($this->exactly(2))
    ->method('createBooking')
    ->willReturnOnConsecutiveCalls(
        null,  // First attempt fails
        ['id' => 'booking_456', 'status' => 'ACCEPTED']
    );
```

**Laravel Mocking:**
```php
$this->appointmentService = $this->mock(AppointmentService::class);
```

**What to Mock:**
- External API clients (Cal.com, Retell, Stripe)
- Third-party services
- Heavy database operations in unit tests
- Time-dependent operations

**What NOT to Mock:**
- Laravel framework internals
- Database in feature tests (use RefreshDatabase)
- The actual code under test

## Fixtures and Factories

**Test Data (Factories in `database/factories/`):**
```php
<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
        ];
    }

    /**
     * Create a customer for a specific company.
     */
    public function forCompany(Company $company): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $company->id,
        ]);
    }
}
```

**Factory Validation (Security):**
```php
public function configure(): static
{
    return $this->afterMaking(function (Customer $customer) {
        if (!$customer->company_id) {
            throw new \RuntimeException(
                'CRITICAL: CustomerFactory attempted to create customer with NULL company_id.'
            );
        }
    });
}
```

**Location:**
- Factories: `database/factories/`
- Test mocks: `tests/Mocks/`

## Coverage

**Requirements:** Not enforced (no minimum threshold)

**View Coverage:**
```bash
vendor/bin/pest --coverage
vendor/bin/pest --coverage-html coverage/
```

**Source Directory:**
```xml
<source>
    <include>
        <directory>app</directory>
    </include>
</source>
```

## Test Types

**Unit Tests:**
- Scope: Single class/method in isolation
- Location: `tests/Unit/`
- Traits: `RefreshDatabase` when needed
- Mocking: Heavy use of mocks for dependencies

**Feature Tests:**
- Scope: Full request/response cycle
- Location: `tests/Feature/`
- Database: `RefreshDatabase` or `DatabaseTransactions`
- Authentication: `actingAs()` helpers

**Security Tests:**
- Scope: Multi-tenant isolation, RBAC
- Location: `tests/Feature/Security/`
- Pattern: Cross-company data access prevention

**Performance Tests:**
- Scope: Query optimization, load testing
- Location: `tests/Performance/`
- Tools: K6 for load testing (`tests/Performance/k6/`)

**E2E Tests:**
- Scope: Full user workflows
- Location: `tests/E2E/`, `tests/puppeteer/`
- Tools: Puppeteer, Pest Laravel plugin

## Common Patterns

**Async Testing:**
```php
// Not directly applicable - Laravel uses sync queue in tests
// phpunit.xml sets: QUEUE_CONNECTION=sync
```

**Error Testing:**
```php
/** @test */
public function cross_tenant_findOrFail_throws_not_found()
{
    $customerB = Customer::factory()->create(['company_id' => $this->companyB->id]);
    $this->actingAs($this->adminCompanyA);

    $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    Customer::findOrFail($customerB->id);
}
```

**Multi-Tenant Isolation Testing:**
```php
/** @test */
public function customer_model_enforces_company_isolation()
{
    $customerA = Customer::factory()->create(['company_id' => $this->companyA->id]);
    $customerB = Customer::factory()->create(['company_id' => $this->companyB->id]);

    $this->actingAs($this->adminCompanyA);
    $customers = Customer::all();

    $this->assertCount(1, $customers);
    $this->assertTrue($customers->contains($customerA->id));
    $this->assertFalse($customers->contains($customerB->id));
}
```

**HTTP Testing:**
```php
public function test_health_endpoint_returns_correct_status(): void
{
    $response = $this->get('/api/health');

    $response->assertStatus(200);
    $response->assertJson(['status' => 'healthy']);
    $response->assertJsonStructure([
        'status',
        'timestamp',
        'checks' => ['database', 'cache', 'redis', 'storage', 'queue'],
    ]);
}
```

## Test Environment Configuration

**Environment Variables (`phpunit.xml`):**
```xml
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="CACHE_STORE" value="array"/>
    <env name="DB_CONNECTION" value="mysql"/>
    <env name="DB_DATABASE" value="testing"/>
    <env name="MAIL_MAILER" value="array"/>
    <env name="QUEUE_CONNECTION" value="sync"/>
    <env name="SESSION_DRIVER" value="array"/>
    <env name="TELESCOPE_ENABLED" value="false"/>
    <env name="CALCOM_API_KEY" value="test-api-key-for-testing"/>
    <env name="RETELLAI_API_KEY" value="test-retell-key-for-testing"/>
</php>
```

## Production Database Safeguards (CRITICAL)

**Base TestCase (`tests/TestCase.php`):**
```php
abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        // LAYER 1: Check for cached config BEFORE Laravel boots
        $this->assertNoCachedConfiguration();

        parent::setUp();

        // LAYER 2 & 3: Runtime checks after boot
        $this->assertNotProductionEnvironment();
        $this->assertNotProductionDatabase();
    }

    private function assertNoCachedConfiguration(): void
    {
        $cachedConfigPath = dirname(__DIR__) . '/bootstrap/cache/config.php';
        if (file_exists($cachedConfigPath)) {
            throw new RuntimeException(
                'CRITICAL: Tests cannot run with cached configuration!'
            );
        }
    }

    private function assertNotProductionDatabase(): void
    {
        $currentDatabase = config('database.connections.mysql.database');
        $productionDatabases = ['askproai_db', 'askproai_staging'];
        if (in_array($currentDatabase, $productionDatabases, true)) {
            throw new RuntimeException(
                'CRITICAL: Tests cannot run against production database!'
            );
        }
    }
}
```

## Additional Test Resources

**Manual Test Scripts:**
- `tests/booking-integration-test.sh`
- `tests/calendar-integration-test.sh`
- `tests/security-test.sh`
- `tests/go-live-smoke.sh`

**Load Testing:**
- K6 scripts: `tests/Performance/k6/`
- Shell scripts: `tests/load-test-v2.sh`

**Visual Testing:**
- Puppeteer: `tests/puppeteer/`
- Screenshots: `tests/screenshots/`

**Documentation:**
- `tests/QUICK_START_TESTING_GUIDE.md`
- `tests/COMPREHENSIVE_TEST_SUITE_SUMMARY.md`
- `tests/DATA_INTEGRITY_TEST_PLAN.md`

---

*Testing analysis: 2026-01-19*
