# Testing Guide

## Overview

This guide covers the testing strategy, tools, and best practices for AskProAI. We follow a comprehensive testing approach including unit tests, integration tests, feature tests, and end-to-end tests.

## Testing Philosophy

### Testing Pyramid
```
         /\
        /  \  E2E Tests (Few)
       /    \ 
      /      \  Integration Tests (Some)
     /        \
    /          \  Unit Tests (Many)
   /____________\
```

### Test Coverage Goals
- Overall coverage: > 80%
- Critical paths: 100%
- Business logic: > 90%
- API endpoints: 100%

## Test Environment Setup

### Configuration

```bash
# Copy test environment file
cp .env.testing.example .env.testing
```

Configure `.env.testing`:
```env
APP_ENV=testing
APP_DEBUG=true

# Use SQLite in-memory database for speed
DB_CONNECTION=sqlite
DB_DATABASE=:memory:

# Use array cache driver
CACHE_DRIVER=array
SESSION_DRIVER=array
QUEUE_CONNECTION=sync

# Mock external services
RETELL_MOCK_ENABLED=true
CALCOM_MOCK_ENABLED=true
STRIPE_MOCK_ENABLED=true
```

### PHPUnit Configuration

```xml
<!-- phpunit.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory suffix="Test.php">./tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory suffix="Test.php">./tests/Integration</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory suffix="Test.php">./tests/Feature</directory>
        </testsuite>
        <testsuite name="E2E">
            <directory suffix="Test.php">./tests/E2E</directory>
        </testsuite>
    </testsuites>
    <coverage>
        <include>
            <directory suffix=".php">./app</directory>
        </include>
        <exclude>
            <directory>./app/Console/Kernel.php</directory>
            <directory>./app/Exceptions/Handler.php</directory>
        </exclude>
    </coverage>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="BCRYPT_ROUNDS" value="4"/>
        <env name="CACHE_DRIVER" value="array"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="MAIL_MAILER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>
        <env name="TELESCOPE_ENABLED" value="false"/>
    </php>
</phpunit>
```

## Unit Tests

Unit tests focus on individual classes and methods in isolation.

### Example Unit Test

```php
<?php

namespace Tests\Unit\Services;

use App\Services\PriceCalculator;
use App\Models\Service;
use Tests\TestCase;

class PriceCalculatorTest extends TestCase
{
    private PriceCalculator $calculator;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new PriceCalculator();
    }
    
    /** @test */
    public function it_calculates_base_price_correctly()
    {
        // Arrange
        $service = new Service(['base_price' => 50.00]);
        $duration = 60; // minutes
        
        // Act
        $price = $this->calculator->calculateBasePrice($service, $duration);
        
        // Assert
        $this->assertEquals(50.00, $price);
    }
    
    /** @test */
    public function it_applies_duration_multiplier()
    {
        // Arrange
        $service = new Service(['base_price' => 50.00, 'price_per_30min' => 25.00]);
        $duration = 90; // 1.5 hours
        
        // Act
        $price = $this->calculator->calculateBasePrice($service, $duration);
        
        // Assert
        $this->assertEquals(75.00, $price); // 50 + 25 for extra 30 min
    }
    
    /** @test */
    public function it_applies_weekend_surcharge()
    {
        // Arrange
        $service = new Service(['base_price' => 100.00]);
        $dateTime = Carbon::parse('2025-06-28 14:00:00'); // Saturday
        
        // Act
        $price = $this->calculator->calculatePrice($service, $dateTime);
        
        // Assert
        $this->assertEquals(120.00, $price); // 20% weekend surcharge
    }
    
    /**
     * @test
     * @dataProvider priceCalculationProvider
     */
    public function it_calculates_various_prices_correctly($basePrice, $duration, $isWeekend, $expected)
    {
        // Arrange
        $service = new Service(['base_price' => $basePrice]);
        $dateTime = $isWeekend 
            ? Carbon::parse('next Saturday 14:00') 
            : Carbon::parse('next Monday 14:00');
        
        // Act
        $price = $this->calculator->calculatePrice($service, $dateTime, $duration);
        
        // Assert
        $this->assertEquals($expected, $price);
    }
    
    public function priceCalculationProvider(): array
    {
        return [
            'weekday standard' => [100, 60, false, 100],
            'weekday extended' => [100, 90, false, 150],
            'weekend standard' => [100, 60, true, 120],
            'weekend extended' => [100, 90, true, 180],
        ];
    }
}
```

### Testing Models

```php
<?php

namespace Tests\Unit\Models;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function it_belongs_to_a_customer()
    {
        // Arrange
        $appointment = Appointment::factory()->create();
        
        // Act & Assert
        $this->assertInstanceOf(Customer::class, $appointment->customer);
    }
    
    /** @test */
    public function it_calculates_end_time_correctly()
    {
        // Arrange
        $appointment = Appointment::factory()->create([
            'time' => '14:00:00',
            'duration' => 45,
        ]);
        
        // Act
        $endTime = $appointment->end_time;
        
        // Assert
        $this->assertEquals('14:45:00', $endTime->format('H:i:s'));
    }
    
    /** @test */
    public function it_scopes_upcoming_appointments()
    {
        // Arrange
        Appointment::factory()->create(['date' => now()->subDay()]); // Past
        $upcoming1 = Appointment::factory()->create(['date' => now()->addDay()]);
        $upcoming2 = Appointment::factory()->create(['date' => now()->addWeek()]);
        
        // Act
        $appointments = Appointment::upcoming()->get();
        
        // Assert
        $this->assertCount(2, $appointments);
        $this->assertTrue($appointments->contains($upcoming1));
        $this->assertTrue($appointments->contains($upcoming2));
    }
    
    /** @test */
    public function it_checks_if_appointment_is_editable()
    {
        // Arrange
        $past = Appointment::factory()->create([
            'date' => now()->subDay(),
            'status' => 'scheduled'
        ]);
        
        $future = Appointment::factory()->create([
            'date' => now()->addDay(),
            'status' => 'scheduled'
        ]);
        
        $completed = Appointment::factory()->create([
            'date' => now()->addDay(),
            'status' => 'completed'
        ]);
        
        // Act & Assert
        $this->assertFalse($past->isEditable());
        $this->assertTrue($future->isEditable());
        $this->assertFalse($completed->isEditable());
    }
}
```

## Integration Tests

Integration tests verify that different parts of the system work together correctly.

### Service Integration Test

```php
<?php

namespace Tests\Integration\Services;

use App\Services\AppointmentService;
use App\Services\CalendarService;
use App\Services\NotificationService;
use App\Models\Appointment;
use App\Models\Customer;
use App\Events\AppointmentCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Mockery;

class AppointmentServiceTest extends TestCase
{
    use RefreshDatabase;
    
    private AppointmentService $service;
    private $calendarMock;
    private $notificationMock;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mocks for external services
        $this->calendarMock = Mockery::mock(CalendarService::class);
        $this->notificationMock = Mockery::mock(NotificationService::class);
        
        // Bind mocks to container
        $this->app->instance(CalendarService::class, $this->calendarMock);
        $this->app->instance(NotificationService::class, $this->notificationMock);
        
        $this->service = app(AppointmentService::class);
    }
    
    /** @test */
    public function it_creates_appointment_with_calendar_sync()
    {
        // Arrange
        Event::fake();
        $customer = Customer::factory()->create();
        
        $data = [
            'customer_id' => $customer->id,
            'service_id' => 1,
            'date' => '2025-07-01',
            'time' => '14:00',
            'duration' => 60,
        ];
        
        // Set up calendar mock expectation
        $this->calendarMock
            ->shouldReceive('isAvailable')
            ->once()
            ->with('2025-07-01', '14:00', 60)
            ->andReturn(true);
            
        $this->calendarMock
            ->shouldReceive('createBooking')
            ->once()
            ->andReturn(['id' => 'cal_123']);
        
        // Act
        $appointment = $this->service->createAppointment($data);
        
        // Assert
        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertEquals('cal_123', $appointment->calendar_id);
        $this->assertDatabaseHas('appointments', [
            'customer_id' => $customer->id,
            'date' => '2025-07-01',
        ]);
        
        Event::assertDispatched(AppointmentCreated::class);
    }
    
    /** @test */
    public function it_sends_notifications_after_booking()
    {
        // Arrange
        $customer = Customer::factory()->create([
            'email' => 'test@example.com',
            'phone' => '+49123456789',
        ]);
        
        $data = [
            'customer_id' => $customer->id,
            'service_id' => 1,
            'date' => '2025-07-01',
            'time' => '14:00',
        ];
        
        // Set up expectations
        $this->calendarMock
            ->shouldReceive('isAvailable')
            ->andReturn(true);
            
        $this->calendarMock
            ->shouldReceive('createBooking')
            ->andReturn(['id' => 'cal_123']);
            
        $this->notificationMock
            ->shouldReceive('sendAppointmentConfirmation')
            ->once()
            ->with(Mockery::type(Appointment::class));
        
        // Act
        $appointment = $this->service->createAppointment($data);
        
        // Assert notification was triggered
        $this->assertNotNull($appointment);
    }
    
    /** @test */
    public function it_rollbacks_on_calendar_failure()
    {
        // Arrange
        $customer = Customer::factory()->create();
        
        $this->calendarMock
            ->shouldReceive('isAvailable')
            ->andReturn(true);
            
        $this->calendarMock
            ->shouldReceive('createBooking')
            ->andThrow(new \Exception('Calendar API error'));
        
        // Act & Assert
        $this->expectException(\Exception::class);
        
        $this->service->createAppointment([
            'customer_id' => $customer->id,
            'service_id' => 1,
            'date' => '2025-07-01',
            'time' => '14:00',
        ]);
        
        // Verify no appointment was created
        $this->assertDatabaseCount('appointments', 0);
    }
}
```

## Feature Tests

Feature tests verify complete features from the user's perspective.

### API Feature Test

```php
<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Company;
use App\Models\Appointment;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentApiTest extends TestCase
{
    use RefreshDatabase;
    
    private User $user;
    private Company $company;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
    }
    
    /** @test */
    public function it_requires_authentication_to_list_appointments()
    {
        // Act
        $response = $this->getJson('/api/appointments');
        
        // Assert
        $response->assertUnauthorized();
    }
    
    /** @test */
    public function it_lists_appointments_for_authenticated_user()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        
        $appointments = Appointment::factory()
            ->count(3)
            ->create(['company_id' => $this->company->id]);
            
        // Create appointment for different company (should not be returned)
        Appointment::factory()->create();
        
        // Act
        $response = $this->getJson('/api/appointments');
        
        // Assert
        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'customer' => ['id', 'name', 'phone'],
                        'service' => ['id', 'name', 'price'],
                        'date',
                        'time',
                        'status',
                    ]
                ],
                'links',
                'meta',
            ]);
    }
    
    /** @test */
    public function it_creates_appointment_with_valid_data()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        
        $data = [
            'customer_name' => 'John Doe',
            'customer_phone' => '+49301234567',
            'service_id' => 1,
            'branch_id' => 1,
            'date' => now()->addDays(3)->format('Y-m-d'),
            'time' => '14:00',
            'duration' => 60,
            'notes' => 'First time customer',
        ];
        
        // Mock external services
        $this->mockCalendarAvailability(true);
        
        // Act
        $response = $this->postJson('/api/appointments', $data);
        
        // Assert
        $response->assertCreated()
            ->assertJsonPath('data.customer.name', 'John Doe')
            ->assertJsonPath('data.status', 'scheduled');
            
        $this->assertDatabaseHas('appointments', [
            'company_id' => $this->company->id,
            'date' => $data['date'],
        ]);
    }
    
    /** @test */
    public function it_validates_appointment_data()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        
        $data = [
            'customer_name' => '', // Required
            'customer_phone' => 'invalid', // Invalid format
            'date' => now()->subDay()->format('Y-m-d'), // Past date
            'time' => '25:00', // Invalid time
        ];
        
        // Act
        $response = $this->postJson('/api/appointments', $data);
        
        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'customer_name',
                'customer_phone',
                'date',
                'time',
            ]);
    }
    
    /** @test */
    public function it_prevents_double_booking()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        
        // Create existing appointment
        $existing = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'date' => '2025-07-01',
            'time' => '14:00:00',
            'duration' => 60,
        ]);
        
        $data = [
            'customer_name' => 'Jane Doe',
            'customer_phone' => '+49301234567',
            'service_id' => 1,
            'branch_id' => $existing->branch_id,
            'staff_id' => $existing->staff_id,
            'date' => '2025-07-01',
            'time' => '14:30', // Overlaps with existing
        ];
        
        // Act
        $response = $this->postJson('/api/appointments', $data);
        
        // Assert
        $response->assertUnprocessable()
            ->assertJsonPath('message', 'The selected time slot is not available.');
    }
    
    private function mockCalendarAvailability(bool $available = true): void
    {
        $this->mock(CalendarService::class)
            ->shouldReceive('isAvailable')
            ->andReturn($available);
    }
}
```

## End-to-End Tests

E2E tests verify complete user workflows.

### Booking Flow E2E Test

```php
<?php

namespace Tests\E2E;

use App\Models\Company;
use App\Models\Branch;
use App\Models\Service;
use App\Models\PhoneNumber;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function complete_phone_booking_flow()
    {
        // Arrange
        Event::fake();
        Queue::fake();
        
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $service = Service::factory()->create(['company_id' => $company->id]);
        $phoneNumber = PhoneNumber::factory()->create([
            'number' => '+49301234567',
            'branch_id' => $branch->id,
            'retell_agent_id' => 'agent_123',
        ]);
        
        // Step 1: Incoming call webhook from Retell
        $response = $this->postJson('/api/retell/webhook', [
            'event_type' => 'call_started',
            'call_id' => 'call_123',
            'from_number' => '+49309876543',
            'to_number' => '+49301234567',
            'agent_id' => 'agent_123',
        ], [
            'x-retell-signature' => $this->generateRetellSignature([]),
        ]);
        
        $response->assertOk();
        Queue::assertPushed(ProcessRetellCallJob::class);
        
        // Step 2: Call ended with booking data
        $bookingData = [
            'event_type' => 'call_analyzed',
            'call_id' => 'call_123',
            'transcript' => 'Customer wants to book appointment...',
            'call_summary' => 'Appointment booking request',
            'function_calls' => [
                [
                    'name' => 'book_appointment',
                    'arguments' => [
                        'customer_name' => 'Max Mustermann',
                        'phone_number' => '+49309876543',
                        'service_type' => $service->name,
                        'preferred_date' => '2025-07-01',
                        'preferred_time' => '14:00',
                    ],
                ],
            ],
        ];
        
        $response = $this->postJson('/api/retell/webhook', $bookingData, [
            'x-retell-signature' => $this->generateRetellSignature($bookingData),
        ]);
        
        $response->assertOk();
        
        // Process queued jobs
        Queue::assertPushed(ProcessRetellCallEndedJob::class);
        
        // Step 3: Verify appointment was created
        $this->assertDatabaseHas('customers', [
            'name' => 'Max Mustermann',
            'phone' => '+49309876543',
            'company_id' => $company->id,
        ]);
        
        $this->assertDatabaseHas('appointments', [
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'date' => '2025-07-01',
            'status' => 'scheduled',
        ]);
        
        // Step 4: Verify notifications were queued
        Queue::assertPushed(SendAppointmentConfirmation::class);
        
        // Step 5: Verify events were fired
        Event::assertDispatched(CallProcessed::class);
        Event::assertDispatched(AppointmentCreated::class);
    }
    
    private function generateRetellSignature(array $payload): string
    {
        $secret = config('services.retell.webhook_secret');
        return hash_hmac('sha256', json_encode($payload), $secret);
    }
}
```

## Browser Tests (Laravel Dusk)

### Installation

```bash
# Install Dusk
composer require --dev laravel/dusk
php artisan dusk:install
```

### Browser Test Example

```php
<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Company;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AdminDashboardTest extends DuskTestCase
{
    /** @test */
    public function admin_can_view_dashboard()
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);
        
        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/admin')
                ->assertSee('Dashboard')
                ->assertSee('Today\'s Appointments')
                ->assertSee('Active Calls')
                ->assertVisible('@appointments-widget')
                ->assertVisible('@calls-widget');
        });
    }
    
    /** @test */
    public function admin_can_create_appointment()
    {
        $user = User::factory()->create(['role' => 'admin']);
        
        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/admin/appointments/create')
                ->type('customer_name', 'John Doe')
                ->type('customer_phone', '+49301234567')
                ->select('service_id', '1')
                ->click('@date-picker')
                ->click('@date-tomorrow')
                ->select('time', '14:00')
                ->type('notes', 'Test appointment')
                ->press('Create Appointment')
                ->waitForText('Appointment created successfully')
                ->assertPathIs('/admin/appointments');
        });
    }
}
```

## Testing Best Practices

### Test Data Factories

```php
// database/factories/AppointmentFactory.php
namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;
    
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'branch_id' => Branch::factory(),
            'customer_id' => Customer::factory(),
            'staff_id' => Staff::factory(),
            'service_id' => Service::factory(),
            'date' => $this->faker->dateTimeBetween('tomorrow', '+30 days')->format('Y-m-d'),
            'time' => $this->faker->time('H:00:00'),
            'duration' => $this->faker->randomElement([30, 45, 60, 90]),
            'status' => 'scheduled',
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
    
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
    }
    
    public function tomorrow(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => now()->addDay()->format('Y-m-d'),
        ]);
    }
    
    public function withCustomer(Customer $customer): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => $customer->id,
            'company_id' => $customer->company_id,
        ]);
    }
}
```

### Test Helpers

```php
// tests/TestCase.php
namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    
    protected function signIn($user = null)
    {
        $user = $user ?: User::factory()->create();
        $this->actingAs($user);
        return $user;
    }
    
    protected function signInAdmin()
    {
        return $this->signIn(User::factory()->admin()->create());
    }
    
    protected function createCompanyWithData(): Company
    {
        $company = Company::factory()->create();
        
        Branch::factory()->count(3)->create(['company_id' => $company->id]);
        Service::factory()->count(5)->create(['company_id' => $company->id]);
        Staff::factory()->count(10)->create(['company_id' => $company->id]);
        
        return $company;
    }
    
    protected function assertDatabaseHasWithRelations($table, array $data, array $relations = [])
    {
        $this->assertDatabaseHas($table, $data);
        
        $model = DB::table($table)->where($data)->first();
        
        foreach ($relations as $relation => $expected) {
            $this->assertEquals($expected, $model->$relation);
        }
    }
}
```

### Testing Traits

```php
// tests/Traits/MocksExternalServices.php
namespace Tests\Traits;

trait MocksExternalServices
{
    protected function mockRetellService($responses = [])
    {
        $mock = $this->mock(RetellService::class);
        
        foreach ($responses as $method => $response) {
            $mock->shouldReceive($method)->andReturn($response);
        }
        
        return $mock;
    }
    
    protected function mockCalendarService($available = true)
    {
        $this->mock(CalendarService::class)
            ->shouldReceive('isAvailable')
            ->andReturn($available)
            ->shouldReceive('createBooking')
            ->andReturn(['id' => 'cal_' . Str::random(10)]);
    }
    
    protected function mockSuccessfulBookingFlow()
    {
        $this->mockCalendarService(true);
        $this->mockNotificationService();
        Queue::fake();
        Event::fake();
    }
}
```

## Code Coverage

### Generate Coverage Report

```bash
# Generate HTML coverage report
php artisan test --coverage-html=coverage

# Generate coverage with minimum threshold
php artisan test --coverage --min=80

# Generate Clover XML for CI
./vendor/bin/phpunit --coverage-clover=coverage.xml
```

### Coverage Configuration

```xml
<!-- phpunit.xml -->
<coverage>
    <include>
        <directory suffix=".php">./app</directory>
    </include>
    <exclude>
        <directory>./app/Console/Kernel.php</directory>
        <directory>./app/Exceptions/Handler.php</directory>
        <directory>./app/Providers</directory>
    </exclude>
    <report>
        <html outputDirectory="coverage"/>
        <text outputFile="coverage.txt"/>
        <clover outputFile="coverage.xml"/>
    </report>
</coverage>
```

## Continuous Integration

### GitHub Actions Example

```yaml
# .github/workflows/tests.yml
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
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: testing
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s
      
      redis:
        image: redis:7
        ports:
          - 6379:6379
        options: --health-cmd="redis-cli ping"
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, pdo, pdo_mysql, redis
          coverage: xdebug
      
      - name: Install dependencies
        run: |
          composer install --prefer-dist --no-progress
          npm ci
          npm run build
      
      - name: Prepare testing environment
        run: |
          cp .env.testing.ci .env.testing
          php artisan key:generate --env=testing
          php artisan migrate --env=testing
      
      - name: Run tests with coverage
        run: |
          php artisan test --coverage --min=80
          
      - name: Upload coverage to Codecov
        uses: codecov/codecov-action@v3
        with:
          file: ./coverage.xml
          fail_ci_if_error: true
```

## Testing Commands

### Useful Testing Commands

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Run specific test file
php artisan test tests/Feature/AppointmentTest.php

# Run specific test method
php artisan test --filter=it_creates_appointment_with_valid_data

# Run tests in parallel
php artisan test --parallel

# Run tests and stop on failure
php artisan test --stop-on-failure

# Generate test
php artisan make:test AppointmentServiceTest --unit
php artisan make:test AppointmentApiTest
```

## Related Documentation

- [Development Setup](setup.md)
- [Coding Standards](standards.md)
- [Debugging Guide](debugging.md)
- [CI/CD Pipeline](../deployment/ci-cd.md)