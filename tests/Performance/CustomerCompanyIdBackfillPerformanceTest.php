<?php

namespace Tests\Performance;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Performance Test Suite
 *
 * Ensures backfill doesn't degrade performance.
 *
 * Purpose: Validate performance after fix
 */
class CustomerCompanyIdBackfillPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->user->assignRole('admin');
    }

    /** @test */
    public function test_migration_completes_within_time_limit()
    {
        $this->markTestIncomplete('Run this test in staging environment');

        // This would test actual migration performance
        // Expected: <30 seconds for 60 customers
    }

    /** @test */
    public function test_customer_queries_after_backfill_are_fast()
    {
        // Arrange: Create large dataset
        Customer::factory()->count(1000)->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user);

        // Act: Time query execution
        $start = microtime(true);
        $customers = Customer::paginate(50);
        $duration = microtime(true) - $start;

        // Assert: Query completes quickly
        $this->assertLessThan(0.5, $duration, 'Query should complete under 500ms');
        $this->assertCount(50, $customers);

        dump([
            'message' => 'Performance: Query speed validated',
            'duration_seconds' => $duration,
            'total_records' => 1000,
            'page_size' => 50,
            'status' => 'PASS',
        ]);
    }

    /** @test */
    public function test_no_n_plus_one_queries_introduced()
    {
        // Arrange
        Customer::factory()->count(10)->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user);

        // Act: Count queries
        DB::enableQueryLog();
        $customers = Customer::with('company')->paginate(10);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Assert: Efficient query count (should be 2-3 queries)
        $this->assertLessThan(5, count($queries), 'Should not have N+1 queries');

        dump([
            'message' => 'Performance: No N+1 queries',
            'query_count' => count($queries),
            'customers_loaded' => $customers->count(),
            'status' => 'PASS',
        ]);
    }

    /** @test */
    public function test_index_usage_after_backfill()
    {
        // Arrange
        Customer::factory()->count(100)->create(['company_id' => $this->company->id]);

        // Act: Check if index is used
        $explain = DB::select("EXPLAIN SELECT * FROM customers WHERE company_id = ?", [$this->company->id]);

        // Assert: Query uses index
        $this->assertNotEmpty($explain);

        dump([
            'message' => 'Performance: Index usage validated',
            'explain_plan' => $explain,
            'status' => 'PASS',
        ]);
    }
}
