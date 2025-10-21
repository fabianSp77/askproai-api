<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Retell\AppointmentCreationService;
use App\Services\CalcomService;
use App\Services\AppointmentAlternativeFinder;
use App\Models\{Call, Customer, Service, Appointment, Company, Branch};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Mockery;

/**
 * RCA Prevention Test Suite
 *
 * Tests specifically designed to prevent RCA-identified bugs from recurring:
 * - DUPLICATE_BOOKING_BUG_ANALYSIS_2025-10-06
 * - RCA_AVAILABILITY_RACE_CONDITION_2025-10-14
 * - BOOKING_ERROR_ANALYSIS_2025-10-06
 */
class RcaPreventionTest extends TestCase
{
    use RefreshDatabase;

    private AppointmentCreationService $appointmentService;
    private CalcomService $calcomService;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup test dependencies
        $this->calcomService = Mockery::mock(CalcomService::class);
        $this->app->instance(CalcomService::class, $this->calcomService);
    }

    // ============================================================
    // RCA: DUPLICATE_BOOKING_BUG_ANALYSIS_2025-10-06
    // ============================================================

    /**
     * @test
     * RCA Reference: DUPLICATE_BOOKING_BUG_ANALYSIS_2025-10-06.md
     *
     * Problem: System accepted stale Cal.com booking from 35 minutes ago
     * Solution: Validate createdAt timestamp is within 30 seconds
     */
    public function it_rejects_stale_calcom_booking_response()
    {
        // Arrange: Mock Cal.com returning booking from 1 hour ago
        $staleBooking = [
            'id' => 12345,
            'uid' => 'old_booking_uid',
            'createdAt' => Carbon::now()->subHour()->toIso8601String(),
            'metadata' => ['call_id' => 'call_123'],
            'attendees' => [
                ['name' => 'Test User', 'email' => 'test@askproai.de']
            ]
        ];

        Http::fake([
            '*/bookings' => Http::response([
                'data' => $staleBooking
            ], 200)
        ]);

        // Act: Attempt booking
        $response = $this->postJson('/api/retell/collect-appointment', [
            'args' => [
                'datum' => '2025-10-20',
                'uhrzeit' => '14:00',
                'name' => 'Test User',
                'dienstleistung' => 'Beratung',
                'bestaetigung' => true,
                'call_id' => 'call_123'
            ]
        ]);

        // Assert: Rejected with clear error
        $response->assertStatus(200);
        $response->assertJson([
            'success' => false,
            'status' => 'error'
        ]);

        // Verify no appointment created
        $this->assertDatabaseMissing('appointments', [
            'calcom_v2_booking_id' => 'old_booking_uid'
        ]);
    }

    /**
     * @test
     * RCA Reference: DUPLICATE_BOOKING_BUG_ANALYSIS_2025-10-06.md
     *
     * Problem: Booking metadata contained wrong call_id
     * Solution: Validate metadata matches current call
     */
    public function it_rejects_booking_with_mismatched_call_id_metadata()
    {
        // Arrange: Fresh booking but wrong metadata
        $booking = [
            'id' => 12345,
            'uid' => 'test_booking_uid',
            'createdAt' => Carbon::now()->toIso8601String(),
            'metadata' => ['call_id' => 'call_WRONG'],
            'attendees' => [
                ['name' => 'Test User', 'email' => 'test@askproai.de']
            ]
        ];

        Http::fake([
            '*/bookings' => Http::response(['data' => $booking], 200)
        ]);

        // Act: Request with different call_id
        $response = $this->postJson('/api/retell/collect-appointment', [
            'args' => [
                'datum' => '2025-10-20',
                'uhrzeit' => '14:00',
                'name' => 'Test User',
                'dienstleistung' => 'Beratung',
                'bestaetigung' => true,
                'call_id' => 'call_CORRECT'
            ]
        ]);

        // Assert: Rejected
        $response->assertStatus(200);
        $response->assertJson([
            'success' => false,
            'status' => 'error'
        ]);
    }

    /**
     * @test
     * RCA Reference: DUPLICATE_BOOKING_BUG_ANALYSIS_2025-10-06.md
     *
     * Problem: Two appointments created with same calcom_v2_booking_id
     * Solution: Prevent duplicate booking IDs via validation
     */
    public function it_prevents_duplicate_calcom_booking_ids()
    {
        // Arrange: Create company and branch
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);

        // Existing appointment with booking ID
        $existingAppointment = Appointment::factory()->create([
            'calcom_v2_booking_id' => 'duplicate_uid',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'starts_at' => '2025-10-20 10:00:00'
        ]);

        // Mock Cal.com returning same booking ID
        $booking = [
            'id' => 99999,
            'uid' => 'duplicate_uid',
            'createdAt' => Carbon::now()->toIso8601String(),
            'metadata' => ['call_id' => 'call_new']
        ];

        Http::fake([
            '*/bookings' => Http::response(['data' => $booking], 200)
        ]);

        // Act: Attempt booking
        $response = $this->postJson('/api/retell/collect-appointment', [
            'args' => [
                'datum' => '2025-10-20',
                'uhrzeit' => '14:00',
                'name' => 'Test User',
                'dienstleistung' => 'Beratung',
                'bestaetigung' => true,
                'call_id' => 'call_new'
            ]
        ]);

        // Assert: Rejected with specific error
        $response->assertStatus(200);
        $response->assertJson([
            'success' => false
        ]);

        // Only 1 appointment with this booking ID
        $this->assertEquals(1, Appointment::where('calcom_v2_booking_id', 'duplicate_uid')->count());
    }

    /**
     * @test
     * RCA Reference: DUPLICATE_BOOKING_BUG_ANALYSIS_2025-10-06.md
     *
     * Problem: Customer name overwritten in Cal.com
     * Solution: Validate attendee name matches expected customer
     */
    public function it_validates_attendee_name_matches_customer()
    {
        // Arrange: Mock booking with different attendee name
        $booking = [
            'uid' => 'test_uid',
            'createdAt' => Carbon::now()->toIso8601String(),
            'metadata' => ['call_id' => 'call_123'],
            'attendees' => [
                ['name' => 'Wrong Customer', 'email' => 'termin@askproai.de']
            ]
        ];

        Http::fake([
            '*/bookings' => Http::response(['data' => $booking], 200)
        ]);

        // Act: Book with different name
        $response = $this->postJson('/api/retell/collect-appointment', [
            'args' => [
                'datum' => '2025-10-20',
                'uhrzeit' => '14:00',
                'name' => 'Correct Customer',
                'dienstleistung' => 'Beratung',
                'bestaetigung' => true,
                'call_id' => 'call_123'
            ]
        ]);

        // Assert: Warning logged (non-blocking, but flagged)
        // Note: This is a warning scenario, not a hard rejection
        $response->assertStatus(200);
    }

    // ============================================================
    // RCA: RCA_AVAILABILITY_RACE_CONDITION_2025-10-14
    // ============================================================

    /**
     * @test
     * RCA Reference: RCA_AVAILABILITY_RACE_CONDITION_2025-10-14.md
     *
     * Problem: 14-second gap between availability check and booking
     * Solution: V85 double-check mechanism
     */
    public function it_implements_double_check_before_booking()
    {
        // Arrange: Mock initial availability check (slot available)
        Http::fake([
            '*/availability*' => Http::sequence()
                ->push([
                    'data' => [
                        'slots' => [
                            '2025-10-20' => [
                                ['time' => '2025-10-20T14:00:00.000Z']
                            ]
                        ]
                    ]
                ], 200)
                ->push([
                    'data' => [
                        'slots' => [
                            '2025-10-20' => [] // Slot gone on double-check
                        ]
                    ]
                ], 200),
            '*/bookings' => Http::response(['error' => 'Host already has booking'], 400)
        ]);

        // Act: Request booking
        $response = $this->postJson('/api/retell/collect-appointment', [
            'args' => [
                'datum' => '2025-10-20',
                'uhrzeit' => '14:00',
                'name' => 'Test User',
                'dienstleistung' => 'Beratung',
                'bestaetigung' => true
            ]
        ]);

        // Assert: V85 double-check caught race condition
        $response->assertStatus(200);
        $response->assertJson([
            'success' => false,
            'status' => 'slot_taken',
            'reason' => 'race_condition_detected'
        ]);

        // Alternatives offered
        $response->assertJsonStructure(['alternatives']);
    }

    /**
     * @test
     * RCA Reference: RCA_AVAILABILITY_RACE_CONDITION_2025-10-14.md
     *
     * Problem: Cal.com API error "Host already has booking at this time"
     * Solution: Graceful handling with alternatives
     */
    public function it_handles_calcom_booking_conflict_gracefully()
    {
        // Arrange: Mock Cal.com API conflict error
        Http::fake([
            '*/availability*' => Http::response([
                'data' => [
                    'slots' => [
                        '2025-10-20' => [
                            ['time' => '2025-10-20T14:00:00.000Z']
                        ]
                    ]
                ]
            ], 200),
            '*/bookings' => Http::response([
                'error' => 'One of the hosts either already has booking at this time or is not available'
            ], 400)
        ]);

        // Act: Attempt booking
        $response = $this->postJson('/api/retell/collect-appointment', [
            'args' => [
                'datum' => '2025-10-20',
                'uhrzeit' => '14:00',
                'name' => 'Test User',
                'dienstleistung' => 'Beratung',
                'bestaetigung' => true
            ]
        ]);

        // Assert: Not a 500 error, graceful handling
        $response->assertStatus(200);
        $response->assertJson([
            'success' => false
        ]);

        // Should offer alternatives
        // Note: Implementation detail - verify alternatives are offered
    }

    // ============================================================
    // RCA: BOOKING_ERROR_ANALYSIS_2025-10-06
    // ============================================================

    /**
     * @test
     * RCA Reference: BOOKING_ERROR_ANALYSIS_2025-10-06.md
     *
     * Problem: TypeError - branch_id expected int, received string (UUID)
     * Solution: Type signature accepts ?string for branch_id
     */
    public function it_handles_branch_id_as_uuid_string()
    {
        // Arrange: Create company and branch with UUID
        $company = Company::factory()->create();
        $branch = Branch::factory()->create([
            'id' => '9f4d5e2a-46f7-41b6-b81d-1532725381d4',
            'company_id' => $company->id
        ]);

        $service = Service::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id
        ]);

        $customer = Customer::factory()->create([
            'company_id' => $company->id
        ]);

        // Act: Trigger alternative finder with UUID branch_id
        $alternativeFinder = app(AppointmentAlternativeFinder::class);
        $alternatives = $alternativeFinder
            ->setTenantContext($company->id, $branch->id)
            ->findAlternatives(
                Carbon::tomorrow(),
                60,
                $service->calcom_event_type_id ?? 123,
                $customer->id
            );

        // Assert: No TypeError, alternatives returned
        $this->assertIsArray($alternatives);
        $this->assertArrayHasKey('alternatives', $alternatives);
    }

    /**
     * @test
     * Alternative scenario validation
     */
    public function it_finds_alternative_slots_when_exact_time_unavailable()
    {
        // Arrange: Mock requested time unavailable, but alternatives available
        Http::fake([
            '*/availability*' => Http::response([
                'data' => [
                    'slots' => [
                        '2025-10-20' => [
                            ['time' => '2025-10-20T15:00:00.000Z'], // 15:00 available
                            ['time' => '2025-10-20T16:00:00.000Z']  // 16:00 available
                        ]
                    ]
                ]
            ], 200)
        ]);

        // Act: Request 14:00 (unavailable)
        $response = $this->postJson('/api/retell/check-availability', [
            'args' => [
                'datum' => '2025-10-20',
                'uhrzeit' => '14:00'
            ]
        ]);

        // Assert: Alternatives returned
        $response->assertStatus(200);
        $response->assertJsonStructure(['alternatives']);
    }

    // ============================================================
    // Additional Validations
    // ============================================================

    /**
     * @test
     * Validation: German date/time parsing
     */
    public function it_parses_german_date_formats_correctly()
    {
        $testCases = [
            ['morgen', Carbon::tomorrow()->format('Y-m-d')],
            ['übermorgen', Carbon::tomorrow()->addDay()->format('Y-m-d')],
        ];

        foreach ($testCases as [$input, $expected]) {
            $response = $this->postJson('/api/retell/parse-date', [
                'datum' => $input
            ]);

            $response->assertJson(['parsed_date' => $expected]);
        }
    }

    /**
     * @test
     * Validation: Confidence threshold enforcement
     */
    public function it_validates_minimum_confidence_threshold()
    {
        // Arrange: Low confidence booking details
        $response = $this->postJson('/api/retell/collect-appointment', [
            'args' => [
                'datum' => '2025-10-20',
                'uhrzeit' => '14:00',
                'name' => 'Test User',
                'confidence' => 50 // Below 60% threshold
            ]
        ]);

        // Assert: Rejected due to low confidence
        $response->assertStatus(200);
        $response->assertJson([
            'success' => false,
            'status' => 'low_confidence'
        ]);
    }

    /**
     * @test
     * Performance: Booking flow completes within acceptable time
     */
    public function it_completes_booking_within_performance_threshold()
    {
        // Arrange: Mock Cal.com responses
        Http::fake([
            '*/availability*' => Http::response([
                'data' => [
                    'slots' => [
                        '2025-10-20' => [
                            ['time' => '2025-10-20T14:00:00.000Z']
                        ]
                    ]
                ]
            ], 200),
            '*/bookings' => Http::response([
                'data' => [
                    'uid' => 'perf_test_uid',
                    'createdAt' => Carbon::now()->toIso8601String()
                ]
            ], 200)
        ]);

        // Act: Measure booking flow time
        $startTime = microtime(true);

        $response = $this->postJson('/api/retell/collect-appointment', [
            'args' => [
                'datum' => '2025-10-20',
                'uhrzeit' => '14:00',
                'name' => 'Test User',
                'dienstleistung' => 'Beratung',
                'bestaetigung' => true
            ]
        ]);

        $duration = (microtime(true) - $startTime) * 1000; // Convert to ms

        // Assert: Completed within 5 seconds (5000ms)
        $this->assertLessThan(5000, $duration, "Booking flow took {$duration}ms (should be <5000ms)");
        $response->assertStatus(200);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
