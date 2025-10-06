# AskProAI Appointment Management System - Testing Guide

**Last Updated:** 2025-10-02
**Laravel Version:** 11
**PHPUnit Version:** 10.x
**Document Status:** Technical Reference

---

## Table of Contents

1. [Testing Overview](#testing-overview)
2. [Test Environment Setup](#test-environment-setup)
3. [Test Structure](#test-structure)
4. [Running Tests](#running-tests)
5. [Writing New Tests](#writing-new-tests)
6. [Database Testing](#database-testing)
7. [Mocking External Services](#mocking-external-services)
8. [E2E Integration Tests](#e2e-integration-tests)
9. [CI/CD Integration](#cicd-integration)
10. [Debugging Tests](#debugging-tests)
11. [Best Practices](#best-practices)

---

## Testing Overview

### Test Philosophy

The AskProAI test suite follows these principles:

1. **Fast Execution:** Tests run in <30 seconds for rapid feedback
2. **Isolation:** Each test is independent and doesn't affect others
3. **Real Database:** Uses MySQL test database for accurate behavior
4. **Comprehensive Coverage:** Unit, integration, and E2E test layers
5. **Production Parity:** Test environment mirrors production configuration

### Test Pyramid

```
         ┌─────────────┐
         │   E2E (6)   │  Complete user journeys
         │ Integration │  Retell → Handler → Services → DB
         └─────────────┘
              ▲
         ┌────────────────┐
         │  Integration   │  Service layer tests
         │  Tests (~30)   │  Multiple components working together
         └────────────────┘
              ▲
         ┌──────────────────┐
         │   Unit Tests     │  Individual class/method tests
         │    (~100+)       │  Fast, isolated, focused
         └──────────────────┘
```

### Test Coverage Goals

| Component | Current Coverage | Target |
|-----------|-----------------|--------|
| PolicyEngine | ~85% | 90% |
| SmartAppointmentFinder | ~80% | 85% |
| CallbackManagementService | ~75% | 85% |
| RetellFunctionCallHandler | ~70% | 80% |
| Models | ~60% | 70% |
| **Overall** | **~75%** | **80%** |

---

## Test Environment Setup

### Prerequisites

```bash
# PHP 8.2+ with required extensions
php -v
php -m | grep -E 'pdo_mysql|mbstring|xml|ctype|json|bcmath'

# Composer (for dependencies)
composer --version

# MySQL 8.0+ (test database)
mysql --version

# Redis (optional, for cache/queue tests)
redis-cli --version
```

### Environment Configuration

**File:** `/var/www/api-gateway/.env.testing`

```env
APP_ENV=testing
APP_DEBUG=true
APP_KEY=base64:YOUR_TEST_KEY_HERE

# Test Database (separate from production/dev)
DB_CONNECTION=mysql_testing
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=askproai_test
DB_USERNAME=askproai_test
DB_PASSWORD=test_password

# Cache (array driver for speed)
CACHE_DRIVER=array
SESSION_DRIVER=array
QUEUE_CONNECTION=sync

# External Services (mocked in tests)
RETELL_WEBHOOK_SECRET=test_webhook_secret
CALCOM_API_KEY=test_api_key
CALCOM_API_URL=https://api.cal.com/v2
```

### Database Setup

```bash
# Create test database
mysql -u root -p <<EOF
CREATE DATABASE IF NOT EXISTS askproai_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'askproai_test'@'localhost' IDENTIFIED BY 'test_password';
GRANT ALL PRIVILEGES ON askproai_test.* TO 'askproai_test'@'localhost';
FLUSH PRIVILEGES;
EOF

# Run test migrations
php artisan migrate --env=testing --database=mysql_testing

# Verify database
php artisan db:show --env=testing
```

### Config Override

**File:** `/var/www/api-gateway/config/database.php`

```php
'connections' => [
    // Production/Development
    'mysql' => [
        'driver' => 'mysql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'database' => env('DB_DATABASE', 'askproai'),
        // ...
    ],

    // Testing
    'mysql_testing' => [
        'driver' => 'mysql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'askproai_test'),
        'username' => env('DB_USERNAME', 'askproai_test'),
        'password' => env('DB_PASSWORD', 'test_password'),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
        'engine' => null,
    ],
],
```

---

## Test Structure

### Directory Layout

```
/var/www/api-gateway/tests/
├── TestCase.php                      # Base test case with helpers
├── Feature/                          # Integration and E2E tests
│   ├── EndToEndFlowTest.php         # Complete user journey tests
│   ├── AppointmentPolicyTest.php    # Policy engine integration tests
│   ├── CallbackManagementTest.php   # Callback workflow tests
│   └── RetellWebhookTest.php        # Webhook integration tests
│
├── Unit/                             # Unit tests (isolated components)
│   ├── Services/
│   │   ├── PolicyConfigurationServiceTest.php
│   │   ├── SmartAppointmentFinderTest.php
│   │   └── CalcomApiRateLimiterTest.php
│   ├── Models/
│   │   ├── AppointmentTest.php
│   │   ├── CallbackRequestTest.php
│   │   └── PolicyConfigurationTest.php
│   └── ValueObjects/
│       └── PolicyResultTest.php
│
└── database/
    └── factories/                    # Test data factories
        ├── CompanyFactory.php
        ├── BranchFactory.php
        ├── ServiceFactory.php
        ├── StaffFactory.php
        ├── CustomerFactory.php
        ├── AppointmentFactory.php
        └── CallbackRequestFactory.php
```

### Base TestCase

**File:** `/var/www/api-gateway/tests/TestCase.php`

```php
<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Set test environment
        config(['app.env' => 'testing']);

        // Use test database connection
        config(['database.default' => 'mysql_testing']);

        // Disable external service observers
        \App\Models\Service::unsetEventDispatcher();
    }

    /**
     * Helper: Create authenticated API request
     */
    protected function authenticatedJson(string $method, string $uri, array $data = [])
    {
        return $this->json($method, $uri, $data, [
            'Authorization' => 'Bearer test_token',
            'Accept' => 'application/json'
        ]);
    }

    /**
     * Helper: Create Retell webhook request with signature
     */
    protected function retellWebhook(array $payload)
    {
        $timestamp = time();
        $body = json_encode($payload);
        $signature = hash_hmac('sha256', $timestamp . $body, config('services.retell.webhook_secret'));

        return $this->postJson('/api/webhooks/retell/function-call', $payload, [
            'X-Retell-Signature' => "sha256={$signature}",
            'X-Retell-Timestamp' => $timestamp
        ]);
    }

    /**
     * Helper: Assert database has modification record
     */
    protected function assertModificationTracked(int $appointmentId, string $type, bool $withinPolicy)
    {
        $this->assertDatabaseHas('appointment_modifications', [
            'appointment_id' => $appointmentId,
            'modification_type' => $type,
            'within_policy' => $withinPolicy
        ]);
    }
}
```

---

## Running Tests

### Basic Test Execution

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test file
php artisan test tests/Feature/EndToEndFlowTest.php

# Run specific test method
php artisan test --filter test_cancellation_within_policy_window_succeeds

# Run tests in specific directory
php artisan test tests/Unit/Services/

# Parallel execution (faster)
php artisan test --parallel
```

### PHPUnit Direct Usage

```bash
# Run all tests
./vendor/bin/phpunit

# Run with detailed output
./vendor/bin/phpunit --testdox

# Run with coverage report (HTML)
./vendor/bin/phpunit --coverage-html coverage/

# Run with coverage report (text)
./vendor/bin/phpunit --coverage-text

# Run specific test suite
./vendor/bin/phpunit --testsuite Feature
./vendor/bin/phpunit --testsuite Unit

# Run with filter
./vendor/bin/phpunit --filter PolicyEngine
```

### Test Output

**Success:**

```
   PASS  Tests\Feature\EndToEndFlowTest
  ✓ cancellation within policy window succeeds                0.52s
  ✓ cancellation outside policy charges fee                   0.31s
  ✓ reschedule finds next available slot                      0.28s
  ✓ callback request auto assigned and escalated              0.45s
  ✓ policy configuration inheritance works                    0.39s
  ✓ recurring appointment partial cancellation with fees      0.42s

  Tests:    6 passed (36 assertions)
  Duration: 2.37s
```

**Failure:**

```
   FAIL  Tests\Feature\EndToEndFlowTest
  ✓ cancellation within policy window succeeds                0.52s
  ⨯ cancellation outside policy charges fee                   0.31s

  ──────────────────────────────────────────────────────────
  FAILED  Tests\Feature\EndToEndFlowTest > cancellation outside policy charges fee

  Expected fee: 10.0
  Actual fee: 0.0

  at tests/Feature/EndToEndFlowTest.php:263
    259│         $modification = AppointmentModification::where('appointment_id', $appointment->id)->first();
    260│         $this->assertEquals(10.0, $modification->fee_charged);
    261│         $this->assertFalse($modification->within_policy);
    262│     }
  ➜ 263│
```

---

## Writing New Tests

### Unit Test Example

**File:** `tests/Unit/Services/PolicyConfigurationServiceTest.php`

```php
<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Company;
use App\Models\Branch;
use App\Models\PolicyConfiguration;
use App\Services\Policies\PolicyConfigurationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;

class PolicyConfigurationServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected PolicyConfigurationService $service;
    protected Company $company;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PolicyConfigurationService::class);
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
    }

    /** @test */
    public function it_resolves_policy_from_entity()
    {
        // Arrange
        PolicyConfiguration::create([
            'configurable_type' => Company::class,
            'configurable_id' => $this->company->id,
            'policy_type' => 'cancellation',
            'config' => ['hours_before' => 24]
        ]);

        // Act
        $policy = $this->service->resolvePolicy($this->company, 'cancellation');

        // Assert
        $this->assertNotNull($policy);
        $this->assertEquals(24, $policy['hours_before']);
    }

    /** @test */
    public function it_traverses_hierarchy_to_parent()
    {
        // Arrange: Company policy, no branch policy
        PolicyConfiguration::create([
            'configurable_type' => Company::class,
            'configurable_id' => $this->company->id,
            'policy_type' => 'cancellation',
            'config' => ['hours_before' => 48]
        ]);

        // Act: Resolve from branch (should find company policy)
        $policy = $this->service->resolvePolicy($this->branch, 'cancellation');

        // Assert
        $this->assertNotNull($policy);
        $this->assertEquals(48, $policy['hours_before']);
    }

    /** @test */
    public function it_caches_resolved_policies()
    {
        // Arrange
        PolicyConfiguration::create([
            'configurable_type' => Company::class,
            'configurable_id' => $this->company->id,
            'policy_type' => 'cancellation',
            'config' => ['hours_before' => 24]
        ]);

        // Act: First resolution (cache miss)
        $policy1 = $this->service->resolvePolicy($this->company, 'cancellation');

        // Act: Second resolution (cache hit)
        $policy2 = $this->service->resolvePolicy($this->company, 'cancellation');

        // Assert: Same result
        $this->assertEquals($policy1, $policy2);

        // Assert: Cache key exists
        $cacheKey = "policy_config_Company_{$this->company->id}_cancellation";
        $this->assertTrue(Cache::has($cacheKey));
    }

    /** @test */
    public function it_clears_cache_on_policy_update()
    {
        // Arrange
        $policy = PolicyConfiguration::create([
            'configurable_type' => Company::class,
            'configurable_id' => $this->company->id,
            'policy_type' => 'cancellation',
            'config' => ['hours_before' => 24]
        ]);

        // Populate cache
        $this->service->resolvePolicy($this->company, 'cancellation');

        $cacheKey = "policy_config_Company_{$this->company->id}_cancellation";
        $this->assertTrue(Cache::has($cacheKey));

        // Act: Update policy (should clear cache)
        $this->service->setPolicy($this->company, 'cancellation', ['hours_before' => 48]);

        // Assert: Cache cleared
        $this->assertFalse(Cache::has($cacheKey));
    }

    /** @test */
    public function it_returns_null_when_no_policy_found()
    {
        // Act
        $policy = $this->service->resolvePolicy($this->company, 'cancellation');

        // Assert
        $this->assertNull($policy);
    }
}
```

### Integration Test Example

**File:** `tests/Feature/AppointmentPolicyTest.php`

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\PolicyConfiguration;
use App\Services\Policies\AppointmentPolicyEngine;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AppointmentPolicyTest extends TestCase
{
    use DatabaseTransactions;

    protected AppointmentPolicyEngine $policyEngine;
    protected Company $company;
    protected Branch $branch;
    protected Service $service;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policyEngine = app(AppointmentPolicyEngine::class);
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->service = Service::factory()->create([
            'company_id' => $this->company->id,
            'calcom_event_type_id' => 12345
        ]);
        $this->customer = Customer::factory()->create(['company_id' => $this->company->id]);
    }

    /** @test */
    public function cancellation_allowed_within_policy_window()
    {
        // Arrange: Policy requires 24h notice
        PolicyConfiguration::create([
            'configurable_type' => Company::class,
            'configurable_id' => $this->company->id,
            'policy_type' => 'cancellation',
            'config' => ['hours_before' => 24, 'fee_tiers' => [['min_hours' => 24, 'fee' => 0]]]
        ]);

        // Appointment 72h in future
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'starts_at' => Carbon::now()->addHours(72),
            'status' => 'confirmed'
        ]);

        // Act
        $result = $this->policyEngine->canCancel($appointment);

        // Assert
        $this->assertTrue($result->allowed);
        $this->assertEquals(0.0, $result->fee);
        $this->assertArrayHasKey('hours_notice', $result->details);
        $this->assertGreaterThan(24, $result->details['hours_notice']);
    }

    /** @test */
    public function cancellation_denied_outside_policy_window()
    {
        // Arrange: Policy requires 24h notice
        PolicyConfiguration::create([
            'configurable_type' => Company::class,
            'configurable_id' => $this->company->id,
            'policy_type' => 'cancellation',
            'config' => [
                'hours_before' => 24,
                'fee_tiers' => [
                    ['min_hours' => 24, 'fee' => 0],
                    ['min_hours' => 0, 'fee' => 15]
                ]
            ]
        ]);

        // Appointment 12h in future (outside policy)
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'starts_at' => Carbon::now()->addHours(12),
            'status' => 'confirmed'
        ]);

        // Act
        $result = $this->policyEngine->canCancel($appointment);

        // Assert
        $this->assertFalse($result->allowed);
        $this->assertStringContainsString('24 hours notice', $result->reason);
        $this->assertEquals(15.0, $result->details['fee_if_forced']);
    }

    /** @test */
    public function branch_policy_overrides_company_policy()
    {
        // Arrange: Strict company policy
        PolicyConfiguration::create([
            'configurable_type' => Company::class,
            'configurable_id' => $this->company->id,
            'policy_type' => 'cancellation',
            'config' => ['hours_before' => 48]
        ]);

        // Lenient branch policy
        PolicyConfiguration::create([
            'configurable_type' => Branch::class,
            'configurable_id' => $this->branch->id,
            'policy_type' => 'cancellation',
            'config' => ['hours_before' => 12]
        ]);

        // Appointment 24h in future
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'starts_at' => Carbon::now()->addHours(24),
            'status' => 'confirmed'
        ]);

        // Act
        $result = $this->policyEngine->canCancel($appointment);

        // Assert: Branch policy (12h) used, not company (48h)
        $this->assertTrue($result->allowed);
        $this->assertEquals(12, $result->details['required_hours']);
    }
}
```

---

## Database Testing

### Database Transactions

**Best Practice:** Use `DatabaseTransactions` trait for automatic rollback

```php
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MyTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_appointment()
    {
        $appointment = Appointment::create([...]);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id
        ]);

        // Automatically rolled back after test
    }
}
```

### Factory Usage

**Define Factory:** `database/factories/AppointmentFactory.php`

```php
<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'customer_id' => Customer::factory(),
            'service_id' => Service::factory(),
            'branch_id' => Branch::factory(),
            'starts_at' => Carbon::now()->addDays(3),
            'ends_at' => Carbon::now()->addDays(3)->addHour(),
            'status' => 'confirmed',
            'is_recurring' => false,
            'notes' => $this->faker->sentence(),
        ];
    }

    // State modifiers
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => Carbon::now(),
        ]);
    }

    public function inFuture(int $days): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => Carbon::now()->addDays($days),
            'ends_at' => Carbon::now()->addDays($days)->addHour(),
        ]);
    }

    public function inPast(int $days): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => Carbon::now()->subDays($days),
            'ends_at' => Carbon::now()->subDays($days)->addHour(),
            'status' => 'completed',
        ]);
    }
}
```

**Use Factory in Tests:**

```php
// Simple creation
$appointment = Appointment::factory()->create();

// With specific attributes
$appointment = Appointment::factory()->create([
    'customer_id' => $customer->id,
    'starts_at' => Carbon::parse('2025-10-05 14:00')
]);

// With state modifier
$appointment = Appointment::factory()->cancelled()->create();

// Multiple instances
$appointments = Appointment::factory()->count(5)->create();

// Relationships
$appointment = Appointment::factory()
    ->for($customer)
    ->for($service)
    ->create();
```

### Database Assertions

```php
// Record exists
$this->assertDatabaseHas('appointments', [
    'customer_id' => $customer->id,
    'status' => 'cancelled'
]);

// Record doesn't exist
$this->assertDatabaseMissing('appointments', [
    'customer_id' => $customer->id,
    'status' => 'pending'
]);

// Count
$this->assertDatabaseCount('appointments', 5);

// Soft deleted
$this->assertSoftDeleted('appointments', [
    'id' => $appointment->id
]);

// Not soft deleted
$this->assertNotSoftDeleted('appointments', [
    'id' => $appointment->id
]);
```

---

## Mocking External Services

### Cal.com API Mocking

```php
use Illuminate\Support\Facades\Http;

/** @test */
public function it_finds_available_slots_from_calcom()
{
    // Arrange: Mock Cal.com API response
    Http::fake([
        '*/slots/available*' => Http::response([
            'data' => [
                'slots' => [
                    '2025-10-05T09:00:00Z',
                    '2025-10-05T10:00:00Z',
                    '2025-10-05T14:00:00Z'
                ]
            ]
        ], 200, ['X-RateLimit-Remaining' => '95'])
    ]);

    // Act
    $finder = new SmartAppointmentFinder($this->company);
    $slots = $finder->findInTimeWindow(
        $this->service,
        Carbon::parse('2025-10-05'),
        Carbon::parse('2025-10-06')
    );

    // Assert
    $this->assertCount(3, $slots);
    $this->assertEquals('2025-10-05 09:00:00', $slots->first()->format('Y-m-d H:i:s'));

    // Assert HTTP request was made
    Http::assertSent(function ($request) {
        return $request->hasHeader('Authorization') &&
               str_contains($request->url(), 'slots/available');
    });
}
```

### Event Mocking

```php
use Illuminate\Support\Facades\Event;

/** @test */
public function it_fires_callback_requested_event()
{
    // Arrange
    Event::fake([CallbackRequested::class]);

    // Act
    $callback = $this->callbackService->createRequest([...]);

    // Assert
    Event::assertDispatched(CallbackRequested::class, function ($event) use ($callback) {
        return $event->callback->id === $callback->id;
    });
}
```

### Cache Mocking

```php
use Illuminate\Support\Facades\Cache;

/** @test */
public function it_caches_availability_queries()
{
    // Arrange
    Cache::flush();

    // Act: First call (cache miss)
    $slots1 = $this->finder->findNextAvailable($this->service);

    // Act: Second call (cache hit)
    $slots2 = $this->finder->findNextAvailable($this->service);

    // Assert: Same result
    $this->assertEquals($slots1, $slots2);

    // Assert: Cache was used
    $cacheKey = "appointment_finder:next_available:service_{$this->service->id}:*";
    // Cache::assertCached($cacheKey);  // Not available in Laravel 11, use Cache::has()
}
```

---

## E2E Integration Tests

### Complete User Journey Test

**File:** `tests/Feature/EndToEndFlowTest.php` (excerpt)

```php
/**
 * USER JOURNEY 1: Cancellation Within Policy Window
 */
public function test_cancellation_within_policy_window_succeeds()
{
    // ARRANGE: Create policy
    PolicyConfiguration::create([
        'configurable_type' => Company::class,
        'configurable_id' => $this->company->id,
        'policy_type' => 'cancellation',
        'config' => [
            'hours_before' => 24,
            'max_cancellations_per_month' => 3,
            'fee_tiers' => [
                ['min_hours' => 24, 'fee' => 0],
                ['min_hours' => 12, 'fee' => 10],
                ['min_hours' => 0, 'fee' => 25]
            ]
        ]
    ]);

    // Create appointment 72h in future
    $appointmentTime = Carbon::now()->addHours(72);
    $appointment = Appointment::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'service_id' => $this->service->id,
        'starts_at' => $appointmentTime,
        'status' => 'confirmed'
    ]);

    // Create call context
    $call = Call::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'phone_number_id' => $this->phoneNumber->id,
        'retell_call_id' => 'test_call_001',
        'status' => 'active'
    ]);

    // ACT: Call cancellation handler via Retell webhook
    $response = $this->postJson('/api/webhooks/retell/function-call', [
        'function_name' => 'cancel_appointment',
        'call_id' => 'test_call_001',
        'parameters' => [
            'appointment_date' => $appointmentTime->format('Y-m-d'),
            'reason' => 'Zeitlicher Konflikt'
        ]
    ]);

    // ASSERT: Success response
    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'status' => 'cancelled'
    ]);

    // ASSERT: Appointment updated
    $appointment->refresh();
    $this->assertEquals('cancelled', $appointment->status);
    $this->assertNotNull($appointment->cancelled_at);

    // ASSERT: Modification tracked
    $modification = AppointmentModification::where('appointment_id', $appointment->id)->first();
    $this->assertNotNull($modification);
    $this->assertEquals('cancel', $modification->modification_type);
    $this->assertTrue($modification->within_policy);
    $this->assertEquals(0.0, $modification->fee_charged);
    $this->assertEquals('Zeitlicher Konflikt', $modification->reason);
}
```

### Running E2E Tests

```bash
# Run only E2E tests
php artisan test tests/Feature/EndToEndFlowTest.php

# Run with verbose output
php artisan test tests/Feature/EndToEndFlowTest.php --testdox

# Run specific journey
php artisan test --filter test_cancellation_within_policy_window_succeeds

# Debug single test
php artisan test --filter test_callback_request_auto_assigned_and_escalated --stop-on-failure
```

---

## CI/CD Integration

### GitHub Actions Example

**File:** `.github/workflows/tests.yml`

```yaml
name: Tests

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: askproai_test
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3

    steps:
      - name: Checkout code
        uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2
          extensions: mbstring, xml, ctype, json, bcmath, pdo_mysql
          coverage: xdebug

      - name: Install Composer dependencies
        run: composer install --prefer-dist --no-interaction

      - name: Copy .env.testing
        run: cp .env.testing .env

      - name: Generate application key
        run: php artisan key:generate --env=testing

      - name: Run migrations
        run: php artisan migrate --env=testing --database=mysql_testing

      - name: Run tests
        run: php artisan test --coverage --min=75

      - name: Upload coverage
        uses: codecov/codecov-action@v3
        with:
          files: ./coverage.xml
```

### GitLab CI Example

**File:** `.gitlab-ci.yml`

```yaml
stages:
  - test

test:
  stage: test
  image: php:8.2-cli
  services:
    - mysql:8.0
  variables:
    MYSQL_DATABASE: askproai_test
    MYSQL_ROOT_PASSWORD: root
    DB_HOST: mysql
  before_script:
    - apt-get update && apt-get install -y git unzip
    - curl -sS https://getcomposer.org/installer | php
    - php composer.phar install
    - cp .env.testing .env
    - php artisan key:generate --env=testing
    - php artisan migrate --env=testing --database=mysql_testing
  script:
    - php artisan test --coverage --min=75
  artifacts:
    reports:
      coverage_report:
        coverage_format: cobertura
        path: coverage.xml
```

---

## Debugging Tests

### Enable Debug Output

```bash
# Verbose output
php artisan test --testdox

# Stop on first failure
php artisan test --stop-on-failure

# Print debug info
php artisan test --debug
```

### Dump and Die in Tests

```php
/** @test */
public function debug_appointment_data()
{
    $appointment = Appointment::factory()->create();

    // Dump and continue
    dump($appointment->toArray());

    // Dump and die
    dd($appointment->starts_at->diffForHumans());

    // Dump SQL queries
    \DB::enableQueryLog();
    $result = $this->policyEngine->canCancel($appointment);
    dd(\DB::getQueryLog());
}
```

### Database Query Logging

```php
protected function setUp(): void
{
    parent::setUp();

    // Log all queries
    \DB::listen(function ($query) {
        dump([
            'sql' => $query->sql,
            'bindings' => $query->bindings,
            'time' => $query->time . 'ms'
        ]);
    });
}
```

### Ray Debugging (Optional)

```php
use Spatie\Ray\Ray;

/** @test */
public function debug_with_ray()
{
    $appointment = Appointment::factory()->create();

    // Send to Ray
    ray($appointment);

    // Measure execution time
    ray()->measure(fn() => $this->policyEngine->canCancel($appointment));

    // Conditional debugging
    ray()->if($appointment->status === 'cancelled', $appointment);
}
```

---

## Best Practices

### Test Naming Conventions

```php
// ✅ Good: Descriptive, explains what's being tested
public function test_cancellation_allowed_within_policy_window()
public function test_callback_auto_assigns_to_expert_staff()
public function test_policy_hierarchy_resolves_correctly()

// ❌ Bad: Vague, unclear intent
public function testCancellation()
public function testCallbacks()
public function testPolicy()
```

### AAA Pattern (Arrange-Act-Assert)

```php
/** @test */
public function it_escalates_overdue_callbacks()
{
    // ARRANGE: Set up test data
    $callback = CallbackRequest::factory()->create([
        'priority' => CallbackRequest::PRIORITY_HIGH,
        'expires_at' => Carbon::now()->subHours(2)  // Overdue
    ]);
    $staff2 = Staff::factory()->create(['branch_id' => $callback->branch_id]);

    // ACT: Perform the action being tested
    $escalation = $this->callbackService->escalate($callback, 'sla_breach');

    // ASSERT: Verify expected outcomes
    $this->assertNotNull($escalation);
    $this->assertEquals('sla_breach', $escalation->escalation_reason);
    $this->assertNotEquals($callback->assigned_to, $escalation->escalated_to);
}
```

### One Assertion Per Test (Guideline)

```php
// ✅ Good: Focused test
/** @test */
public function cancellation_updates_appointment_status()
{
    $appointment = Appointment::factory()->create(['status' => 'confirmed']);

    $appointment->update(['status' => 'cancelled', 'cancelled_at' => now()]);

    $this->assertEquals('cancelled', $appointment->status);
}

/** @test */
public function cancellation_tracks_modification()
{
    $appointment = Appointment::factory()->create();

    $appointment->cancel();

    $this->assertDatabaseHas('appointment_modifications', [
        'appointment_id' => $appointment->id,
        'modification_type' => 'cancel'
    ]);
}

// ⚠️ Acceptable: Related assertions in E2E tests
/** @test */
public function cancellation_flow_completes_successfully()
{
    $appointment = Appointment::factory()->create();

    $result = $this->policyEngine->canCancel($appointment);
    $appointment->cancel();

    // Multiple related assertions OK for E2E
    $this->assertTrue($result->allowed);
    $this->assertEquals('cancelled', $appointment->status);
    $this->assertDatabaseHas('appointment_modifications', [...]);
}
```

### Test Data Isolation

```php
// ✅ Good: Each test creates its own data
/** @test */
public function test_example_a()
{
    $company = Company::factory()->create();
    // Test logic...
}

/** @test */
public function test_example_b()
{
    $company = Company::factory()->create();  // Fresh data
    // Test logic...
}

// ❌ Bad: Shared test data (brittle, order-dependent)
protected Company $company;

protected function setUp(): void
{
    parent::setUp();
    $this->company = Company::factory()->create();  // Shared across tests
}
```

### Mock External Services

```php
// ✅ Good: Mock external API
Http::fake([
    '*/slots/available*' => Http::response(['data' => ['slots' => [...]]], 200)
]);

$slots = $this->finder->findNextAvailable($service);

// ❌ Bad: Real API call in test (slow, unreliable)
$slots = $this->finder->findNextAvailable($service);  // Makes real HTTP request
```

### Use Transactions for Speed

```php
// ✅ Good: DatabaseTransactions (automatic rollback)
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MyTest extends TestCase
{
    use DatabaseTransactions;
    // Tests automatically rolled back
}

// ⚠️ Alternative: RefreshDatabase (slower, rebuilds DB each test)
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyTest extends TestCase
{
    use RefreshDatabase;
    // Use only when necessary
}
```

### Test Coverage Goals

```bash
# Check coverage
php artisan test --coverage

# Enforce minimum coverage
php artisan test --coverage --min=75

# Generate HTML coverage report
./vendor/bin/phpunit --coverage-html coverage/
open coverage/index.html
```

---

## Common Pitfalls

### 1. Not Clearing Cache

```php
// ❌ Problem: Cache pollution between tests
/** @test */
public function test_a() {
    Cache::put('key', 'value1', 60);
}

/** @test */
public function test_b() {
    // Fails if test_a ran first
    $this->assertNull(Cache::get('key'));
}

// ✅ Solution: Clear cache in setUp or use array driver
protected function setUp(): void
{
    parent::setUp();
    Cache::flush();
}
```

### 2. Time-Dependent Tests

```php
// ❌ Problem: Test depends on current time
/** @test */
public function test_expiration() {
    $callback = CallbackRequest::create([
        'expires_at' => Carbon::now()->addHours(2)
    ]);

    sleep(7200);  // Wait 2 hours (terrible!)

    $this->assertTrue($callback->is_overdue);
}

// ✅ Solution: Use Carbon::setTestNow() or update expires_at
/** @test */
public function test_expiration() {
    $callback = CallbackRequest::factory()->create([
        'expires_at' => Carbon::now()->addHours(2)
    ]);

    // Simulate time passing
    $callback->update(['expires_at' => Carbon::now()->subHour()]);

    $this->assertTrue($callback->is_overdue);
}
```

### 3. Order-Dependent Tests

```php
// ❌ Problem: Tests pass only when run in specific order
/** @test */
public function test_creates_user() {
    $this->user = User::create(['name' => 'Test']);
}

/** @test */
public function test_user_exists() {
    $this->assertNotNull($this->user);  // Depends on previous test
}

// ✅ Solution: Each test is independent
/** @test */
public function test_creates_user() {
    $user = User::create(['name' => 'Test']);
    $this->assertDatabaseHas('users', ['name' => 'Test']);
}

/** @test */
public function test_finds_user() {
    $user = User::factory()->create(['name' => 'Test']);
    $found = User::where('name', 'Test')->first();
    $this->assertNotNull($found);
}
```

---

## Appendix: Quick Reference

### PHPUnit Assertions

```php
// Equality
$this->assertEquals($expected, $actual);
$this->assertNotEquals($expected, $actual);
$this->assertSame($expected, $actual);  // Strict equality (===)

// Booleans
$this->assertTrue($value);
$this->assertFalse($value);

// Null
$this->assertNull($value);
$this->assertNotNull($value);

// Arrays
$this->assertCount(5, $array);
$this->assertArrayHasKey('key', $array);
$this->assertContains('value', $array);

// Strings
$this->assertStringContainsString('substring', $string);
$this->assertStringStartsWith('prefix', $string);
$this->assertStringEndsWith('suffix', $string);

// Numeric
$this->assertGreaterThan(10, $value);
$this->assertLessThan(100, $value);

// Exceptions
$this->expectException(InvalidArgumentException::class);
$this->expectExceptionMessage('Invalid input');
```

### Laravel Test Helpers

```php
// HTTP assertions
$response->assertStatus(200);
$response->assertJson(['success' => true]);
$response->assertJsonPath('data.id', 123);

// Database assertions
$this->assertDatabaseHas('table', ['column' => 'value']);
$this->assertDatabaseMissing('table', ['column' => 'value']);
$this->assertDatabaseCount('table', 5);
$this->assertSoftDeleted('table', ['id' => 1]);

// Event assertions
Event::assertDispatched(EventClass::class);
Event::assertNotDispatched(EventClass::class);

// Queue assertions
Queue::assertPushed(JobClass::class);

// HTTP fake assertions
Http::assertSent(function ($request) {
    return $request->url() === 'https://api.example.com';
});
```

### Test Shortcuts

```bash
# Quick test commands
alias t='php artisan test'
alias tw='php artisan test --testdox'
alias tf='php artisan test --filter'
alias ts='php artisan test --stop-on-failure'
alias tc='php artisan test --coverage'

# Usage
t                              # Run all tests
tw                             # Run with verbose output
tf PolicyEngine                # Run policy engine tests
ts                             # Stop on first failure
tc --min=75                    # Coverage with 75% minimum
```

---

## Support

**Test Issues:** Check `/var/www/api-gateway/storage/logs/testing.log`
**Documentation:** See ARCHITECTURE.md and API_REFERENCE.md
**Coverage Reports:** `./vendor/bin/phpunit --coverage-html coverage/`
