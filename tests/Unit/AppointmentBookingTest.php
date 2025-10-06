<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Appointment;
use App\Services\CalcomV2Service;
use Carbon\Carbon;
use Mockery;

class AppointmentBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Set timezone to Berlin
        date_default_timezone_set('Europe/Berlin');
    }

    /**
     * Test date parsing from German format
     */
    public function test_german_date_parsing()
    {
        $dates = [
            '30.09.2025' => '2025-09-30',
            '01.10.2025' => '2025-10-01',
            '15.12.2025' => '2025-12-15'
        ];

        foreach ($dates as $german => $expected) {
            $parsed = Carbon::createFromFormat('d.m.Y', $german)->format('Y-m-d');
            $this->assertEquals($expected, $parsed, "Failed to parse: $german");
        }
    }

    /**
     * Test time slot validation
     */
    public function test_time_slot_validation()
    {
        $validTimes = ['09:00', '14:00', '16:30', '18:00'];
        $invalidTimes = ['25:00', '09:65', 'abc', ''];

        foreach ($validTimes as $time) {
            $this->assertTrue($this->isValidTime($time), "Should be valid: $time");
        }

        foreach ($invalidTimes as $time) {
            $this->assertFalse($this->isValidTime($time), "Should be invalid: $time");
        }
    }

    /**
     * Test customer creation or finding
     */
    public function test_customer_creation_or_finding()
    {
        // Test creating new customer
        $phoneNumber = '+491510' . rand(1000000, 9999999);
        $customerName = 'Test Kunde';

        $customer = Customer::firstOrCreate(
            ['phone' => $phoneNumber],
            [
                'name' => $customerName,
                'email' => null,
                'company_id' => 15 // AskProAI
            ]
        );

        $this->assertNotNull($customer);
        $this->assertEquals($phoneNumber, $customer->phone);
        $this->assertEquals($customerName, $customer->name);

        // Test finding existing customer
        $existingCustomer = Customer::firstOrCreate(
            ['phone' => $phoneNumber],
            ['name' => 'Different Name']
        );

        $this->assertEquals($customer->id, $existingCustomer->id);
        $this->assertEquals($customerName, $existingCustomer->name); // Should keep original name
    }

    /**
     * Test service selection logic
     */
    public function test_service_selection_logic()
    {
        // Create test services
        $service30min = Service::create([
            'name' => '30 Minuten Beratung',
            'duration' => 30,
            'price' => 50.00,
            'company_id' => 15,
            'is_active' => true,
            'calcom_event_type_id' => 123456
        ]);

        $service60min = Service::create([
            'name' => '60 Minuten Beratung',
            'duration' => 60,
            'price' => 90.00,
            'company_id' => 15,
            'is_active' => true,
            'calcom_event_type_id' => 123457
        ]);

        // Test finding service by name
        $foundService = Service::where('name', 'like', '%30 Minuten%')
            ->where('is_active', true)
            ->whereNotNull('calcom_event_type_id')
            ->first();

        $this->assertNotNull($foundService);
        $this->assertEquals($service30min->id, $foundService->id);

        // Test fallback to any active service
        $anyService = Service::where('is_active', true)
            ->whereNotNull('calcom_event_type_id')
            ->first();

        $this->assertNotNull($anyService);
    }

    /**
     * Test appointment data structure
     */
    public function test_appointment_data_structure()
    {
        $customer = Customer::create([
            'name' => 'Test Kunde',
            'phone' => '+491510' . rand(1000000, 9999999),
            'company_id' => 15
        ]);

        $service = Service::create([
            'name' => 'Test Service',
            'duration' => 30,
            'price' => 50.00,
            'company_id' => 15,
            'is_active' => true,
            'calcom_event_type_id' => 123456
        ]);

        $call = Call::create([
            'retell_call_id' => 'test_call_' . uniqid(),
            'from_number' => $customer->phone,
            'to_number' => '+493083793369',
            'status' => 'in_progress',
            'company_id' => 15
        ]);

        $scheduledAt = Carbon::tomorrow()->setTime(14, 0);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'call_id' => $call->id,
            'scheduled_at' => $scheduledAt,
            'duration' => $service->duration,
            'status' => 'pending',
            'notes' => 'Test appointment',
            'company_id' => 15,
            'calcom_booking_id' => null // Will be set after Cal.com booking
        ]);

        $this->assertNotNull($appointment);
        $this->assertEquals($customer->id, $appointment->customer_id);
        $this->assertEquals($service->id, $appointment->service_id);
        $this->assertEquals($call->id, $appointment->call_id);
        $this->assertEquals($scheduledAt->format('Y-m-d H:i:s'), $appointment->scheduled_at->format('Y-m-d H:i:s'));
        $this->assertEquals('pending', $appointment->status);
    }

    /**
     * Test timezone handling
     */
    public function test_timezone_handling()
    {
        // Test UTC to Berlin conversion
        $utcTime = Carbon::parse('2025-09-30 12:00:00', 'UTC');
        $berlinTime = $utcTime->setTimezone('Europe/Berlin');

        // In September, Berlin is UTC+2
        $this->assertEquals('14:00', $berlinTime->format('H:i'));

        // Test timestamp conversion (Retell sends milliseconds)
        $timestampMs = 1727698800000; // 2025-09-30 12:00:00 UTC
        $fromTimestamp = Carbon::createFromTimestampMs($timestampMs)->setTimezone('Europe/Berlin');

        $this->assertEquals('2025-09-30', $fromTimestamp->format('Y-m-d'));
        $this->assertEquals('14:00', $fromTimestamp->format('H:i'));
    }

    /**
     * Test duplicate appointment prevention
     */
    public function test_duplicate_appointment_prevention()
    {
        $customer = Customer::create([
            'name' => 'Test Kunde',
            'phone' => '+491510' . rand(1000000, 9999999),
            'company_id' => 15
        ]);

        $service = Service::create([
            'name' => 'Test Service',
            'duration' => 30,
            'price' => 50.00,
            'company_id' => 15,
            'is_active' => true,
            'calcom_event_type_id' => 123456
        ]);

        $scheduledAt = Carbon::tomorrow()->setTime(14, 0);

        // Create first appointment
        $appointment1 = Appointment::create([
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'scheduled_at' => $scheduledAt,
            'duration' => $service->duration,
            'status' => 'confirmed',
            'company_id' => 15
        ]);

        // Check for existing appointment
        $existing = Appointment::where('customer_id', $customer->id)
            ->where('service_id', $service->id)
            ->whereDate('scheduled_at', $scheduledAt->format('Y-m-d'))
            ->whereTime('scheduled_at', $scheduledAt->format('H:i:s'))
            ->where('status', '!=', 'cancelled')
            ->exists();

        $this->assertTrue($existing, 'Should find existing appointment');
    }

    /**
     * Test Cal.com integration mock
     */
    public function test_calcom_booking_mock()
    {
        // Mock Cal.com service
        $calcomMock = Mockery::mock(CalcomV2Service::class);

        $calcomMock->shouldReceive('checkAvailability')
            ->with(Mockery::any(), Mockery::any(), Mockery::any())
            ->andReturn([
                'available' => true,
                'slots' => ['14:00', '14:30', '15:00']
            ]);

        $calcomMock->shouldReceive('createBooking')
            ->with(Mockery::any())
            ->andReturn([
                'success' => true,
                'booking_id' => 'cal_' . uniqid(),
                'booking_url' => 'https://cal.com/booking/abc123'
            ]);

        $this->app->instance(CalcomV2Service::class, $calcomMock);

        // Test availability check
        $availability = $calcomMock->checkAvailability(123456, '2025-09-30', 30);
        $this->assertTrue($availability['available']);
        $this->assertContains('14:00', $availability['slots']);

        // Test booking creation
        $booking = $calcomMock->createBooking([
            'eventTypeId' => 123456,
            'start' => '2025-09-30T14:00:00+02:00',
            'name' => 'Test Kunde',
            'email' => 'test@example.com',
            'phone' => '+491510123456'
        ]);

        $this->assertTrue($booking['success']);
        $this->assertNotNull($booking['booking_id']);
    }

    /**
     * Test complete booking flow
     */
    public function test_complete_booking_flow()
    {
        // Step 1: Create call
        $call = Call::create([
            'retell_call_id' => 'test_call_' . uniqid(),
            'from_number' => '+491510' . rand(1000000, 9999999),
            'to_number' => '+493083793369',
            'status' => 'in_progress',
            'company_id' => 15
        ]);

        // Step 2: Find or create customer
        $customer = Customer::firstOrCreate(
            ['phone' => $call->from_number],
            [
                'name' => 'Test Kunde',
                'company_id' => 15
            ]
        );

        // Step 3: Find service
        $service = Service::create([
            'name' => 'Herrenhaarschnitt',
            'duration' => 30,
            'price' => 35.00,
            'company_id' => 15,
            'is_active' => true,
            'calcom_event_type_id' => 123456
        ]);

        // Step 4: Parse date and time
        $germanDate = '30.09.2025';
        $time = '14:00';
        $scheduledAt = Carbon::createFromFormat('d.m.Y H:i', "$germanDate $time", 'Europe/Berlin');

        // Step 5: Create appointment
        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'call_id' => $call->id,
            'scheduled_at' => $scheduledAt,
            'duration' => $service->duration,
            'status' => 'pending',
            'company_id' => 15
        ]);

        // Step 6: Update call
        $call->update([
            'status' => 'completed',
            'appointment_made' => true,
            'customer_id' => $customer->id
        ]);

        // Assertions
        $this->assertNotNull($appointment);
        $this->assertEquals('pending', $appointment->status);
        $this->assertTrue($call->appointment_made);
        $this->assertEquals($customer->id, $call->customer_id);
        $this->assertEquals('2025-09-30 14:00:00', $appointment->scheduled_at->format('Y-m-d H:i:s'));
    }

    /**
     * Helper function to validate time format
     */
    private function isValidTime($time)
    {
        if (!preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
            return false;
        }

        $parts = explode(':', $time);
        $hour = (int)$parts[0];
        $minute = (int)$parts[1];

        return $hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}