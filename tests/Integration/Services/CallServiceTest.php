<?php

namespace Tests\Integration\Services;

use App\Events\CallCompleted;
use App\Events\CallFailed;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Call;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\User;
use App\Repositories\AppointmentRepository;
use App\Repositories\CallRepository;
use App\Repositories\CustomerRepository;
use App\Services\AppointmentService;
use App\Services\CallService;
use App\Services\RetellService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CallServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CallService $callService;
    protected Company $company;
    protected User $user;
    protected RetellService $retellServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->company = Company::factory()->create();
        
        // Create and authenticate user
        $this->user = User::factory()->for($this->company)->create();
        $this->actingAs($this->user);

        // Mock RetellService
        $this->retellServiceMock = Mockery::mock(RetellService::class);
        $this->app->instance(RetellService::class, $this->retellServiceMock);

        // Create service instance
        $this->callService = new CallService(
            new CallRepository(),
            new CustomerRepository(),
            new AppointmentRepository(),
            $this->retellServiceMock
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test processing call started webhook
     */
    #[Test]
    public function test_processes_call_started_webhook()
    {
        Event::fake();

        $webhookData = [
            'event' => 'call_started',
            'call_id' => 'retell_call_123',
            'from_number' => '+491234567890',
            'to_number' => '+493012345678',
            'agent_id' => 'agent_123',
            'direction' => 'inbound',
            'status' => 'in_progress',
            'company_id' => $this->company->id,
        ];

        $call = $this->callService->processWebhook($webhookData);

        // Assert call was created
        $this->assertInstanceOf(Call::class, $call);
        $this->assertDatabaseHas('calls', [
            'id' => $call->id,
            'retell_call_id' => 'retell_call_123',
            'call_id' => 'retell_call_123',
            'from_number' => '+491234567890',
            'to_number' => '+493012345678',
            'agent_id' => 'agent_123',
            'direction' => 'inbound',
            'status' => 'in_progress',
            'company_id' => $this->company->id,
        ]);

        // Assert customer was created from phone number
        $this->assertNotNull($call->customer);
        $this->assertEquals('+491234567890', $call->customer->phone);
        $this->assertEquals($this->company->id, $call->customer->company_id);

        // Assert started_at is set
        $this->assertNotNull($call->started_at);

        // No events should be fired for call started
        Event::assertNotDispatched(CallCompleted::class);
        Event::assertNotDispatched(CallFailed::class);
    }

    /**
     * Test processing call ended webhook with appointment creation
     */
    #[Test]
    public function test_processes_call_ended_webhook_with_appointment()
    {
        Event::fake();

        // Create existing call
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'retell_call_id' => 'retell_call_456',
            'status' => 'in_progress',
            'customer_id' => Customer::factory()->create(['company_id' => $this->company->id])->id,
        ]);

        // Create staff and branch for appointment
        $branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $staff = Staff::factory()->create(['company_id' => $this->company->id]);

        // Mock AppointmentService
        $appointmentMock = Appointment::factory()->make([
            'id' => 999,
            'company_id' => $this->company->id,
        ]);

        $appointmentServiceMock = Mockery::mock(AppointmentService::class);
        $appointmentServiceMock->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) use ($call) {
                return $data['customer_phone'] === $call->from_number &&
                       $data['source'] === 'phone' &&
                       str_contains($data['notes'], $call->retell_call_id);
            }))
            ->andReturn($appointmentMock);

        $this->app->instance(AppointmentService::class, $appointmentServiceMock);

        $webhookData = [
            'event' => 'call_ended',
            'call_id' => 'retell_call_456',
            'call_duration' => 180, // 3 minutes
            'status' => 'completed',
            'variables' => [
                'appointment_created' => true,
                'appointment_data' => [
                    'customer_name' => 'John Doe',
                    'customer_phone' => $call->from_number,
                    'customer_email' => 'john@example.com',
                    'staff_id' => $staff->id,
                    'branch_id' => $branch->id,
                    'start_time' => Carbon::tomorrow()->setTime(10, 0),
                    'end_time' => Carbon::tomorrow()->setTime(10, 30),
                ],
            ],
        ];

        $updatedCall = $this->callService->processWebhook($webhookData);

        // Assert call was updated
        $this->assertEquals('completed', $updatedCall->status);
        $this->assertEquals(180, $updatedCall->duration_seconds);
        $this->assertEquals(999, $updatedCall->appointment_id);
        $this->assertNotNull($updatedCall->ended_at);

        // Assert event was fired
        Event::assertDispatched(CallCompleted::class, function ($event) use ($call) {
            return $event->call->id === $call->id;
        });
    }

    /**
     * Test processing call analyzed webhook
     */
    #[Test]
    public function test_processes_call_analyzed_webhook()
    {
        Event::fake();

        // Create existing call with customer
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Unknown',
            'email' => null,
        ]);

        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'retell_call_id' => 'retell_call_789',
            'customer_id' => $customer->id,
        ]);

        $webhookData = [
            'event' => 'call_analyzed',
            'call_id' => 'retell_call_789',
            'transcript' => 'Hello, I would like to book an appointment...',
            'call_analysis' => [
                'summary' => 'Customer wants to book appointment',
                'intent' => 'appointment_booking',
                'satisfaction_score' => 8,
            ],
            'sentiment' => 'positive',
            'structured_data' => [
                'customer' => [
                    'name' => 'Jane Smith',
                    'email' => 'jane.smith@example.com',
                ],
                'appointment_request' => [
                    'service' => 'Haircut',
                    'preferred_time' => 'next Tuesday afternoon',
                ],
            ],
        ];

        $updatedCall = $this->callService->processWebhook($webhookData);

        // Assert call was updated with analysis data
        $this->assertEquals('Hello, I would like to book an appointment...', $updatedCall->transcript);
        $this->assertEquals($webhookData['call_analysis'], $updatedCall->analysis);
        $this->assertEquals('positive', $updatedCall->sentiment);
        $this->assertEquals($webhookData['structured_data'], $updatedCall->structured_data);

        // Assert customer was updated with extracted data
        $customer->refresh();
        $this->assertEquals('Jane Smith', $customer->name);
        $this->assertEquals('jane.smith@example.com', $customer->email);
    }

    /**
     * Test creating new call with customer creation
     */
    #[Test]
    public function test_creates_new_call_with_customer()
    {
        Event::fake();

        $webhookData = [
            'event' => 'call_started',
            'call_id' => 'new_call_123',
            'from_number' => '+499999999999',
            'to_number' => '+493098765432',
            'customer_name' => 'New Customer',
            'agent_id' => 'agent_456',
            'status' => 'initiated',
            'direction' => 'inbound',
            'company_id' => $this->company->id,
        ];

        $call = $this->callService->processWebhook($webhookData);

        // Assert call was created
        $this->assertInstanceOf(Call::class, $call);
        $this->assertEquals('new_call_123', $call->retell_call_id);

        // Assert customer was created
        $this->assertNotNull($call->customer);
        $this->assertEquals('New Customer', $call->customer->name);
        $this->assertEquals('+499999999999', $call->customer->phone);
        $this->assertEquals($this->company->id, $call->customer->company_id);

        // Assert webhook data was stored
        $this->assertEquals($webhookData, $call->webhook_data);
    }

    /**
     * Test updating existing call preserves data
     */
    #[Test]
    public function test_updates_existing_call_preserving_data()
    {
        $existingCall = Call::factory()->create([
            'company_id' => $this->company->id,
            'retell_call_id' => 'existing_call_123',
            'status' => 'in_progress',
            'transcript' => 'Initial transcript',
            'webhook_data' => ['initial' => 'data'],
        ]);

        $webhookData = [
            'event' => 'call_ended',
            'call_id' => 'existing_call_123',
            'status' => 'completed',
            'call_duration' => 240,
            'recording_url' => 'https://example.com/recording.mp3',
            'price' => 2.50,
            'additional' => 'data',
        ];

        $updatedCall = $this->callService->processWebhook($webhookData);

        // Assert call was updated
        $this->assertEquals($existingCall->id, $updatedCall->id);
        $this->assertEquals('completed', $updatedCall->status);
        $this->assertEquals(240, $updatedCall->duration_seconds);
        $this->assertEquals('https://example.com/recording.mp3', $updatedCall->recording_url);
        $this->assertEquals(250, $updatedCall->cost_cents); // 2.50 * 100
        $this->assertEquals('Initial transcript', $updatedCall->transcript); // Preserved

        // Assert webhook data was merged
        $this->assertArrayHasKey('initial', $updatedCall->webhook_data);
        $this->assertArrayHasKey('additional', $updatedCall->webhook_data);
    }

    /**
     * Test call statistics calculation
     */
    #[Test]
    public function test_calculates_call_statistics()
    {
        // Create calls with different statuses and dates
        $calls = [
            Call::factory()->create([
                'company_id' => $this->company->id,
                'status' => 'completed',
                'duration_seconds' => 120,
                'cost_cents' => 150,
                'created_at' => Carbon::now()->subDays(5),
            ]),
            Call::factory()->create([
                'company_id' => $this->company->id,
                'status' => 'completed',
                'duration_seconds' => 180,
                'cost_cents' => 200,
                'created_at' => Carbon::now()->subDays(3),
            ]),
            Call::factory()->create([
                'company_id' => $this->company->id,
                'status' => 'failed',
                'duration_seconds' => 0,
                'cost_cents' => 0,
                'created_at' => Carbon::now()->subDays(2),
            ]),
            Call::factory()->create([
                'company_id' => $this->company->id,
                'status' => 'completed',
                'duration_seconds' => 300,
                'cost_cents' => 350,
                'created_at' => Carbon::now()->subDay(),
            ]),
        ];

        $stats = $this->callService->getStatistics(
            Carbon::now()->subWeek(),
            Carbon::now()
        );

        // Assert statistics structure and values
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_calls', $stats);
        $this->assertArrayHasKey('completed_calls', $stats);
        $this->assertArrayHasKey('failed_calls', $stats);
        $this->assertArrayHasKey('total_duration_seconds', $stats);
        $this->assertArrayHasKey('average_duration_seconds', $stats);
        $this->assertArrayHasKey('total_cost_cents', $stats);

        $this->assertEquals(4, $stats['total_calls']);
        $this->assertEquals(3, $stats['completed_calls']);
        $this->assertEquals(1, $stats['failed_calls']);
        $this->assertEquals(600, $stats['total_duration_seconds']); // 120 + 180 + 300
        $this->assertEquals(200, $stats['average_duration_seconds']); // 600 / 3
        $this->assertEquals(700, $stats['total_cost_cents']); // 150 + 200 + 350
    }

    /**
     * Test refreshing call data from Retell API
     */
    #[Test]
    public function test_refreshes_call_data_from_retell()
    {
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'retell_call_id' => 'retell_refresh_123',
            'status' => 'in_progress',
            'duration_seconds' => 0,
        ]);

        // Mock Retell API response
        $this->retellServiceMock->shouldReceive('getCall')
            ->once()
            ->with('retell_refresh_123')
            ->andReturn([
                'call_id' => 'retell_refresh_123',
                'status' => 'completed',
                'call_duration' => 150,
                'transcript' => 'Updated transcript from API',
                'recording_url' => 'https://retell.ai/recordings/123.mp3',
                'price' => 1.75,
            ]);

        $result = $this->callService->refreshCallData($call->id);

        // Assert refresh was successful
        $this->assertTrue($result);

        // Assert call was updated
        $call->refresh();
        $this->assertEquals('completed', $call->status);
        $this->assertEquals(150, $call->duration_seconds);
        $this->assertEquals('Updated transcript from API', $call->transcript);
        $this->assertEquals('https://retell.ai/recordings/123.mp3', $call->recording_url);
        $this->assertEquals(175, $call->cost_cents);
    }

    /**
     * Test refresh handles Retell API errors
     */
    #[Test]
    public function test_handles_retell_api_errors_on_refresh()
    {
        Log::spy();

        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'retell_call_id' => 'retell_error_123',
        ]);

        // Mock Retell API to throw exception
        $this->retellServiceMock->shouldReceive('getCall')
            ->once()
            ->with('retell_error_123')
            ->andThrow(new \Exception('Retell API error'));

        $result = $this->callService->refreshCallData($call->id);

        // Assert refresh failed
        $this->assertFalse($result);

        // Assert error was logged
        Log::shouldHaveReceived('error')
            ->once()
            ->with('Failed to refresh call data', Mockery::on(function ($context) use ($call) {
                return $context['call_id'] === $call->id &&
                       $context['error'] === 'Retell API error';
            }));
    }

    /**
     * Test marking call as failed
     */
    #[Test]
    public function test_marks_call_as_failed_with_reason()
    {
        Event::fake();

        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'in_progress',
        ]);

        $this->callService->markAsFailed($call->id, 'Network connection lost');

        // Assert call was marked as failed
        $call->refresh();
        $this->assertEquals('failed', $call->status);
        $this->assertEquals('Network connection lost', $call->error_message);
        $this->assertNotNull($call->ended_at);

        // Assert event was fired
        Event::assertDispatched(CallFailed::class, function ($event) use ($call) {
            return $event->call->id === $call->id;
        });
    }

    /**
     * Test webhook processing with missing customer phone
     */
    #[Test]
    public function test_processes_webhook_without_customer_phone()
    {
        $webhookData = [
            'event' => 'call_started',
            'call_id' => 'no_phone_123',
            'from_number' => null, // No phone number
            'to_number' => '+493012345678',
            'agent_id' => 'agent_789',
            'status' => 'in_progress',
            'company_id' => $this->company->id,
        ];

        $call = $this->callService->processWebhook($webhookData);

        // Assert call was created without customer
        $this->assertInstanceOf(Call::class, $call);
        $this->assertNull($call->customer_id);
        $this->assertNull($call->from_number);
    }

    /**
     * Test structured data processing updates customer
     */
    #[Test]
    public function test_processes_structured_data_and_updates_customer()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Original Name',
            'email' => null,
        ]);

        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'retell_call_id' => 'structured_123',
            'customer_id' => $customer->id,
        ]);

        $webhookData = [
            'event' => 'call_analyzed',
            'call_id' => 'structured_123',
            'structured_data' => [
                'customer' => [
                    'name' => 'Updated Name',
                    'email' => 'updated@example.com',
                ],
                'preferences' => [
                    'preferred_staff' => 'Maria',
                    'preferred_time' => 'mornings',
                ],
            ],
        ];

        $this->callService->processWebhook($webhookData);

        // Assert customer was updated
        $customer->refresh();
        $this->assertEquals('Updated Name', $customer->name);
        $this->assertEquals('updated@example.com', $customer->email);

        // Assert structured data was stored
        $call->refresh();
        $this->assertEquals($webhookData['structured_data'], $call->structured_data);
    }

    /**
     * Test transaction rollback on appointment creation failure
     */
    #[Test]
    public function test_rolls_back_call_update_on_appointment_failure()
    {
        Event::fake();

        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'retell_call_id' => 'rollback_123',
            'status' => 'in_progress',
            'appointment_id' => null,
        ]);

        // Mock AppointmentService to throw exception
        $appointmentServiceMock = Mockery::mock(AppointmentService::class);
        $appointmentServiceMock->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('Appointment creation failed'));

        $this->app->instance(AppointmentService::class, $appointmentServiceMock);

        $webhookData = [
            'event' => 'call_ended',
            'call_id' => 'rollback_123',
            'call_duration' => 120,
            'variables' => [
                'appointment_created' => true,
                'appointment_data' => [
                    'staff_id' => 1,
                    'branch_id' => 1,
                    'start_time' => Carbon::tomorrow()->setTime(10, 0),
                    'end_time' => Carbon::tomorrow()->setTime(10, 30),
                ],
            ],
        ];

        try {
            $this->callService->processWebhook($webhookData);
            $this->fail('Exception should have been thrown');
        } catch (\Exception $e) {
            // Expected exception
        }

        // Assert call was not updated due to transaction rollback
        $call->refresh();
        $this->assertEquals('in_progress', $call->status);
        $this->assertNull($call->appointment_id);
        $this->assertEquals(0, $call->duration_seconds);

        // Assert no event was fired
        Event::assertNotDispatched(CallCompleted::class);
    }

    /**
     * Test call without retell_call_id cannot be refreshed
     */
    #[Test]
    public function test_cannot_refresh_call_without_retell_id()
    {
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'retell_call_id' => null,
        ]);

        $result = $this->callService->refreshCallData($call->id);

        // Assert refresh failed
        $this->assertFalse($result);

        // Assert Retell service was not called
        $this->retellServiceMock->shouldNotHaveReceived('getCall');
    }

    /**
     * Test finding existing call by retell_call_id
     */
    #[Test]
    public function test_finds_existing_call_for_update()
    {
        $existingCall = Call::factory()->create([
            'company_id' => $this->company->id,
            'retell_call_id' => 'existing_123',
            'status' => 'initiated',
        ]);

        $webhookData = [
            'event' => 'call_started',
            'call_id' => 'existing_123',
            'status' => 'in_progress',
        ];

        $call = $this->callService->processWebhook($webhookData);

        // Assert existing call was found and updated
        $this->assertEquals($existingCall->id, $call->id);
        $this->assertEquals('in_progress', $call->status);

        // Assert no duplicate call was created
        $this->assertEquals(1, Call::where('retell_call_id', 'existing_123')->count());
    }
}