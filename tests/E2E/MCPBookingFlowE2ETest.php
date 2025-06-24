<?php

namespace Tests\E2E;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\PhoneNumber;
use App\Models\CalcomEventType;
use App\Services\MCP\MCPGateway;
use App\Services\MCP\WebhookMCPServer;
use App\Services\MCP\RetellCustomFunctionMCPServer;
use App\Mail\AppointmentConfirmation;
use Carbon\Carbon;

class MCPBookingFlowE2ETest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected Staff $staff;
    protected Service $service;
    protected CalcomEventType $eventType;
    protected PhoneNumber $phoneNumber;
    protected MCPGateway $mcpGateway;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup test data
        $this->setupTestEnvironment();
        
        // Setup MCP Gateway
        $this->mcpGateway = app(MCPGateway::class);
        
        // Configure fake HTTP responses
        $this->setupHttpFakes();
    }

    protected function setupTestEnvironment(): void
    {
        // Create company with API credentials
        $this->company = Company::factory()->create([
            'name' => 'MCP Test Company',
            'calcom_api_key' => 'test_calcom_key',
            'calcom_team_slug' => 'mcp-test',
            'retell_api_key' => 'test_retell_key',
        ]);

        // Create branch with Cal.com event type
        $this->branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'MCP Test Branch',
            'email' => 'branch@mcptest.de',
            'address' => 'MCP Street 1, Berlin',
            'calcom_event_type_id' => 2563193, // Test event type ID
            'timezone' => 'Europe/Berlin',
            'is_active' => true,
        ]);

        // Create phone number mapping
        $this->phoneNumber = PhoneNumber::factory()->create([
            'phone_number' => '+493083793369',
            'branch_id' => $this->branch->id,
            'company_id' => $this->company->id,
            'retell_phone_number_id' => 'retell_phone_123',
            'retell_agent_id' => 'agent_abc123',
            'is_active' => true,
        ]);

        // Create staff member
        $this->staff = Staff::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Dr. MCP Test',
            'email' => 'dr.mcp@mcptest.de',
            'calcom_user_id' => 123,
        ]);

        // Create service
        $this->service = Service::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'MCP Consultation',
            'duration_minutes' => 30,
            'price' => 85.00,
        ]);

        // Create Cal.com event type
        $this->eventType = CalcomEventType::factory()->create([
            'company_id' => $this->company->id,
            'calcom_id' => 2563193,
            'title' => 'MCP Consultation',
            'slug' => 'mcp-consultation',
            'length' => 30,
        ]);

        // Link staff to service and event type
        $this->staff->services()->attach($this->service);
        $this->staff->eventTypes()->attach($this->eventType);
    }

    protected function setupHttpFakes(): void
    {
        // Mock Cal.com API responses
        Http::fake([
            'api.cal.com/v2/slots/available*' => Http::response([
                'data' => [
                    'slots' => [
                        Carbon::now()->next('Monday')->setTime(10, 0)->toIso8601String(),
                        Carbon::now()->next('Monday')->setTime(11, 0)->toIso8601String(),
                        Carbon::now()->next('Monday')->setTime(14, 0)->toIso8601String(),
                    ],
                ],
            ], 200),

            'api.cal.com/v2/bookings' => Http::response([
                'data' => [
                    'id' => 999,
                    'uid' => 'mcp_booking_uid_123',
                    'title' => 'MCP Consultation',
                    'start' => Carbon::now()->next('Monday')->setTime(10, 0)->toIso8601String(),
                    'end' => Carbon::now()->next('Monday')->setTime(10, 30)->toIso8601String(),
                    'attendees' => [
                        [
                            'email' => 'mcp.customer@test.de',
                            'name' => 'MCP Test Customer',
                        ],
                    ],
                ],
            ], 201),
        ]);
    }

    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function complete_mcp_booking_flow_with_custom_functions()
    {
        Event::fake();
        Mail::fake();
        Queue::fake();

        // Step 1: Simulate Retell custom function call to collect appointment data
        $customFunctionRequest = [
            'jsonrpc' => '2.0',
            'method' => 'retell_custom.collectAppointmentInformation',
            'params' => [
                'call_id' => 'mcp_call_123',
                'name' => 'MCP Test Customer',
                'date' => Carbon::now()->next('Monday')->format('Y-m-d'),
                'time' => '10:00',
                'service' => 'Consultation',
                'notes' => 'Testing MCP booking flow',
            ],
            'id' => 'func_001',
        ];

        // Process through MCP Gateway
        $functionResponse = $this->mcpGateway->process($customFunctionRequest);

        // Verify function response
        $this->assertEquals('2.0', $functionResponse['jsonrpc']);
        $this->assertTrue($functionResponse['result']['success']);
        $this->assertEquals('Appointment information collected successfully', $functionResponse['result']['message']);

        // Step 2: Verify data was cached
        $cachedData = Cache::get('retell:appointment:mcp_call_123');
        $this->assertNotNull($cachedData);
        $this->assertEquals('MCP Test Customer', $cachedData['customer_name']);
        $this->assertEquals(Carbon::now()->next('Monday')->format('Y-m-d'), $cachedData['appointment_date']);
        $this->assertEquals('10:00', $cachedData['appointment_time']);

        // Step 3: Simulate Retell webhook for call ended
        $webhookRequest = [
            'jsonrpc' => '2.0',
            'method' => 'webhook.processRetellWebhook',
            'params' => [
                'event' => 'call_ended',
                'call' => [
                    'call_id' => 'mcp_call_123',
                    'retell_agent_id' => 'agent_abc123',
                    'from_number' => '+491234567890',
                    'to_number' => '+493083793369',
                    'status' => 'ended',
                    'duration' => 180,
                    'variables' => [
                        'booking_confirmed' => 'true',
                    ],
                ],
            ],
            'id' => 'webhook_001',
        ];

        // Process webhook through MCP Gateway
        $webhookResponse = $this->mcpGateway->process($webhookRequest);

        // Verify webhook was processed
        $this->assertEquals('2.0', $webhookResponse['jsonrpc']);
        $this->assertTrue($webhookResponse['result']['success']);
        $this->assertArrayHasKey('call_id', $webhookResponse['result']);

        // Step 4: Verify customer was created
        $this->assertDatabaseHas('customers', [
            'company_id' => $this->company->id,
            'name' => 'MCP Test Customer',
            'phone' => '+491234567890',
        ]);

        $customer = Customer::where('phone', '+491234567890')->first();
        $this->assertNotNull($customer);

        // Step 5: Verify call record was created
        $this->assertDatabaseHas('calls', [
            'retell_call_id' => 'mcp_call_123',
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'phone_number' => '+491234567890',
            'duration' => 180,
            'status' => 'completed',
        ]);

        $call = Call::where('retell_call_id', 'mcp_call_123')->first();
        $this->assertNotNull($call);

        // Step 6: Verify appointment was created
        $this->assertDatabaseHas('appointments', [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'status' => 'confirmed',
            'notes' => 'Testing MCP booking flow',
        ]);

        $appointment = Appointment::where('customer_id', $customer->id)->first();
        $this->assertNotNull($appointment);
        $this->assertEquals(
            Carbon::now()->next('Monday')->setTime(10, 0)->format('Y-m-d H:i'),
            $appointment->starts_at->format('Y-m-d H:i')
        );

        // Step 7: Verify Cal.com booking was linked
        $this->assertNotNull($appointment->calcom_booking_uid);
        $this->assertEquals('mcp_booking_uid_123', $appointment->calcom_booking_uid);

        // Step 8: Verify appointment management functions work
        $findRequest = [
            'jsonrpc' => '2.0',
            'method' => 'appointment_mgmt.findAppointments',
            'params' => [
                'phone' => '+491234567890',
            ],
            'id' => 'find_001',
        ];

        $findResponse = $this->mcpGateway->process($findRequest);
        
        $this->assertTrue(count($findResponse['result']['appointments']) > 0);
        $this->assertEquals($appointment->id, $findResponse['result']['appointments'][0]['id']);
        $this->assertEquals('MCP Test Customer', $findResponse['result']['customer_name']);
    }

    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function handles_appointment_rescheduling_via_mcp()
    {
        Event::fake();
        Mail::fake();

        // Create existing appointment
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'phone' => '+491234567890',
            'name' => 'Reschedule Test Customer',
        ]);

        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'starts_at' => Carbon::now()->addDays(5)->setTime(14, 0),
            'ends_at' => Carbon::now()->addDays(5)->setTime(14, 30),
            'status' => 'confirmed',
            'calcom_booking_uid' => 'original_booking_123',
        ]);

        // Mock Cal.com reschedule response
        Http::fake([
            'api.cal.com/v2/bookings/original_booking_123' => Http::response([
                'data' => [
                    'id' => 1000,
                    'uid' => 'rescheduled_booking_123',
                    'start' => Carbon::now()->addDays(7)->setTime(16, 0)->toIso8601String(),
                    'end' => Carbon::now()->addDays(7)->setTime(16, 30)->toIso8601String(),
                ],
            ], 200),
        ]);

        // Request reschedule via MCP
        $rescheduleRequest = [
            'jsonrpc' => '2.0',
            'method' => 'appointment_mgmt.rescheduleAppointment',
            'params' => [
                'appointment_id' => $appointment->id,
                'phone' => '+491234567890',
                'new_date' => Carbon::now()->addDays(7)->format('Y-m-d'),
                'new_time' => '16:00',
            ],
            'id' => 'reschedule_001',
        ];

        $response = $this->mcpGateway->process($rescheduleRequest);

        // Verify response
        $this->assertTrue($response['result']['success']);
        $this->assertEquals($appointment->id, $response['result']['appointment']['id']);

        // Verify appointment was updated
        $appointment->refresh();
        $this->assertEquals(
            Carbon::now()->addDays(7)->setTime(16, 0)->format('Y-m-d H:i'),
            $appointment->starts_at->format('Y-m-d H:i')
        );
        $this->assertEquals('rescheduled_booking_123', $appointment->calcom_booking_uid);

        // Verify notification was sent
        Mail::assertQueued(AppointmentConfirmation::class, function ($mail) use ($customer) {
            return $mail->hasTo($customer->email);
        });
    }

    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function handles_appointment_cancellation_via_mcp()
    {
        Event::fake();
        Mail::fake();

        // Create existing appointment
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'phone' => '+491234567890',
            'name' => 'Cancel Test Customer',
        ]);

        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'starts_at' => Carbon::now()->addDays(3),
            'status' => 'confirmed',
            'calcom_booking_uid' => 'cancel_booking_123',
        ]);

        // Mock Cal.com cancellation
        Http::fake([
            'api.cal.com/v2/bookings/cancel_booking_123' => Http::response(null, 204),
        ]);

        // Request cancellation via MCP
        $cancelRequest = [
            'jsonrpc' => '2.0',
            'method' => 'appointment_mgmt.cancelAppointment',
            'params' => [
                'appointment_id' => $appointment->id,
                'phone' => '+491234567890',
                'reason' => 'Customer requested cancellation',
            ],
            'id' => 'cancel_001',
        ];

        $response = $this->mcpGateway->process($cancelRequest);

        // Verify response
        $this->assertTrue($response['result']['success']);
        $this->assertEquals('Appointment cancelled successfully', $response['result']['message']);

        // Verify appointment was cancelled
        $appointment->refresh();
        $this->assertEquals('cancelled', $appointment->status);
    }

    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function validates_phone_ownership_for_appointment_changes()
    {
        // Create appointment for different customer
        $otherCustomer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'phone' => '+499999999999',
        ]);

        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $otherCustomer->id,
        ]);

        // Try to reschedule with wrong phone number
        $rescheduleRequest = [
            'jsonrpc' => '2.0',
            'method' => 'appointment_mgmt.rescheduleAppointment',
            'params' => [
                'appointment_id' => $appointment->id,
                'phone' => '+491234567890', // Different phone
                'new_date' => Carbon::tomorrow()->format('Y-m-d'),
                'new_time' => '10:00',
            ],
            'id' => 'invalid_001',
        ];

        $response = $this->mcpGateway->process($rescheduleRequest);

        // Should fail
        $this->assertFalse($response['result']['success']);
        $this->assertStringContains('not found or access denied', $response['result']['error']);
    }

    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function handles_mcp_circuit_breaker_correctly()
    {
        // Simulate multiple failures to trigger circuit breaker
        Http::fake([
            'api.cal.com/*' => Http::sequence()
                ->push(null, 500)
                ->push(null, 500)
                ->push(null, 500)
                ->push(null, 500)
                ->push(null, 500),
        ]);

        // Make multiple failed requests
        for ($i = 0; $i < 5; $i++) {
            $request = [
                'jsonrpc' => '2.0',
                'method' => 'calcom.checkAvailability',
                'params' => [
                    'eventTypeId' => 123,
                    'startDate' => Carbon::tomorrow()->format('Y-m-d'),
                    'endDate' => Carbon::tomorrow()->format('Y-m-d'),
                ],
                'id' => "fail_{$i}",
            ];

            $response = $this->mcpGateway->process($request);
            $this->assertArrayHasKey('error', $response);
        }

        // Next request should fail immediately (circuit open)
        $request = [
            'jsonrpc' => '2.0',
            'method' => 'calcom.checkAvailability',
            'params' => ['eventTypeId' => 123],
            'id' => 'circuit_test',
        ];

        $response = $this->mcpGateway->process($request);
        
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals(-32603, $response['error']['code']);
        $this->assertStringContains('temporarily unavailable', $response['error']['message']);
    }

    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function processes_batch_mcp_requests()
    {
        $batchRequest = [
            [
                'jsonrpc' => '2.0',
                'method' => 'retell_config.getWebhook',
                'params' => ['company_id' => $this->company->id],
                'id' => 'batch_1',
            ],
            [
                'jsonrpc' => '2.0',
                'method' => 'appointment_mgmt.findAppointments',
                'params' => ['phone' => '+491234567890'],
                'id' => 'batch_2',
            ],
        ];

        $responses = $this->mcpGateway->processBatch($batchRequest);

        $this->assertCount(2, $responses);
        $this->assertEquals('batch_1', $responses[0]['id']);
        $this->assertEquals('batch_2', $responses[1]['id']);
        $this->assertArrayHasKey('result', $responses[0]);
        $this->assertArrayHasKey('result', $responses[1]);
    }

    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function handles_complex_multi_language_booking_flow()
    {
        Event::fake();
        Cache::flush();

        // Test German language flow
        $germanRequest = [
            'jsonrpc' => '2.0',
            'method' => 'retell_custom.collectAppointmentInformation',
            'params' => [
                'call_id' => 'german_call_123',
                'name' => 'Deutscher Kunde',
                'date' => '15.07.2025', // German date format
                'time' => '14:00',
                'service' => 'Beratung',
                'notes' => 'Deutschsprachiger Termin',
            ],
            'id' => 'de_001',
        ];

        $response = $this->mcpGateway->process($germanRequest);
        $this->assertTrue($response['result']['success']);

        // Verify date was normalized
        $cachedData = Cache::get('retell:appointment:german_call_123');
        $this->assertEquals('2025-07-15', $cachedData['appointment_date']);

        // Test appointment lookup in German
        $findRequest = [
            'jsonrpc' => '2.0',
            'method' => 'retell_custom.findAppointmentsByPhone',
            'params' => [
                'phone' => '+491234567890',
                'language' => 'de',
            ],
            'id' => 'de_find_001',
        ];

        $findResponse = $this->mcpGateway->process($findRequest);
        
        if (!$findResponse['result']['found']) {
            $this->assertStringContains('Keine Termine', $findResponse['result']['message']);
        }
    }
}