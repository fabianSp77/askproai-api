<?php

namespace Tests\E2E;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Staff;
use App\Services\CalcomV2Service;
use App\Jobs\ProcessRetellWebhookJob;
use Carbon\Carbon;

class RetellDataFlowE2ETest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected Service $service;
    protected Staff $staff;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear Redis and Queue
        Redis::flushall();
        Queue::fake();
        
        // Setup test environment
        $this->setupTestCompany();
        $this->mockCalcomApi();
    }

    /**
     * Test 1: Complete phone call to appointment booking flow
     * Scenario: Customer calls, AI captures booking details, appointment is created
     */
    public function test_complete_phone_to_appointment_flow()
    {
        // Step 1: Simulate incoming call (call_started event)
        $callId = 'test-call-' . uniqid();
        $customerPhone = '+49 151 12345678';
        
        $this->simulateCallStarted($callId, $customerPhone);
        
        // Verify call record was created
        $call = Call::where('retell_call_id', $callId)->first();
        $this->assertNotNull($call);
        $this->assertEquals('in_progress', $call->status);
        $this->assertEquals($customerPhone, $call->from_number);
        $this->assertEquals($this->branch->phone_number, $call->to_number);
        
        // Step 2: Simulate call ending with booking details
        $bookingDate = Carbon::now()->addDays(3)->format('Y-m-d');
        $bookingTime = '14:30';
        
        $this->simulateCallEndedWithBooking($callId, $bookingDate, $bookingTime);
        
        // Verify call was updated
        $call->refresh();
        $this->assertEquals('completed', $call->status);
        $this->assertNotNull($call->duration_seconds);
        $this->assertNotNull($call->transcript);
        
        // Verify customer was created/found
        $customer = Customer::where('phone', $customerPhone)->first();
        $this->assertNotNull($customer);
        $this->assertEquals($this->company->id, $customer->company_id);
        
        // Verify appointment was created
        $appointment = Appointment::where('call_id', $call->id)->first();
        $this->assertNotNull($appointment);
        $this->assertEquals($customer->id, $appointment->customer_id);
        $this->assertEquals($this->branch->id, $appointment->branch_id);
        $this->assertEquals($bookingDate, $appointment->start_time->format('Y-m-d'));
        $this->assertEquals($bookingTime, $appointment->start_time->format('H:i'));
        $this->assertEquals('scheduled', $appointment->status);
        
        // Verify Cal.com booking was attempted
        Http::assertSent(function ($request) use ($bookingDate, $bookingTime) {
            return $request->url() === 'https://api.cal.com/v2/bookings' &&
                   $request['eventTypeId'] === $this->branch->calcom_event_type_id &&
                   str_contains($request['start'], $bookingDate) &&
                   str_contains($request['start'], '14:30');
        });
    }

    /**
     * Test 2: Customer preferences and alternative slot finding
     * Scenario: Requested time not available, AI suggests alternatives
     */
    public function test_availability_check_with_customer_preferences()
    {
        // Mock Cal.com availability response
        $requestedDate = Carbon::now()->addDays(2)->format('Y-m-d');
        $requestedTime = '15:00';
        
        Http::fake([
            '*/eventTypes/*/availability*' => Http::sequence()
                ->push([
                    'success' => true,
                    'data' => [
                        'slots' => [
                            // Requested time not available
                            ['time' => $requestedDate . 'T09:00:00Z'],
                            ['time' => $requestedDate . 'T10:00:00Z'],
                            ['time' => $requestedDate . 'T16:30:00Z'], // Alternative in afternoon
                        ]
                    ]
                ])
                ->push([
                    'success' => true,
                    'data' => [
                        'slots' => [
                            // Next day slots
                            ['time' => Carbon::parse($requestedDate)->addDay()->format('Y-m-d') . 'T15:00:00Z'],
                            ['time' => Carbon::parse($requestedDate)->addDay()->format('Y-m-d') . 'T15:30:00Z'],
                        ]
                    ]
                ])
        ]);
        
        // Simulate call_inbound with availability check request
        $response = $this->postJson('/api/retell/webhook', [
            'event' => 'call_inbound',
            'call_id' => 'availability-test-123',
            'call_inbound' => [
                'from_number' => '+49 151 98765432',
                'to_number' => $this->branch->phone_number,
            ],
            'dynamic_variables' => [
                'check_availability' => true,
                'requested_date' => $requestedDate,
                'requested_time' => $requestedTime,
                'event_type_id' => $this->branch->calcom_event_type_id,
                'customer_preferences' => 'nachmittags zwischen 15 und 18 Uhr',
            ]
        ], [
            'x-retell-signature' => $this->generateRetellSignature([])
        ]);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'response' => [
                'agent_id',
                'dynamic_variables' => [
                    'requested_slot_available',
                    'alternative_slots',
                    'slots_count',
                ]
            ]
        ]);
        
        $dynamicVars = $response->json('response.dynamic_variables');
        $this->assertFalse($dynamicVars['requested_slot_available']);
        $this->assertGreaterThan(0, $dynamicVars['slots_count']);
        $this->assertStringContainsString('16:30', $dynamicVars['alternative_slots']);
    }

    /**
     * Test 3: Multiple calls from same customer
     * Scenario: Returning customer, system recognizes them
     */
    public function test_returning_customer_recognition()
    {
        $customerPhone = '+49 151 11223344';
        
        // First call - new customer
        $firstCallId = 'first-call-123';
        $this->simulateCallStarted($firstCallId, $customerPhone);
        $this->simulateCallEndedWithBooking($firstCallId, '2025-06-25', '10:00');
        
        $customer = Customer::where('phone', $customerPhone)->first();
        $this->assertNotNull($customer);
        $firstCustomerId = $customer->id;
        
        // Second call - returning customer
        $secondCallId = 'second-call-456';
        $this->simulateCallStarted($secondCallId, $customerPhone);
        $this->simulateCallEndedWithBooking($secondCallId, '2025-06-30', '15:00');
        
        // Verify same customer was used
        $customer->refresh();
        $this->assertEquals($firstCustomerId, $customer->id);
        $this->assertEquals(2, $customer->total_calls);
        
        // Verify both appointments exist
        $appointments = Appointment::where('customer_id', $customer->id)->get();
        $this->assertCount(2, $appointments);
    }

    /**
     * Test 4: Call without appointment booking
     * Scenario: Customer just asks for information, no booking
     */
    public function test_informational_call_without_booking()
    {
        $callId = 'info-call-789';
        
        // Simulate call that doesn't result in booking
        $this->postJson('/api/retell/webhook', [
            'event' => 'call_ended',
            'call_id' => $callId,
            'call' => [
                'from_number' => '+49 151 55667788',
                'to_number' => $this->branch->phone_number,
                'direction' => 'inbound',
                'call_duration' => 45,
                'start_timestamp' => now()->subSeconds(45)->timestamp * 1000,
                'end_timestamp' => now()->timestamp * 1000,
                'transcript' => 'Kunde fragte nach Öffnungszeiten',
                'retell_llm_dynamic_variables' => [
                    'booking_confirmed' => false,
                    'inquiry_type' => 'opening_hours',
                ]
            ]
        ], [
            'x-retell-signature' => $this->generateRetellSignature([])
        ]);
        
        // Verify call was recorded but no appointment created
        $call = Call::where('retell_call_id', $callId)->first();
        $this->assertNotNull($call);
        $this->assertEquals('completed', $call->status);
        $this->assertNull($call->appointment_id);
        
        $appointments = Appointment::where('call_id', $call->id)->count();
        $this->assertEquals(0, $appointments);
    }

    /**
     * Test 5: Service and staff assignment
     * Scenario: Customer requests specific service and staff member
     */
    public function test_booking_with_service_and_staff_selection()
    {
        $callId = 'service-staff-call-123';
        
        $this->postJson('/api/retell/webhook', [
            'event' => 'call_ended',
            'call_id' => $callId,
            'call' => [
                'from_number' => '+49 151 99887766',
                'to_number' => $this->branch->phone_number,
                'direction' => 'inbound',
                'call_duration' => 180,
                'start_timestamp' => now()->subMinutes(3)->timestamp * 1000,
                'end_timestamp' => now()->timestamp * 1000,
                'transcript' => 'Ich möchte einen Haarschnitt bei Max Mustermann buchen',
                'retell_llm_dynamic_variables' => [
                    'booking_confirmed' => true,
                    'datum' => '2025-06-28',
                    'uhrzeit' => '11:00',
                    'dienstleistung_id' => $this->service->id,
                    'mitarbeiter_id' => $this->staff->id,
                    'kundenwunsch' => 'Haarschnitt mit Waschen',
                ]
            ]
        ], [
            'x-retell-signature' => $this->generateRetellSignature([])
        ]);
        
        $appointment = Appointment::whereHas('call', function ($query) use ($callId) {
            $query->where('retell_call_id', $callId);
        })->first();
        
        $this->assertNotNull($appointment);
        $this->assertEquals($this->service->id, $appointment->service_id);
        $this->assertEquals($this->staff->id, $appointment->staff_id);
        $this->assertStringContainsString('Haarschnitt mit Waschen', $appointment->notes);
        
        // Verify duration matches service duration
        $expectedEndTime = $appointment->start_time->copy()->addMinutes($this->service->duration);
        $this->assertEquals($expectedEndTime, $appointment->end_time);
    }

    /**
     * Test 6: Multi-branch company phone routing
     * Scenario: Different phone numbers route to different branches
     */
    public function test_multi_branch_phone_routing()
    {
        // Create second branch
        $branch2 = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Filiale München',
            'phone_number' => '+49 89 12345678',
            'is_active' => true,
            'calcom_event_type_id' => 789012,
        ]);
        
        // Call to first branch
        $call1Id = 'branch1-call-123';
        $this->postJson('/api/retell/webhook', [
            'event' => 'call_started',
            'call_id' => $call1Id,
            'call' => [
                'from_number' => '+49 151 11111111',
                'to_number' => $this->branch->phone_number,
                'direction' => 'inbound',
                'start_timestamp' => now()->timestamp * 1000,
            ]
        ], [
            'x-retell-signature' => $this->generateRetellSignature([])
        ]);
        
        // Call to second branch
        $call2Id = 'branch2-call-456';
        $this->postJson('/api/retell/webhook', [
            'event' => 'call_started',
            'call_id' => $call2Id,
            'call' => [
                'from_number' => '+49 151 22222222',
                'to_number' => $branch2->phone_number,
                'direction' => 'inbound',
                'start_timestamp' => now()->timestamp * 1000,
            ]
        ], [
            'x-retell-signature' => $this->generateRetellSignature([])
        ]);
        
        // Verify correct branch assignment
        $call1 = Call::where('retell_call_id', $call1Id)->first();
        $call2 = Call::where('retell_call_id', $call2Id)->first();
        
        $this->assertEquals($this->branch->id, $call1->branch_id);
        $this->assertEquals($branch2->id, $call2->branch_id);
        $this->assertEquals($this->company->id, $call1->company_id);
        $this->assertEquals($this->company->id, $call2->company_id);
    }

    /**
     * Test 7: Error handling and retry logic
     * Scenario: Cal.com API failure, system handles gracefully
     */
    public function test_graceful_handling_of_calcom_api_failure()
    {
        // Mock Cal.com API failure
        Http::fake([
            '*/bookings' => Http::response(['error' => 'Service unavailable'], 503)
        ]);
        
        $callId = 'api-failure-call-123';
        
        $this->postJson('/api/retell/webhook', [
            'event' => 'call_ended',
            'call_id' => $callId,
            'call' => [
                'from_number' => '+49 151 33445566',
                'to_number' => $this->branch->phone_number,
                'direction' => 'inbound',
                'call_duration' => 120,
                'start_timestamp' => now()->subMinutes(2)->timestamp * 1000,
                'end_timestamp' => now()->timestamp * 1000,
                'retell_llm_dynamic_variables' => [
                    'booking_confirmed' => true,
                    'datum' => '2025-06-27',
                    'uhrzeit' => '16:00',
                ]
            ]
        ], [
            'x-retell-signature' => $this->generateRetellSignature([])
        ]);
        
        // Appointment should still be created locally
        $appointment = Appointment::whereHas('call', function ($query) use ($callId) {
            $query->where('retell_call_id', $callId);
        })->first();
        
        $this->assertNotNull($appointment);
        $this->assertEquals('scheduled', $appointment->status);
        $this->assertNull($appointment->calcom_booking_id); // Cal.com booking failed
        
        // Verify appointment is still functional
        $this->assertEquals('2025-06-27', $appointment->start_time->format('Y-m-d'));
        $this->assertEquals('16:00', $appointment->start_time->format('H:i'));
    }

    /**
     * Test 8: Webhook deduplication
     * Scenario: Same webhook sent multiple times
     */
    public function test_webhook_deduplication_prevents_duplicate_bookings()
    {
        $callId = 'dedup-test-call-123';
        $payload = [
            'event' => 'call_ended',
            'call_id' => $callId,
            'call' => [
                'from_number' => '+49 151 77889900',
                'to_number' => $this->branch->phone_number,
                'direction' => 'inbound',
                'call_duration' => 90,
                'start_timestamp' => now()->subSeconds(90)->timestamp * 1000,
                'end_timestamp' => now()->timestamp * 1000,
                'retell_llm_dynamic_variables' => [
                    'booking_confirmed' => true,
                    'datum' => '2025-06-29',
                    'uhrzeit' => '13:00',
                ]
            ]
        ];
        
        $headers = ['x-retell-signature' => $this->generateRetellSignature($payload)];
        
        // Send webhook 3 times
        for ($i = 0; $i < 3; $i++) {
            $response = $this->postJson('/api/retell/webhook', $payload, $headers);
            $response->assertStatus(200);
        }
        
        // Only one appointment should be created
        $appointments = Appointment::whereHas('call', function ($query) use ($callId) {
            $query->where('retell_call_id', $callId);
        })->count();
        
        $this->assertEquals(1, $appointments);
        
        // Only one call record should exist
        $calls = Call::where('retell_call_id', $callId)->count();
        $this->assertEquals(1, $calls);
    }

    /**
     * Helper Methods
     */
    protected function setupTestCompany(): void
    {
        $this->company = Company::factory()->create([
            'name' => 'Test Friseursalon',
            'phone_number' => '+49 30 12345678',
            'retell_api_key' => 'test-retell-key',
            'calcom_api_key' => 'test-calcom-key',
            'is_active' => true,
        ]);
        
        $this->branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Hauptfiliale Berlin',
            'phone_number' => '+49 30 12345678',
            'is_active' => true,
            'is_main' => true,
            'calcom_event_type_id' => 123456,
        ]);
        
        $this->service = Service::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Haarschnitt',
            'duration' => 30,
            'price' => 25.00,
            'is_active' => true,
        ]);
        
        $this->staff = Staff::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
            'email' => 'max@example.com',
            'active' => true,
        ]);
    }

    protected function mockCalcomApi(): void
    {
        Http::fake([
            // Default successful booking response
            '*/bookings' => Http::response([
                'id' => 'booking-' . uniqid(),
                'uid' => 'uid-' . uniqid(),
                'eventTypeId' => $this->branch->calcom_event_type_id,
                'title' => 'Test Booking',
                'startTime' => now()->addDays(3)->toIso8601String(),
                'endTime' => now()->addDays(3)->addMinutes(30)->toIso8601String(),
            ], 200),
            
            // Default availability response
            '*/availability*' => Http::response([
                'success' => true,
                'data' => [
                    'slots' => [
                        ['time' => now()->addDays(3)->setTime(14, 30)->toIso8601String()],
                        ['time' => now()->addDays(3)->setTime(15, 0)->toIso8601String()],
                        ['time' => now()->addDays(3)->setTime(15, 30)->toIso8601String()],
                    ]
                ]
            ], 200),
        ]);
    }

    protected function simulateCallStarted(string $callId, string $customerPhone): void
    {
        $this->postJson('/api/retell/webhook', [
            'event' => 'call_started',
            'call_id' => $callId,
            'call' => [
                'from_number' => $customerPhone,
                'to_number' => $this->branch->phone_number,
                'direction' => 'inbound',
                'start_timestamp' => now()->timestamp * 1000,
                'agent_id' => 'agent-123',
            ]
        ], [
            'x-retell-signature' => $this->generateRetellSignature([])
        ])->assertStatus(200);
    }

    protected function simulateCallEndedWithBooking(string $callId, string $date, string $time): void
    {
        $this->postJson('/api/retell/webhook', [
            'event' => 'call_ended',
            'call_id' => $callId,
            'call' => [
                'from_number' => '+49 151 12345678',
                'to_number' => $this->branch->phone_number,
                'direction' => 'inbound',
                'call_duration' => 180,
                'start_timestamp' => now()->subMinutes(3)->timestamp * 1000,
                'end_timestamp' => now()->timestamp * 1000,
                'transcript' => 'Ich möchte gerne einen Termin für einen Haarschnitt buchen.',
                'recording_url' => 'https://example.com/recording-' . $callId . '.mp3',
                'retell_llm_dynamic_variables' => [
                    'booking_confirmed' => true,
                    'datum' => $date,
                    'uhrzeit' => $time,
                    'kundenwunsch' => 'Haarschnitt',
                    'kundenname' => 'Test Kunde',
                ]
            ]
        ], [
            'x-retell-signature' => $this->generateRetellSignature([])
        ])->assertStatus(200);
    }

    protected function generateRetellSignature($payload): string
    {
        $secret = config('services.retell.secret', 'test-secret');
        return hash_hmac('sha256', json_encode($payload), $secret);
    }
}