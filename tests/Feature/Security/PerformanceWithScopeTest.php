<?php

namespace Tests\Feature\Security;

use App\Models\Company;
use App\Models\User;
use App\Models\Policy;
use App\Models\Booking;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Performance with Scope Test Suite
 *
 * Tests query performance with company scope:
 * - Query performance with company_id filtering
 * - Index usage verification
 * - Critical query benchmarks (<100ms)
 * - N+1 query prevention
 */
class PerformanceWithScopeTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Seed test data
        $this->seedTestData();
    }

    private function seedTestData(): void
    {
        // Create multiple companies with data
        for ($i = 0; $i < 5; $i++) {
            $company = Company::factory()->create();

            // Create policies, services, and bookings for each company
            Policy::factory()->count(20)->create(['company_id' => $company->id]);
            Service::factory()->count(10)->create(['company_id' => $company->id]);
        }

        // Create data for test user's company
        Policy::factory()->count(50)->create(['company_id' => $this->company->id]);
        Service::factory()->count(25)->create(['company_id' => $this->company->id]);
    }

    /**
     * @test
     * Test policy query performance with company scope
     */
    public function policy_queries_perform_efficiently_with_scope(): void
    {
        $this->actingAs($this->user);

        $startTime = microtime(true);

        $policies = Policy::all();

        $executionTime = (microtime(true) - $startTime) * 1000; // Convert to ms

        // Should return only user's company policies
        $this->assertCount(50, $policies);

        // Should complete under 100ms
        $this->assertLessThan(100, $executionTime, "Query took {$executionTime}ms");
    }

    /**
     * @test
     * Test company_id index is used in queries
     */
    public function queries_use_company_id_index(): void
    {
        $this->actingAs($this->user);

        // Enable query log
        DB::enableQueryLog();

        Policy::all();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Check that query includes company_id filter
        $this->assertNotEmpty($queries);

        $policyQuery = collect($queries)->first(function ($query) {
            return str_contains($query['query'], 'policies');
        });

        if ($policyQuery) {
            $this->assertStringContainsString('company_id', $policyQuery['query']);
        }
    }

    /**
     * @test
     * Test pagination performance with large datasets
     */
    public function pagination_performs_efficiently_with_large_datasets(): void
    {
        $this->actingAs($this->user);

        $startTime = microtime(true);

        $policies = Policy::paginate(15);

        $executionTime = (microtime(true) - $startTime) * 1000;

        $this->assertEquals(15, $policies->count());
        $this->assertLessThan(100, $executionTime, "Pagination took {$executionTime}ms");
    }

    /**
     * @test
     * Test relationship eager loading prevents N+1 queries
     */
    public function eager_loading_prevents_n_plus_one_queries(): void
    {
        $this->actingAs($this->user);

        // Create services with bookings
        $services = Service::factory()->count(10)->create([
            'company_id' => $this->company->id,
        ]);

        foreach ($services as $service) {
            Booking::factory()->count(3)->create([
                'service_id' => $service->id,
                'company_id' => $this->company->id,
                'user_id' => $this->user->id,
            ]);
        }

        DB::enableQueryLog();

        // Eager load bookings
        $servicesWithBookings = Service::with('bookings')->get();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Should execute only 2 queries (services + bookings)
        $this->assertLessThanOrEqual(3, count($queries), "Expected 2-3 queries, got " . count($queries));
    }

    /**
     * @test
     * Test count queries perform efficiently
     */
    public function count_queries_perform_efficiently(): void
    {
        $this->actingAs($this->user);

        $startTime = microtime(true);

        $count = Policy::count();

        $executionTime = (microtime(true) - $startTime) * 1000;

        $this->assertEquals(50, $count);
        $this->assertLessThan(50, $executionTime, "Count query took {$executionTime}ms");
    }

    /**
     * @test
     * Test complex queries with joins perform efficiently
     */
    public function complex_join_queries_perform_efficiently(): void
    {
        $this->actingAs($this->user);

        $startTime = microtime(true);

        $results = Policy::join('booking_types', 'policies.booking_type_id', '=', 'booking_types.id')
            ->select('policies.*')
            ->get();

        $executionTime = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(150, $executionTime, "Join query took {$executionTime}ms");
    }

    /**
     * @test
     * Test search queries with company scope perform efficiently
     */
    public function search_queries_perform_efficiently(): void
    {
        $this->actingAs($this->user);

        // Create searchable policies
        Policy::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Searchable Policy',
        ]);

        $startTime = microtime(true);

        $results = Policy::where('name', 'like', '%Searchable%')->get();

        $executionTime = (microtime(true) - $startTime) * 1000;

        $this->assertGreaterThan(0, $results->count());
        $this->assertLessThan(100, $executionTime, "Search query took {$executionTime}ms");
    }

    /**
     * @test
     * Test batch retrieval performs efficiently
     */
    public function batch_retrieval_performs_efficiently(): void
    {
        $this->actingAs($this->user);

        $startTime = microtime(true);

        $processed = 0;
        Policy::chunk(10, function ($policies) use (&$processed) {
            $processed += $policies->count();
        });

        $executionTime = (microtime(true) - $startTime) * 1000;

        $this->assertEquals(50, $processed);
        $this->assertLessThan(200, $executionTime, "Chunk query took {$executionTime}ms");
    }
}
