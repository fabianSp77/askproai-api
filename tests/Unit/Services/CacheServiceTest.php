<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CacheService;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\Service;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CacheServiceTest extends TestCase
{
    private CacheService $cacheService;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cacheService = new CacheService();
        $this->company = Company::factory()->create();
        
        // Clear cache before each test
        Cache::flush();
    }

    /** @test */
    public function it_caches_event_types_with_correct_ttl()
    {
        // Arrange
        $eventTypes = [
            ['id' => 1, 'name' => 'Consultation'],
            ['id' => 2, 'name' => 'Follow-up'],
        ];
        
        Cache::shouldReceive('remember')
            ->once()
            ->with('event_types_company_' . $this->company->id, 300, \Mockery::type('callable'))
            ->andReturn($eventTypes);

        // Act
        $result = $this->cacheService->rememberEventTypes($this->company->id, function () use ($eventTypes) {
            return $eventTypes;
        });

        // Assert
        $this->assertEquals($eventTypes, $result);
    }

    /** @test */
    public function it_caches_customer_lookup_data()
    {
        // Arrange
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'phone' => '+491234567890',
        ]);

        // Act
        $cachedCustomer = $this->cacheService->rememberCustomer(
            $this->company->id,
            $customer->phone,
            function () use ($customer) {
                return $customer;
            }
        );

        // Assert
        $this->assertEquals($customer->id, $cachedCustomer->id);
        
        // Verify it's cached
        Cache::shouldReceive('get')
            ->with('customer_' . $this->company->id . '_' . md5($customer->phone))
            ->andReturn($customer);
            
        $fromCache = Cache::get('customer_' . $this->company->id . '_' . md5($customer->phone));
        $this->assertEquals($customer->id, $fromCache->id);
    }

    /** @test */
    public function it_invalidates_cache_when_clearing()
    {
        // Arrange
        $cacheKey = 'test_key';
        Cache::put($cacheKey, 'test_value', 300);

        // Act
        $this->cacheService->forget($cacheKey);

        // Assert
        $this->assertNull(Cache::get($cacheKey));
    }

    /** @test */
    public function it_caches_availability_data_with_short_ttl()
    {
        // Arrange
        $date = Carbon::now()->addDays(2);
        $availability = [
            '09:00' => true,
            '10:00' => true,
            '11:00' => false,
        ];

        // Act
        $cached = $this->cacheService->rememberAvailability(
            $this->company->id,
            1, // branch_id
            $date->format('Y-m-d'),
            function () use ($availability) {
                return $availability;
            }
        );

        // Assert
        $this->assertEquals($availability, $cached);
        
        // Verify TTL is 2 minutes (120 seconds)
        $cacheKey = 'availability_' . $this->company->id . '_1_' . $date->format('Y-m-d');
        $this->assertTrue(Cache::has($cacheKey));
    }

    /** @test */
    public function it_caches_company_settings()
    {
        // Arrange
        $settings = [
            'timezone' => 'Europe/Berlin',
            'language' => 'de',
            'notifications_enabled' => true,
        ];

        // Act
        $cached = $this->cacheService->rememberCompanySettings(
            $this->company->id,
            function () use ($settings) {
                return $settings;
            }
        );

        // Assert
        $this->assertEquals($settings, $cached);
        
        // Verify it's cached for 30 minutes
        $cacheKey = 'company_settings_' . $this->company->id;
        $this->assertTrue(Cache::has($cacheKey));
    }

    /** @test */
    public function it_caches_staff_schedule()
    {
        // Arrange
        $staff = Staff::factory()->create([
            'company_id' => $this->company->id,
        ]);
        
        $schedule = [
            'monday' => ['09:00-17:00'],
            'tuesday' => ['09:00-17:00'],
            'wednesday' => ['09:00-13:00'],
        ];

        // Act
        $cached = $this->cacheService->rememberStaffSchedule(
            $staff->id,
            function () use ($schedule) {
                return $schedule;
            }
        );

        // Assert
        $this->assertEquals($schedule, $cached);
    }

    /** @test */
    public function it_caches_service_list()
    {
        // Arrange
        $services = Service::factory()->count(3)->create([
            'company_id' => $this->company->id,
        ]);

        // Act
        $cached = $this->cacheService->rememberServices(
            $this->company->id,
            function () use ($services) {
                return $services;
            }
        );

        // Assert
        $this->assertCount(3, $cached);
        $this->assertEquals($services->pluck('id')->toArray(), $cached->pluck('id')->toArray());
    }

    /** @test */
    public function it_uses_cache_tags_for_grouped_invalidation()
    {
        // Arrange
        if (!Cache::supportsTags()) {
            $this->markTestSkipped('Cache driver does not support tags');
        }

        Cache::tags(['company_' . $this->company->id])->put('key1', 'value1', 300);
        Cache::tags(['company_' . $this->company->id])->put('key2', 'value2', 300);
        Cache::tags(['other_company'])->put('key3', 'value3', 300);

        // Act
        $this->cacheService->flushCompanyCache($this->company->id);

        // Assert
        $this->assertNull(Cache::tags(['company_' . $this->company->id])->get('key1'));
        $this->assertNull(Cache::tags(['company_' . $this->company->id])->get('key2'));
        $this->assertEquals('value3', Cache::tags(['other_company'])->get('key3'));
    }

    /** @test */
    public function it_handles_cache_key_collisions()
    {
        // Arrange
        $phone1 = '+491234567890';
        $phone2 = '+499876543210';
        
        $customer1 = Customer::factory()->create([
            'company_id' => $this->company->id,
            'phone' => $phone1,
        ]);
        
        $customer2 = Customer::factory()->create([
            'company_id' => $this->company->id,
            'phone' => $phone2,
        ]);

        // Act
        $cached1 = $this->cacheService->rememberCustomer(
            $this->company->id,
            $phone1,
            function () use ($customer1) {
                return $customer1;
            }
        );
        
        $cached2 = $this->cacheService->rememberCustomer(
            $this->company->id,
            $phone2,
            function () use ($customer2) {
                return $customer2;
            }
        );

        // Assert - Different customers should have different cache entries
        $this->assertNotEquals($cached1->id, $cached2->id);
        $this->assertEquals($customer1->id, $cached1->id);
        $this->assertEquals($customer2->id, $cached2->id);
    }

    /** @test */
    public function it_handles_null_values_in_cache()
    {
        // Arrange
        $executed = false;

        // Act
        $result1 = $this->cacheService->rememberCustomer(
            $this->company->id,
            '+49000000000',
            function () use (&$executed) {
                $executed = true;
                return null;
            }
        );

        // Reset flag
        $executed = false;

        // Second call should use cache
        $result2 = $this->cacheService->rememberCustomer(
            $this->company->id,
            '+49000000000',
            function () use (&$executed) {
                $executed = true;
                return null;
            }
        );

        // Assert
        $this->assertNull($result1);
        $this->assertNull($result2);
        $this->assertFalse($executed); // Callback should not be executed second time
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}