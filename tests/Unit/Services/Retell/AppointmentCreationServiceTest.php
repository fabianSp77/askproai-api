<?php

namespace Tests\Unit\Services\Retell;

use Tests\TestCase;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Branch;
use App\Models\PhoneNumber;
use App\Services\Retell\AppointmentCreationService;
use App\Services\Retell\CallLifecycleService;
use App\Services\Retell\ServiceSelectionService;
use App\Services\AppointmentAlternativeFinder;
use App\Services\NestedBookingManager;
use App\Services\CalcomService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AppointmentCreationServiceTest extends TestCase
{
    use RefreshDatabase;

    private AppointmentCreationService $service;
    private CallLifecycleService $callLifecycle;
    private ServiceSelectionService $serviceSelector;
    private AppointmentAlternativeFinder $alternativeFinder;
    private NestedBookingManager $nestedBookingManager;
    private CalcomService $calcomService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create service dependencies
        $this->callLifecycle = $this->createMock(CallLifecycleService::class);
        $this->serviceSelector = $this->createMock(ServiceSelectionService::class);
        $this->alternativeFinder = $this->createMock(AppointmentAlternativeFinder::class);
        $this->nestedBookingManager = $this->createMock(NestedBookingManager::class);
        $this->calcomService = $this->createMock(CalcomService::class);

        // Create service instance
        $this->service = new AppointmentCreationService(
            $this->callLifecycle,
            $this->serviceSelector,
            $this->alternativeFinder,
            $this->nestedBookingManager,
            $this->calcomService
        );
    }

    // ==========================================
    // Customer Management Tests (4 tests)
    // ==========================================

    /** @test */
    public function it_returns_existing_customer_when_already_linked_to_call()
    {
        $company = Company::create(['name' => 'Test Company', 'status' => 'active']);
        $branch = Branch::create(['name' => 'Main Branch', 'company_id' => $company->id, 'status' => 'active']);
        $customer = Customer::create([
            'name' => 'John Doe',
            'phone' => '+491234567890',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'status' => 'active'
        ]);

        $call = Call::create([
            'retell_call_id' => 'test_call_1',
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'status' => 'ongoing'
        ]);

        $result = $this->service->ensureCustomer($call);

        $this->assertNotNull($result);
        $this->assertEquals($customer->id, $result->id);
        $this->assertEquals('John Doe', $result->name);
    }

    /** @test */
    public function it_finds_existing_customer_by_phone_and_company()
    {
        $company = Company::create(['name' => 'Test Company', 'status' => 'active']);
        $branch = Branch::create(['name' => 'Main Branch', 'company_id' => $company->id, 'status' => 'active']);

        $existingCustomer = Customer::create([
            'name' => 'Jane Smith',
            'phone' => '+491234567890',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'status' => 'active'
        ]);

        $call = Call::create([
            'retell_call_id' => 'test_call_2',
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'status' => 'ongoing'
        ]);

        $this->callLifecycle->expects($this->once())
            ->method('linkCustomer')
            ->with($call, $existingCustomer)
            ->willReturn($call);

        $result = $this->service->ensureCustomer($call);

        $this->assertNotNull($result);
        $this->assertEquals($existingCustomer->id, $result->id);
        $this->assertEquals('Jane Smith', $result->name);
    }

    /** @test */
    public function it_creates_new_customer_from_analysis_data()
    {
        $company = Company::create(['name' => 'Test Company', 'status' => 'active']);
        $branch = Branch::create(['name' => 'Main Branch', 'company_id' => $company->id, 'status' => 'active']);

        $call = Call::create([
            'retell_call_id' => 'test_call_3',
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'status' => 'ongoing',
            'analysis' => [
                'custom_analysis_data' => [
                    'patient_full_name' => 'Max Mustermann'
                ]
            ]
        ]);

        $this->callLifecycle->expects($this->once())
            ->method('linkCustomer')
            ->willReturnCallback(function ($call, $customer) {
                return $call;
            });

        $result = $this->service->ensureCustomer($call);

        $this->assertNotNull($result);
        $this->assertEquals('Max Mustermann', $result->name);
        $this->assertEquals('+491234567890', $result->phone);
        $this->assertEquals($company->id, $result->company_id);
    }

    /** @test */
    public function it_creates_anonymous_customer_when_no_name_available()
    {
        $company = Company::create(['name' => 'Test Company', 'status' => 'active']);
        $branch = Branch::create(['name' => 'Main Branch', 'company_id' => $company->id, 'status' => 'active']);

        $call = Call::create([
            'retell_call_id' => 'test_call_4',
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'status' => 'ongoing'
        ]);

        $this->callLifecycle->expects($this->once())
            ->method('linkCustomer')
            ->willReturnCallback(function ($call, $customer) {
                return $call;
            });

        $result = $this->service->ensureCustomer($call);

        $this->assertNotNull($result);
        $this->assertStringContainsString('Anonym', $result->name);
        $this->assertEquals('+491234567890', $result->phone);
    }

    // ==========================================
    // Service Resolution Tests (3 tests)
    // ==========================================

    /** @test */
    public function it_finds_service_using_service_selector()
    {
        $company = Company::create(['name' => 'Test Company', 'status' => 'active']);
        $branch = Branch::create(['name' => 'Main Branch', 'company_id' => $company->id, 'status' => 'active']);

        $service = Service::create([
            'name' => 'Haircut',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'duration_minutes' => 45,
            'price' => 50.00,
            'calcom_event_type_id' => 123
        ]);

        $bookingDetails = [
            'service_name' => 'Haircut',
            'duration_minutes' => 45
        ];

        $this->serviceSelector->expects($this->once())
            ->method('selectService')
            ->with($bookingDetails, $company->id, $branch->id)
            ->willReturn($service);

        $result = $this->service->findService($bookingDetails, $company->id, $branch->id);

        $this->assertNotNull($result);
        $this->assertEquals('Haircut', $result->name);
        $this->assertEquals($branch->id, $result->branch_id);
    }

    /** @test */
    public function it_returns_null_when_service_not_found()
    {
        $bookingDetails = [
            'service_name' => 'Unknown Service',
            'duration_minutes' => 45
        ];

        $this->serviceSelector->expects($this->once())
            ->method('selectService')
            ->willReturn(null);

        $result = $this->service->findService($bookingDetails, 1, 1);

        $this->assertNull($result);
    }

    /** @test */
    public function it_finds_service_without_branch_filtering()
    {
        $company = Company::create(['name' => 'Test Company', 'status' => 'active']);

        $service = Service::create([
            'name' => 'Massage',
            'company_id' => $company->id,
            'duration_minutes' => 60,
            'price' => 80.00,
            'calcom_event_type_id' => 456
        ]);

        $bookingDetails = [
            'service_name' => 'Massage',
            'duration_minutes' => 60
        ];

        $this->serviceSelector->expects($this->once())
            ->method('selectService')
            ->with($bookingDetails, $company->id, null)
            ->willReturn($service);

        $result = $this->service->findService($bookingDetails, $company->id, null);

        $this->assertNotNull($result);
        $this->assertEquals('Massage', $result->name);
    }

    // ==========================================
    // Validation Tests (2 tests)
    // ==========================================

    /** @test */
    public function it_validates_booking_confidence_above_threshold()
    {
        $bookingDetails = [
            'confidence' => 85,
            'service_name' => 'Haircut',
            'starts_at' => '2025-10-01 10:00:00'
        ];

        $result = $this->service->validateConfidence($bookingDetails);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_rejects_booking_with_low_confidence()
    {
        $bookingDetails = [
            'confidence' => 45,
            'service_name' => 'Haircut',
            'starts_at' => '2025-10-01 10:00:00'
        ];

        $result = $this->service->validateConfidence($bookingDetails);

        $this->assertFalse($result);
    }

    // ==========================================
    // Local Record Creation Tests (3 tests)
    // ==========================================

    /** @test */
    public function it_creates_local_appointment_record_with_calcom_booking()
    {
        $company = Company::create(['name' => 'Test Company', 'status' => 'active']);
        $branch = Branch::create(['name' => 'Main Branch', 'company_id' => $company->id, 'status' => 'active']);

        $customer = Customer::create([
            'name' => 'John Doe',
            'phone' => '+491234567890',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'status' => 'active'
        ]);

        $service = Service::create([
            'name' => 'Haircut',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'duration_minutes' => 45,
            'price' => 50.00,
            'calcom_event_type_id' => 123
        ]);

        $call = Call::create([
            'retell_call_id' => 'test_call_5',
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'status' => 'completed'
        ]);

        $bookingDetails = [
            'starts_at' => '2025-10-01 10:00:00',
            'duration_minutes' => 45,
            'service_name' => 'Haircut',
            'confidence' => 85
        ];

        $this->callLifecycle->expects($this->once())
            ->method('linkAppointment')
            ->willReturnCallback(function ($call, $appointment) {
                return $call;
            });

        $appointment = $this->service->createLocalRecord(
            $customer,
            $service,
            $bookingDetails,
            'calcom_booking_123',
            $call
        );

        $this->assertNotNull($appointment);
        $this->assertEquals($customer->id, $appointment->customer_id);
        $this->assertEquals($service->id, $appointment->service_id);
        $this->assertEquals('calcom_booking_123', $appointment->external_id);
        $this->assertEquals('confirmed', $appointment->status);
        $this->assertEquals('2025-10-01 10:00:00', $appointment->starts_at->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_creates_local_appointment_record_without_calcom_booking()
    {
        $company = Company::create(['name' => 'Test Company', 'status' => 'active']);
        $branch = Branch::create(['name' => 'Main Branch', 'company_id' => $company->id, 'status' => 'active']);

        $customer = Customer::create([
            'name' => 'Jane Smith',
            'phone' => '+491234567890',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'status' => 'active'
        ]);

        $service = Service::create([
            'name' => 'Massage',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'duration_minutes' => 60,
            'price' => 80.00
        ]);

        $bookingDetails = [
            'starts_at' => '2025-10-01 14:00:00',
            'duration_minutes' => 60,
            'service_name' => 'Massage'
        ];

        $appointment = $this->service->createLocalRecord(
            $customer,
            $service,
            $bookingDetails,
            null,
            null
        );

        $this->assertNotNull($appointment);
        $this->assertEquals($customer->id, $appointment->customer_id);
        $this->assertEquals($service->id, $appointment->service_id);
        $this->assertNull($appointment->external_id);
        $this->assertEquals('pending', $appointment->status);
    }

    /** @test */
    public function it_stores_booking_metadata_in_appointment()
    {
        $company = Company::create(['name' => 'Test Company', 'status' => 'active']);
        $branch = Branch::create(['name' => 'Main Branch', 'company_id' => $company->id, 'status' => 'active']);

        $customer = Customer::create([
            'name' => 'Test Customer',
            'phone' => '+491234567890',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'status' => 'active'
        ]);

        $service = Service::create([
            'name' => 'Haircut',
            'company_id' => $company->id,
            'duration_minutes' => 45,
            'price' => 50.00
        ]);

        $bookingDetails = [
            'starts_at' => '2025-10-01 10:00:00',
            'duration_minutes' => 45,
            'service_name' => 'Haircut',
            'confidence' => 85,
            'alternative_used' => true,
            'alternative_type' => 'same_day_later'
        ];

        $appointment = $this->service->createLocalRecord(
            $customer,
            $service,
            $bookingDetails
        );

        $this->assertNotNull($appointment->booking_metadata);
        $this->assertEquals(85, $appointment->booking_metadata['confidence']);
        $this->assertTrue($appointment->booking_metadata['alternative_used']);
        $this->assertEquals('same_day_later', $appointment->booking_metadata['alternative_type']);
    }

    // ==========================================
    // Cal.com Integration Tests (4 tests)
    // ==========================================

    /** @test */
    public function it_books_appointment_in_calcom_successfully()
    {
        $company = Company::create(['name' => 'Test Company', 'status' => 'active']);
        $branch = Branch::create(['name' => 'Main Branch', 'company_id' => $company->id, 'status' => 'active']);

        $customer = Customer::create([
            'name' => 'John Doe',
            'phone' => '+491234567890',
            'email' => 'john@example.com',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'status' => 'active'
        ]);

        $service = Service::create([
            'name' => 'Haircut',
            'company_id' => $company->id,
            'duration_minutes' => 45,
            'price' => 50.00,
            'calcom_event_type_id' => 123
        ]);

        $startTime = Carbon::parse('2025-10-01 10:00:00');

        $this->calcomService->expects($this->once())
            ->method('createBooking')
            ->willReturn([
                'id' => 'calcom_booking_123',
                'status' => 'ACCEPTED',
                'data' => ['some' => 'data']
            ]);

        $result = $this->service->bookInCalcom($customer, $service, $startTime, 45);

        $this->assertNotNull($result);
        $this->assertEquals('calcom_booking_123', $result['booking_id']);
        $this->assertIsArray($result['booking_data']);
    }

    /** @test */
    public function it_returns_null_when_calcom_booking_fails()
    {
        $company = Company::create(['name' => 'Test Company', 'status' => 'active']);
        $branch = Branch::create(['name' => 'Main Branch', 'company_id' => $company->id, 'status' => 'active']);

        $customer = Customer::create([
            'name' => 'John Doe',
            'phone' => '+491234567890',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'status' => 'active'
        ]);

        $service = Service::create([
            'name' => 'Haircut',
            'company_id' => $company->id,
            'duration_minutes' => 45,
            'price' => 50.00,
            'calcom_event_type_id' => 123
        ]);

        $startTime = Carbon::parse('2025-10-01 10:00:00');

        $this->calcomService->expects($this->once())
            ->method('createBooking')
            ->willReturn(null);

        $result = $this->service->bookInCalcom($customer, $service, $startTime, 45);

        $this->assertNull($result);
    }

    /** @test */
    public function it_uses_customer_email_from_call_when_available()
    {
        $company = Company::create(['name' => 'Test Company', 'status' => 'active']);
        $branch = Branch::create(['name' => 'Main Branch', 'company_id' => $company->id, 'status' => 'active']);

        $customer = Customer::create([
            'name' => 'John Doe',
            'phone' => '+491234567890',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'status' => 'active'
        ]);

        $service = Service::create([
            'name' => 'Haircut',
            'company_id' => $company->id,
            'duration_minutes' => 45,
            'price' => 50.00,
            'calcom_event_type_id' => 123
        ]);

        $call = Call::create([
            'retell_call_id' => 'test_call_6',
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'company_id' => $company->id,
            'status' => 'completed',
            'analysis' => [
                'custom_analysis_data' => [
                    'patient_email' => 'john.extracted@example.com'
                ]
            ]
        ]);

        $startTime = Carbon::parse('2025-10-01 10:00:00');

        $this->calcomService->expects($this->once())
            ->method('createBooking')
            ->with($this->callback(function ($bookingData) {
                return $bookingData['responses']['email'] === 'john.extracted@example.com';
            }))
            ->willReturn([
                'id' => 'calcom_booking_123',
                'status' => 'ACCEPTED'
            ]);

        $this->service->bookInCalcom($customer, $service, $startTime, 45, $call);
    }

    /** @test */
    public function it_handles_calcom_exceptions_gracefully()
    {
        $company = Company::create(['name' => 'Test Company', 'status' => 'active']);
        $branch = Branch::create(['name' => 'Main Branch', 'company_id' => $company->id, 'status' => 'active']);

        $customer = Customer::create([
            'name' => 'John Doe',
            'phone' => '+491234567890',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'status' => 'active'
        ]);

        $service = Service::create([
            'name' => 'Haircut',
            'company_id' => $company->id,
            'duration_minutes' => 45,
            'price' => 50.00,
            'calcom_event_type_id' => 123
        ]);

        $startTime = Carbon::parse('2025-10-01 10:00:00');

        $this->calcomService->expects($this->once())
            ->method('createBooking')
            ->willThrowException(new \Exception('Cal.com API error'));

        $result = $this->service->bookInCalcom($customer, $service, $startTime, 45);

        $this->assertNull($result);
    }

    // ==========================================
    // Alternative Search Tests (4 tests)
    // ==========================================

    /** @test */
    public function it_finds_alternatives_using_alternative_finder()
    {
        $desiredTime = Carbon::parse('2025-10-01 10:00:00');
        $alternatives = [
            [
                'start_time' => '2025-10-01 11:00:00',
                'type' => 'same_day_later',
                'score' => 90
            ],
            [
                'start_time' => '2025-10-01 14:00:00',
                'type' => 'same_day_later',
                'score' => 85
            ]
        ];

        $this->alternativeFinder->expects($this->once())
            ->method('findAlternatives')
            ->with($desiredTime, 45, 123)
            ->willReturn($alternatives);

        $result = $this->service->findAlternatives($desiredTime, 45, 123);

        $this->assertCount(2, $result);
        $this->assertEquals('same_day_later', $result[0]['type']);
        $this->assertEquals(90, $result[0]['score']);
    }

    /** @test */
    public function it_returns_empty_array_when_no_alternatives_found()
    {
        $desiredTime = Carbon::parse('2025-10-01 10:00:00');

        $this->alternativeFinder->expects($this->once())
            ->method('findAlternatives')
            ->willReturn([]);

        $result = $this->service->findAlternatives($desiredTime, 45, 123);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /** @test */
    public function it_books_first_available_alternative()
    {
        $company = Company::create(['name' => 'Test Company', 'status' => 'active']);
        $branch = Branch::create(['name' => 'Main Branch', 'company_id' => $company->id, 'status' => 'active']);

        $customer = Customer::create([
            'name' => 'John Doe',
            'phone' => '+491234567890',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'status' => 'active'
        ]);

        $service = Service::create([
            'name' => 'Haircut',
            'company_id' => $company->id,
            'duration_minutes' => 45,
            'price' => 50.00,
            'calcom_event_type_id' => 123
        ]);

        $call = Call::create([
            'retell_call_id' => 'test_call_7',
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'company_id' => $company->id,
            'status' => 'completed'
        ]);

        $alternatives = [
            [
                'start_time' => '2025-10-01 11:00:00',
                'type' => 'same_day_later',
                'score' => 90
            ]
        ];

        $bookingDetails = [
            'starts_at' => '2025-10-01 10:00:00',
            'duration_minutes' => 45
        ];

        $this->calcomService->expects($this->once())
            ->method('createBooking')
            ->willReturn([
                'id' => 'calcom_booking_alt_123',
                'status' => 'ACCEPTED'
            ]);

        $this->callLifecycle->expects($this->once())
            ->method('trackBooking')
            ->with($call, $this->anything(), true, 'calcom_booking_alt_123');

        $result = $this->service->bookAlternative(
            $alternatives,
            $customer,
            $service,
            45,
            $call,
            $bookingDetails
        );

        $this->assertNotNull($result);
        $this->assertEquals('calcom_booking_alt_123', $result['booking_id']);
        $this->assertInstanceOf(Carbon::class, $result['alternative_time']);
        $this->assertEquals('same_day_later', $result['alternative_type']);
        $this->assertTrue($bookingDetails['alternative_used']);
    }

    /** @test */
    public function it_tries_multiple_alternatives_until_success()
    {
        $company = Company::create(['name' => 'Test Company', 'status' => 'active']);
        $branch = Branch::create(['name' => 'Main Branch', 'company_id' => $company->id, 'status' => 'active']);

        $customer = Customer::create([
            'name' => 'John Doe',
            'phone' => '+491234567890',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'status' => 'active'
        ]);

        $service = Service::create([
            'name' => 'Haircut',
            'company_id' => $company->id,
            'duration_minutes' => 45,
            'price' => 50.00,
            'calcom_event_type_id' => 123
        ]);

        $call = Call::create([
            'retell_call_id' => 'test_call_8',
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'company_id' => $company->id,
            'status' => 'completed'
        ]);

        $alternatives = [
            [
                'start_time' => '2025-10-01 11:00:00',
                'type' => 'same_day_later',
                'score' => 90
            ],
            [
                'start_time' => '2025-10-01 14:00:00',
                'type' => 'same_day_later',
                'score' => 85
            ]
        ];

        $bookingDetails = [
            'starts_at' => '2025-10-01 10:00:00',
            'duration_minutes' => 45
        ];

        // First attempt fails, second succeeds
        $this->calcomService->expects($this->exactly(2))
            ->method('createBooking')
            ->willReturnOnConsecutiveCalls(
                null, // First attempt fails
                ['id' => 'calcom_booking_alt_456', 'status' => 'ACCEPTED'] // Second succeeds
            );

        $result = $this->service->bookAlternative(
            $alternatives,
            $customer,
            $service,
            45,
            $call,
            $bookingDetails
        );

        $this->assertNotNull($result);
        $this->assertEquals('calcom_booking_alt_456', $result['booking_id']);
    }

    // ==========================================
    // Nested Booking Tests (3 tests)
    // ==========================================

    /** @test */
    public function it_supports_nesting_for_coloring_service()
    {
        $result = $this->service->supportsNesting('coloring');
        $this->assertTrue($result);
    }

    /** @test */
    public function it_does_not_support_nesting_for_regular_services()
    {
        $result = $this->service->supportsNesting('haircut');
        $this->assertFalse($result);
    }

    /** @test */
    public function it_determines_service_type_correctly()
    {
        $this->assertEquals('coloring', $this->service->determineServiceType('Haarfärbung'));
        $this->assertEquals('coloring', $this->service->determineServiceType('Hair Coloring'));
        $this->assertEquals('perm', $this->service->determineServiceType('Dauerwelle'));
        $this->assertEquals('highlights', $this->service->determineServiceType('Strähnchen'));
        $this->assertEquals('regular', $this->service->determineServiceType('Haircut'));
    }

    // ==========================================
    // Full Flow Tests (5 tests)
    // ==========================================

    /** @test */
    public function it_creates_appointment_from_call_with_successful_booking()
    {
        $company = Company::create(['name' => 'Test Company', 'status' => 'active']);
        $branch = Branch::create(['name' => 'Main Branch', 'company_id' => $company->id, 'status' => 'active']);

        $customer = Customer::create([
            'name' => 'John Doe',
            'phone' => '+491234567890',
            'email' => 'john@example.com',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'status' => 'active'
        ]);

        $service = Service::create([
            'name' => 'Haircut',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'duration_minutes' => 45,
            'price' => 50.00,
            'calcom_event_type_id' => 123
        ]);

        $call = Call::create([
            'retell_call_id' => 'test_call_9',
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'status' => 'completed'
        ]);

        $bookingDetails = [
            'service_name' => 'Haircut',
            'starts_at' => '2025-10-01 10:00:00',
            'duration_minutes' => 45,
            'confidence' => 85
        ];

        $this->serviceSelector->expects($this->once())
            ->method('selectService')
            ->willReturn($service);

        $this->calcomService->expects($this->once())
            ->method('createBooking')
            ->willReturn([
                'id' => 'calcom_booking_123',
                'status' => 'ACCEPTED'
            ]);

        $this->callLifecycle->expects($this->once())
            ->method('trackBooking')
            ->with($call, $bookingDetails, true, 'calcom_booking_123');

        $this->callLifecycle->expects($this->once())
            ->method('linkAppointment')
            ->willReturnCallback(function ($call, $appointment) {
                return $call;
            });

        $appointment = $this->service->createFromCall($call, $bookingDetails);

        $this->assertNotNull($appointment);
        $this->assertEquals($customer->id, $appointment->customer_id);
        $this->assertEquals($service->id, $appointment->service_id);
        $this->assertEquals('calcom_booking_123', $appointment->external_id);
    }

    /** @test */
    public function it_returns_null_when_confidence_too_low()
    {
        $company = Company::create(['name' => 'Test Company', 'status' => 'active']);
        $branch = Branch::create(['name' => 'Main Branch', 'company_id' => $company->id, 'status' => 'active']);

        $call = Call::create([
            'retell_call_id' => 'test_call_10',
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'status' => 'completed'
        ]);

        $bookingDetails = [
            'service_name' => 'Haircut',
            'starts_at' => '2025-10-01 10:00:00',
            'confidence' => 40 // Too low
        ];

        $this->callLifecycle->expects($this->once())
            ->method('trackFailedBooking')
            ->with($call, $bookingDetails, 'Low confidence extraction - needs manual review');

        $appointment = $this->service->createFromCall($call, $bookingDetails);

        $this->assertNull($appointment);
    }

    /** @test */
    public function it_uses_alternatives_when_desired_time_unavailable()
    {
        $company = Company::create(['name' => 'Test Company', 'status' => 'active']);
        $branch = Branch::create(['name' => 'Main Branch', 'company_id' => $company->id, 'status' => 'active']);

        $customer = Customer::create([
            'name' => 'Jane Smith',
            'phone' => '+491234567890',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'status' => 'active'
        ]);

        $service = Service::create([
            'name' => 'Massage',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'duration_minutes' => 60,
            'price' => 80.00,
            'calcom_event_type_id' => 456
        ]);

        $call = Call::create([
            'retell_call_id' => 'test_call_11',
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'status' => 'completed'
        ]);

        $bookingDetails = [
            'service_name' => 'Massage',
            'starts_at' => '2025-10-01 14:00:00',
            'duration_minutes' => 60,
            'confidence' => 85
        ];

        $alternatives = [
            [
                'start_time' => '2025-10-01 15:00:00',
                'type' => 'same_day_later',
                'score' => 90
            ]
        ];

        $this->serviceSelector->expects($this->once())
            ->method('selectService')
            ->willReturn($service);

        // First booking attempt fails
        $this->calcomService->expects($this->exactly(2))
            ->method('createBooking')
            ->willReturnOnConsecutiveCalls(
                null, // Desired time fails
                ['id' => 'calcom_alt_789', 'status' => 'ACCEPTED'] // Alternative succeeds
            );

        $this->alternativeFinder->expects($this->once())
            ->method('findAlternatives')
            ->willReturn($alternatives);

        $this->callLifecycle->expects($this->once())
            ->method('trackBooking')
            ->with($call, $this->anything(), true, 'calcom_alt_789');

        $this->callLifecycle->expects($this->once())
            ->method('linkAppointment')
            ->willReturnCallback(function ($call, $appointment) {
                return $call;
            });

        $appointment = $this->service->createFromCall($call, $bookingDetails);

        $this->assertNotNull($appointment);
        $this->assertEquals('calcom_alt_789', $appointment->external_id);
        $this->assertTrue($appointment->booking_metadata['alternative_used']);
    }

    /** @test */
    public function it_returns_null_from_create_from_call_when_service_not_found()
    {
        $company = Company::create(['name' => 'Test Company', 'status' => 'active']);
        $branch = Branch::create(['name' => 'Main Branch', 'company_id' => $company->id, 'status' => 'active']);

        $customer = Customer::create([
            'name' => 'John Doe',
            'phone' => '+491234567890',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'status' => 'active'
        ]);

        $call = Call::create([
            'retell_call_id' => 'test_call_12',
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'status' => 'completed'
        ]);

        $bookingDetails = [
            'service_name' => 'Unknown Service',
            'starts_at' => '2025-10-01 10:00:00',
            'confidence' => 85
        ];

        $this->serviceSelector->expects($this->once())
            ->method('selectService')
            ->willReturn(null);

        $this->callLifecycle->expects($this->once())
            ->method('trackFailedBooking')
            ->with($call, $bookingDetails, 'service_not_found');

        $appointment = $this->service->createFromCall($call, $bookingDetails);

        $this->assertNull($appointment);
    }

    /** @test */
    public function it_returns_null_when_all_alternatives_fail()
    {
        $company = Company::create(['name' => 'Test Company', 'status' => 'active']);
        $branch = Branch::create(['name' => 'Main Branch', 'company_id' => $company->id, 'status' => 'active']);

        $customer = Customer::create([
            'name' => 'Test Customer',
            'phone' => '+491234567890',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'status' => 'active'
        ]);

        $service = Service::create([
            'name' => 'Haircut',
            'company_id' => $company->id,
            'duration_minutes' => 45,
            'price' => 50.00,
            'calcom_event_type_id' => 123
        ]);

        $call = Call::create([
            'retell_call_id' => 'test_call_13',
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'status' => 'completed'
        ]);

        $bookingDetails = [
            'service_name' => 'Haircut',
            'starts_at' => '2025-10-01 10:00:00',
            'duration_minutes' => 45,
            'confidence' => 85
        ];

        $alternatives = [
            ['start_time' => '2025-10-01 11:00:00', 'type' => 'same_day_later', 'score' => 90],
            ['start_time' => '2025-10-01 14:00:00', 'type' => 'same_day_later', 'score' => 85]
        ];

        $this->serviceSelector->expects($this->once())
            ->method('selectService')
            ->willReturn($service);

        // All booking attempts fail
        $this->calcomService->expects($this->exactly(3))
            ->method('createBooking')
            ->willReturn(null);

        $this->alternativeFinder->expects($this->once())
            ->method('findAlternatives')
            ->willReturn($alternatives);

        $this->callLifecycle->expects($this->once())
            ->method('trackFailedBooking')
            ->with($call, $bookingDetails, 'all_alternatives_failed');

        $appointment = $this->service->createFromCall($call, $bookingDetails);

        $this->assertNull($appointment);
    }
}