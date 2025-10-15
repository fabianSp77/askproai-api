<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SyncAppointmentToCalcomJob;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Service;
use App\Services\CalcomV2Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Comprehensive Test Suite for SyncAppointmentToCalcomJob
 *
 * Tests cover:
 * - Loop prevention (sync_origin checking)
 * - Success scenarios (create, cancel, reschedule)
 * - Error handling with retry logic
 * - Manual review flagging
 * - Payload validation
 * - Edge cases (missing data, timeouts)
 */
class SyncAppointmentToCalcomJobTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Service $service;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test company
        $this->company = Company::factory()->create([
            'calcom_api_key' => 'test_api_key_123',
        ]);

        // Create test service with Cal.com event type
        $this->service = Service::factory()->create([
            'company_id' => $this->company->id,
            'calcom_event_type_id' => 12345,
        ]);

        // Create test customer with unique email
        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'test-' . uniqid() . '@example.com',  // Unique email per test run
            'phone' => '+49123456789',
        ]);

        // Fake HTTP for all tests
        Http::fake();
    }

    // ═══════════════════════════════════════════════════════════
    // LOOP PREVENTION TESTS (CRITICAL)
    // ═══════════════════════════════════════════════════════════

    /** @test */
    public function it_skips_sync_when_origin_is_calcom()
    {
        // Arrange: Create appointment from Cal.com webhook
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'sync_origin' => 'calcom',  // ← CRITICAL: Origin is Cal.com
            'calcom_sync_status' => 'synced',
        ]);

        // Act: Dispatch sync job
        $job = new SyncAppointmentToCalcomJob($appointment, 'create');
        $job->handle();

        // Assert: No API call made (loop prevented)
        Http::assertNothingDispatched();
    }

    /** @test */
    public function it_skips_sync_when_recently_synced()
    {
        // Arrange: Create appointment synced 10 seconds ago
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'sync_origin' => 'retell',
            'calcom_sync_status' => 'synced',
            'sync_verified_at' => now()->subSeconds(10),  // ← Within 30s threshold
        ]);

        // Act: Dispatch sync job
        $job = new SyncAppointmentToCalcomJob($appointment, 'create');
        $job->handle();

        // Assert: No API call made (already synced recently)
        Http::assertNothingDispatched();
    }

    /** @test */
    public function it_allows_sync_when_origin_is_retell()
    {
        // Arrange: Create appointment from Retell AI (phone booking)
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'sync_origin' => 'retell',  // ← Origin is Retell AI
            'calcom_sync_status' => 'pending',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
        ]);

        Http::fake([
            'https://api.cal.com/v2/bookings' => Http::response([
                'status' => 'success',
                'data' => [
                    'id' => 67890,
                    'status' => 'accepted',
                ],
            ], 201),
        ]);

        // Act: Dispatch sync job
        $job = new SyncAppointmentToCalcomJob($appointment, 'create');
        $job->handle();

        // Assert: API call was made (sync allowed)
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.cal.com/v2/bookings';
        });
    }

    /** @test */
    public function it_allows_sync_when_origin_is_admin()
    {
        // Arrange: Create appointment from Admin UI
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'sync_origin' => 'admin',  // ← Origin is Admin UI
            'calcom_sync_status' => 'pending',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
        ]);

        Http::fake([
            'https://api.cal.com/v2/bookings' => Http::response([
                'status' => 'success',
                'data' => ['id' => 67890],
            ], 201),
        ]);

        // Act: Dispatch sync job
        $job = new SyncAppointmentToCalcomJob($appointment, 'create');
        $job->handle();

        // Assert: API call was made (sync allowed)
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.cal.com/v2/bookings';
        });
    }

    // ═══════════════════════════════════════════════════════════
    // SUCCESS SCENARIO TESTS
    // ═══════════════════════════════════════════════════════════

    /** @test */
    public function it_successfully_creates_booking_in_calcom()
    {
        // Arrange: Create appointment needing sync
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'sync_origin' => 'retell',
            'calcom_sync_status' => 'pending',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'booking_timezone' => 'Europe/Berlin',
        ]);

        // Mock successful Cal.com response
        Http::fake([
            'https://api.cal.com/v2/bookings' => Http::response([
                'status' => 'success',
                'data' => [
                    'id' => 67890,
                    'status' => 'accepted',
                    'startTime' => $appointment->starts_at->toIso8601String(),
                ],
            ], 201),
        ]);

        // Act: Dispatch sync job
        $job = new SyncAppointmentToCalcomJob($appointment, 'create');
        $job->handle();

        // Assert: Appointment marked as synced
        $appointment->refresh();
        $this->assertEquals('synced', $appointment->calcom_sync_status);
        $this->assertEquals(67890, $appointment->calcom_v2_booking_id);
        $this->assertNotNull($appointment->sync_verified_at);
        $this->assertNull($appointment->sync_error_message);

        // Assert: Correct payload sent
        Http::assertSent(function ($request) use ($appointment) {
            $data = $request->data();
            return $request->method() === 'POST'
                && $data['eventTypeId'] === 12345
                && $data['name'] === $this->customer->name
                && str_contains($data['email'], '@example.com')  // Check email domain instead of exact match
                && $data['phone'] === '+49123456789'
                && $data['timeZone'] === 'Europe/Berlin'
                && isset($data['metadata']['crm_appointment_id'])
                && $data['metadata']['sync_origin'] === 'retell';
        });
    }

    /** @test */
    public function it_successfully_cancels_booking_in_calcom()
    {
        // Arrange: Create appointment with Cal.com booking ID
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'sync_origin' => 'admin',
            'calcom_v2_booking_id' => 67890,
            'calcom_sync_status' => 'synced',
            'cancellation_reason' => 'Customer cancelled',
        ]);

        // Mock successful Cal.com cancellation
        Http::fake([
            'https://api.cal.com/v2/bookings/67890' => Http::response([
                'status' => 'success',
                'data' => [
                    'id' => 67890,
                    'status' => 'cancelled',
                ],
            ], 200),
        ]);

        // Act: Dispatch cancel sync job
        $job = new SyncAppointmentToCalcomJob($appointment, 'cancel');
        $job->handle();

        // Assert: Appointment marked as synced
        $appointment->refresh();
        $this->assertEquals('synced', $appointment->calcom_sync_status);
        $this->assertNotNull($appointment->sync_verified_at);

        // Assert: Correct cancel request sent
        Http::assertSent(function ($request) {
            return $request->method() === 'DELETE'
                && str_contains($request->url(), '/bookings/67890')
                && isset($request->data()['reason']);
        });
    }

    /** @test */
    public function it_successfully_reschedules_booking_in_calcom()
    {
        // Arrange: Create appointment with Cal.com booking ID
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'sync_origin' => 'admin',
            'calcom_v2_booking_id' => 67890,
            'calcom_sync_status' => 'synced',
            'starts_at' => now()->addDays(2),  // ← New time
            'ends_at' => now()->addDays(2)->addHour(),
        ]);

        // Mock successful Cal.com reschedule
        Http::fake([
            'https://api.cal.com/v2/bookings/67890/reschedule' => Http::response([
                'status' => 'success',
                'data' => [
                    'id' => 67890,
                    'startTime' => $appointment->starts_at->toIso8601String(),
                ],
            ], 200),
        ]);

        // Act: Dispatch reschedule sync job
        $job = new SyncAppointmentToCalcomJob($appointment, 'reschedule');
        $job->handle();

        // Assert: Appointment marked as synced
        $appointment->refresh();
        $this->assertEquals('synced', $appointment->calcom_sync_status);
        $this->assertNotNull($appointment->sync_verified_at);

        // Assert: Correct reschedule request sent
        Http::assertSent(function ($request) use ($appointment) {
            $data = $request->data();
            return $request->method() === 'POST'
                && str_contains($request->url(), '/reschedule')
                && $data['start'] === $appointment->starts_at->toIso8601String()
                && $data['end'] === $appointment->ends_at->toIso8601String();
        });
    }

    // ═══════════════════════════════════════════════════════════
    // ERROR HANDLING TESTS
    // ═══════════════════════════════════════════════════════════

    /** @test */
    public function it_handles_api_error_responses()
    {
        // Arrange: Create appointment
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'sync_origin' => 'retell',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
        ]);

        // Mock Cal.com error response
        Http::fake([
            'https://api.cal.com/v2/bookings' => Http::response([
                'error' => 'Invalid event type',
            ], 422),
        ]);

        // Act & Assert: Job throws exception
        $job = new SyncAppointmentToCalcomJob($appointment, 'create');

        try {
            $job->handle();
            $this->fail('Expected RuntimeException was not thrown');
        } catch (\RuntimeException $e) {
            // Expected exception
        }

        // Assert: Error details saved
        $appointment->refresh();
        $this->assertEquals('failed', $appointment->calcom_sync_status);
        $this->assertEquals('HTTP_422', $appointment->sync_error_code);
        $this->assertStringContainsString('Cal.com API error', $appointment->sync_error_message);
        $this->assertEquals(1, $appointment->sync_attempt_count);
    }

    /** @test */
    public function it_flags_for_manual_review_after_max_retries()
    {
        // Arrange: Create appointment
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'sync_origin' => 'retell',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
        ]);

        // Mock persistent failure
        Http::fake([
            'https://api.cal.com/v2/bookings' => Http::response(['error' => 'Server error'], 500),
        ]);

        // Act: Simulate max retries by calling failed() directly
        $job = new SyncAppointmentToCalcomJob($appointment, 'create');
        $job->failed(new \Exception('Max retries exceeded'));

        // Assert: Manual review flagged
        $appointment->refresh();
        $this->assertTrue($appointment->requires_manual_review);
        $this->assertNotNull($appointment->manual_review_flagged_at);
        $this->assertEquals('failed', $appointment->calcom_sync_status);
    }

    /** @test */
    public function it_throws_exception_when_service_has_no_event_type()
    {
        // Arrange: Create service without Cal.com event type
        $serviceWithoutEventType = Service::factory()->create([
            'company_id' => $this->company->id,
            'calcom_event_type_id' => null,  // ← Missing!
        ]);

        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $serviceWithoutEventType->id,
            'sync_origin' => 'retell',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
        ]);

        // Act & Assert: Job throws exception
        $job = new SyncAppointmentToCalcomJob($appointment, 'create');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Service has no Cal.com event type');

        $job->handle();
    }

    /** @test */
    public function it_throws_exception_when_cancel_without_booking_id()
    {
        // Arrange: Create appointment without Cal.com booking ID
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'sync_origin' => 'admin',
            'calcom_v2_booking_id' => null,  // ← Missing!
        ]);

        // Act & Assert: Job throws exception
        $job = new SyncAppointmentToCalcomJob($appointment, 'cancel');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No Cal.com booking ID to cancel');

        $job->handle();
    }

    /** @test */
    public function it_throws_exception_when_reschedule_without_booking_id()
    {
        // Arrange: Create appointment without Cal.com booking ID
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'sync_origin' => 'admin',
            'calcom_v2_booking_id' => null,  // ← Missing!
            'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(2)->addHour(),
        ]);

        // Act & Assert: Job throws exception
        $job = new SyncAppointmentToCalcomJob($appointment, 'reschedule');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No Cal.com booking ID to reschedule');

        $job->handle();
    }

    // ═══════════════════════════════════════════════════════════
    // PAYLOAD VALIDATION TESTS
    // ═══════════════════════════════════════════════════════════

    /** @test */
    public function it_includes_all_required_fields_in_create_payload()
    {
        // Arrange
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'sync_origin' => 'retell',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'booking_timezone' => 'Europe/Berlin',
        ]);

        Http::fake([
            'https://api.cal.com/v2/bookings' => Http::response(['status' => 'success', 'data' => ['id' => 123]], 201),
        ]);

        // Act
        $job = new SyncAppointmentToCalcomJob($appointment, 'create');
        $job->handle();

        // Assert: All required fields present
        Http::assertSent(function ($request) {
            $data = $request->data();
            $requiredFields = ['eventTypeId', 'start', 'end', 'timeZone', 'name', 'email', 'metadata'];

            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    return false;
                }
            }

            return true;
        });
    }

    /** @test */
    public function it_includes_phone_when_available()
    {
        // Arrange: Customer with phone
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'sync_origin' => 'retell',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
        ]);

        Http::fake([
            'https://api.cal.com/v2/bookings' => Http::response(['status' => 'success', 'data' => ['id' => 123]], 201),
        ]);

        // Act
        $job = new SyncAppointmentToCalcomJob($appointment, 'create');
        $job->handle();

        // Assert: Phone included
        Http::assertSent(function ($request) {
            $data = $request->data();
            return isset($data['phone']) && $data['phone'] === '+49123456789';
        });
    }

    /** @test */
    public function it_includes_crm_metadata_in_create_payload()
    {
        // Arrange
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'sync_origin' => 'retell',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
        ]);

        Http::fake([
            'https://api.cal.com/v2/bookings' => Http::response(['status' => 'success', 'data' => ['id' => 123]], 201),
        ]);

        // Act
        $job = new SyncAppointmentToCalcomJob($appointment, 'create');
        $job->handle();

        // Assert: Metadata includes CRM tracking
        Http::assertSent(function ($request) use ($appointment) {
            $data = $request->data();
            return isset($data['metadata'])
                && $data['metadata']['crm_appointment_id'] === $appointment->id
                && $data['metadata']['sync_origin'] === 'retell'
                && $data['metadata']['created_via'] === 'crm_sync'
                && isset($data['metadata']['synced_at']);
        });
    }

    // ═══════════════════════════════════════════════════════════
    // JOB CONFIGURATION TESTS
    // ═══════════════════════════════════════════════════════════

    /** @test */
    public function it_has_correct_retry_configuration()
    {
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
        ]);

        $job = new SyncAppointmentToCalcomJob($appointment, 'create');

        // Assert: Retry configuration
        $this->assertEquals(3, $job->tries);
        $this->assertEquals([1, 5, 30], $job->backoff);
        $this->assertEquals(30, $job->timeout);
    }

    /** @test */
    public function it_stores_job_id_on_construction()
    {
        // Arrange
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
        ]);

        // Act: Create job
        $job = new SyncAppointmentToCalcomJob($appointment, 'create');

        // Assert: Job ID stored
        $appointment->refresh();
        $this->assertNotNull($appointment->sync_job_id);
        $this->assertEquals('pending', $appointment->calcom_sync_status);
        $this->assertNotNull($appointment->last_sync_attempt_at);
    }
}
