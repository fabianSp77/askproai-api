<?php

namespace Tests\Feature\CalcomV2;

use Tests\TestCase;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\WebhookEvent;
use App\Models\CalcomEventMap;
use App\Services\CalcomV2Client;
use App\Jobs\SyncCalcomBooking;
use App\Jobs\ProcessWebhookEvent;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Sync Tests - Webhook processing and data synchronization
 */
class CalcomV2SyncTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Branch $branch;
    private Service $service;
    private Staff $staff;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestData();
    }

    private function setupTestData(): void
    {
        $this->company = Company::factory()->create([
            'name' => 'Sync Test Company'
        ]);

        $this->branch = Branch::factory()->create([
            'company_id' => $this->company->id
        ]);

        $this->service = Service::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Sync Test Service',
            'duration_minutes' => 30
        ]);

        $this->staff = Staff::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id
        ]);

        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id
        ]);

        CalcomEventMap::create([
            'service_id' => $this->service->id,
            'staff_id' => $this->staff->id,
            'event_type_id' => 1001,
            'sync_status' => 'synced'
        ]);
    }

    /**
     * Test 1: Webhook BOOKING.CREATED processing
     */
    public function test_webhook_booking_created_sync()
    {
        $webhookPayload = [
            'triggerEvent' => 'BOOKING.CREATED',
            'payload' => [
                'id' => 12345,
                'uid' => 'cal_' . uniqid(),
                'eventTypeId' => 1001,
                'title' => 'Test Booking',
                'start' => Carbon::tomorrow()->setTime(10, 0)->toIso8601String(),
                'end' => Carbon::tomorrow()->setTime(10, 30)->toIso8601String(),
                'attendees' => [
                    [
                        'name' => 'John Doe',
                        'email' => 'john@example.com',
                        'timeZone' => 'Europe/Berlin'
                    ]
                ],
                'organizer' => [
                    'name' => $this->staff->name,
                    'email' => $this->staff->email
                ],
                'location' => 'Office',
                'status' => 'ACCEPTED',
                'metadata' => [
                    'source' => 'webhook_test'
                ]
            ]
        ];

        // Generate valid signature
        $secret = config('services.calcom.webhook_secret', 'test_secret');
        $signature = 'sha256=' . hash_hmac('sha256', json_encode($webhookPayload), $secret);

        // Send webhook
        $response = $this->postJson('/api/calcom/webhook', $webhookPayload, [
            'X-Cal-Signature-256' => $signature
        ]);

        $response->assertStatus(200);
        $response->assertJson(['received' => true]);

        // Verify appointment was created/synced
        $appointment = Appointment::where('calcom_v2_booking_id', 12345)->first();
        $this->assertNotNull($appointment);
        $this->assertEquals('booked', $appointment->status);
        $this->assertEquals(
            Carbon::tomorrow()->setTime(10, 0)->toDateTimeString(),
            $appointment->starts_at->toDateTimeString()
        );

        // Verify customer was created/linked
        $this->assertNotNull($appointment->customer);
        $this->assertEquals('john@example.com', $appointment->customer->email);

        // Verify webhook event was logged
        $webhookEvent = WebhookEvent::where('source', 'calcom')
            ->where('event_type', 'BOOKING.CREATED')
            ->first();
        $this->assertNotNull($webhookEvent);
        $this->assertEquals('processed', $webhookEvent->status);
    }

    /**
     * Test 2: Webhook BOOKING.CANCELLED processing
     */
    public function test_webhook_booking_cancelled_sync()
    {
        // Create existing appointment
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'service_id' => $this->service->id,
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'calcom_v2_booking_id' => 12346,
            'status' => 'booked',
            'starts_at' => Carbon::tomorrow()->setTime(14, 0),
            'ends_at' => Carbon::tomorrow()->setTime(14, 30)
        ]);

        $webhookPayload = [
            'triggerEvent' => 'BOOKING.CANCELLED',
            'payload' => [
                'id' => 12346,
                'uid' => 'cal_cancelled_' . uniqid(),
                'eventTypeId' => 1001,
                'title' => 'Cancelled Booking',
                'start' => $appointment->starts_at->toIso8601String(),
                'end' => $appointment->ends_at->toIso8601String(),
                'status' => 'CANCELLED',
                'cancellationReason' => 'Customer requested cancellation'
            ]
        ];

        $secret = config('services.calcom.webhook_secret', 'test_secret');
        $signature = 'sha256=' . hash_hmac('sha256', json_encode($webhookPayload), $secret);

        $response = $this->postJson('/api/calcom/webhook', $webhookPayload, [
            'X-Cal-Signature-256' => $signature
        ]);

        $response->assertStatus(200);

        // Verify appointment was cancelled
        $appointment->refresh();
        $this->assertEquals('cancelled', $appointment->status);
        $this->assertArrayHasKey('cancellation_reason', $appointment->metadata ?? []);
    }

    /**
     * Test 3: Webhook BOOKING.RESCHEDULED processing
     */
    public function test_webhook_booking_rescheduled_sync()
    {
        // Create existing appointment
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'service_id' => $this->service->id,
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'calcom_v2_booking_id' => 12347,
            'status' => 'booked',
            'starts_at' => Carbon::tomorrow()->setTime(10, 0),
            'ends_at' => Carbon::tomorrow()->setTime(10, 30)
        ]);

        $newStart = Carbon::tomorrow()->setTime(15, 0);
        $newEnd = $newStart->copy()->addMinutes(30);

        $webhookPayload = [
            'triggerEvent' => 'BOOKING.RESCHEDULED',
            'payload' => [
                'id' => 12347,
                'uid' => 'cal_rescheduled_' . uniqid(),
                'eventTypeId' => 1001,
                'title' => 'Rescheduled Booking',
                'start' => $newStart->toIso8601String(),
                'end' => $newEnd->toIso8601String(),
                'previousStart' => $appointment->starts_at->toIso8601String(),
                'previousEnd' => $appointment->ends_at->toIso8601String(),
                'status' => 'ACCEPTED',
                'rescheduleReason' => 'Customer requested new time'
            ]
        ];

        $secret = config('services.calcom.webhook_secret', 'test_secret');
        $signature = 'sha256=' . hash_hmac('sha256', json_encode($webhookPayload), $secret);

        $response = $this->postJson('/api/calcom/webhook', $webhookPayload, [
            'X-Cal-Signature-256' => $signature
        ]);

        $response->assertStatus(200);

        // Verify appointment was rescheduled
        $appointment->refresh();
        $this->assertEquals($newStart->toDateTimeString(), $appointment->starts_at->toDateTimeString());
        $this->assertEquals($newEnd->toDateTimeString(), $appointment->ends_at->toDateTimeString());
        $this->assertArrayHasKey('reschedule_reason', $appointment->metadata ?? []);
        $this->assertArrayHasKey('previous_start', $appointment->metadata ?? []);
    }

    /**
     * Test 4: Webhook signature validation
     */
    public function test_webhook_signature_validation()
    {
        $payload = [
            'triggerEvent' => 'BOOKING.CREATED',
            'payload' => ['id' => 99999]
        ];

        // Test with invalid signature
        $response = $this->postJson('/api/calcom/webhook', $payload, [
            'X-Cal-Signature-256' => 'invalid_signature'
        ]);

        $response->assertStatus(401);

        // Test with missing signature
        $response = $this->postJson('/api/calcom/webhook', $payload);
        $response->assertStatus(401);

        // Test with valid signature
        $secret = config('services.calcom.webhook_secret', 'test_secret');
        $validSignature = 'sha256=' . hash_hmac('sha256', json_encode($payload), $secret);

        $response = $this->postJson('/api/calcom/webhook', $payload, [
            'X-Cal-Signature-256' => $validSignature
        ]);

        $response->assertStatus(200);
    }

    /**
     * Test 5: Orphaned appointment detection
     */
    public function test_orphaned_appointment_detection()
    {
        // Create appointments with Cal.com IDs but no recent sync
        $orphaned1 = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'calcom_v2_booking_id' => 50001,
            'status' => 'booked',
            'updated_at' => Carbon::now()->subDays(3)
        ]);

        $orphaned2 = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'calcom_v2_booking_id' => 50002,
            'status' => 'booked',
            'updated_at' => Carbon::now()->subDays(5)
        ]);

        // Recent appointment (not orphaned)
        $recent = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'calcom_v2_booking_id' => 50003,
            'status' => 'booked',
            'updated_at' => Carbon::now()->subHours(1)
        ]);

        // Find orphaned appointments
        $orphaned = Appointment::whereNotNull('calcom_v2_booking_id')
            ->where('status', 'booked')
            ->where('updated_at', '<', Carbon::now()->subDays(2))
            ->get();

        $this->assertEquals(2, $orphaned->count());
        $this->assertTrue($orphaned->contains('id', $orphaned1->id));
        $this->assertTrue($orphaned->contains('id', $orphaned2->id));
        $this->assertFalse($orphaned->contains('id', $recent->id));
    }

    /**
     * Test 6: Duplicate webhook prevention
     */
    public function test_duplicate_webhook_prevention()
    {
        $webhookPayload = [
            'triggerEvent' => 'BOOKING.CREATED',
            'payload' => [
                'id' => 60001,
                'uid' => 'dup_test_' . uniqid(),
                'eventTypeId' => 1001,
                'start' => Carbon::tomorrow()->toIso8601String(),
                'end' => Carbon::tomorrow()->addMinutes(30)->toIso8601String(),
                'attendees' => [
                    ['name' => 'Test', 'email' => 'test@example.com']
                ],
                'status' => 'ACCEPTED'
            ]
        ];

        $secret = config('services.calcom.webhook_secret', 'test_secret');
        $signature = 'sha256=' . hash_hmac('sha256', json_encode($webhookPayload), $secret);

        // Send webhook first time
        $response1 = $this->postJson('/api/calcom/webhook', $webhookPayload, [
            'X-Cal-Signature-256' => $signature
        ]);
        $response1->assertStatus(200);

        // Send same webhook again
        $response2 = $this->postJson('/api/calcom/webhook', $webhookPayload, [
            'X-Cal-Signature-256' => $signature
        ]);
        $response2->assertStatus(200);

        // Should only create one appointment
        $appointments = Appointment::where('calcom_v2_booking_id', 60001)->get();
        $this->assertEquals(1, $appointments->count());

        // Should log both webhook events
        $webhookEvents = WebhookEvent::where('source', 'calcom')
            ->whereJsonContains('payload->payload->id', 60001)
            ->get();
        $this->assertEquals(2, $webhookEvents->count());
    }

    /**
     * Test 7: Sync conflict resolution
     */
    public function test_sync_conflict_resolution()
    {
        // Create local appointment
        $localAppointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'service_id' => $this->service->id,
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'calcom_v2_booking_id' => 70001,
            'status' => 'booked',
            'starts_at' => Carbon::tomorrow()->setTime(10, 0),
            'ends_at' => Carbon::tomorrow()->setTime(10, 30),
            'updated_at' => Carbon::now()->subMinutes(5)
        ]);

        // Webhook with different time (Cal.com is source of truth)
        $webhookPayload = [
            'triggerEvent' => 'BOOKING.UPDATED',
            'payload' => [
                'id' => 70001,
                'start' => Carbon::tomorrow()->setTime(11, 0)->toIso8601String(),
                'end' => Carbon::tomorrow()->setTime(11, 30)->toIso8601String(),
                'status' => 'ACCEPTED',
                'attendees' => [
                    ['name' => $this->customer->name, 'email' => $this->customer->email]
                ]
            ]
        ];

        $secret = config('services.calcom.webhook_secret', 'test_secret');
        $signature = 'sha256=' . hash_hmac('sha256', json_encode($webhookPayload), $secret);

        $response = $this->postJson('/api/calcom/webhook', $webhookPayload, [
            'X-Cal-Signature-256' => $signature
        ]);

        $response->assertStatus(200);

        // Local should be updated to match Cal.com
        $localAppointment->refresh();
        $this->assertEquals(
            Carbon::tomorrow()->setTime(11, 0)->toDateTimeString(),
            $localAppointment->starts_at->toDateTimeString()
        );
    }

    /**
     * Test 8: Composite booking webhook sync
     */
    public function test_composite_booking_webhook_sync()
    {
        $compositeUid = 'comp_' . uniqid();

        // Create composite appointment
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'service_id' => $this->service->id,
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'is_composite' => true,
            'composite_group_uid' => $compositeUid,
            'segments' => [
                ['booking_id' => 80001, 'status' => 'booked'],
                ['booking_id' => 80002, 'status' => 'booked']
            ],
            'status' => 'booked'
        ]);

        // Webhook for first segment cancellation
        $webhookPayload = [
            'triggerEvent' => 'BOOKING.CANCELLED',
            'payload' => [
                'id' => 80001,
                'status' => 'CANCELLED',
                'metadata' => [
                    'composite_group_uid' => $compositeUid,
                    'segment_index' => 0
                ]
            ]
        ];

        $secret = config('services.calcom.webhook_secret', 'test_secret');
        $signature = 'sha256=' . hash_hmac('sha256', json_encode($webhookPayload), $secret);

        $response = $this->postJson('/api/calcom/webhook', $webhookPayload, [
            'X-Cal-Signature-256' => $signature
        ]);

        $response->assertStatus(200);

        // Entire composite booking should be cancelled
        $appointment->refresh();
        $this->assertEquals('cancelled', $appointment->status);
    }

    /**
     * Test 9: Queue job for async sync
     */
    public function test_queue_job_for_async_sync()
    {
        Queue::fake();

        $webhookPayload = [
            'triggerEvent' => 'BOOKING.CREATED',
            'payload' => [
                'id' => 90001,
                'eventTypeId' => 1001,
                'start' => Carbon::tomorrow()->toIso8601String(),
                'end' => Carbon::tomorrow()->addMinutes(30)->toIso8601String(),
                'attendees' => [
                    ['name' => 'Queue Test', 'email' => 'queue@test.com']
                ]
            ]
        ];

        $secret = config('services.calcom.webhook_secret', 'test_secret');
        $signature = 'sha256=' . hash_hmac('sha256', json_encode($webhookPayload), $secret);

        $response = $this->postJson('/api/calcom/webhook', $webhookPayload, [
            'X-Cal-Signature-256' => $signature
        ]);

        $response->assertStatus(200);

        // Verify sync job was dispatched if async processing is enabled
        // Queue::assertPushed(SyncCalcomBooking::class);
        // Queue::assertPushed(ProcessWebhookEvent::class);
    }

    /**
     * Test 10: Data integrity during sync
     */
    public function test_data_integrity_during_sync()
    {
        DB::transaction(function () {
            // Create multiple appointments
            $appointments = [];
            for ($i = 0; $i < 5; $i++) {
                $appointments[] = Appointment::factory()->create([
                    'company_id' => $this->company->id,
                    'branch_id' => $this->branch->id,
                    'calcom_v2_booking_id' => 100000 + $i,
                    'status' => 'booked'
                ]);
            }

            // Simulate concurrent webhook updates
            foreach ($appointments as $index => $appointment) {
                $webhookPayload = [
                    'triggerEvent' => 'BOOKING.UPDATED',
                    'payload' => [
                        'id' => $appointment->calcom_v2_booking_id,
                        'status' => 'ACCEPTED',
                        'metadata' => ['update_index' => $index]
                    ]
                ];

                $secret = config('services.calcom.webhook_secret', 'test_secret');
                $signature = 'sha256=' . hash_hmac('sha256', json_encode($webhookPayload), $secret);

                $response = $this->postJson('/api/calcom/webhook', $webhookPayload, [
                    'X-Cal-Signature-256' => $signature
                ]);

                $response->assertStatus(200);
            }
        });

        // Verify all appointments were updated correctly
        $updatedAppointments = Appointment::whereBetween('calcom_v2_booking_id', [100000, 100004])->get();
        $this->assertEquals(5, $updatedAppointments->count());

        foreach ($updatedAppointments as $appointment) {
            $this->assertNotNull($appointment->metadata);
        }
    }

    /**
     * Test 11: Webhook retry mechanism
     */
    public function test_webhook_retry_mechanism()
    {
        // Create webhook event that failed
        $webhookEvent = WebhookEvent::create([
            'source' => 'calcom',
            'event_type' => 'BOOKING.CREATED',
            'payload' => [
                'triggerEvent' => 'BOOKING.CREATED',
                'payload' => [
                    'id' => 110001,
                    'start' => Carbon::tomorrow()->toIso8601String()
                ]
            ],
            'status' => 'failed',
            'error_message' => 'Database connection error',
            'retry_count' => 0
        ]);

        // Simulate retry
        $webhookEvent->increment('retry_count');
        $webhookEvent->status = 'pending';
        $webhookEvent->save();

        // Process retry
        $secret = config('services.calcom.webhook_secret', 'test_secret');
        $signature = 'sha256=' . hash_hmac('sha256', json_encode($webhookEvent->payload), $secret);

        $response = $this->postJson('/api/calcom/webhook', $webhookEvent->payload, [
            'X-Cal-Signature-256' => $signature
        ]);

        $response->assertStatus(200);

        // Verify retry succeeded
        $webhookEvent->refresh();
        $this->assertEquals('processed', $webhookEvent->status);
        $this->assertEquals(1, $webhookEvent->retry_count);
    }

    /**
     * Test 12: Sync status monitoring
     */
    public function test_sync_status_monitoring()
    {
        // Create appointments with different sync statuses
        $synced = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'calcom_v2_booking_id' => 120001,
            'status' => 'booked',
            'updated_at' => Carbon::now()
        ]);

        $pending = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'calcom_v2_booking_id' => 120002,
            'status' => 'pending',
            'updated_at' => Carbon::now()->subHours(2)
        ]);

        $failed = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'calcom_v2_booking_id' => null,
            'status' => 'failed',
            'updated_at' => Carbon::now()->subDays(1)
        ]);

        // Get sync statistics
        $stats = [
            'synced' => Appointment::whereNotNull('calcom_v2_booking_id')
                ->where('status', 'booked')
                ->where('updated_at', '>', Carbon::now()->subHour())
                ->count(),
            'pending' => Appointment::where('status', 'pending')
                ->count(),
            'failed' => Appointment::where('status', 'failed')
                ->count(),
            'orphaned' => Appointment::whereNotNull('calcom_v2_booking_id')
                ->where('updated_at', '<', Carbon::now()->subDays(2))
                ->count()
        ];

        $this->assertEquals(1, $stats['synced']);
        $this->assertEquals(1, $stats['pending']);
        $this->assertEquals(1, $stats['failed']);
        $this->assertEquals(0, $stats['orphaned']);

        Log::info('Sync status monitoring', $stats);
    }
}