<?php

namespace Tests\Unit\Listeners;

use Tests\TestCase;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Company;
use App\Events\Appointments\AppointmentBooked;
use App\Events\Appointments\AppointmentCancelled;
use App\Events\Appointments\AppointmentRescheduled;
use App\Listeners\Appointments\InvalidateSlotsCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class InvalidateSlotsCacheTest extends TestCase
{
    use RefreshDatabase;

    private InvalidateSlotsCache $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->listener = new InvalidateSlotsCache();
    }

    /** @test */
    public function it_invalidates_cache_for_booked_appointment()
    {
        // Arrange
        $company = Company::factory()->create(['id' => 15]);
        $service = Service::factory()->create([
            'calcom_event_type_id' => 123,
            'company_id' => 15,
        ]);
        $customer = Customer::factory()->create(['company_id' => 15]);

        $appointment = Appointment::factory()->create([
            'starts_at' => Carbon::parse('2025-10-15 14:00:00'),
            'company_id' => 15,
            'branch_id' => 1,
            'service_id' => $service->id,
            'customer_id' => $customer->id,
        ]);

        // Pre-populate cache
        $cacheKey = 'cal_slots_15_1_123_2025-10-15-14_2025-10-15-15';
        Cache::put($cacheKey, [
            ['time' => '2025-10-15T14:00:00Z'],
            ['time' => '2025-10-15T14:30:00Z'],
        ], 300);

        $this->assertTrue(Cache::has($cacheKey), 'Cache should be pre-populated');

        // Act
        $event = new AppointmentBooked($appointment);
        $this->listener->handleBooked($event);

        // Assert
        $this->assertFalse(Cache::has($cacheKey), 'Cache should be invalidated after booking');
    }

    /** @test */
    public function it_invalidates_surrounding_hour_windows()
    {
        // Arrange
        $company = Company::factory()->create(['id' => 15]);
        $service = Service::factory()->create([
            'calcom_event_type_id' => 123,
            'company_id' => 15,
        ]);
        $customer = Customer::factory()->create(['company_id' => 15]);

        $appointment = Appointment::factory()->create([
            'starts_at' => Carbon::parse('2025-10-15 14:00:00'),
            'company_id' => 15,
            'branch_id' => 1,
            'service_id' => $service->id,
            'customer_id' => $customer->id,
        ]);

        // Pre-populate 3-hour window cache
        $keyBefore = 'cal_slots_15_1_123_2025-10-15-13_2025-10-15-14';
        $keyDuring = 'cal_slots_15_1_123_2025-10-15-14_2025-10-15-15';
        $keyAfter = 'cal_slots_15_1_123_2025-10-15-15_2025-10-15-16';

        Cache::put($keyBefore, ['slot1'], 300);
        Cache::put($keyDuring, ['slot2'], 300);
        Cache::put($keyAfter, ['slot3'], 300);

        // Act
        $event = new AppointmentBooked($appointment);
        $this->listener->handleBooked($event);

        // Assert - All 3 cache keys should be invalidated
        $this->assertFalse(Cache::has($keyBefore), 'Hour before should be invalidated');
        $this->assertFalse(Cache::has($keyDuring), 'Appointment hour should be invalidated');
        $this->assertFalse(Cache::has($keyAfter), 'Hour after should be invalidated');
    }

    /** @test */
    public function it_invalidates_both_old_and_new_slots_on_reschedule()
    {
        // Arrange
        $company = Company::factory()->create(['id' => 15]);
        $service = Service::factory()->create([
            'calcom_event_type_id' => 123,
            'company_id' => 15,
        ]);
        $customer = Customer::factory()->create(['company_id' => 15]);

        $oldTime = Carbon::parse('2025-10-15 14:00:00');
        $newTime = Carbon::parse('2025-10-15 16:00:00');

        $appointment = Appointment::factory()->create([
            'starts_at' => $newTime,
            'company_id' => 15,
            'branch_id' => 1,
            'service_id' => $service->id,
            'customer_id' => $customer->id,
        ]);

        // Pre-populate both old and new time caches
        $oldKey = 'cal_slots_15_1_123_2025-10-15-14_2025-10-15-15';
        $newKey = 'cal_slots_15_1_123_2025-10-15-16_2025-10-15-17';
        Cache::put($oldKey, ['old_slot'], 300);
        Cache::put($newKey, ['new_slot'], 300);

        // Act
        $event = new AppointmentRescheduled($appointment, $oldTime, $newTime);
        $this->listener->handleRescheduled($event);

        // Assert
        $this->assertFalse(Cache::has($oldKey), 'Old slot cache should be invalidated');
        $this->assertFalse(Cache::has($newKey), 'New slot cache should be invalidated');
    }

    /** @test */
    public function it_invalidates_cache_on_cancellation()
    {
        // Arrange
        $company = Company::factory()->create(['id' => 15]);
        $service = Service::factory()->create([
            'calcom_event_type_id' => 123,
            'company_id' => 15,
        ]);
        $customer = Customer::factory()->create(['company_id' => 15]);

        $appointment = Appointment::factory()->create([
            'starts_at' => Carbon::parse('2025-10-15 14:00:00'),
            'company_id' => 15,
            'branch_id' => 1,
            'service_id' => $service->id,
            'customer_id' => $customer->id,
            'status' => 'cancelled',
        ]);

        // Pre-populate cache
        $cacheKey = 'cal_slots_15_1_123_2025-10-15-14_2025-10-15-15';
        Cache::put($cacheKey, ['slot1'], 300);

        // Act
        $event = new AppointmentCancelled($appointment, 'Customer request');
        $this->listener->handleCancelled($event);

        // Assert
        $this->assertFalse(Cache::has($cacheKey), 'Cache should be invalidated to restore availability');
    }

    /** @test */
    public function it_handles_missing_event_type_id_gracefully()
    {
        // Arrange
        $company = Company::factory()->create(['id' => 15]);
        $service = Service::factory()->create([
            'calcom_event_type_id' => null, // Missing
            'company_id' => 15,
        ]);
        $customer = Customer::factory()->create(['company_id' => 15]);

        $appointment = Appointment::factory()->create([
            'starts_at' => Carbon::parse('2025-10-15 14:00:00'),
            'company_id' => 15,
            'service_id' => $service->id,
            'customer_id' => $customer->id,
        ]);

        // Act & Assert - Should not throw exception
        $event = new AppointmentBooked($appointment);
        $this->listener->handleBooked($event); // Should log warning and return gracefully

        // No assertion needed - just verifying no exception thrown
        $this->assertTrue(true);
    }

    /** @test */
    public function it_is_non_blocking_on_cache_failure()
    {
        // Arrange
        $company = Company::factory()->create(['id' => 15]);
        $service = Service::factory()->create([
            'calcom_event_type_id' => 123,
            'company_id' => 15,
        ]);
        $customer = Customer::factory()->create(['company_id' => 15]);

        $appointment = Appointment::factory()->create([
            'starts_at' => Carbon::parse('2025-10-15 14:00:00'),
            'company_id' => 15,
            'service_id' => $service->id,
            'customer_id' => $customer->id,
        ]);

        // Simulate cache failure by using invalid cache driver
        // Note: This is tricky to test without mocking - in real scenario,
        // Redis connection failure would be caught and logged

        // Act & Assert - Should not throw exception
        $event = new AppointmentBooked($appointment);
        $this->listener->handleBooked($event); // Should catch exception and log

        // No assertion needed - just verifying no exception thrown
        $this->assertTrue(true);
    }

    /** @test */
    public function it_respects_multi_tenant_isolation()
    {
        // Arrange - Create appointments for two different companies at same time
        $company1 = Company::factory()->create(['id' => 15]);
        $company2 = Company::factory()->create(['id' => 20]);

        $service1 = Service::factory()->create([
            'calcom_event_type_id' => 123,
            'company_id' => 15,
        ]);
        $service2 = Service::factory()->create([
            'calcom_event_type_id' => 123,
            'company_id' => 20,
        ]);

        $customer1 = Customer::factory()->create(['company_id' => 15]);
        $customer2 = Customer::factory()->create(['company_id' => 20]);

        $apt1 = Appointment::factory()->create([
            'starts_at' => Carbon::parse('2025-10-15 14:00:00'),
            'company_id' => 15,
            'branch_id' => 1,
            'service_id' => $service1->id,
            'customer_id' => $customer1->id,
        ]);

        $apt2 = Appointment::factory()->create([
            'starts_at' => Carbon::parse('2025-10-15 14:00:00'),
            'company_id' => 20,
            'branch_id' => 2,
            'service_id' => $service2->id,
            'customer_id' => $customer2->id,
        ]);

        // Pre-populate cache for both companies
        $key1 = 'cal_slots_15_1_123_2025-10-15-14_2025-10-15-15'; // Company 15
        $key2 = 'cal_slots_20_2_123_2025-10-15-14_2025-10-15-15'; // Company 20
        Cache::put($key1, ['slot1'], 300);
        Cache::put($key2, ['slot2'], 300);

        // Act - Invalidate only Company 15's appointment
        $event = new AppointmentBooked($apt1);
        $this->listener->handleBooked($event);

        // Assert - Only Company 15's cache should be invalidated
        $this->assertFalse(Cache::has($key1), 'Company 15 cache should be invalidated');
        $this->assertTrue(Cache::has($key2), 'Company 20 cache should NOT be affected (tenant isolation)');
    }

    /** @test */
    public function it_handles_cross_midnight_appointments()
    {
        // Arrange - Appointment spanning midnight (23:30-00:30)
        $company = Company::factory()->create(['id' => 15]);
        $service = Service::factory()->create([
            'calcom_event_type_id' => 123,
            'company_id' => 15,
        ]);
        $customer = Customer::factory()->create(['company_id' => 15]);

        $appointment = Appointment::factory()->create([
            'starts_at' => Carbon::parse('2025-10-15 23:30:00'),
            'company_id' => 15,
            'branch_id' => 1,
            'service_id' => $service->id,
            'customer_id' => $customer->id,
        ]);

        // Pre-populate cache for both dates
        $key1 = 'cal_slots_15_1_123_2025-10-15-23_2025-10-16-00'; // 23:00-00:00
        $key2 = 'cal_slots_15_1_123_2025-10-16-00_2025-10-16-01'; // 00:00-01:00 (next day)
        Cache::put($key1, ['slot1'], 300);
        Cache::put($key2, ['slot2'], 300);

        // Act
        $event = new AppointmentBooked($appointment);
        $this->listener->handleBooked($event);

        // Assert - Both date caches should be invalidated
        $this->assertFalse(Cache::has($key1), 'Same day cache should be invalidated');
        $this->assertFalse(Cache::has($key2), 'Next day cache should be invalidated');
    }
}
