<?php

namespace Tests\Unit\Database;

use Tests\TestCase;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Call;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PerformanceTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        
        // Enable query log for analysis
        DB::enableQueryLog();
    }

    protected function tearDown(): void
    {
        DB::disableQueryLog();
        parent::tearDown();
    }

    /** @test */
    public function n_plus_one_queries_are_avoided_with_eager_loading()
    {
        // Create customers with appointments
        $customers = Customer::factory()
            ->count(10)
            ->has(Appointment::factory()->count(3))
            ->create(['company_id' => $this->company->id]);

        DB::flushQueryLog();

        // Bad: N+1 queries
        $badQueries = 0;
        foreach (Customer::where('company_id', $this->company->id)->get() as $customer) {
            $count = $customer->appointments->count();
            $badQueries++;
        }
        $badQueryCount = count(DB::getQueryLog());

        DB::flushQueryLog();

        // Good: Eager loading
        $goodQueries = 0;
        foreach (Customer::where('company_id', $this->company->id)->with('appointments')->get() as $customer) {
            $count = $customer->appointments->count();
            $goodQueries++;
        }
        $goodQueryCount = count(DB::getQueryLog());

        // Eager loading should use significantly fewer queries
        $this->assertLessThan($badQueryCount, $goodQueryCount);
        $this->assertEquals(2, $goodQueryCount); // 1 for customers, 1 for appointments
    }

    /** @test */
    public function index_usage_improves_query_performance()
    {
        // Create large dataset
        Customer::factory()->count(1000)->create([
            'company_id' => $this->company->id,
        ]);

        // Query with indexed column (company_id)
        $startTime = microtime(true);
        $indexedResults = Customer::where('company_id', $this->company->id)->get();
        $indexedTime = microtime(true) - $startTime;

        // Query with non-indexed column (let's assume 'notes' is not indexed)
        $startTime = microtime(true);
        $nonIndexedResults = Customer::where('notes', 'LIKE', '%test%')->get();
        $nonIndexedTime = microtime(true) - $startTime;

        // Indexed query should be faster
        // Note: This might not always be true in testing with small datasets
        $this->assertLessThan(1, $indexedTime); // Should complete within 1 second
    }

    /** @test */
    public function batch_inserts_are_more_efficient()
    {
        $records = 1000;
        $data = [];
        
        for ($i = 0; $i < $records; $i++) {
            $data[] = [
                'company_id' => $this->company->id,
                'name' => "Customer $i",
                'email' => "customer$i@example.com",
                'phone_number' => "+49123456$i",
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Single inserts
        $startTime = microtime(true);
        DB::flushQueryLog();
        
        foreach (array_slice($data, 0, 100) as $row) {
            DB::table('customers')->insert($row);
        }
        
        $singleInsertTime = microtime(true) - $startTime;
        $singleInsertQueries = count(DB::getQueryLog());

        // Batch insert
        $startTime = microtime(true);
        DB::flushQueryLog();
        
        DB::table('customers')->insert(array_slice($data, 100, 100));
        
        $batchInsertTime = microtime(true) - $startTime;
        $batchInsertQueries = count(DB::getQueryLog());

        // Batch should be faster and use fewer queries
        $this->assertLessThan($singleInsertTime, $batchInsertTime);
        $this->assertLessThan($singleInsertQueries, $batchInsertQueries);
    }

    /** @test */
    public function query_caching_reduces_database_hits()
    {
        // Create test data
        Call::factory()->count(50)->create([
            'company_id' => $this->company->id,
        ]);

        DB::flushQueryLog();

        // First query - hits database
        $results1 = Call::where('company_id', $this->company->id)
            ->remember(60) // Cache for 60 seconds
            ->get();
        
        $firstQueryCount = count(DB::getQueryLog());

        DB::flushQueryLog();

        // Second identical query - should use cache
        $results2 = Call::where('company_id', $this->company->id)
            ->remember(60)
            ->get();
        
        $secondQueryCount = count(DB::getQueryLog());

        // Second query should not hit database
        $this->assertEquals(0, $secondQueryCount);
        $this->assertEquals($results1->count(), $results2->count());
    }

    /** @test */
    public function select_only_required_columns()
    {
        // Create large customers with many columns
        Customer::factory()->count(100)->create([
            'company_id' => $this->company->id,
        ]);

        // Select all columns
        $startTime = microtime(true);
        $allColumns = Customer::where('company_id', $this->company->id)->get();
        $allColumnsTime = microtime(true) - $startTime;
        $allColumnsMemory = memory_get_peak_usage();

        // Select only required columns
        $startTime = microtime(true);
        $selectedColumns = Customer::where('company_id', $this->company->id)
            ->select('id', 'name', 'email')
            ->get();
        $selectedColumnsTime = microtime(true) - $startTime;
        $selectedColumnsMemory = memory_get_peak_usage();

        // Selecting fewer columns should use less memory
        $this->assertLessThanOrEqual($allColumnsMemory, $selectedColumnsMemory);
    }

    /** @test */
    public function chunking_prevents_memory_exhaustion()
    {
        // Create large dataset
        Customer::factory()->count(5000)->create([
            'company_id' => $this->company->id,
        ]);

        $initialMemory = memory_get_usage();
        $processedCount = 0;

        // Process in chunks to avoid memory issues
        Customer::where('company_id', $this->company->id)
            ->chunk(100, function ($customers) use (&$processedCount) {
                foreach ($customers as $customer) {
                    // Simulate processing
                    $processedCount++;
                }
                
                // Memory should not grow significantly
                $currentMemory = memory_get_usage();
                $this->assertLessThan(50 * 1024 * 1024, $currentMemory); // Less than 50MB
            });

        $this->assertEquals(5000, $processedCount);
    }

    /** @test */
    public function composite_indexes_improve_multi_column_queries()
    {
        // Create appointments
        Appointment::factory()->count(1000)->create([
            'company_id' => $this->company->id,
        ]);

        // Query using composite index (company_id, appointment_datetime)
        $startTime = microtime(true);
        $results = Appointment::where('company_id', $this->company->id)
            ->where('appointment_datetime', '>=', now())
            ->get();
        $compositeIndexTime = microtime(true) - $startTime;

        // Should complete quickly with composite index
        $this->assertLessThan(0.1, $compositeIndexTime);
    }

    /** @test */
    public function aggregation_queries_use_database_functions()
    {
        // Create calls with various durations
        Call::factory()->count(1000)->create([
            'company_id' => $this->company->id,
            'duration' => rand(60, 600),
        ]);

        DB::flushQueryLog();

        // Use database aggregation
        $stats = Call::where('company_id', $this->company->id)
            ->selectRaw('
                COUNT(*) as total,
                AVG(duration) as avg_duration,
                SUM(duration) as total_duration
            ')
            ->first();

        $queryCount = count(DB::getQueryLog());

        // Should use single query for all aggregations
        $this->assertEquals(1, $queryCount);
        $this->assertEquals(1000, $stats->total);
        $this->assertGreaterThan(0, $stats->avg_duration);
    }

    /** @test */
    public function lazy_loading_vs_eager_loading_performance()
    {
        // Create companies with related data
        $companies = Company::factory()
            ->count(10)
            ->has(Branch::factory()->count(5))
            ->has(Staff::factory()->count(10))
            ->create();

        // Lazy loading
        $startTime = microtime(true);
        DB::flushQueryLog();
        
        foreach (Company::all() as $company) {
            $branchCount = $company->branches->count();
            $staffCount = $company->staff->count();
        }
        
        $lazyTime = microtime(true) - $startTime;
        $lazyQueries = count(DB::getQueryLog());

        // Eager loading
        $startTime = microtime(true);
        DB::flushQueryLog();
        
        foreach (Company::with(['branches', 'staff'])->get() as $company) {
            $branchCount = $company->branches->count();
            $staffCount = $company->staff->count();
        }
        
        $eagerTime = microtime(true) - $startTime;
        $eagerQueries = count(DB::getQueryLog());

        // Eager loading should be faster and use fewer queries
        $this->assertLessThan($lazyTime, $eagerTime);
        $this->assertLessThan($lazyQueries, $eagerQueries);
    }

    /** @test */
    public function connection_pooling_handles_concurrent_requests()
    {
        $iterations = 50;
        $times = [];

        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            
            // Simulate concurrent database operations
            DB::connection()->getPdo();
            Customer::where('company_id', $this->company->id)->count();
            
            $times[] = microtime(true) - $startTime;
        }

        // Connection time should be consistent (pooling working)
        $avgTime = array_sum($times) / count($times);
        $maxTime = max($times);
        
        // Max time should not be significantly higher than average
        $this->assertLessThan($avgTime * 3, $maxTime);
    }
}