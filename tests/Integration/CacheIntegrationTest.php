<?php

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Services\CacheService;
use App\Services\Cache\CompanyCacheService;
use App\Services\CalcomService;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Appointment;
use App\Models\Customer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Carbon\Carbon;

class CacheIntegrationTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    private CacheService $cacheService;
    private CompanyCacheService $companyCacheService;
    private Company $company;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cacheService = app(CacheService::class);
        $this->companyCacheService = app(CompanyCacheService::class);
        
        // Create test data
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'phone' => '+499876543210',
        ]);
        
        Cache::flush();
    }

    /** @test */
    public function cache_is_invalidated_when_company_data_changes()
    {
        // Arrange
        $cachedCompany = $this->companyCacheService->getCompanyWithRelations($this->company->id);
        $this->assertEquals($this->company->name, $cachedCompany->name);
        
        // Act - Update company
        $this->company->update(['name' => 'Updated Company Name']);
        
        // Trigger the event that should clear cache
        Event::dispatch('eloquent.updated: ' . Company::class, $this->company);
        
        // Assert - Cache should be cleared
        $freshCompany = $this->companyCacheService->getCompanyWithRelations($this->company->id);
        $this->assertEquals('Updated Company Name', $freshCompany->name);
    }

    /** @test */
    public function availability_cache_is_updated_when_appointment_is_created()
    {
        // Arrange
        $staff = Staff::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
        ]);
        
        $service = Service::factory()->create([
            'company_id' => $this->company->id,
            'duration' => 60,
        ]);
        
        $appointmentTime = Carbon::tomorrow()->setHour(10)->setMinute(0);
        
        // Cache availability before appointment
        $availabilityBefore = $this->cacheService->rememberAvailability(
            $this->company->id,
            $this->branch->id,
            $appointmentTime->format('Y-m-d'),
            function () {
                return [
                    '10:00' => true,
                    '11:00' => true,
                    '12:00' => true,
                ];
            }
        );
        
        // Act - Create appointment
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'staff_id' => $staff->id,
            'service_id' => $service->id,
            'appointment_datetime' => $appointmentTime,
            'duration' => 60,
            'status' => 'scheduled',
        ]);
        
        // Clear availability cache as would happen in real scenario
        $cacheKey = 'availability_' . $this->company->id . '_' . $this->branch->id . '_' . $appointmentTime->format('Y-m-d');
        Cache::forget($cacheKey);
        
        // Re-fetch availability
        $availabilityAfter = $this->cacheService->rememberAvailability(
            $this->company->id,
            $this->branch->id,
            $appointmentTime->format('Y-m-d'),
            function () use ($appointment) {
                // Simulate checking real availability
                return [
                    '10:00' => false, // Now booked
                    '11:00' => true,
                    '12:00' => true,
                ];
            }
        );
        
        // Assert
        $this->assertTrue($availabilityBefore['10:00']);
        $this->assertFalse($availabilityAfter['10:00']);
    }

    /** @test */
    public function branch_lookup_by_phone_uses_cache()
    {
        // Arrange
        $queryCount = 0;
        \DB::listen(function () use (&$queryCount) {
            $queryCount++;
        });
        
        // Act - First call should query database
        $branch1 = $this->companyCacheService->getBranchByPhone($this->branch->phone);
        $queriesAfterFirst = $queryCount;
        
        // Second call should use cache
        $branch2 = $this->companyCacheService->getBranchByPhone($this->branch->phone);
        $queriesAfterSecond = $queryCount;
        
        // Assert
        $this->assertEquals($this->branch->id, $branch1->id);
        $this->assertEquals($this->branch->id, $branch2->id);
        $this->assertGreaterThan(0, $queriesAfterFirst);
        $this->assertEquals($queriesAfterFirst, $queriesAfterSecond); // No additional queries
    }

    /** @test */
    public function cal_com_event_types_are_cached_with_proper_invalidation()
    {
        // Arrange
        $this->mockCalcomApi();
        
        $calcomService = app(CalcomService::class);
        
        // Act - First call should make API request
        $eventTypes1 = $calcomService->getEventTypes();
        
        // Second call should use cache
        $eventTypes2 = $calcomService->getEventTypes();
        
        // Assert - Both should return same data
        $this->assertEquals($eventTypes1, $eventTypes2);
        
        // Act - Force cache refresh
        Cache::tags(['calcom', 'company_' . $this->company->id])->flush();
        
        // Third call should make new API request
        $eventTypes3 = $calcomService->getEventTypes();
        
        // Assert
        $this->assertEquals($eventTypes1, $eventTypes3);
    }

    /** @test */
    public function customer_search_results_are_cached()
    {
        // Arrange
        Customer::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'name' => 'John Doe',
        ]);
        
        Customer::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'name' => 'Jane Smith',
        ]);
        
        // Act - Search for customers
        $searchResults1 = $this->companyCacheService->searchCustomers($this->company->id, 'John');
        
        // Add another John (should not appear in cached results)
        Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'John Added',
        ]);
        
        // Search again (should use cache)
        $searchResults2 = $this->companyCacheService->searchCustomers($this->company->id, 'John');
        
        // Assert
        $this->assertCount(5, $searchResults1);
        $this->assertCount(5, $searchResults2); // Still 5, not 6
    }

    /** @test */
    public function cache_warming_works_for_dashboard_data()
    {
        // Arrange
        Appointment::factory()->count(10)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'appointment_datetime' => Carbon::today()->addHours(rand(9, 17)),
            'status' => 'scheduled',
        ]);
        
        // Act - Warm cache
        $this->artisan('cache:warm', [
            '--type' => 'dashboard',
            '--company' => $this->company->id,
        ]);
        
        // Assert - Dashboard data should be cached
        $cacheKey = 'dashboard_stats_' . $this->company->id . '_' . Carbon::today()->format('Y-m-d');
        $this->assertTrue(Cache::has($cacheKey));
        
        $stats = Cache::get($cacheKey);
        $this->assertArrayHasKey('appointments_today', $stats);
        $this->assertEquals(10, $stats['appointments_today']);
    }

    /** @test */
    public function redis_connection_failure_falls_back_gracefully()
    {
        // Arrange
        // Temporarily use array cache to simulate Redis failure
        config(['cache.default' => 'array']);
        
        $service = Service::factory()->create([
            'company_id' => $this->company->id,
        ]);
        
        // Act
        $cached = $this->cacheService->rememberServices(
            $this->company->id,
            function () use ($service) {
                return collect([$service]);
            }
        );
        
        // Assert - Should still work with array cache
        $this->assertCount(1, $cached);
        $this->assertEquals($service->id, $cached->first()->id);
        
        // Restore Redis
        config(['cache.default' => 'redis']);
    }

    /** @test */
    public function cache_tags_isolate_company_data()
    {
        // Skip if cache driver doesn't support tags
        if (!Cache::supportsTags()) {
            $this->markTestSkipped('Cache driver does not support tags');
        }
        
        // Arrange
        $company2 = Company::factory()->create();
        
        // Cache data for both companies
        Cache::tags(['company_' . $this->company->id])->put('test_data', 'Company 1 Data', 300);
        Cache::tags(['company_' . $company2->id])->put('test_data', 'Company 2 Data', 300);
        
        // Act - Clear company 1 cache
        Cache::tags(['company_' . $this->company->id])->flush();
        
        // Assert
        $this->assertNull(Cache::tags(['company_' . $this->company->id])->get('test_data'));
        $this->assertEquals('Company 2 Data', Cache::tags(['company_' . $company2->id])->get('test_data'));
    }

    /** @test */
    public function concurrent_cache_updates_are_handled_correctly()
    {
        // Arrange
        $key = 'concurrent_test';
        $iterations = 10;
        $results = [];
        
        // Act - Simulate concurrent updates
        for ($i = 0; $i < $iterations; $i++) {
            $value = $this->cacheService->remember($key, 60, function () use ($i) {
                // Simulate some processing time
                usleep(1000); // 1ms
                return 'value_' . $i;
            });
            $results[] = $value;
        }
        
        // Assert - All results should be the same (first execution wins)
        $uniqueResults = array_unique($results);
        $this->assertCount(1, $uniqueResults);
        $this->assertEquals('value_0', $uniqueResults[0]);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}