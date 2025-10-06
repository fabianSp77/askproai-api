<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Service;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\WorkingHour;
use App\Models\Company;
use App\Services\Booking\AvailabilityService;
use App\Services\Booking\BookingLockService;
use App\Livewire\PublicBooking\BookingWizard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Carbon\Carbon;
use Livewire\Livewire;

class PublicBookingTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected Service $service;
    protected Staff $staff;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear caches
        Cache::flush();
        RateLimiter::clear('booking-wizard:127.0.0.1:navigation');

        // Create test data
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->service = Service::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'is_active' => true,
            'is_online' => true,
            'duration_minutes' => 60,
            'price' => 50.00,
            'buffer_time_minutes' => 15,
        ]);

        $this->staff = Staff::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);

        // Attach service to staff
        $this->staff->services()->attach($this->service->id);

        // Create working hours for tomorrow
        $tomorrow = Carbon::tomorrow();
        WorkingHour::create([
            'staff_id' => $this->staff->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'day_of_week' => $tomorrow->dayOfWeek,
            'start' => '09:00',
            'end' => '18:00',
            'is_active' => true,
        ]);
    }

    /**
     * Test that guest can view available services
     */
    public function testGuestCanViewAvailableServices()
    {
        Livewire::test(BookingWizard::class)
            ->assertSee($this->service->name)
            ->assertSee(number_format($this->service->price, 2))
            ->assertSee($this->service->duration_minutes . ' Min.')
            ->assertSet('currentStep', 1)
            ->assertSet('totalSteps', 5);
    }

    /**
     * Test service selection shows correct staff
     */
    public function testServiceSelectionShowsCorrectStaff()
    {
        Livewire::test(BookingWizard::class)
            ->call('selectService', $this->service->id)
            ->assertSet('selectedServiceId', $this->service->id)
            ->assertSet('currentStep', 2)
            ->set('selectedBranchId', $this->branch->id)
            ->assertSee($this->staff->name);
    }

    /**
     * Test availability grid shows correct slots
     */
    public function testAvailabilityGridShowsCorrectSlots()
    {
        $tomorrow = Carbon::tomorrow();

        Livewire::test(BookingWizard::class)
            ->set('selectedServiceId', $this->service->id)
            ->set('selectedBranchId', $this->branch->id)
            ->set('selectedDate', $tomorrow->format('Y-m-d'))
            ->set('currentStep', 3)
            ->call('loadAvailableSlots')
            ->assertSee('09:00')
            ->assertSee('10:00')
            ->assertSee('11:00');
    }

    /**
     * Test double booking prevention
     */
    public function testDoubleBookingPrevention()
    {
        $tomorrow = Carbon::tomorrow()->setTime(10, 0);

        // Create existing appointment
        $existingAppointment = Appointment::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'service_id' => $this->service->id,
            'customer_id' => Customer::factory()->create()->id,
            'staff_id' => $this->staff->id,
            'starts_at' => $tomorrow,
            'ends_at' => $tomorrow->copy()->addMinutes(60),
            'status' => 'confirmed',
        ]);

        // Try to book same slot
        Livewire::test(BookingWizard::class)
            ->set('selectedServiceId', $this->service->id)
            ->set('selectedBranchId', $this->branch->id)
            ->set('selectedStaffId', $this->staff->id)
            ->set('selectedDate', $tomorrow->format('Y-m-d'))
            ->set('currentStep', 3)
            ->call('loadAvailableSlots')
            ->assertDontSee('10:00 - 11:00'); // Should not show occupied slot
    }

    /**
     * Test complete booking flow
     */
    public function testCompleteBookingFlow()
    {
        $tomorrow = Carbon::tomorrow();

        Livewire::test(BookingWizard::class)
            // Step 1: Select Service
            ->call('selectService', $this->service->id)
            ->assertSet('selectedServiceId', $this->service->id)
            ->assertSet('currentStep', 2)

            // Step 2: Select Branch & Staff
            ->set('selectedBranchId', $this->branch->id)
            ->set('anyStaff', true)
            ->call('nextStep')
            ->assertSet('currentStep', 3)

            // Step 3: Select Date & Time
            ->set('selectedDate', $tomorrow->format('Y-m-d'))
            ->call('loadAvailableSlots')
            ->call('selectTimeSlot', '10:00')
            ->assertSet('selectedTimeSlot', '10:00')
            ->assertSet('currentStep', 4)

            // Step 4: Enter Customer Data
            ->set('customerName', 'Test Customer')
            ->set('customerEmail', 'test@example.com')
            ->set('customerPhone', '+49123456789')
            ->set('customerNotes', 'Test booking')
            ->set('gdprConsent', true)

            // Step 5: Confirm Booking
            ->call('confirmBooking')
            ->assertSet('currentStep', 5)
            ->assertSee('Termin erfolgreich gebucht!')
            ->assertNotNull('confirmationCode');

        // Verify appointment was created
        $this->assertDatabaseHas('appointments', [
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'status' => 'pending',
            'source' => 'online',
        ]);

        // Verify customer was created
        $this->assertDatabaseHas('customers', [
            'email' => 'test@example.com',
            'name' => 'Test Customer',
            'journey_status' => 'lead',
            'acquisition_channel' => 'website',
        ]);
    }

    /**
     * Test validation errors
     */
    public function testValidationErrors()
    {
        Livewire::test(BookingWizard::class)
            ->set('currentStep', 4)
            ->set('customerName', '')
            ->set('customerEmail', 'invalid-email')
            ->set('gdprConsent', false)
            ->call('confirmBooking')
            ->assertHasErrors(['customerName', 'customerEmail', 'gdprConsent']);
    }

    /**
     * Test rate limiting
     */
    public function testRateLimiting()
    {
        $component = Livewire::test(BookingWizard::class);

        // Simulate many rapid requests
        for ($i = 0; $i < 35; $i++) {
            $component->call('nextStep');
        }

        $component->assertSee('Zu viele Anfragen');
    }

    /**
     * Test composite booking flow
     */
    public function testCompositeBookingFlow()
    {
        // Create composite service
        $compositeService = Service::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'is_active' => true,
            'is_online' => true,
            'composite' => true,
            'segments' => [
                [
                    'key' => 'segment_a',
                    'name' => 'Part 1',
                    'durationMin' => 45,
                    'gapAfterMin' => 30,
                    'gapAfterMax' => 60,
                    'preferSameStaff' => true,
                ],
                [
                    'key' => 'segment_b',
                    'name' => 'Part 2',
                    'durationMin' => 30,
                ],
            ],
        ]);

        $this->staff->services()->attach($compositeService->id);

        Livewire::test(BookingWizard::class)
            ->call('selectService', $compositeService->id)
            ->assertSet('selectedServiceId', $compositeService->id)
            ->assertSee('Part 1')
            ->assertSee('Part 2');
    }

    /**
     * Test timezone conversion
     */
    public function testTimezoneConversion()
    {
        $this->withHeaders(['X-Timezone' => 'America/New_York']);

        Livewire::test(BookingWizard::class)
            ->assertSet('timezone', 'America/New_York');
    }

    /**
     * Test booking with resource allocation
     */
    public function testBookingWithResourceAllocation()
    {
        // This would test resource booking once implemented
        $this->markTestIncomplete('Resource allocation not yet implemented');
    }

    /**
     * Test availability calculation performance
     */
    public function testAvailabilityCalculationPerformance()
    {
        $start = microtime(true);

        $availabilityService = app(AvailabilityService::class);
        $slots = $availabilityService->getAvailableSlots(
            $this->service->id,
            $this->branch->id,
            Carbon::tomorrow(),
            null,
            'Europe/Berlin'
        );

        $duration = (microtime(true) - $start) * 1000;

        // Should complete in under 50ms
        $this->assertLessThan(50, $duration);
        $this->assertNotEmpty($slots);
    }

    /**
     * Test concurrent booking attempts
     */
    public function testConcurrentBookingAttempts()
    {
        $lockService = app(BookingLockService::class);
        $tomorrow = Carbon::tomorrow()->setTime(10, 0);
        $endTime = $tomorrow->copy()->addHour();

        // First lock should succeed
        $lock1 = $lockService->acquireStaffLock(
            (string) $this->staff->id,
            $tomorrow,
            $endTime
        );
        $this->assertNotNull($lock1);

        // Second lock should fail (already locked)
        $lock2 = $lockService->acquireStaffLock(
            (string) $this->staff->id,
            $tomorrow,
            $endTime
        );
        $this->assertNull($lock2);

        // Release first lock
        $lock1->release();

        // Now third lock should succeed
        $lock3 = $lockService->acquireStaffLock(
            (string) $this->staff->id,
            $tomorrow,
            $endTime
        );
        $this->assertNotNull($lock3);
        $lock3->release();
    }

    /**
     * Test slot optimization suggestions
     */
    public function testSlotOptimizationSuggestions()
    {
        $availabilityService = app(AvailabilityService::class);

        $suggestions = $availabilityService->getOptimizedSlotSuggestions(
            $this->service->id,
            $this->branch->id,
            Carbon::tomorrow(),
            '14:00'
        );

        $this->assertNotEmpty($suggestions);
        $this->assertArrayHasKey('score', $suggestions->first());
        $this->assertArrayHasKey('recommendation_reason', $suggestions->first());
    }

    /**
     * Test availability heatmap generation
     */
    public function testAvailabilityHeatmapGeneration()
    {
        $availabilityService = app(AvailabilityService::class);

        $heatmap = $availabilityService->getAvailabilityHeatmap(
            $this->branch->id,
            Carbon::now()->startOfMonth()
        );

        $this->assertIsArray($heatmap);
        $this->assertNotEmpty($heatmap);

        $firstDay = array_values($heatmap)[0];
        $this->assertArrayHasKey('utilization', $firstDay);
        $this->assertArrayHasKey('level', $firstDay);
        $this->assertArrayHasKey('available', $firstDay);
    }

    /**
     * Test customer data persistence
     */
    public function testCustomerDataPersistence()
    {
        $email = 'returning@customer.com';

        // First booking
        Customer::create([
            'name' => 'Existing Customer',
            'email' => $email,
            'phone' => '+49123456789',
            'company_id' => $this->company->id,
        ]);

        // Second booking with same email
        Livewire::test(BookingWizard::class)
            ->set('selectedServiceId', $this->service->id)
            ->set('selectedBranchId', $this->branch->id)
            ->set('selectedDate', Carbon::tomorrow()->format('Y-m-d'))
            ->set('selectedTimeSlot', '10:00')
            ->set('currentStep', 4)
            ->set('customerName', 'Updated Name')
            ->set('customerEmail', $email)
            ->set('gdprConsent', true)
            ->call('confirmBooking');

        // Should use existing customer, not create duplicate
        $this->assertEquals(1, Customer::where('email', $email)->count());
    }

    /**
     * Test booking cancellation window
     */
    public function testBookingCancellationWindow()
    {
        // Test that bookings cannot be made with insufficient notice
        $tooSoon = Carbon::now()->addMinutes(30);

        Livewire::test(BookingWizard::class)
            ->set('selectedServiceId', $this->service->id)
            ->set('selectedBranchId', $this->branch->id)
            ->set('selectedDate', $tooSoon->format('Y-m-d'))
            ->set('currentStep', 3)
            ->call('loadAvailableSlots')
            ->assertDontSee($tooSoon->format('H:i'));
    }
}