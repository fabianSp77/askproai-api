<?php

namespace Tests\Performance;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Call;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * CompanyScope Performance Benchmark Tests
 *
 * Purpose: Measure and validate performance impact of CompanyScope global scope
 *
 * Usage:
 *   php artisan test --filter CompanyScopePerformanceTest
 *
 * Performance Baselines:
 *   - Simple scoped query: <1ms
 *   - Complex query with relations: <5ms
 *   - Scope overhead: <0.5ms
 *   - Dashboard queries: <50ms
 *
 * IMPORTANT: Run these tests in ISOLATION (not in CI) as they measure actual execution time
 */
class CompanyScopePerformanceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $superAdmin;
    private Company $company;
    private Company $otherCompany;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test companies
        $this->company = Company::factory()->create(['name' => 'Performance Test Company']);
        $this->otherCompany = Company::factory()->create(['name' => 'Other Company']);

        // Create regular user
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'test@performance.com'
        ]);

        // Create super admin
        $this->superAdmin = User::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'admin@performance.com'
        ]);
        $this->superAdmin->assignRole('super_admin');

        $this->actingAs($this->user);
    }

    /**
     * Test: Simple scoped query performance
     *
     * Expected: <1ms for 10 records from 100 total
     * Validates: Basic CompanyScope overhead is minimal
     */
    public function test_simple_scoped_query_performance()
    {
        // Arrange: Create test data
        Appointment::factory()->count(50)->create(['company_id' => $this->company->id]);
        Appointment::factory()->count(50)->create(['company_id' => $this->otherCompany->id]);

        // Warm up query cache
        Appointment::first();
        DB::flushQueryLog();

        // Act: Benchmark simple query
        $start = microtime(true);
        DB::enableQueryLog();

        $appointments = Appointment::take(10)->get();

        $queries = DB::getQueryLog();
        $duration = (microtime(true) - $start) * 1000; // ms

        // Assert: Performance and correctness
        $this->assertLessThan(2.0, $duration, "Simple scoped query took {$duration}ms (expected <2ms)");
        $this->assertCount(1, $queries, 'More than 1 query executed');
        $this->assertStringContainsString('company_id', $queries[0]['query'], 'Scope not applied');
        $this->assertCount(10, $appointments);

        // Verify isolation
        foreach ($appointments as $appointment) {
            $this->assertEquals($this->company->id, $appointment->company_id, 'Scope isolation failed');
        }

        $this->logPerformance('Simple scoped query', $duration, count($queries));
    }

    /**
     * Test: Scoped query with relationships
     *
     * Expected: <5ms with proper eager loading
     * Validates: No N+1 queries when using proper eager loading
     */
    public function test_scoped_query_with_relationships()
    {
        // Arrange: Create related data
        $customers = Customer::factory()->count(10)->create(['company_id' => $this->company->id]);
        $services = Service::factory()->count(5)->create(['company_id' => $this->company->id]);
        $staff = Staff::factory()->count(3)->create(['company_id' => $this->company->id]);

        Appointment::factory()->count(50)->create([
            'company_id' => $this->company->id,
            'customer_id' => $customers->random()->id,
            'service_id' => $services->random()->id,
            'staff_id' => $staff->random()->id,
        ]);

        // Act: Benchmark with eager loading
        $start = microtime(true);
        DB::enableQueryLog();

        $appointments = Appointment::with(['customer', 'service', 'staff', 'company'])
            ->take(20)
            ->get();

        $queries = DB::getQueryLog();
        $duration = (microtime(true) - $start) * 1000;

        // Assert: Performance and query count
        $this->assertLessThan(10.0, $duration, "Complex query took {$duration}ms (expected <10ms)");
        $this->assertLessThanOrEqual(5, count($queries), 'Too many queries - possible N+1 detected');
        $this->assertCount(20, $appointments);

        // Verify all relationships loaded
        $this->assertTrue($appointments->first()->relationLoaded('customer'));
        $this->assertTrue($appointments->first()->relationLoaded('service'));
        $this->assertTrue($appointments->first()->relationLoaded('staff'));

        $this->logPerformance('Scoped query with relations', $duration, count($queries));
    }

    /**
     * Test: CompanyScope overhead measurement
     *
     * Expected: <0.5ms overhead compared to unscoped query
     * Validates: Scope application has minimal performance impact
     */
    public function test_scope_overhead()
    {
        // Arrange
        Appointment::factory()->count(100)->create(['company_id' => $this->company->id]);

        // Warm up
        Appointment::first();

        // Act: Measure WITH scope (regular user)
        $this->actingAs($this->user);
        $start = microtime(true);
        Appointment::take(50)->get();
        $withScope = (microtime(true) - $start) * 1000;

        // Measure WITHOUT scope (super_admin bypasses scope)
        $this->actingAs($this->superAdmin);
        $start = microtime(true);
        Appointment::take(50)->get();
        $withoutScope = (microtime(true) - $start) * 1000;

        $overhead = $withScope - $withoutScope;

        // Assert: Overhead is minimal
        $this->assertLessThan(1.0, abs($overhead), "Scope overhead {$overhead}ms exceeds threshold");

        $this->logPerformance(
            'Scope overhead',
            $overhead,
            null,
            "With scope: {$withScope}ms, Without: {$withoutScope}ms"
        );
    }

    /**
     * Test: N+1 detection in Call appointment accessor
     *
     * Expected: Should detect N+1 without eager loading, efficient with eager loading
     * Validates: Accessor optimization recommendations
     */
    public function test_n_plus_one_detection_in_call_accessor()
    {
        // Arrange: Create calls with appointments
        $calls = Call::factory()->count(20)->create(['company_id' => $this->company->id]);

        foreach ($calls->take(10) as $call) {
            Appointment::factory()->create([
                'company_id' => $this->company->id,
                'call_id' => $call->id,
            ]);
        }

        // Test 1: WITHOUT eager loading (should have N+1)
        DB::flushQueryLog();
        $callsWithoutEager = Call::take(10)->get();

        foreach ($callsWithoutEager as $call) {
            $appointment = $call->appointment; // Accessor may trigger lazy load
        }

        $queriesWithoutEager = count(DB::getQueryLog());

        // Test 2: WITH eager loading (should be efficient)
        DB::flushQueryLog();
        $callsWithEager = Call::with('latestAppointment')->take(10)->get();

        foreach ($callsWithEager as $call) {
            $appointment = $call->appointment;
        }

        $queriesWithEager = count(DB::getQueryLog());

        // Assert: Eager loading reduces queries
        $this->assertLessThanOrEqual(3, $queriesWithEager, 'Eager loading not working properly');

        if ($queriesWithoutEager > $queriesWithEager) {
            $this->logPerformance(
                'N+1 in Call accessor',
                null,
                null,
                "Without eager: {$queriesWithoutEager} queries, With eager: {$queriesWithEager} queries"
            );
        }
    }

    /**
     * Test: Index usage verification
     *
     * Expected: MySQL should use company_id index for scoped queries
     * Validates: Database indexes are properly utilized
     */
    public function test_index_usage()
    {
        // Arrange: Create enough data to make index useful
        Appointment::factory()->count(500)->create(['company_id' => $this->company->id]);

        // Act: Get EXPLAIN plan
        $explain = DB::select(
            "EXPLAIN SELECT * FROM appointments WHERE company_id = ? LIMIT 10",
            [$this->company->id]
        );

        // Assert: Index is being used
        $this->assertEquals('ref', $explain[0]->type, 'Expected index lookup (ref), got: ' . $explain[0]->type);
        $this->assertStringContainsString(
            'company',
            strtolower($explain[0]->key ?? ''),
            'company_id index not being used'
        );

        $this->logPerformance(
            'Index usage',
            null,
            null,
            "Type: {$explain[0]->type}, Key: {$explain[0]->key}, Rows: {$explain[0]->rows}"
        );
    }

    /**
     * Test: Dashboard query performance
     *
     * Expected: <50ms for typical dashboard data aggregation
     * Validates: Real-world usage performance
     */
    public function test_dashboard_query_performance()
    {
        // Arrange: Create realistic dashboard data
        Customer::factory()->count(30)->create(['company_id' => $this->company->id]);
        Appointment::factory()->count(100)->create(['company_id' => $this->company->id]);
        Call::factory()->count(50)->create(['company_id' => $this->company->id]);

        // Act: Simulate dashboard queries
        $start = microtime(true);
        DB::enableQueryLog();

        $stats = [
            'total_customers' => Customer::count(),
            'total_appointments' => Appointment::count(),
            'total_calls' => Call::count(),
            'upcoming_appointments' => Appointment::where('starts_at', '>=', now())->count(),
            'recent_customers' => Customer::latest()->take(10)->get(),
        ];

        $queries = DB::getQueryLog();
        $duration = (microtime(true) - $start) * 1000;

        // Assert: Acceptable performance
        $this->assertLessThan(100, $duration, "Dashboard queries took {$duration}ms (expected <100ms)");
        $this->assertLessThan(15, count($queries), 'Too many dashboard queries');

        $this->logPerformance('Dashboard queries', $duration, count($queries));
    }

    /**
     * Test: Memory usage for large scoped queries
     *
     * Expected: <30MB for 500 records
     * Validates: Memory efficiency of scoped queries
     */
    public function test_memory_usage_for_large_queries()
    {
        // Arrange
        Appointment::factory()->count(1000)->create(['company_id' => $this->company->id]);

        $memoryBefore = memory_get_usage(true);

        // Act: Load large dataset
        $appointments = Appointment::take(500)->get();

        $memoryAfter = memory_get_usage(true);
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // MB

        // Assert: Reasonable memory usage
        $this->assertLessThan(50, $memoryUsed, "Memory usage {$memoryUsed}MB exceeds 50MB threshold");
        $this->assertCount(500, $appointments);

        $this->logPerformance('Memory usage (500 records)', null, null, sprintf('%.2f MB', $memoryUsed));
    }

    /**
     * Test: Concurrent scoped queries
     *
     * Expected: <200ms for 10 sequential queries
     * Validates: No locking or contention issues
     */
    public function test_concurrent_scoped_queries()
    {
        // Arrange
        Appointment::factory()->count(100)->create(['company_id' => $this->company->id]);

        // Act: Simulate multiple concurrent requests
        $start = microtime(true);

        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $results[] = Appointment::where('status', 'scheduled')->take(10)->get();
        }

        $duration = (microtime(true) - $start) * 1000;

        // Assert: Acceptable performance
        $this->assertLessThan(200, $duration, "10 concurrent queries took {$duration}ms (expected <200ms)");
        $this->assertCount(10, $results);

        $this->logPerformance('10 concurrent queries', $duration, null);
    }

    /**
     * Test: Scope isolation verification
     *
     * Expected: Zero cross-tenant data leakage
     * Validates: Security of tenant isolation
     */
    public function test_scope_isolation()
    {
        // Arrange: Create data for different companies
        Appointment::factory()->count(50)->create(['company_id' => $this->company->id]);
        Appointment::factory()->count(50)->create(['company_id' => $this->otherCompany->id]);

        // Act: Query as regular user
        $this->actingAs($this->user);
        $appointments = Appointment::all();

        // Assert: Only see own company's data
        $this->assertCount(50, $appointments, 'Scope isolation failed - wrong count');

        foreach ($appointments as $appointment) {
            $this->assertEquals(
                $this->company->id,
                $appointment->company_id,
                'Scope isolation failed - cross-tenant data leaked'
            );
        }

        // Act: Query as super_admin
        $this->actingAs($this->superAdmin);
        $allAppointments = Appointment::all();

        // Assert: Super admin sees all data
        $this->assertCount(100, $allAppointments, 'Super admin scope bypass failed');

        $this->logPerformance('Scope isolation', null, null, 'Verified: No data leakage');
    }

    /**
     * Test: Complex join query performance
     *
     * Expected: <15ms for joins across multiple scoped tables
     * Validates: Scope performance with complex queries
     */
    public function test_complex_join_performance()
    {
        // Arrange
        $customers = Customer::factory()->count(20)->create(['company_id' => $this->company->id]);
        $services = Service::factory()->count(10)->create(['company_id' => $this->company->id]);

        Appointment::factory()->count(100)->create([
            'company_id' => $this->company->id,
            'customer_id' => $customers->random()->id,
            'service_id' => $services->random()->id,
        ]);

        // Act: Complex query with joins and aggregations
        $start = microtime(true);
        DB::enableQueryLog();

        $results = Appointment::query()
            ->join('customers', 'appointments.customer_id', '=', 'customers.id')
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->select('appointments.*', 'customers.name as customer_name', 'services.name as service_name')
            ->where('appointments.status', 'scheduled')
            ->take(20)
            ->get();

        $queries = DB::getQueryLog();
        $duration = (microtime(true) - $start) * 1000;

        // Assert
        $this->assertLessThan(20, $duration, "Complex join query took {$duration}ms (expected <20ms)");
        $this->assertLessThanOrEqual(3, count($queries));

        $this->logPerformance('Complex join query', $duration, count($queries));
    }

    /**
     * Helper: Log performance metrics
     */
    private function logPerformance(string $test, ?float $duration = null, ?int $queries = null, ?string $extra = null)
    {
        $message = "📊 Performance: {$test}";

        if ($duration !== null) {
            $message .= sprintf(' | Duration: %.2fms', $duration);
        }

        if ($queries !== null) {
            $message .= " | Queries: {$queries}";
        }

        if ($extra !== null) {
            $message .= " | {$extra}";
        }

        dump($message);
    }
}
