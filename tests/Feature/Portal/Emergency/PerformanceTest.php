<?php

namespace Tests\Feature\Portal\Emergency;

use Tests\TestCase;
use App\Models\Customer;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Call;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected $customer;
    protected $company;
    protected $branch;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id
        ]);
    }

    /**
     * Test dashboard stats performance with large dataset
     */
    public function test_dashboard_stats_performance()
    {
        // Create large dataset
        Call::factory()->count(1000)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id
        ]);
        
        Appointment::factory()->count(500)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id
        ]);

        $startTime = microtime(true);
        
        $response = $this->actingAs($this->customer, 'customer')
            ->getJson('/business/api/stats');
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to ms

        $response->assertOk();
        
        // Assert response time is under 200ms (API target)
        $this->assertLessThan(200, $responseTime, 
            "Dashboard stats API took {$responseTime}ms, exceeding 200ms target");
    }

    /**
     * Test customer list performance with pagination
     */
    public function test_customer_list_performance_with_pagination()
    {
        // Create 1000 customers
        Customer::factory()->count(1000)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id
        ]);

        $startTime = microtime(true);
        
        $response = $this->actingAs($this->customer, 'customer')
            ->getJson('/business/api/customers?per_page=50');
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertOk();
        
        // Should return exactly 50 records
        $this->assertCount(50, $response->json('data'));
        
        // Response time should be under 200ms
        $this->assertLessThan(200, $responseTime,
            "Customer list API took {$responseTime}ms, exceeding 200ms target");
    }

    /**
     * Test search performance
     */
    public function test_search_performance()
    {
        // Create customers with various names
        $names = ['John', 'Jane', 'Bob', 'Alice', 'Charlie'];
        foreach ($names as $name) {
            Customer::factory()->count(200)->create([
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'name' => $name . ' ' . fake()->lastName()
            ]);
        }

        $startTime = microtime(true);
        
        $response = $this->actingAs($this->customer, 'customer')
            ->getJson('/business/api/customers?search=John');
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertOk();
        
        // Search should still be performant
        $this->assertLessThan(300, $responseTime,
            "Search API took {$responseTime}ms, exceeding 300ms target");
    }

    /**
     * Test concurrent request handling
     */
    public function test_concurrent_request_handling()
    {
        $responses = [];
        $times = [];
        
        // Simulate 5 concurrent requests
        for ($i = 0; i < 5; $i++) {
            $startTime = microtime(true);
            
            $response = $this->actingAs($this->customer, 'customer')
                ->getJson('/business/api/dashboard/stats');
            
            $endTime = microtime(true);
            $times[] = ($endTime - $startTime) * 1000;
            $responses[] = $response;
        }

        // All requests should succeed
        foreach ($responses as $response) {
            $response->assertOk();
        }

        // Average response time should be reasonable
        $avgTime = array_sum($times) / count($times);
        $this->assertLessThan(250, $avgTime,
            "Average response time {$avgTime}ms exceeds 250ms target under load");
    }

    /**
     * Test database query count
     */
    public function test_database_query_efficiency()
    {
        // Create related data
        $customers = Customer::factory()->count(10)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id
        ]);

        foreach ($customers as $customer) {
            Call::factory()->count(5)->create([
                'customer_id' => $customer->id,
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id
            ]);
        }

        DB::enableQueryLog();
        
        $response = $this->actingAs($this->customer, 'customer')
            ->getJson('/business/api/customers');
        
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertOk();
        
        // Should use eager loading to minimize queries
        $this->assertLessThan(10, count($queries),
            "Too many database queries (" . count($queries) . "), indicates N+1 problem");
    }

    /**
     * Test memory usage for large responses
     */
    public function test_memory_usage_for_large_responses()
    {
        // Create large dataset
        Customer::factory()->count(100)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id
        ]);

        $memoryBefore = memory_get_usage();
        
        $response = $this->actingAs($this->customer, 'customer')
            ->getJson('/business/api/customers?per_page=50');
        
        $memoryAfter = memory_get_usage();
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // Convert to MB

        $response->assertOk();
        
        // Memory usage should be reasonable (less than 10MB for 50 records)
        $this->assertLessThan(10, $memoryUsed,
            "Request used {$memoryUsed}MB of memory, exceeding 10MB limit");
    }

    /**
     * Test response size optimization
     */
    public function test_response_size_optimization()
    {
        Customer::factory()->count(50)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->getJson('/business/api/customers');

        $response->assertOk();
        
        $responseSize = strlen($response->getContent()) / 1024; // KB
        
        // Response should be reasonably sized (under 100KB for 50 records)
        $this->assertLessThan(100, $responseSize,
            "Response size {$responseSize}KB exceeds 100KB limit");
    }

    /**
     * Test cache effectiveness
     */
    public function test_cache_effectiveness()
    {
        // First request (cache miss)
        $startTime1 = microtime(true);
        $response1 = $this->actingAs($this->customer, 'customer')
            ->getJson('/business/api/stats');
        $time1 = (microtime(true) - $startTime1) * 1000;

        // Second request (potential cache hit)
        $startTime2 = microtime(true);
        $response2 = $this->actingAs($this->customer, 'customer')
            ->getJson('/business/api/stats');
        $time2 = (microtime(true) - $startTime2) * 1000;

        $response1->assertOk();
        $response2->assertOk();

        // Second request should be faster if caching is working
        $this->assertLessThan($time1, $time2,
            "Second request ({$time2}ms) not faster than first ({$time1}ms), cache may not be working");
    }
}